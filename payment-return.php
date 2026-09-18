<?php
/**
 * payment-return.php
 *
 * This is the URL registered as DFCC_RETURN_URL in payment-config.php.
 * DFCC redirects the student's browser here (HTTP GET) after they submit
 * their card on the hosted payment page, with a single query parameter:
 *   ?reqid=<the reqid we got back from PAYMENT_INIT>
 *
 * We then make the second REST call (PAYMENT_COMPLETE) using that reqid to
 * get the final, authoritative transaction result — the guide is explicit
 * that the payment is only actually complete once this second call is made
 * and confirmed (section 6.4, steps 5-7).
 */

define('SIPWAY_APP', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'payment-config.php';
require_once 'dfcc-api.php';

$reqId = isset($_GET['reqid']) ? trim($_GET['reqid']) : '';

if ($reqId === '') {
    header('Location: payment-failed.php?reason=missing_reqid');
    exit;
}

// Look up which of our orders this reqid belongs to
$stmt = $conn->prepare("SELECT order_id, student_id, package_id, package_type, amount, currency, status FROM payment_transactions WHERE reqid = ? LIMIT 1");
$stmt->bind_param('s', $reqId);
$stmt->execute();
$txn = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$txn) {
    error_log('[DFCC] payment-return.php: no payment_transactions row for reqid=' . $reqId);
    header('Location: payment-failed.php?reason=unknown_transaction');
    exit;
}

// Idempotency guard: if this reqid was already completed (e.g. user hit
// back/refresh on this page), don't double-activate.
if ($txn['status'] === 'completed') {
    header('Location: payment-success.php?order=' . urlencode($txn['order_id']));
    exit;
}

// ---- PAYMENT_COMPLETE call ----
$requestData = [
    'clientId' => (string) DFCC_CLIENT_ID,
    'reqid'    => $reqId,
];

$result = dfcc_api_call('PAYMENT_COMPLETE', $requestData);
$responseData = $result['body']['responseData'] ?? null;

$responseCode = $responseData['responseCode'] ?? null;
$responseText = $responseData['responseText'] ?? ($result['error'] ?? 'Unknown error');

if (!$result['ok'] || $responseCode !== '00') {
    error_log('[DFCC] PAYMENT_COMPLETE failed for order ' . $txn['order_id'] . ' (reqid ' . $reqId . '): ' . $responseText);
    $fail = $conn->prepare("UPDATE payment_transactions SET status = 'failed' WHERE reqid = ?");
    $fail->bind_param('s', $reqId);
    $fail->execute();
    $fail->close();
    $conn->close();
    header('Location: payment-failed.php?order=' . urlencode($txn['order_id']) . '&reason=' . urlencode($responseText));
    exit;
}

// ---- Success: mark transaction completed and activate the package ----
$txnReference = $responseData['txnReference'] ?? null;

$mark = $conn->prepare("UPDATE payment_transactions SET status = 'completed', txn_reference = ? WHERE reqid = ?");
$mark->bind_param('ss', $txnReference, $reqId);
$mark->execute();
$mark->close();

$studentId   = (int) $txn['student_id'];
$packageId   = (int) $txn['package_id'];
$packageType = $txn['package_type'];

if ($packageType === 'vocabulary') {
    // NOTE: verify these column names against your actual
    // activated_vocabulary_packages schema before relying on this.
    $act = $conn->prepare("INSERT INTO activated_vocabulary_packages (student_id, package_id, status, activated_at) VALUES (?, ?, 'active', NOW())");
    $act->bind_param('ii', $studentId, $packageId);
    $act->execute();
    $act->close();

} elseif ($packageType === 'ai_video') {
    // A 'pending' row was already inserted into activated_ai_video_packages
    // at initiate-payment.php time (with this order_id), the same way the
    // admin-approval flow works in admin_activated_ai_video_packages.php.
    // So here we UPDATE that existing row instead of inserting a new one.

    $durStmt = $conn->prepare("SELECT duration_days FROM ai_video_packages WHERE id = ? LIMIT 1");
    $durStmt->bind_param('i', $packageId);
    $durStmt->execute();
    $durRow = $durStmt->get_result()->fetch_assoc();
    $durStmt->close();

    $days   = $durRow ? (int) $durRow['duration_days'] : 0;
    $expiry = date('Y-m-d', strtotime("+{$days} days"));

    $act = $conn->prepare("
        UPDATE activated_ai_video_packages
        SET status = 'active',
            payment_status = 'paid',
            activated_at = NOW(),
            expiry_date = ?
        WHERE order_id = ?
    ");
    $act->bind_param('ss', $expiry, $txn['order_id']);
    $act->execute();
    $act->close();

} else {
    // Fetch the full package row so we save the REAL package_name,
    // package_type (individual/group) and price instead of relying on
    // column defaults (which was silently forcing package_type to
    // 'individual' for every online payment before this fix).
    $pkgStmt = $conn->prepare("SELECT package_name, package_type, price, total_sessions FROM packages WHERE id = ? LIMIT 1");
    $pkgStmt->bind_param('i', $packageId);
    $pkgStmt->execute();
    $pkgRow = $pkgStmt->get_result()->fetch_assoc();
    $pkgStmt->close();

    $pkgName       = $pkgRow ? $pkgRow['package_name'] : '';
    $pkgType       = $pkgRow ? $pkgRow['package_type'] : 'individual';
    $pkgPrice      = $pkgRow ? (float) $pkgRow['price'] : (float) $txn['amount'];
    $totalSessions = $pkgRow ? (int) $pkgRow['total_sessions'] : 0;

    // Fetch student_name too, since activated_packages requires it (NOT NULL)
    $stuStmt = $conn->prepare("SELECT full_name FROM students WHERE id = ? LIMIT 1");
    $stuStmt->bind_param('i', $studentId);
    $stuStmt->execute();
    $stuRow = $stuStmt->get_result()->fetch_assoc();
    $stuStmt->close();
    $studentName = $stuRow ? $stuRow['full_name'] : '';

    $act = $conn->prepare("
        INSERT INTO activated_packages
            (student_id, student_name, package_id, package_name, package_type, price, status, total_sessions, sessions_remaining, activated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, 'active', ?, ?, NOW())
    ");
    $act->bind_param(
        'isissdii',
        $studentId,
        $studentName,
        $packageId,
        $pkgName,
        $pkgType,
        $pkgPrice,
        $totalSessions,
        $totalSessions
    );
    $act->execute();
    $act->close();
}

$conn->close();
header('Location: payment-success.php?order=' . urlencode($txn['order_id']));
exit;