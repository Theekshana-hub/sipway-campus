<?php
header('Content-Type: application/json');
require_once 'db.php';

// NOTE: added `language` to the SELECT — this is the field that was missing.
$result = $conn->query("SELECT id, full_name, email, mobile, language, status, created_at FROM students ORDER BY created_at DESC");

if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'id'         => (int)$row['id'],
        'full_name'  => $row['full_name'],
        'email'      => $row['email'],
        'mobile'     => $row['mobile'],
        'language'   => $row['language'] ?? 'en',
        'status'     => $row['status'] ?? 'pending',
        'created_at' => $row['created_at'],
    ];
}

echo json_encode(['success' => true, 'data' => $students]);
$conn->close();