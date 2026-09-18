<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM lecturers WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Teacher deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();