<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['lecturer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expire velalu. Please login again.']);
    exit();
}


require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$lecturerId = $_SESSION['lecturer_id'];

$input  = json_decode(file_get_contents('php://input'), true);
$slotId = isset($input['slot_id']) ? (int)$input['slot_id'] : 0;

if ($slotId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Slot ID eka wrong / missing.']);
    exit();
}

$stmt = $conn->prepare("SELECT id, room_name, status FROM lecturer_availability WHERE id = ? AND lecturer_id = ? LIMIT 1");
$stmt->bind_param("ii", $slotId, $lecturerId);
$stmt->execute();
$result = $stmt->get_result();
$slot = $result->fetch_assoc();
$stmt->close();

if (!$slot) {
    echo json_encode(['success' => false, 'message' => 'Slot eka hoyaganna baha, nattham oyage slot ekak nemei.']);
    $conn->close();
    exit();
}

if ($slot['status'] === 'live') {
    echo json_encode([
        'success'      => true,
        'room_name'    => $slot['room_name'],
        'already_live' => true
    ]);
    $conn->close();
    exit();
}

$roomName = $slot['room_name'];
if (empty($roomName)) {
    $roomName = "SipwayCampus-Live-Slot-{$slotId}";
}

$update = $conn->prepare("
    UPDATE lecturer_availability
    SET status = 'live', room_name = ?, live_started_at = NOW(), ended_at = NULL
    WHERE id = ? AND lecturer_id = ?
");
$update->bind_param("sii", $roomName, $slotId, $lecturerId);

if ($update->execute()) {
    echo json_encode([
        'success'      => true,
        'room_name'    => $roomName,
        'already_live' => false
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Status update karanna baha: ' . $conn->error]);
}

$update->close();
$conn->close();