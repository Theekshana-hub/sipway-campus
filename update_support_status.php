<?php
header('Content-Type: application/json');
include 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);
$status = $input['status'] ?? '';

if (!$id || !in_array($status, ['pending', 'resolved'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$stmt = $conn->prepare("UPDATE support_requests SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$stmt->close();
$conn->close();