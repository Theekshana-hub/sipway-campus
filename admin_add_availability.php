<?php

header('Content-Type: application/json');
require_once 'db.php';

function respond($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    respond(['success' => false, 'message' => 'Database connection eka setup karanna baa una (db.php check karanna).'], 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Invalid request method'], 405);
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    respond(['success' => false, 'message' => 'Invalid JSON body']);
}

// How many weeks ahead to generate for a "weekly recurring" slot.
// e.g. 12 = ~3 months of slots created in one go.
// If you want it to keep going forever, re-run this endpoint (or a cron)
// every few weeks with the same subject/day/time to top it back up.
const RECURRING_WEEKS_AHEAD = 12;

$lecturerId = isset($body['lecturer_id']) ? (int) $body['lecturer_id'] : 0;
$subject    = isset($body['subject']) ? trim($body['subject']) : '';
$type       = isset($body['type']) ? trim($body['type']) : 'once'; // 'once' | 'weekly'
$date       = isset($body['date'])  ? trim((string) $body['date'])  : '';
$weekday    = isset($body['weekday']) && $body['weekday'] !== null ? (int) $body['weekday'] : null; // 0=Sun..6=Sat
$start      = isset($body['start']) ? trim($body['start']) : '';
$end        = isset($body['end'])   ? trim($body['end'])   : '';
$isFree     = (isset($body['is_free']) && (int) $body['is_free'] === 1) ? 1 : 0;

if ($type !== 'weekly') {
    $type = 'once';
}

$errors = [];

if ($lecturerId <= 0) {
    $errors['lecturer_id'] = 'Lecturer eka select karanna';
}

if ($subject === '') {
    $errors['subject'] = 'Subject eka select karanna';
}

// ===== Build the list of dates this request should create slots for =====
$dates = [];

if ($type === 'weekly') {
    if ($weekday === null || $weekday < 0 || $weekday > 6) {
        $errors['weekday'] = 'Valid day ekak select karanna';
    } else {
        // Find the first occurrence of this weekday on/after today, then
        // step forward one week at a time for RECURRING_WEEKS_AHEAD weeks.
        $phpWeekdayMap = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'];
        $today = new DateTime('today');
        $first = new DateTime('today');
        // DateTime 'next Monday' etc. skips today even if today matches, so handle today separately
        if ((int) $today->format('w') === $weekday) {
            $first = clone $today;
        } else {
            $first->modify('next ' . $phpWeekdayMap[$weekday]);
        }
        for ($i = 0; $i < RECURRING_WEEKS_AHEAD; $i++) {
            $d = clone $first;
            $d->modify('+' . ($i * 7) . ' days');
            $dates[] = $d->format('Y-m-d');
        }
    }
} else {
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$date || !$dateObj || $dateObj->format('Y-m-d') !== $date) {
        $errors['date'] = 'Valid date ekak select karanna';
    } else {
        $today = new DateTime('today');
        if ($dateObj < $today) {
            $errors['date'] = 'Past date ekak walata slot add karanna baa';
        } else {
            $dates[] = $date;
        }
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

$stmt = $conn->prepare('SELECT id, full_name FROM lecturers WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $lecturerId);
$stmt->execute();
$lecturerResult = $stmt->get_result();

if ($lecturerResult->num_rows === 0) {
    $stmt->close();
    respond(['success' => false, 'message' => 'Lecturer hamu unne na']);
}

$lecturer = $lecturerResult->fetch_assoc();
$stmt->close();

$overlapStmt = $conn->prepare(
    "SELECT id FROM lecturer_availability
     WHERE lecturer_id = ? AND date = ? AND subject = ?
       AND approval_status IN ('pending','approved')
       AND start_time < ? AND end_time > ?
     LIMIT 1"
);

$insertStmt = $conn->prepare(
    "INSERT INTO lecturer_availability
        (lecturer_id, subject, date, start_time, end_time, status, approval_status, is_free)
     VALUES (?, ?, ?, ?, ?, 'scheduled', 'approved', ?)"
);

$createdIds   = [];
$skippedDates = []; // dates that already had an overlapping slot, so were left untouched

foreach ($dates as $d) {
    $overlapStmt->bind_param('issss', $lecturerId, $d, $subject, $endTime, $startTime);
    $overlapStmt->execute();
    $overlapResult = $overlapStmt->get_result();

    if ($overlapResult->num_rows > 0) {
        $skippedDates[] = $d;
        continue;
    }

    $insertStmt->bind_param('issssi', $lecturerId, $subject, $d, $startTime, $endTime, $isFree);
    if ($insertStmt->execute()) {
        $createdIds[] = $insertStmt->insert_id;
    }
}

$overlapStmt->close();
$insertStmt->close();

if (count($createdIds) === 0) {
    // Every date requested already had a clashing slot
    if ($type === 'weekly') {
        respond(['success' => false, 'errors' => [
            'weekday' => 'Me lecturer ta me subject ekata me time ekata (weekly) already slots thiyenawa'
        ]]);
    }
    respond(['success' => false, 'errors' => [
        'end' => 'Me lecturer ta me subject ekata me time ekata already slot ekak thiyenawa'
    ]]);
}

if ($type === 'weekly') {
    $msg = $lecturer['full_name'] . ' ge Weekly slot eka add unuwa (Subject: ' . $subject . ') — '
        . count($createdIds) . ' weeks ekata slots hadunuwa';
    if (!empty($skippedDates)) {
        $msg .= ' (' . count($skippedDates) . ' dates walata dan slots thibbawa nisa ewa skip unuwa)';
    }
} else {
    $msg = $isFree
        ? ($lecturer['full_name'] . ' ge Free Session slot eka add unuwa (Subject: ' . $subject . ')')
        : ($lecturer['full_name'] . ' ge slot eka add unuwa (Subject: ' . $subject . ')');
}

respond([
    'success' => true,
    'message' => $msg,
    'data' => [
        'lecturer_id'     => $lecturerId,
        'lecturer'        => $lecturer['full_name'],
        'subject'         => $subject,
        'type'            => $type,
        'created_ids'     => $createdIds,
        'created_count'   => count($createdIds),
        'skipped_dates'   => $skippedDates,
        'start'           => $start,
        'end'             => $end,
        'status'          => 'scheduled',
        'approval_status' => 'approved',
        'is_free'         => $isFree,
    ]
]);