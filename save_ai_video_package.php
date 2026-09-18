<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$id           = (int)($_POST['id'] ?? 0);
$name         = trim($_POST['package_name'] ?? '');
$price        = (float)($_POST['price'] ?? 0);
$durationDays = (int)($_POST['duration_days'] ?? 30);
$description  = trim($_POST['description'] ?? '');
$sortOrder    = (int)($_POST['sort_order'] ?? 0);
$status       = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Package name required']);
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE ai_video_packages SET name=?, price=?, duration_days=?, description=?, sort_order=?, status=? WHERE id=?");
    $stmt->bind_param("sdisisi", $name, $price, $durationDays, $description, $sortOrder, $status, $id);
} else {
    $stmt = $conn->prepare("INSERT INTO ai_video_packages (name, price, duration_days, description, sort_order, status) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("sdisis", $name, $price, $durationDays, $description, $sortOrder, $status);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Save failed']);
}
$stmt->close();
$conn->close();