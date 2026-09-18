<?php
session_start();
header('Content-Type: application/json');

// ==================== AUTH CHECK ====================
if (!isset($_SESSION['lecturer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expire velalu. Please login again.']);
    exit();
}

// ==================== DATABASE CONNECTION ====================
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$lecturerId = $_SESSION['lecturer_id'];

// ==================== READ INPUT ====================
$input  = json_decode(file_get_contents('php://input'), true);
$slotId = isset($input['slot_id']) ? (int)$input['slot_id'] : 0;

if ($slotId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Slot ID eka wrong / missing.']);
    exit();
}

// ==================== CONFIRM SLOT BELONGS TO THIS LECTURER ====================
$stmt = $conn->prepare("SELECT id, status FROM lecturer_availability WHERE id = ? AND lecturer_id = ? LIMIT 1");
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

// ==================== SET STATUS = ENDED ====================
$update = $conn->prepare("
    UPDATE lecturer_availability
    SET status = 'ended', ended_at = NOW()
    WHERE id = ? AND lecturer_id = ?
");
$update->bind_param("ii", $slotId, $lecturerId);

if ($update->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update fail unnah: ' . $conn->error]);
}

$update->close();
$conn->close();