<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('SIPWAY_APP', true);

require_once 'db.php';
require_once 'payment-config.php';
require_once 'dfcc-api.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: vocabulary-practice.php?error=not_logged_in');
    exit;
}

$studentId  = (int)$_SESSION['student_id'];
$packageId  = (int)($_GET['package_id'] ?? 0);

if ($packageId <= 0) {
    header('Location: vocabulary-practice.php?error=invalid_package');
    exit;
}

// Check active package
$stmt = $conn->prepare("
    SELECT id, package_name, price 
    FROM vocabulary_packages 
    WHERE id = ? AND status = 'active' 
    LIMIT 1
");
$stmt->bind_param('i', $packageId);
$stmt->execute();
$pkg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pkg) {
    header('Location: vocabulary-practice.php?error=package_not_found');
    exit;
}

// Check existing active package
$stmt = $conn->prepare("
    SELECT id FROM activated_vocabulary_packages 
    WHERE student_id = ? AND status IN ('active','pending') 
    LIMIT 1
");
$stmt->bind_param('i', $studentId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    header('Location: vocabulary-practice.php?error=already_active');
    exit;
}
$stmt->close();

// Unique order ID
$orderId = 'VOCAB-' . $studentId . '-' . $packageId . '-' . time();
$amount  = (float)$pkg['price'];
$cents   = dfcc_amount_to_cents($amount);

// Save initial transaction record
$stmt1 = $conn->prepare("
    INSERT INTO payment_transactions 
    (student_id, package_id, package_type, order_id, amount, currency, status, created_at)
    VALUES (?, ?, 'vocabulary', ?, ?, 'LKR', 'initiated', NOW())
");
$stmt1->bind_param('iisd', $studentId, $packageId, $orderId, $amount);
$stmt1->execute();
$stmt1->close();

$stmt2 = $conn->prepare("
    INSERT INTO payments 
    (student_id, package_id, order_id, amount, currency, status, created_at)
    VALUES (?, ?, ?, ?, 'LKR', 'pending', NOW())
");
$stmt2->bind_param('iisd', $studentId, $packageId, $orderId, $amount);
$stmt2->execute();
$stmt2->close();

// ---- PAYMENT_INIT ----
$requestData = [
    'clientId'          => (string) DFCC_CLIENT_ID,
    'clientIdHash'      => '',
    'transactionType'   => 'PURCHASE',
    'transactionAmount' => [
        'totalAmount'      => 0,
        'paymentAmount'    => $cents,
        'serviceFeeAmount' => 0,
        'currency'         => DFCC_CURRENCY,
    ],
    'redirect' => [
        'returnUrl'    => DFCC_RETURN_URL,
        'returnMethod' => 'GET',
    ],
    'clientRef'      => $orderId,
    'comment'        => $pkg['package_name'],
    'tokenize'       => false,
    'cssLocation1'   => '',
    'cssLocation2'   => '',
    'useReliability' => true,
    'extraData'      => ['package_type' => 'vocabulary'],
];

$result = dfcc_api_call('PAYMENT_INIT', $requestData);

if (!$result['ok'] || empty($result['body']['responseData']['paymentPageUrl']) || empty($result['body']['responseData']['reqid'])) {
    error_log('[DFCC] PAYMENT_INIT failed for vocab order ' . $orderId . ': ' . ($result['error'] ?? $result['raw']));
    
    $fail = $conn->prepare("UPDATE payment_transactions SET status = 'failed' WHERE order_id = ?");
    $fail->bind_param('s', $orderId);
    $fail->execute();
    $fail->close();

    $fail2 = $conn->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ?");
    $fail2->bind_param('s', $orderId);
    $fail2->execute();
    $fail2->close();

    $conn->close();

    header('Location: vocabulary-practice.php?error=payment_gateway_error');
    exit;
}

$reqId          = $result['body']['responseData']['reqid'];
$paymentPageUrl = $result['body']['responseData']['paymentPageUrl'];

$upd1 = $conn->prepare("UPDATE payment_transactions SET reqid = ? WHERE order_id = ?");
$upd1->bind_param('ss', $reqId, $orderId);
$upd1->execute();
$upd1->close();

$upd2 = $conn->prepare("UPDATE payments SET reqid = ? WHERE order_id = ?");
$upd2->bind_param('ss', $reqId, $orderId);
$upd2->execute();
$upd2->close();

$conn->close();

// Redirect to DFCC payment portal
header('Location: ' . $paymentPageUrl);
exit;