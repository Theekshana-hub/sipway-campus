<?php


header('Content-Type: application/json');
require_once 'db.php'; 
$input = json_decode(file_get_contents('php://input'), true);

$id      = isset($input['id']) ? (int)$input['id'] : 0;
$subject = isset($input['subject']) ? trim($input['subject']) : '';
$date    = isset($input['date']) ? trim($input['date']) : '';
$start   = isset($input['start']) ? trim($input['start']) : '';
$end     = isset($input['end']) ? trim($input['end']) : '';

if ($id <= 0 || $date === '' || $start === '' || $end === '') {
    echo json_encode(['success' => false, 'message' => 'Id, date, start සහ end අනිවාර්යයි']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Date format එක වැරදියි']);
    exit;
}
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $start) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $end)) {
    echo json_encode(['success' => false, 'message' => 'Time format එක වැරදියි']);
    exit;
}
if ($start >= $end) {
    echo json_encode(['success' => false, 'message' => 'End time, start time එකට වඩා පස්සේ එකක් වෙන්න ඕන']);
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

    $stmt = $conn->prepare("UPDATE lecturer_availability SET subject = ?, date = ?, start_time = ?, end_time = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $subject, $date, $start, $end, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Slot එක update කරා']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update කිරීමට නොහැකි විය']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();