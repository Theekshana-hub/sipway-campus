<?php
session_start();
header('Content-Type: application/json');


set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $e->getMessage()]);
    exit;
});
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'PHP Fatal: ' . $err['message'] . ' in ' . $err['file'] . ' on line ' . $err['line']]);
    }
});

require_once __DIR__ . '/db.php';

if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connect failed: ' . ($conn->connect_error ?? 'unknown')]);
    exit;
}

if (!isset($_SESSION['lecturer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login වෙන්න ඕන']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$lecturerId = (int)$_SESSION['lecturer_id'];
$input = json_decode(file_get_contents('php://input'), true);

$date  = trim($input['date'] ?? '');
$start = trim($input['start'] ?? '');
$end   = trim($input['end'] ?? '');

// ===== Validation =====
if (!$date || !$start || !$end) {
    echo json_encode(['success' => false, 'message' => 'සියලුම fields පුරවන්න']);
    exit;
}

$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    echo json_encode(['success' => false, 'message' => 'Date format එක වැරදියි']);
    exit;
}

$today = new DateTime('today');
if ($d < $today) {
    echo json_encode(['success' => false, 'message' => 'අතීත දවසකට slot එකක් add කරන්න බෑ']);
    exit;
}

if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $start) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $end)) {
    echo json_encode(['success' => false, 'message' => 'Time format එක වැරදියි']);
    exit;
}

if (strtotime($start) >= strtotime($end)) {
    echo json_encode(['success' => false, 'message' => 'End time එක start time එකට පස්සේ වෙන්න ඕන']);
    exit;
}

// ===== Prevent overlapping slots on the same date =====
$check = $conn->prepare(
    "SELECT id FROM lecturer_availability
     WHERE lecturer_id = ? AND date = ?
       AND start_time < ? AND end_time > ?
     LIMIT 1"
);
if ($check === false) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed (overlap check): ' . $conn->error . ' — "lecturer_availability" table එක DB එකේ තියෙනවද check කරන්න.']);
    exit;
}
$startSql = $start . ':00';
$endSql   = $end . ':00';
$check->bind_param('isss', $lecturerId, $date, $endSql, $startSql);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'මේ time range එක වෙන slot එකක් එක්ක overlap වෙනවා']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();


$stmt = $conn->prepare(
    "INSERT INTO lecturer_availability (lecturer_id, date, start_time, end_time, approval_status) VALUES (?, ?, ?, ?, 'pending')"
);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed (insert): ' . $conn->error . ' — "lecturer_availability" table එක DB එකේ තියෙනවද check කරන්න.']);
    exit;
}
$stmt->bind_param('isss', $lecturerId, $date, $startSql, $endSql);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Slot එක add උනා! Admin approve කරන තුරු students ට පේන්නෙ නෑ.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();