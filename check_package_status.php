<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit();
}

include 'db.php';

$studentId = (int)$_SESSION['student_id'];
$orderId   = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';

if ($orderId === '') {
    echo json_encode(['success' => false, 'message' => 'Missing order id.']);
    exit();
}

$stmt = $conn->prepare("SELECT status FROM activated_packages WHERE student_id = ? AND order_id = ? LIMIT 1");
$stmt->bind_param('is', $studentId, $orderId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit();
}

echo json_encode(['success' => true, 'status' => $row['status']]);