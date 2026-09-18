<?php
session_start();
header('Content-Type: application/json');

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $e->getMessage()]);
    exit;
});
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'PHP Fatal: ' . $err['message']]);
    }
});

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['lecturer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login වෙන්න ඕන']);
    exit;
}

$lecturerId = (int)$_SESSION['lecturer_id'];

$stmt = $conn->prepare(
    "SELECT id, date, TIME_FORMAT(start_time, '%H:%i') AS start, TIME_FORMAT(end_time, '%H:%i') AS end,
            status, room_name
     FROM lecturer_availability
     WHERE lecturer_id = ? AND date >= CURDATE()
     ORDER BY date ASC, start_time ASC"
);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error . ' — "lecturer_availability" table එකේ "status"/"room_name" columns තියෙනවද check කරන්න (alter_lecturer_availability.sql run කරන්න ඕන).']);
    exit;
}
$stmt->bind_param('i', $lecturerId);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = [
        'id'        => (int)$row['id'],
        'date'      => $row['date'],
        'start'     => $row['start'],
        'end'       => $row['end'],
        'status'    => $row['status'],
        'room_name' => $row['room_name'],
    ];
}

echo json_encode(['success' => true, 'data' => $slots]);

$stmt->close();
$conn->close();