<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('SIPWAY_APP', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'payment-config.php';
require_once 'dfcc-api.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: packages.php');
    exit;
}

// Guard: config still has placeholder domain
if (strpos(DFCC_RETURN_URL, 'YOURDOMAIN.com') !== false) {
    error_log('[DFCC] DFCC_RETURN_URL is still a placeholder in payment-config.php');
    header('Location: packages.php?error=gateway_not_configured');
    exit;
}

$studentId   = (int) $_SESSION['student_id'];
$packageId   = isset($_GET['package_id']) ? (int) $_GET['package_id'] : 0;
$packageType = (isset($_GET['type']) && $_GET['type'] === 'vocabulary') ? 'vocabulary' : 'regular';

if ($packageId <= 0) {
    header('Location: packages.php?error=invalid_package');
    exit;
}

if ($packageType === 'vocabulary') {
    $stmt = $conn->prepare("SELECT id, package_name, price FROM vocabulary_packages WHERE id = ? AND status = 'active' LIMIT 1");
} else {
    $stmt = $conn->prepare("SELECT id, package_name, price, total_sessions FROM packages WHERE id = ? AND status = 'active' LIMIT 1");
}
$stmt->bind_param('i', $packageId);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$package) {
    header('Location: packages.php?error=package_not_found');
    exit;
}

// Block duplicate (regular only)
if ($packageType === 'regular') {
    $chk = $conn->prepare("SELECT id FROM activated_packages WHERE student_id = ? AND status IN ('active','pending') LIMIT 1");
    $chk->bind_param('i', $studentId);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($existing) {
        header('Location: packages.php?error=already_active');
        exit;
    }
}

// Unique order id (max 50 chars)
$orderId  = 'SC' . date('YmdHis') . '-' . $studentId . '-' . random_int(100, 999);
$amount   = (float) $package['price'];
$cents    = dfcc_amount_to_cents($amount);
$currency = DFCC_CURRENCY;

// Log attempt
$ins = $conn->prepare("
    INSERT INTO payment_transactions 
        (order_id, student_id, package_id, package_type, amount, currency, status) 
    VALUES (?, ?, ?, ?, ?, ?, 'initiated')
");
$ins->bind_param('siisss', $orderId, $studentId, $packageId, $packageType, $amount, $currency);
$ins->execute();
$ins->close();

// Also insert into payments table (your existing structure)
$ins2 = $conn->prepare("
    INSERT INTO payments 
        (student_id, package_id, order_id, amount, currency, status) 
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$ins2->bind_param('iisds', $studentId, $packageId, $orderId, $amount, $currency);
$ins2->execute();
$ins2->close();

// ---- PAYMENT_INIT ----
$requestData = [
    'clientId'          => (string) DFCC_CLIENT_ID,
    'clientIdHash'      => '',
    'transactionType'   => 'PURCHASE',
    'transactionAmount' => [
        'totalAmount'      => 0,
        'paymentAmount'    => $cents,
        'serviceFeeAmount' => 0,
        'currency'         => $currency,
    ],
    'redirect' => [
        'returnUrl'    => DFCC_RETURN_URL . '?order_id=' . urlencode($orderId),
        'returnMethod' => 'GET',
    ],
    'clientRef'      => $orderId,
    'comment'        => $package['package_name'],
    'tokenize'       => false,
    'cssLocation1'   => '',
    'cssLocation2'   => '',
    'useReliability' => true,
    'extraData'      => ['package_type' => $packageType],
];

$result = dfcc_api_call('PAYMENT_INIT', $requestData);

// ===== DEBUG LINE ඉවත් කරන්න =====
// echo '<pre>'; print_r($result); echo '</pre>'; exit;

if (!$result['ok'] || empty($result['body']['responseData']['paymentPageUrl']) || empty($result['body']['responseData']['reqid'])) {
    error_log('[DFCC] PAYMENT_INIT failed for order ' . $orderId . ': ' . ($result['error'] ?? $result['raw']));
    
    $fail = $conn->prepare("UPDATE payment_transactions SET status = 'init_failed' WHERE order_id = ?");
    $fail->bind_param('s', $orderId);
    $fail->execute();
    $fail->close();

    $fail2 = $conn->prepare("UPDATE payments SET status = 'failed', raw_response = ? WHERE order_id = ?");
    $raw = $result['raw'] ?? ($result['error'] ?? 'unknown');
    $fail2->bind_param('ss', $raw, $orderId);
    $fail2->execute();
    $fail2->close();

    $conn->close();
    header('Location: packages.php?error=payment_gateway_error');
    exit;
}

$reqId          = $result['body']['responseData']['reqid'];
$paymentPageUrl = $result['body']['responseData']['paymentPageUrl'];

$upd = $conn->prepare("UPDATE payment_transactions SET status = 'redirected', reqid = ? WHERE order_id = ?");
$upd->bind_param('ss', $reqId, $orderId);
$upd->execute();
$upd->close();

$upd2 = $conn->prepare("UPDATE payments SET reqid = ? WHERE order_id = ?");
$upd2->bind_param('ss', $reqId, $orderId);
$upd2->execute();
$upd2->close();

$conn->close();

// Redirect to DFCC payment page
header('Location: ' . $paymentPageUrl);
exit;