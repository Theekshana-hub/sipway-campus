<?php
session_start();
header('Content-Type: application/json');

// ==================== DATABASE CONNECTION ====================
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
// ============================================================

// bookings table eka students table ekath, lecturers table ekath join karanawa
// (bookings table eke student_name / lecturer_name columns nathi nisa)
$sql = "
    SELECT 
        b.id,
        b.student_id,
        s.full_name  AS student_name,
        b.lecturer_id,
        l.full_name  AS lecturer_name,
        b.package_id,
        b.session_date,
        b.session_time,
        b.status,
        b.created_at,
        b.meeting_link,
        b.approved_at,
        b.rejected_at,
        b.rejection_reason
    FROM bookings b
    LEFT JOIN students s  ON b.student_id  = s.id
    LEFT JOIN lecturers l ON b.lecturer_id = l.id
    ORDER BY b.created_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

$bookings = [];
while ($row = $result->fetch_assoc()) {
    // Admin table ekata pennana widihata dates format karanawa
    $row['session_date'] = date('M d, Y', strtotime($row['session_date']));
    $row['session_time'] = date('h:i A', strtotime($row['session_time']));
    $row['created_at']   = date('M d, Y h:i A', strtotime($row['created_at']));

    // lecturer_id ekak nathi bookings (calendar eken newei manual widihata book kalaw) walata
    // lecturer_name eka NULL wenawa — ehema unoth '—' widihata pennanawa
    $row['lecturer_name'] = $row['lecturer_name'] ?? '—';
    $row['package_id']    = $row['package_id'] ?? '—';

    $bookings[] = $row;
}

echo json_encode(['success' => true, 'data' => $bookings]);

$conn->close();