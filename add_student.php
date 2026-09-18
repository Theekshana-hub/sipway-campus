<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$full_name = trim($input['full_name'] ?? '');
$email     = trim($input['email'] ?? '');
$mobile    = trim($input['mobile'] ?? '');
$password  = $input['password'] ?? '';

if ($full_name === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Full name, email, password mandatory']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email format eka wrong']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password characters 6ta wadiya wenna one']);
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    $checkStmt->close();
    echo json_encode(['success' => false, 'message' => 'Meka email ekak dhanma thiyenawa']);
    exit;
}
$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$status = 'approved';

$stmt = $conn->prepare("INSERT INTO students (full_name, email, mobile, password, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssss", $full_name, $email, $mobile, $hashedPassword, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student added', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();