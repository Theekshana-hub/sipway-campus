<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$id             = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
$package_name   = trim($_POST['package_name'] ?? '');
$price          = (float)($_POST['price'] ?? 0);
$duration_label = trim($_POST['duration_label'] ?? 'Unlimited Access');
$description    = trim($_POST['description'] ?? '');
$status         = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
$sort_order     = (int)($_POST['sort_order'] ?? 0);
$is_offer       = isset($_POST['is_offer']) ? 1 : 0;

if ($package_name === '') {
    echo json_encode(['success' => false, 'message' => 'Package name required']);
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE vocabulary_packages SET package_name=?, price=?, duration_label=?, description=?, status=?, sort_order=?, is_offer=? WHERE id=?");
    $stmt->bind_param('sdsssiii', $package_name, $price, $duration_label, $description, $status, $sort_order, $is_offer, $id);
} else {
    $stmt = $conn->prepare("INSERT INTO vocabulary_packages (package_name, price, duration_label, description, status, sort_order, is_offer) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('sdsssii', $package_name, $price, $duration_label, $description, $status, $sort_order, $is_offer);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $id > 0 ? $id : $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();