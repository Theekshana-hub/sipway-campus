<?php
header('Content-Type: application/json');
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid lecturer ID']);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, subject, email, qualifications, photo FROM lecturers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'data' => [
            'id'             => (int)$row['id'],
            'full_name'      => $row['full_name'],
            'subject'        => $row['subject'],
            'email'          => $row['email'],
            'qualifications' => $row['qualifications'],
            // Change 'uploads/lecturers/' below to match wherever lecturer photos are actually saved on your server
            'photo'          => $row['photo'] ? 'uploads/lecturers/' . $row['photo'] : null,
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lecturer not found']);
}

$stmt->close();
$conn->close();