<?php
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    echo json_encode([
        'success' => false,
        'message' => "PHP Error: $errstr in $errfile on line $errline"
    ]);
    exit;
});

set_exception_handler(function ($e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage()
    ]);
    exit;
});

require_once 'db.php';

// Check connection
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection eka hadanna baa una (check db.php)']);
    exit;
}
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connect error: ' . $conn->connect_error]);
    exit;
}

// Set charset
$conn->set_charset('utf8mb4');

$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date'] ?? '');

// Validate dates
function isValidDate($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

if (!isValidDate($startDate) || !isValidDate($endDate)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date range']);
    exit;
}
if ($startDate > $endDate) {
    echo json_encode(['success' => false, 'message' => 'Start date eka end date ekata passe wenna baa']);
    exit;
}

// ===================================================
// 1. Lecture Hours by Lecturer
//    ONLY from Accepted student bookings
//    Unique time slot (date + time) = 1 count
//    Multiple students on same slot → still counts as 1
// ===================================================
$hoursByLecturer = [];
$totalHours = 0;
$totalSlots = 0;

$sql = "
    SELECT
        l.id,
        l.full_name,
        l.subject,
        COUNT(b.id) AS slot_count,                          -- how many students booked
        COUNT(DISTINCT CONCAT(b.session_date, '|', IFNULL(b.session_time, ''))) AS total_hours
                                                            -- unique accepted time slots only
    FROM bookings b
    INNER JOIN lecturers l ON b.lecturer_id = l.id
    WHERE b.session_date BETWEEN ? AND ?
      AND b.status = 'Accepted'                             -- only accepted student bookings
      AND b.lecturer_id IS NOT NULL
    GROUP BY l.id, l.full_name, l.subject
    ORDER BY total_hours DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lecturer query prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('ss', $startDate, $endDate);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Lecturer query execute failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $hours = (float)$row['total_hours'];   // unique slots
    $slots = (int)$row['slot_count'];      // student bookings count

    $hoursByLecturer[] = [
        'lecturer_id' => (int)$row['id'],
        'full_name'   => $row['full_name'],
        'subject'     => $row['subject'] ?? '-',
        'slot_count'  => $slots,
        'total_hours' => $hours,
    ];
    $totalHours += $hours;
    $totalSlots += $slots;
}
$stmt->close();

// ===================================================
// 2. Students registered in the date range
// ===================================================
$students = [];
$sql2 = "
    SELECT id, full_name, email, mobile, created_at
    FROM students
    WHERE DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC
";

$stmt2 = $conn->prepare($sql2);
if (!$stmt2) {
    echo json_encode(['success' => false, 'message' => 'Students query prepare failed: ' . $conn->error]);
    exit;
}

$stmt2->bind_param('ss', $startDate, $endDate);

if (!$stmt2->execute()) {
    echo json_encode(['success' => false, 'message' => 'Students query execute failed: ' . $stmt2->error]);
    exit;
}

$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
    $students[] = [
        'id'         => (int)$row['id'],
        'full_name'  => $row['full_name'],
        'email'      => $row['email'],
        'mobile'     => $row['mobile'],
        'created_at' => $row['created_at'],
    ];
}
$stmt2->close();

// ===================================================
// Final Response
// ===================================================
echo json_encode([
    'success' => true,
    'range' => [
        'start' => $startDate,
        'end'   => $endDate
    ],
    'summary' => [
        'total_hours'        => round($totalHours, 2),
        'total_slots'        => $totalSlots,
        'total_students'     => count($students),
        'lecturers_involved' => count($hoursByLecturer),
    ],
    'lecturer_hours' => $hoursByLecturer,
    'students'       => $students,
], JSON_UNESCAPED_UNICODE);

$conn->close();