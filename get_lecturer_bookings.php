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

/*
  ASSUMPTIONS (badaganna table/column names ownage DB eke wenas nam methana wenas karanna):
  - bookings table: id, student_id, lecturer_id, package_id, session_date, session_time, status
  - students table: id, full_name
  - packages table: id, title   (nathnam 'name' kiyala try karanna, methana comment karala thiyanawa)

  Frontend eka expect karanne statuses: 'pending', 'approved', 'rejected' (lower case),
  ee nisa LOWER() karala output karanawa.
*/

$bookings = [];

$stmt = $conn->prepare("
    SELECT
        b.id,
        s.full_name AS student_name,
        p.title AS package_name,
        b.session_date AS date,
        b.session_time AS time,
        LOWER(b.status) AS status
    FROM bookings b
    LEFT JOIN students s ON b.student_id = s.id
    LEFT JOIN packages p ON b.package_id = p.id
    WHERE b.lecturer_id = ?
    ORDER BY b.session_date DESC, b.session_time DESC
");
$stmt->bind_param("i", $lecturerId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $bookings[] = [
        'id'           => (int)$row['id'],
        'student_name' => $row['student_name'] ?? 'Unknown Student',
        'package_name' => $row['package_name'] ?? 'General Session',
        'date'         => $row['date'],
        'time'         => $row['time'],
        'status'       => $row['status'],
    ];
}
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'data' => $bookings
]);