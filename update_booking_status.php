<?php
session_start();
header('Content-Type: application/json');

require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$id     = intval($input['id'] ?? 0);
$status = trim($input['status'] ?? '');

if ($id <= 0 || !in_array($status, ['Accepted', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$check = $conn->prepare("SELECT id FROM bookings WHERE id = ? LIMIT 1");
$check->bind_param('i', $id);
$check->execute();
$checkResult = $check->get_result();
if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

$meetLink = null;

if ($status === 'Accepted') {

   
    function generateMeetCode() {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $randPart = function ($len) use ($chars) {
            $s = '';
            for ($i = 0; $i < $len; $i++) {
                $s .= $chars[random_int(0, strlen($chars) - 1)];
            }
            return $s;
        };
        return $randPart(3) . '-' . $randPart(4) . '-' . $randPart(3);
    }

    $meetLink = ' https://8x8.vc/' . generateMeetCode();

    $stmt = $conn->prepare("
        UPDATE bookings
        SET status = ?, meeting_link = ?, approved_at = NOW(), rejected_at = NULL, rejection_reason = NULL
        WHERE id = ?
    ");
    $stmt->bind_param('ssi', $status, $meetLink, $id);

} else {
    // Rejected
    $rejectionReason = trim($input['reason'] ?? '');

    $stmt = $conn->prepare("
        UPDATE bookings
        SET status = ?, rejected_at = NOW(), rejection_reason = ?, meeting_link = NULL
        WHERE id = ?
    ");
    $stmt->bind_param('ssi', $status, $rejectionReason, $id);
}

if ($stmt->execute()) {
    echo json_encode([
        'success'      => true,
        'message'      => $status === 'Accepted'
                            ? 'Booking approved and meet link generated'
                            : 'Booking rejected',
        'meeting_link' => $meetLink
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();