<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login වෙන්න ඕන']);
    exit;
}

$date = trim($_GET['date'] ?? '');
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    echo json_encode(['success' => false, 'message' => 'Invalid date']);
    exit;
}

$currentStudentId = (int)$_SESSION['student_id'];
$langFilter       = strtolower(trim($_GET['lang'] ?? ''));
$subjectFilter    = strtolower(trim($_GET['subject'] ?? ''));

// Auto-expire stale live
$cleanup = $conn->prepare(
    "UPDATE lecturer_availability
     SET status = 'scheduled'
     WHERE status = 'live'
       AND live_started_at IS NOT NULL
       AND live_started_at < (NOW() - INTERVAL 3 HOUR)"
);
if ($cleanup) { $cleanup->execute(); $cleanup->close(); }

$sql = "
    SELECT 
        la.id,
        la.start_time,
        la.end_time,
        la.status,
        la.room_name,
        la.is_free,
        la.session_type AS la_session_type,
        la.max_capacity AS la_max_capacity,
        la.subject AS slot_subject,
        l.id AS lecturer_id,
        l.full_name,
        COALESCE(NULLIF(TRIM(l.language), ''), 'en') AS language,
        l.photo,
        b.id AS booking_id,
        (SELECT COUNT(*) FROM bookings bx 
         WHERE bx.availability_id = la.id 
           AND bx.status IN ('Pending', 'Accepted')
        ) AS total_bookings,
        (SELECT bx.booking_type 
         FROM bookings bx 
         WHERE bx.availability_id = la.id 
           AND bx.status IN ('Pending', 'Accepted')
         ORDER BY bx.id ASC 
         LIMIT 1
        ) AS first_booking_type
     FROM lecturer_availability la
     JOIN lecturers l ON la.lecturer_id = l.id
     LEFT JOIN bookings b
            ON b.availability_id = la.id
           AND b.student_id = ?
           AND b.status IN ('Pending', 'Accepted')
     WHERE la.date = ?
       AND l.status = 'approved'
       AND la.approval_status = 'approved'
";

$params = [$currentStudentId, $date];
$types  = 'is';

if ($langFilter !== '') {
    $sql .= " AND LOWER(COALESCE(NULLIF(TRIM(l.language), ''), 'en')) = ?";
    $params[] = $langFilter;
    $types   .= 's';
}

// Subject filter – only when package subject is sent
// Match against lecturer_availability.subject (not lecturers.subject)
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

$sql .= " ORDER BY la.start_time ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $totalBookings = (int)$row['total_bookings'];
    $firstType     = strtolower(trim($row['first_booking_type'] ?? ''));

    if ($totalBookings > 0 && ($firstType === 'group' || $firstType === 'individual')) {
        $sessionType = $firstType;
        $maxCapacity = ($sessionType === 'group') ? 10 : 1;
    } else {
        $sessionType = 'open';
        $maxCapacity = 10;
    }

    $isBookedByMe = $row['booking_id'] !== null;
    $isFull       = ($sessionType !== 'open') && ($totalBookings >= $maxCapacity);
    $remaining    = max(0, $maxCapacity - $totalBookings);

    $effectiveStatus = $row['status'];
    if ($effectiveStatus === 'scheduled' && $isFull) {
        $effectiveStatus = 'booked';
    }

    // ★ ONLY subject from lecturer_availability table
    $slotSubject = trim((string)($row['slot_subject'] ?? ''));
    if ($slotSubject === '') {
        $slotSubject = 'General';
    }

    $slots[] = [
        'id'              => (int)$row['id'],
        'slot_id'         => (int)$row['id'],
        'lecturer_id'     => (int)$row['lecturer_id'],
        'full_name'       => $row['full_name'],
        'subject'         => $slotSubject,
        'language'        => strtolower(trim($row['language'] ?: 'en')),
        'photo'           => $row['photo'] ? ('uploads/lecturers/' . $row['photo']) : null,
        'start'           => substr($row['start_time'], 0, 5),
        'end'             => substr($row['end_time'], 0, 5),
        'status'          => $effectiveStatus,
        'room_name'       => $row['room_name'],
        'is_booked_by_me' => $isBookedByMe,
        'is_free'         => (int)$row['is_free'],
        'session_type'    => $sessionType,
        'max_capacity'    => $maxCapacity,
        'booked_count'    => $totalBookings,
        'remaining'       => $remaining,
        'is_full'         => $isFull,
        'can_book'        => !$isFull && !$isBookedByMe,
    ];
}

echo json_encode([
    'success' => true,
    'date'    => $date,
    'data'    => $slots
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();