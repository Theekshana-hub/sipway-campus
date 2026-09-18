<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['lecturer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$lecturerId = (int)$_SESSION['lecturer_id'];
$today = date('Y-m-d');

/*
  ASSUMPTIONS (badaganna table/column names ownage DB eke wenas nam methana wenas karanna):
  - bookings table: id, student_id, lecturer_id, package_id, session_date, session_time, status
    status values: 'pending', 'approved', 'rejected' (LOWER() karala compare karanne case-insensitive widihata)
  - availability table: id, lecturer_id, date, start, end, status
    status values: 'scheduled', 'live', 'ended'
*/

$upcomingSessions = 0;
$pendingApprovals = 0;
$totalStudents = 0;
$availableSlots = 0;

// Upcoming approved sessions
$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM bookings
    WHERE lecturer_id = ?
      AND LOWER(status) = 'approved'
      AND session_date >= ?
");
$stmt->bind_param("is", $lecturerId, $today);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $upcomingSessions = (int)$row['cnt'];
}
$stmt->close();

// Pending approvals
$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM bookings
    WHERE lecturer_id = ?
      AND LOWER(status) = 'pending'
");
$stmt->bind_param("i", $lecturerId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $pendingApprovals = (int)$row['cnt'];
}
$stmt->close();

// Distinct students taught (approved bookings)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT student_id) AS cnt
    FROM bookings
    WHERE lecturer_id = ?
      AND LOWER(status) = 'approved'
");
$stmt->bind_param("i", $lecturerId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $totalStudents = (int)$row['cnt'];
}
$stmt->close();

// Open (future, not-yet-booked) availability slots
$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM availability
    WHERE lecturer_id = ?
      AND date >= ?
      AND LOWER(status) = 'scheduled'
");
$stmt->bind_param("is", $lecturerId, $today);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $availableSlots = (int)$row['cnt'];
}
$stmt->close();

$conn->close();

echo json_encode([
    'success' => true,
    'data' => [
        'upcoming_sessions'  => $upcomingSessions,
        'pending_approvals'  => $pendingApprovals,
        'total_students'     => $totalStudents,
        'available_slots'    => $availableSlots,
    ]
]);