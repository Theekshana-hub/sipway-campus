<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$res = $conn->query("SELECT id, name AS package_name, price, duration_days, description, sort_order, status FROM ai_video_packages ORDER BY sort_order ASC, id ASC");
$packages = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $packages[] = $row;
    }
}
echo json_encode(['success' => true, 'packages' => $packages]);
$conn->close();