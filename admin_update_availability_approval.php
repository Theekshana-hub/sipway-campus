<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['id'] ?? 0);
$action = $input['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$newStatus = $action === 'approve' ? 'approved' : 'rejected';

$stmt = $conn->prepare("UPDATE lecturer_availability SET approval_status = ? WHERE id = ?");
$stmt->bind_param('si', $newStatus, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => $action === 'approve' ? 'Slot approve උනා! දැන් students ට පේනවා.' : 'Slot reject උනා.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();