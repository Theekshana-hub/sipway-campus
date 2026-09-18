<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Make sure connection uses utf8
if (method_exists($conn, 'set_charset')) {
    $conn->set_charset('utf8mb4');
}

$sql = "
    SELECT 
        av.id,
        av.student_id,
        av.package_id,
        av.status,
        av.payment_method,
        av.transaction_ref,
        av.receipt_path,
        av.notes,
        av.created_at,
        av.activated_at,
        COALESCE(s.full_name, 'Unknown') AS student_name,
        COALESCE(vp.package_name, CONCAT('Package #', av.package_id)) AS package_name,
        COALESCE(vp.price, 0) AS price,
        COALESCE(vp.duration_label, '') AS duration_label
    FROM activated_vocabulary_packages av
    LEFT JOIN students s ON s.id = av.student_id
    LEFT JOIN vocabulary_packages vp ON vp.id = av.package_id
    ORDER BY 
        CASE WHEN av.status = 'pending' THEN 0 ELSE 1 END,
        av.id DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL error: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'              => (int)$row['id'],
        'student_id'      => (int)$row['student_id'],
        'package_id'      => (int)$row['package_id'],
        'status'          => $row['status'] ?: 'pending',
        'payment_method'  => $row['payment_method'] ?: 'online',
        'transaction_ref' => $row['transaction_ref'] ?: '',
        'receipt_path'    => $row['receipt_path'] ?: '',
        'notes'           => $row['notes'] ?: '',
        'created_at'      => $row['created_at'],
        'activated_at'    => $row['activated_at'],
        'student_name'    => $row['student_name'],
        'package_name'    => $row['package_name'],
        'price'           => (float)$row['price'],
        'duration_label'  => $row['duration_label'],
    ];
}

$conn->close();

echo json_encode([
    'success' => true,
    'count'   => count($data),
    'data'    => $data
], JSON_UNESCAPED_UNICODE);