<?php

header('Content-Type: application/json');
require_once 'db.php'; 

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid slot id']);
    exit;
}

try {
    $check = $conn->prepare("SELECT id FROM lecturer_availability WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $checkResult = $check->get_result();
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Slot එක සොයාගත නොහැක']);
        exit;
    }
    $check->close();

    $bookingCheck = $conn->prepare("SELECT id FROM bookings WHERE availability_id = ? LIMIT 1");
    $bookingCheck->bind_param("i", $id);
    $bookingCheck->execute();
    $bookingResult = $bookingCheck->get_result();
    if ($bookingResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'මේ slot එකට student booking එකක් ඇති නිසා delete කරන්න බැහැ']);
        exit;
    }
    $bookingCheck->close();

    $stmt = $conn->prepare("DELETE FROM lecturer_availability WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Slot එක delete කරා']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete කිරීමට නොහැකි විය']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();