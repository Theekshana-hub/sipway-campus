<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);


ob_start();

header('Content-Type: application/json');
require_once 'db.php';


ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON received: ' . json_last_error_msg()]);
    exit;
}

$id     = (int)($input['id'] ?? 0);
$status = trim($input['status'] ?? '');

$allowedStatuses = ['approved', 'rejected', 'pending'];

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connect error: ' . $conn->connect_error]);
    exit;
}

// Student exist check
$check = $conn->prepare("SELECT id FROM students WHERE id = ?");
if (!$check) {
    echo json_encode(['success' => false, 'message' => 'Check prepare failed: ' . $conn->error]);
    exit;
}
$check->bind_param("i", $id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// Update query
$stmt = $conn->prepare("UPDATE students SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();


ob_end_flush();