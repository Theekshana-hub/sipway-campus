<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    exit;
}

$sel = $conn->prepare("SELECT receipt_path FROM activated_vocabulary_packages WHERE id = ? LIMIT 1");
$sel->bind_param('i', $id);
$sel->execute();
$row = $sel->get_result()->fetch_assoc();
$sel->close();

if ($row && !empty($row['receipt_path'])) {
    $path = __DIR__ . '/' . ltrim($row['receipt_path'], '/');
    if (is_file($path)) {
        @unlink($path);
    }
}

$stmt = $conn->prepare("DELETE FROM activated_vocabulary_packages WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
}

$stmt->close();
$conn->close();