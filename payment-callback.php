<?php
session_start();
require_once 'db.php';
$config = require 'payment-config.php';

$invoiceNo = $_GET['invoice'] ?? '';
$reqid     = $_GET['reqid'] ?? '';

if (empty($invoiceNo)) {
    header('Location: packages.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM payments WHERE invoice_no = ? LIMIT 1");
$stmt->bind_param('s', $invoiceNo);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    header('Location: payment-failed.php?error=not_found');
    exit;
}

if ($payment['status'] === 'SUCCESS') {
    header('Location: payment-success.php?invoice=' . urlencode($invoiceNo));
    exit;
}

if (empty($reqid)) {
    $reqid = $payment['reqid'];
}

if (empty($reqid)) {
    header('Location: payment-failed.php?invoice=' . urlencode($invoiceNo));
    exit;
}

$completePayload = [
    'clientId' => (int)$config['client_id'],
    'reqid'    => $reqid,
];

$json = json_encode($completePayload, JSON_UNESCAPED_SLASHES);
$hmac = hash_hmac('sha256', $json, $config['hmac_secret']);

$ch = curl_init($config['service_endpoint']);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $json,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: ' . $config['auth_token'],
        'HMAC: ' . $hmac,
    ],
    CURLOPT_TIMEOUT        => 45,
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

$responseCode = $result['responseData']['responseCode'] 
             ?? $result['responseCode'] 
             ?? null;

$txnId = $result['responseData']['txnReference'] 
      ?? $result['responseData']['transactionId'] 
      ?? $result['txnReference'] 
      ?? null;

$responseText = $result['responseData']['responseText'] 
             ?? $result['responseText'] 
             ?? $response;

if ($responseCode === '00') {
    // SUCCESS
    $upd = $conn->prepare("
        UPDATE payments 
        SET status = 'SUCCESS',
            transaction_id = ?,
            response_code = ?,
            response_text = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $upd->bind_param('sssi', $txnId, $responseCode, $responseText, $payment['id']);
    $upd->execute();

    // Activate package
    activatePackage($conn, (int)$payment['student_id'], (int)$payment['package_id'], (int)$payment['id']);

    header('Location: payment-success.php?invoice=' . urlencode($invoiceNo));
    exit;
}

// FAILED
$upd = $conn->prepare("
    UPDATE payments 
    SET status = 'FAILED',
        response_code = ?,
        response_text = ?,
        updated_at = NOW()
    WHERE id = ?
");
$upd->bind_param('ssi', $responseCode, $responseText, $payment['id']);
$upd->execute();

header('Location: payment-failed.php?invoice=' . urlencode($invoiceNo));
exit;


function activatePackage($conn, int $studentId, int $packageId, int $paymentId)
{
    // Already active?
    $check = $conn->prepare("
        SELECT id FROM activated_packages 
        WHERE student_id = ? AND status = 'active' LIMIT 1
    ");
    $check->bind_param('i', $studentId);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        return;
    }
    $check->close();

    $pkg = $conn->prepare("SELECT total_sessions FROM packages WHERE id = ?");
    $pkg->bind_param('i', $packageId);
    $pkg->execute();
    $row = $pkg->get_result()->fetch_assoc();
    $sessions = (int)($row['total_sessions'] ?? 0);
    $pkg->close();

    $stmt = $conn->prepare("
        INSERT INTO activated_packages 
            (student_id, package_id, payment_id, status, total_sessions, remaining_sessions, activated_at)
        VALUES (?, ?, ?, 'active', ?, ?, NOW())
    ");
    $stmt->bind_param('iiiii', $studentId, $packageId, $paymentId, $sessions, $sessions);
    $stmt->execute();
    $stmt->close();
}