<?php
// admin/delete_package.php
// Deletes a package by id.

header('Content-Type: application/json');
include 'db.php'; // <-- change this if your db connection file has a different name

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid package id.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Package deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();