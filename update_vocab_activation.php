<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

$id     = isset($input['id']) ? (int)$input['id'] : 0;
$status = isset($input['status']) ? trim($input['status']) : '';

$allowed = ['pending', 'active', 'expired', 'cancelled'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid id or status']);
    exit;
}

if ($status === 'active') {
    $stmt = $conn->prepare("
        UPDATE activated_vocabulary_packages
        SET status = ?, activated_at = NOW()
        WHERE id = ?
    ");
} else {
    $stmt = $conn->prepare("
        UPDATE activated_vocabulary_packages
        SET status = ?
        WHERE id = ?
    ");
}

$stmt->bind_param('si', $status, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
$conn->close();