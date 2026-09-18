<?php


header('Content-Type: application/json');
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

$packageId      = isset($input['package_id']) ? (int)$input['package_id'] : 0;
$extraSessions  = isset($input['extra_sessions']) ? (int)$input['extra_sessions'] : 0;

if ($packageId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid package id.']);
    exit();
}

if ($extraSessions <= 0 || $extraSessions > 100) {
    echo json_encode(['success' => false, 'message' => 'Extra sessions eka 1 sita 100 athara anka ekak wenna oni.']);
    exit();
}

// Confirm package exists first
$check = $conn->prepare("SELECT id, total_sessions, sessions_remaining, status FROM activated_packages WHERE id = ? LIMIT 1");
$check->bind_param("i", $packageId);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if (!$existing) {
    echo json_encode(['success' => false, 'message' => 'Package eka hoyaganna baha.']);
    exit();
}

// Update: increase both total_sessions and sessions_remaining
$update = $conn->prepare("
    UPDATE activated_packages
    SET total_sessions = total_sessions + ?,
        sessions_remaining = sessions_remaining + ?
    WHERE id = ?
");
$update->bind_param("iii", $extraSessions, $extraSessions, $packageId);

if (!$update->execute()) {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $update->error]);
    $update->close();
    $conn->close();
    exit();
}
$update->close();


$fetch = $conn->prepare("SELECT total_sessions, sessions_remaining, status FROM activated_packages WHERE id = ? LIMIT 1");
$fetch->bind_param("i", $packageId);
$fetch->execute();
$fresh = $fetch->get_result()->fetch_assoc();
$fetch->close();


if ($fresh && (int)$fresh['sessions_remaining'] > 0 && $fresh['status'] === 'completed') {
    $reactivate = $conn->prepare("UPDATE activated_packages SET status = 'active' WHERE id = ?");
    $reactivate->bind_param("i", $packageId);
    $reactivate->execute();
    $reactivate->close();
    $fresh['status'] = 'active';
}

$total     = (int)$fresh['total_sessions'];
$remaining = (int)$fresh['sessions_remaining'];
$done      = max(0, $total - $remaining);
$percent   = $total > 0 ? round(($done / $total) * 100) : 0;

echo json_encode([
    'success' => true,
    'data' => [
        'package_id'         => $packageId,
        'total_sessions'     => $total,
        'sessions_remaining' => $remaining,
        'sessions_done'      => $done,
        'percent'            => $percent,
        'status'             => $fresh['status'],
    ]
]);

$conn->close();