<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login වෙන්න ඕන']);
    exit;
}

$year  = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));
if ($month < 1 || $month > 12) {
    echo json_encode(['success' => false, 'message' => 'Invalid month']);
    exit;
}

$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate   = date('Y-m-t', strtotime($startDate));

$studentId     = (int)$_SESSION['student_id'];
$langFilter    = strtolower(trim($_GET['lang'] ?? ''));
$subjectFilter = strtolower(trim($_GET['subject'] ?? ''));

if ($langFilter === '') {
    $langStmt = $conn->prepare("SELECT language FROM students WHERE id = ? LIMIT 1");
    $langStmt->bind_param('i', $studentId);
    $langStmt->execute();
    $langRes = $langStmt->get_result();
    if ($langRow = $langRes->fetch_assoc()) {
        $langFilter = strtolower(trim($langRow['language'] ?: 'en'));
    }
    $langStmt->close();
}

// Auto-expire stale live sessions
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

/*
 * ★ FIX: Dates to show a dot for = ANY approved availability slot exists
 * on that date matching language (+ optional subject) — REGARDLESS of
 * whether it's already fully booked or not.
 *
 * Previously this only counted "still bookable" slots, so as soon as
 * ANOTHER student booked the only slot on a date, the dot vanished for
 * everyone else. Now the dot stays; get_availability_by_date.php already
 * correctly shows "Already Booked" for slots that are full.
 */
$sql = "
    SELECT DISTINCT la.date
    FROM lecturer_availability la
    JOIN lecturers l ON la.lecturer_id = l.id
    WHERE la.date BETWEEN ? AND ?
      AND l.status = 'approved'
      AND la.approval_status = 'approved'
      AND LOWER(COALESCE(NULLIF(TRIM(l.language), ''), 'en')) = ?
";

$params = [$startDate, $endDate, $langFilter];
$types  = 'sss';

if ($subjectFilter !== '') {
    $sql .= " AND (
        LOWER(TRIM(COALESCE(la.subject, ''))) = ?
        OR LOWER(TRIM(COALESCE(la.subject, ''))) LIKE ?
        OR ? LIKE CONCAT('%', LOWER(TRIM(COALESCE(la.subject, ''))), '%')
    )";
    $params[] = $subjectFilter;
    $params[] = '%' . $subjectFilter . '%';
    $params[] = $subjectFilter;
    $types   .= 'sss';
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$dates = [];
while ($row = $result->fetch_assoc()) {
    $dates[] = $row['date'];
}
$stmt->close();

// Also include ANY date this student has booked in this month (even if
// language/subject filter would otherwise hide it, e.g. student changed
// their language after booking).
$bookedSql = "
    SELECT DISTINCT b.session_date AS date
    FROM bookings b
    WHERE b.student_id = ?
      AND b.status IN ('Pending','Accepted')
      AND b.session_date BETWEEN ? AND ?
";
$bookedStmt = $conn->prepare($bookedSql);
$bookedStmt->bind_param('iss', $studentId, $startDate, $endDate);
$bookedStmt->execute();
$bookedRes = $bookedStmt->get_result();
while ($row = $bookedRes->fetch_assoc()) {
    if (!in_array($row['date'], $dates, true)) {
        $dates[] = $row['date'];
    }
}
$bookedStmt->close();

// Live dates (unchanged idea)
$liveSql = "
    SELECT DISTINCT la.date
    FROM lecturer_availability la
    JOIN lecturers l ON la.lecturer_id = l.id
    WHERE la.date BETWEEN ? AND ?
      AND l.status = 'approved'
      AND la.approval_status = 'approved'
      AND la.status = 'live'
      AND LOWER(COALESCE(NULLIF(TRIM(l.language), ''), 'en')) = ?
";
$liveParams = [$startDate, $endDate, $langFilter];
$liveTypes  = 'sss';

if ($subjectFilter !== '') {
    $liveSql .= " AND (
        LOWER(TRIM(COALESCE(la.subject, ''))) = ?
        OR LOWER(TRIM(COALESCE(la.subject, ''))) LIKE ?
        OR ? LIKE CONCAT('%', LOWER(TRIM(COALESCE(la.subject, ''))), '%')
    )";
    $liveParams[] = $subjectFilter;
    $liveParams[] = '%' . $subjectFilter . '%';
    $liveParams[] = $subjectFilter;
    $liveTypes   .= 'sss';
}

$liveStmt = $conn->prepare($liveSql);
$liveStmt->bind_param($liveTypes, ...$liveParams);
$liveStmt->execute();
$liveResult = $liveStmt->get_result();

$liveDates = [];
while ($row = $liveResult->fetch_assoc()) {
    $liveDates[] = $row['date'];
}
$liveStmt->close();

sort($dates);

echo json_encode([
    'success'    => true,
    'dates'      => array_values($dates),
    'live_dates' => $liveDates,
], JSON_UNESCAPED_UNICODE);

$conn->close();