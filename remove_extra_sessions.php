<?php


header('Content-Type: application/json');
require_once 'db.php';

function respond($success, $message = '', $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ]);
    exit;
}

if (!isset($conn) || $conn === null) {
    respond(false, 'Database connection failed.');
}

$input = json_decode(file_get_contents('php://input'), true);

$packageId = isset($input['package_id']) ? (int)$input['package_id'] : 0;
$reduceBy  = isset($input['reduce_sessions']) ? (int)$input['reduce_sessions'] : 0;

if ($packageId <= 0) {
    respond(false, 'Invalid package id.');
}
if ($reduceBy <= 0 || $reduceBy > 100) {
    respond(false, 'Reduce amount must be between 1 and 100.');
}


$stmt = $conn->prepare("SELECT total_sessions, sessions_remaining, status FROM activated_packages WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $packageId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    respond(false, 'Package not found.');
}

$total     = (int)$row['total_sessions'];
$remaining = (int)$row['sessions_remaining'];
$completedSoFar = $total - $remaining; 

if ($reduceBy > $remaining) {
    respond(false, "Cannot reduce more than the {$remaining} remaining sessions.");
}

$newTotal     = $total - $reduceBy;
$newRemaining = $remaining - $reduceBy;


if ($newTotal < $completedSoFar) {
    respond(false, 'Cannot reduce below sessions already completed.');
}

$newStatus = $newRemaining <= 0 ? 'completed' : 'active';

$update = $conn->prepare("UPDATE activated_packages SET total_sessions = ?, sessions_remaining = ?, status = ? WHERE id = ?");
$update->bind_param('iisi', $newTotal, $newRemaining, $newStatus, $packageId);

if (!$update->execute()) {
    $update->close();
    $conn->close();
    respond(false, 'Failed to update package.');
}
$update->close();
$conn->close();

$doneCount = max(0, $newTotal - $newRemaining);
$percent = $newTotal > 0 ? round(($doneCount / $newTotal) * 100) : 0;

respond(true, 'Sessions reduced successfully.', [
    'total_sessions'     => $newTotal,
    'sessions_remaining' => $newRemaining,
    'percent'            => $percent,
    'status'             => $newStatus,
]);