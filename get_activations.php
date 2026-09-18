<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$sql = "
    SELECT 
        ap.id,
        ap.student_id,
        ap.package_id,
        ap.status,
        ap.payment_method,
        ap.transaction_ref,
        ap.receipt_path,
        ap.notes,
        ap.activated_at,
        s.full_name AS student_name,
        p.package_name,
        p.price,
        p.total_sessions
    FROM activated_packages ap
    LEFT JOIN students s ON s.id = ap.student_id
    LEFT JOIN packages p ON p.id = ap.package_id
    ORDER BY 
        CASE WHEN ap.status = 'pending' THEN 0 ELSE 1 END,
        ap.id DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    $conn->close();
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'               => (int)$row['id'],
        'student_id'       => (int)$row['student_id'],
        'package_id'       => (int)$row['package_id'],
        'status'           => $row['status'] ?: 'pending',
        'payment_method'   => $row['payment_method'] ?: 'online',  // default online if null
        'transaction_ref'  => $row['transaction_ref'] ?: '',
        'receipt_path'     => $row['receipt_path'] ?: '',
        'notes'            => $row['notes'] ?: '',
        'activated_at'     => $row['activated_at'],
        'student_name'     => $row['student_name'] ?: 'Unknown',
        'package_name'     => $row['package_name'] ?: 'Unknown',
        'price'            => (float)$row['price'],
        'total_sessions'   => (int)$row['total_sessions'],
    ];
}

$conn->close();
echo json_encode(['success' => true, 'data' => $data]);