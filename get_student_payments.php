<?php
/**
 * get_student_payments.php
 * Returns activated_packages rows (student name, package, amount paid,
 * sessions remaining, purchase date) for a date range, for use in
 * filter.php's "Student Payments (by Month)" tab and Excel export.
 *
 * Table used (from phpMyAdmin -> sipway -> activated_packages):
 *   id, student_id, student_name, package_id, package_name, price,
 *   total_sessions, sessions_remaining, status, payment_method,
 *   transaction_ref, receipt_path, notes, activated_at, order_id,
 *   amount, payment_id, updated_at, payment_status, transaction_id
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // don't leak PHP errors into the JSON response

// ---------------------------------------------------------------
// DB CONNECTION
// If your project already has a shared config/connection file
// (e.g. config.php / db_connect.php used by get_bookings.php etc.),
// replace the block below with:  require_once 'db_connect.php';
// and make sure it exposes a mysqli connection in $conn.
// ---------------------------------------------------------------
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'sipway';

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connect unnee na: ' . $conn->connect_error
    ]);
    exit;
}
$conn->set_charset('utf8mb4');

// ---------------------------------------------------------------
// INPUT
// ---------------------------------------------------------------
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate   = isset($_GET['end_date'])   ? trim($_GET['end_date'])   : '';

$dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($dateRegex, $startDate) || !preg_match($dateRegex, $endDate)) {
    echo json_encode([
        'success' => false,
        'message' => 'start_date / end_date format eka YYYY-MM-DD widihata denna.'
    ]);
    exit;
}

// ---------------------------------------------------------------
// QUERY
// amount column eka NULL nam (e.g. free / not-yet-paid rows),
// price eka fallback widihata gannawa, salli gewapu ekak widihata.
// activated_at = student ge package eka activate/purchase una dawasa.
// ---------------------------------------------------------------
$sql = "
    SELECT
        id,
        student_id,
        student_name,
        package_id,
        package_name,
        price,
        total_sessions,
        sessions_remaining,
        status,
        payment_status,
        COALESCE(amount, price) AS amount_paid,
        activated_at
    FROM activated_packages
    WHERE DATE(activated_at) BETWEEN ? AND ?
    ORDER BY activated_at ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Query prepare wenne na: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$totalAmount = 0;
$totalSessionsRemaining = 0;

while ($r = $result->fetch_assoc()) {
    $amount = (float) $r['amount_paid'];
    $sessionsRemaining = (int) $r['sessions_remaining'];

    $rows[] = [
        'id'                 => (int) $r['id'],
        'student_id'         => (int) $r['student_id'],
        'student_name'       => $r['student_name'],
        'package_id'         => (int) $r['package_id'],
        'package_name'       => $r['package_name'],
        'price'              => (float) $r['price'],
        'total_sessions'     => (int) $r['total_sessions'],
        'sessions_remaining' => $sessionsRemaining,
        'status'             => $r['status'],
        'payment_status'     => $r['payment_status'],
        'amount'             => $amount,               // used by filter.php as "Amount Paid"
        'purchased_on'       => $r['activated_at'],     // used by filter.php for month grouping
    ];

    $totalAmount += $amount;
    $totalSessionsRemaining += $sessionsRemaining;
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'range' => ['start' => $startDate, 'end' => $endDate],
    'summary' => [
        'total_payments' => count($rows),
        'total_amount' => $totalAmount,
        'total_sessions_remaining' => $totalSessionsRemaining,
    ],
    'data' => $rows,
]);