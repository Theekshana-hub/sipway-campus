<?php

ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

function out($payload, $code = 200) {
    if (ob_get_length()) { ob_clean(); }   
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$dbFile = __DIR__ . '/db.php';
if (!file_exists($dbFile)) {
    out(['success' => false, 'message' => 'db.php not found on server'], 500);
}
include $dbFile;

if (!isset($conn) || !($conn instanceof mysqli)) {
    out(['success' => false, 'message' => 'db.php did not create $conn'], 500);
}
if ($conn->connect_error) {
    out(['success' => false, 'message' => 'DB connect failed: ' . $conn->connect_error], 500);
}
$conn->set_charset('utf8mb4');

// SELECT * so a missing column (language / package_type / sort_order ...) can't break the query
$result = $conn->query("SELECT * FROM packages");
if (!$result) {
    out(['success' => false, 'message' => 'Query failed: ' . $conn->error], 500);
}

$packages = [];
while ($row = $result->fetch_assoc()) {
    $row['id']             = (int)($row['id'] ?? 0);
    $row['package_name']   = $row['package_name']   ?? '';
    $row['price']          = $row['price']          ?? 0;
    $row['total_sessions'] = $row['total_sessions'] ?? 1;
    $row['duration_label'] = $row['duration_label'] ?? '';
    $row['description']    = $row['description']    ?? '';
    $row['is_offer']       = (int)($row['is_offer'] ?? 0);
    $row['status']         = $row['status']         ?? 'active';
    $row['sort_order']     = (int)($row['sort_order'] ?? 0);

    $row['package_type'] = strtolower(trim($row['package_type'] ?? ''));
    if ($row['package_type'] === '') { $row['package_type'] = 'individual'; }

    $row['language'] = strtolower(trim($row['language'] ?? ''));
    if ($row['language'] === '') { $row['language'] = 'en'; }

    $packages[] = $row;
}

usort($packages, function ($a, $b) {
    return [$a['sort_order'], $a['id']] <=> [$b['sort_order'], $b['id']];
});

$conn->close();

out(['success' => true, 'packages' => $packages]);