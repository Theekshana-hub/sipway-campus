<?php
/**
 * lecturer_add_availability.php
 * ---------------------------------------------------------
 * Lecturer dashboard eken call karana endpoint eka.
 * Lecturer kenek thama time slot ekak add karanawa.
 * Meka DIRECTLY APPROVED widiyata save wenawa (admin approval oona na).
 * ---------------------------------------------------------
 */
session_start();
header('Content-Type: application/json');
require_once 'db.php';

function respond($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

// ---- Auth check ---------------------------------------------------------
if (!isset($_SESSION['lecturer_id'])) {
    respond(['success' => false, 'message' => 'Unauthorized - please login again'], 401);
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    respond(['success' => false, 'message' => 'Database connection eka setup karanna baa una (db.php check karanna).'], 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Invalid request method'], 405);
}

// ---- Read JSON body ---------------------------------------------------
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    respond(['success' => false, 'message' => 'Invalid JSON body']);
}

$lecturerId = (int) $_SESSION['lecturer_id'];
$subject    = isset($body['subject']) ? trim($body['subject']) : '';
$date       = isset($body['date'])  ? trim($body['date'])  : '';
$start      = isset($body['start']) ? trim($body['start']) : '';
$end        = isset($body['end'])   ? trim($body['end'])   : '';
$isFree     = (isset($body['is_free']) && (int) $body['is_free'] === 1) ? 1 : 0;

// ---- Validation ---------------------------------------------------------
$errors = [];

if ($subject === '') {
    $errors['subject'] = 'Subject eka select karanna';
}

$dateObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$date || !$dateObj || $dateObj->format('Y-m-d') !== $date) {
    $errors['date'] = 'Valid date ekak select karanna';
} else {
    $today = new DateTime('today');
    if ($dateObj < $today) {
        $errors['date'] = 'Past date ekak walata slot add karanna baa';
    }
}

if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $start)) {
    $errors['start'] = 'Valid start time ekak denna';
}

if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $end)) {
    $errors['end'] = 'Valid end time ekak denna';
}

if (empty($errors['start']) && empty($errors['end']) && $start >= $end) {
    $errors['end'] = 'End time eka start time ekata passe wenna one';
}

if (!empty($errors)) {
    respond(['success' => false, 'errors' => $errors]);
}

$startTime = strlen($start) === 5 ? $start . ':00' : $start;
$endTime   = strlen($end) === 5 ? $end . ':00' : $end;

// ---- Confirm lecturer exists + get subjects -----------------------------
$stmt = $conn->prepare('SELECT id, full_name, subject FROM lecturers WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $lecturerId);
$stmt->execute();
$lecturerResult = $stmt->get_result();

if ($lecturerResult->num_rows === 0) {
    $stmt->close();
    respond(['success' => false, 'message' => 'Lecturer hamu unne na']);
}

$lecturer = $lecturerResult->fetch_assoc();
$stmt->close();

// Check that the selected subject belongs to this lecturer
$allowedSubjects = array_map('trim', explode(',', $lecturer['subject'] ?? ''));
if (!in_array($subject, $allowedSubjects)) {
    respond(['success' => false, 'message' => 'Me subject eka obata assign karala netha']);
}

// ---- Prevent overlapping slots ------------------------------------------
$overlapStmt = $conn->prepare(
    "SELECT id FROM lecturer_availability
     WHERE lecturer_id = ? AND date = ? AND subject = ?
       AND approval_status IN ('pending','approved')
       AND start_time < ? AND end_time > ?
     LIMIT 1"
);
$overlapStmt->bind_param('issss', $lecturerId, $date, $subject, $endTime, $startTime);
$overlapStmt->execute();
$overlapResult = $overlapStmt->get_result();

if ($overlapResult->num_rows > 0) {
    $overlapStmt->close();
    respond(['success' => false, 'errors' => [
        'end' => 'Me subject ekata me time ekata already slot ekak thiyenawa'
    ]]);
}
$overlapStmt->close();

// ---- Insert the slot as APPROVED (direct) -------------------------------
$insertStmt = $conn->prepare(
    "INSERT INTO lecturer_availability
        (lecturer_id, subject, date, start_time, end_time, status, approval_status, is_free)
     VALUES (?, ?, ?, ?, ?, 'scheduled', 'approved', ?)"
);

$insertStmt->bind_param('issssi', $lecturerId, $subject, $date, $startTime, $endTime, $isFree);

if ($insertStmt->execute()) {
    $newId = $insertStmt->insert_id;
    $insertStmt->close();

    respond([
        'success' => true,
        'message' => $isFree
            ? 'Free Session slot eka add unuwa! ✅'
            : 'Slot eka add unuwa! ✅',
        'data' => [
            'id'              => $newId,
            'lecturer_id'     => $lecturerId,
            'subject'         => $subject,
            'date'            => $date,
            'start'           => $start,
            'end'             => $end,
            'status'          => 'scheduled',
            'approval_status' => 'approved',
            'is_free'         => $isFree,
        ]
    ]);
} else {
    $err = $insertStmt->error;
    $insertStmt->close();
    respond(['success' => false, 'message' => 'Database error: ' . $err], 500);
}