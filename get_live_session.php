<?php
session_start();
header('Content-Type: application/json');

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $e->getMessage()
    ]);
    exit;
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'PHP Fatal: ' . $err['message'] . ' in ' . $err['file'] . ' on line ' . $err['line']
        ]);
    }
});

require_once 'db.php';

if (!isset($conn) || $conn === null || $conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'unknown')
    ]);
    exit();
}

$cleanup = $conn->prepare(
    "UPDATE lecturer_availability
     SET status = 'scheduled'
     WHERE status = 'live'
       AND live_started_at IS NOT NULL
       AND live_started_at < (NOW() - INTERVAL 3 HOUR)"
);
if ($cleanup) {
    $cleanup->execute();
    $cleanup->close();
}

$liveSessions = [];

$stmt = $conn->prepare("
    SELECT
        a.id AS slot_id,
        TIME_FORMAT(a.start_time, '%H:%i') AS start,
        TIME_FORMAT(a.end_time, '%H:%i') AS end,
        a.room_name,
        a.date,
        a.is_free,
        a.session_type AS la_session_type,
        a.max_capacity AS la_max_capacity,
        a.subject AS slot_subject,
        l.id AS lecturer_id,
        l.full_name,
        l.language,
        l.photo,
        (SELECT COUNT(*) FROM bookings bx 
         WHERE bx.availability_id = a.id 
           AND bx.status IN ('Pending', 'Accepted')
        ) AS total_bookings,
        (SELECT bx.booking_type 
         FROM bookings bx 
         WHERE bx.availability_id = a.id 
           AND bx.status IN ('Pending', 'Accepted')
         ORDER BY bx.id ASC 
         LIMIT 1
        ) AS first_booking_type
    FROM lecturer_availability a
    JOIN lecturers l ON a.lecturer_id = l.id
    WHERE a.status = 'live'
    ORDER BY a.live_started_at DESC
");

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL prepare failed: ' . $conn->error
    ]);
    exit();
}

$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $photoUrl = null;
    if (!empty($row['photo'])) {
        $photoUrl = 'uploads/lecturers/' . $row['photo'];
    }

    $totalBookings = (int)$row['total_bookings'];
    $firstType     = strtolower(trim($row['first_booking_type'] ?? ''));

    if ($totalBookings > 0 && ($firstType === 'group' || $firstType === 'individual')) {
        $sessionType = $firstType;
        $maxCapacity = ($sessionType === 'group') ? 10 : 1;
    } else {
        $sessionType = 'open';
        $maxCapacity = 10;
    }

    $isFull = ($sessionType !== 'open') && ($totalBookings >= $maxCapacity);

    $slotSubject = trim((string)($row['slot_subject'] ?? ''));
    if ($slotSubject === '') {
        $slotSubject = 'General';
    }

    $liveSessions[] = [
        'slot_id'      => (int)$row['slot_id'],
        'lecturer_id'  => (int)$row['lecturer_id'],
        'full_name'    => $row['full_name'],
        'subject'      => $slotSubject,
        'language'     => $row['language'] ?: 'en',
        'date'         => $row['date'],
        'start'        => $row['start'],
        'end'          => $row['end'],
        'photo'        => $photoUrl,
        'room_name'    => $row['room_name'],
        'is_free'      => (int)$row['is_free'],
        'session_type' => $sessionType,
        'max_capacity' => $maxCapacity,
        'booked_count' => $totalBookings,
        'is_full'      => $isFull,
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'data'    => $liveSessions,
    'count'   => count($liveSessions)
]);