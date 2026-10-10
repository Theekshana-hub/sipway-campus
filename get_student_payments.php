<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function out($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    ob_start();
    require_once __DIR__ . '/db.php';   
    ob_end_clean();

    if (!isset($conn) || !($conn instanceof mysqli)) {
        out(['success' => false, 'message' => 'db.php eken $conn (mysqli) hadala naha. db.php eka check karanna.']);
    }
    $conn->set_charset('utf8mb4');

    $startDate = trim($_GET['start_date'] ?? '');
    $endDate   = trim($_GET['end_date'] ?? '');

    $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($dateRegex, $startDate) || !preg_match($dateRegex, $endDate)) {
        out(['success' => false, 'message' => 'start_date / end_date format eka YYYY-MM-DD widihata denna.']);
    }

    $sql = "SELECT id, student_id, student_name, package_id, package_name, price,
                   total_sessions, sessions_remaining, status, payment_status,
                   COALESCE(amount, price) AS amount_paid,
                   activated_at
            FROM activated_packages
            WHERE DATE(activated_at) BETWEEN ? AND ?
              AND LOWER(COALESCE(status, ''))         NOT IN ('pending','rejected','cancelled','canceled','failed')
              AND LOWER(COALESCE(payment_status, '')) NOT IN ('pending','rejected','cancelled','canceled','failed')
            ORDER BY activated_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $totalAmount = 0;
    $totalSessionsRemaining = 0;

    while ($r = $result->fetch_assoc()) {
        $amount = (float)$r['amount_paid'];
        $remaining = (int)$r['sessions_remaining'];

        $rows[] = [
            'id'                 => (int)$r['id'],
            'student_id'         => (int)$r['student_id'],
            'student_name'       => $r['student_name'],
            'package_id'         => (int)$r['package_id'],
            'package_name'       => $r['package_name'],
            'price'              => (float)$r['price'],
            'total_sessions'     => (int)$r['total_sessions'],
            'sessions_remaining' => $remaining,
            'status'             => $r['status'],
            'payment_status'     => $r['payment_status'],
            'amount'             => $amount,
            'purchased_on'       => $r['activated_at'],
        ];

        $totalAmount += $amount;
        $totalSessionsRemaining += $remaining;
    }
    $stmt->close();

    out([
        'success' => true,
        'range'   => ['start' => $startDate, 'end' => $endDate],
        'summary' => [
            'total_payments'           => count($rows),
            'total_amount'             => $totalAmount,
            'total_sessions_remaining' => $totalSessionsRemaining,
        ],
        'data' => $rows,
    ]);

} catch (Throwable $e) {
    out(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}