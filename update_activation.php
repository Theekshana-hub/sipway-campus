<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

include 'db.php';



$data = json_decode(file_get_contents('php://input'), true);

$id     = isset($data['id']) ? (int)$data['id'] : 0;
$status = isset($data['status']) ? trim($data['status']) : '';

$allowedStatuses = ['pending', 'active', 'completed', 'cancelled'];

if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$stmt = $conn->prepare("UPDATE activated_packages SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}