<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id        = (int)($input['id'] ?? 0);
$full_name = trim($input['full_name'] ?? '');
$email     = trim($input['email'] ?? '');
$mobile    = trim($input['mobile'] ?? '');
$password  = $input['password'] ?? ''; // optional

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}
if ($full_name === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Full name saha email mandatory']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email format eka wrong']);
    exit;
}
if ($password !== '' && strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password characters 6ta wadiya wenna one']);
    exit;
}

// Duplicate email check excluding self
$checkStmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
$checkStmt->bind_param("si", $email, $id);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    $checkStmt->close();
    echo json_encode(['success' => false, 'message' => 'Meka email ekak dhanma thiyenawa']);
    exit;
}
$checkStmt->close();

if ($password !== '') {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE students SET full_name=?, email=?, mobile=?, password=? WHERE id=?");
    $stmt->bind_param("ssssi", $full_name, $email, $mobile, $hashedPassword, $id);
} else {
    $stmt = $conn->prepare("UPDATE students SET full_name=?, email=?, mobile=? WHERE id=?");
    $stmt->bind_param("sssi", $full_name, $email, $mobile, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();