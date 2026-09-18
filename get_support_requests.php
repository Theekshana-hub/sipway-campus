<?php
header('Content-Type: application/json');
include 'db.php';

$result = $conn->query("SELECT * FROM support_requests ORDER BY created_at DESC");

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode(['success' => true, 'data' => $rows]);
$conn->close();