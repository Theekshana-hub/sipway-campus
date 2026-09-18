<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

include 'db.php';


$data = json_decode(file_get_contents('php://input'), true);

$id = isset($data['id']) ? (int)$data['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM activated_packages WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
} else {
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
}