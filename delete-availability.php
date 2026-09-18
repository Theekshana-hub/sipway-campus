<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['lecturer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login වෙන්න ඕන']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$lecturerId = (int)$_SESSION['lecturer_id'];
$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid slot ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM lecturer_availability WHERE id = ? AND lecturer_id = ?");
$stmt->bind_param('ii', $id, $lecturerId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Slot එක remove උනා']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Slot එක හමු නොවුනා, හෝ ඔබට අයිති එකක් නෙවෙයි']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();