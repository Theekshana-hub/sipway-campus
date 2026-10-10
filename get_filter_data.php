<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function out($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function isValidDate($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date'] ?? '');

if (!isValidDate($startDate) || !isValidDate($endDate)) {
    out(['success' => false, 'message' => 'Invalid date range']);
}
if ($startDate > $endDate) {
    out(['success' => false, 'message' => 'Start date eka end date ekata passe wenna baa']);
}

try {
    $conn = new mysqli('localhost', 'root', '', 'sipway');
    $conn->set_charset('utf8mb4');

    // ---------- Lecturer hours (Accepted student bookings only) ----------
    // slot_count  = how many students booked
    // total_hours = unique (date + time) accepted slots  (1 slot = 30 min)
    $sql = "SELECT l.id, l.full_name, l.subject,
                   COUNT(b.id) AS slot_count,
                   COUNT(DISTINCT CONCAT(DATE(b.session_date), '|', IFNULL(TIME_FORMAT(b.session_time, '%H:%i'), ''))) AS total_hours
            FROM bookings b
            INNER JOIN lecturers l ON b.lecturer_id = l.id
            WHERE DATE(b.session_date) BETWEEN ? AND ?
              AND LOWER(b.status) = 'accepted'
              AND b.lecturer_id IS NOT NULL
            GROUP BY l.id, l.full_name, l.subject
            ORDER BY total_hours DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $hoursByLecturer = [];
    $totalHours = 0;
    $totalSlots = 0;

    while ($row = $result->fetch_assoc()) {
        $hours = (float)$row['total_hours'];
        $slots = (int)$row['slot_count'];

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

    // ---------- Students registered in range ----------
    $sql2 = "SELECT id, full_name, email, mobile, created_at
             FROM students
             WHERE DATE(created_at) BETWEEN ? AND ?
             ORDER BY created_at DESC";

    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param('ss', $startDate, $endDate);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    $students = [];
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
    $conn->close();

    out([
        'success' => true,
        'range'   => ['start' => $startDate, 'end' => $endDate],
        'summary' => [
            'total_hours'        => round($totalHours, 2),
            'total_slots'        => $totalSlots,
            'total_students'     => count($students),
            'lecturers_involved' => count($hoursByLecturer),
        ],
        'lecturer_hours' => $hoursByLecturer,
        'students'       => $students,
    ]);

} catch (Throwable $e) {
    out(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}