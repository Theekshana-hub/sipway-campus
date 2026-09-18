<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$filter = $_GET['filter'] ?? 'pending'; 
$allowed = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $allowed, true)) $filter = 'pending';

$where = $filter === 'all' 
    ? '' 
    : "WHERE la.approval_status = '" . $conn->real_escape_string($filter) . "'";

$sql = "SELECT 
            la.id, 
            la.date, 
            TIME_FORMAT(la.start_time, '%H:%i') AS start_time,
            TIME_FORMAT(la.end_time, '%H:%i') AS end_time, 
            la.approval_status, 
            la.status,
            la.subject AS slot_subject,
            la.is_free,
            l.id AS lecturer_id, 
            l.full_name, 
            l.photo
        FROM lecturer_availability la
        JOIN lecturers l ON la.lecturer_id = l.id
        $where
        ORDER BY la.date ASC, la.start_time ASC";

$result = $conn->query($sql);

if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'SQL error: ' . $conn->error]);
    exit;
}

$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = [
        'id'              => (int)$r['id'],
        'date'            => $r['date'],
        'start'           => $r['start_time'],
        'end'             => $r['end_time'],
        'approval_status' => $r['approval_status'],
        'status'          => $r['status'],
        'lecturer_id'     => (int)$r['lecturer_id'],
        'lecturer_name'   => $r['full_name'],
        'subject'         => $r['slot_subject'] ?? '—',
        'photo'           => $r['photo'],
        'is_free'         => (int)($r['is_free'] ?? 0),
    ];
}

echo json_encode(['success' => true, 'data' => $rows]);
$conn->close();