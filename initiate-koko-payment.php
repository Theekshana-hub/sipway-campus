<?php
/**
 * initiate-koko-payment.php
 * Koko BNPL payment initiation for Vocabulary Packages
 * Matches real table structure of activated_vocabulary_packages
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once 'db.php';

// ========== CONFIG ==========
$KOKO_SANDBOX      = true; // true = sandbox | false = live
$KOKO_MERCHANT_ID  = 'YOUR_KOKO_MERCHANT_ID';   // ← Replace
$KOKO_API_KEY      = 'YOUR_KOKO_API_KEY';       // ← Replace

$BASE_URL = $KOKO_SANDBOX
    ? 'https://qaapi.paykoko.com'
    : 'https://prodapi.paykoko.com';

// Your real domain
$SITE_URL   = 'https://yourdomain.com';         // ← Replace
$RETURN_URL = $SITE_URL . '/koko-return.php';
$CANCEL_URL = $SITE_URL . '/vocabulary-practice.php';
$NOTIFY_URL = $SITE_URL . '/koko-notify.php';

// ========== HELPERS ==========
function jsonResponse($success, $message = '', $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== VALIDATION ==========
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

if (empty($_SESSION['student_id'])) {
    jsonResponse(false, 'Please login first');
}

$studentId = (int)$_SESSION['student_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid JSON data');
}

$packageId   = isset($input['id'])    ? (int)$input['id']      : 0;
$packageName = isset($input['name'])  ? trim($input['name'])   : 'Vocabulary Package';
$price       = isset($input['price']) ? (float)$input['price'] : 0;

if ($packageId <= 0 || $price <= 0) {
    jsonResponse(false, 'Invalid package data');
}

// Check package exists + active
$stmt = $conn->prepare("SELECT id, package_name, price FROM vocabulary_packages WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->bind_param("i", $packageId);
$stmt->execute();
$pkg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pkg) {
    jsonResponse(false, 'Package not found or inactive');
}

$price       = (float)$pkg['price'];
$packageName = $pkg['package_name'];

// Get student
$stmt = $conn->prepare("SELECT id, full_name, email, mobile FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    jsonResponse(false, 'Student not found');
}

// Already has active package?
$stmt = $conn->prepare("
    SELECT id, status FROM activated_vocabulary_packages 
    WHERE student_id = ? AND status IN ('active', 'pending') 
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing && $existing['status'] === 'active') {
    jsonResponse(false, 'You already have an active vocabulary package');
}

// ========== CREATE PENDING RECORD ==========
$transactionRef = 'VOCAB-KOKO-' . $studentId . '-' . $packageId . '-' . time() . '-' . rand(1000, 9999);

$stmt = $conn->prepare("
    INSERT INTO activated_vocabulary_packages 
    (student_id, package_id, status, payment_method, transaction_ref, created_at) 
    VALUES (?, ?, 'pending', 'koko', ?, NOW())
");
$stmt->bind_param("iis", $studentId, $packageId, $transactionRef);

if (!$stmt->execute()) {
    jsonResponse(false, 'Could not create pending order: ' . $stmt->error);
}

$pendingId = $stmt->insert_id;
$stmt->close();

// ========== PREPARE KOKO PAYLOAD ==========
$nameParts = explode(' ', trim($student['full_name']), 2);
$firstName = $nameParts[0] ?? 'Student';
$lastName  = $nameParts[1] ?? '';

$payload = [
    'merchant_id'   => $KOKO_MERCHANT_ID,
    'order_id'      => $transactionRef,
    'amount'        => number_format($price, 2, '.', ''),
    'currency'      => 'LKR',
    'description'   => $packageName,
    'customer'      => [
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'email'      => $student['email'] ?? '',
        'phone'      => $student['mobile'] ?? ''
    ],
    'return_url'    => $RETURN_URL . '?ref=' . urlencode($transactionRef),
    'cancel_url'    => $CANCEL_URL,
    'notify_url'    => $NOTIFY_URL,
    'metadata'      => [
        'student_id' => $studentId,
        'package_id' => $packageId,
        'pending_id' => $pendingId,
        'type'       => 'vocabulary'
    ]
];

// ========== CALL KOKO API ==========
$endpoint = $BASE_URL . '/v1/checkout/create';   // Confirm exact endpoint with Koko

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $KOKO_API_KEY,
        'X-Merchant-Id: ' . $KOKO_MERCHANT_ID
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 30,

    // ===== SSL Fix for WAMP / Local =====
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$responseBody = curl_exec($ch);
$httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError    = curl_error($ch);
curl_close($ch);

if ($curlError) {
    $conn->query("UPDATE activated_vocabulary_packages SET status = 'cancelled' WHERE id = " . (int)$pendingId);
    jsonResponse(false, 'Connection error: ' . $curlError);
}

$data = json_decode($responseBody, true);

// ========== SUCCESS ==========
if ($httpCode >= 200 && $httpCode < 300 && is_array($data)) {

    $redirectUrl = $data['redirect_url'] 
                ?? $data['payment_url'] 
                ?? $data['checkout_url'] 
                ?? $data['url'] 
                ?? null;

    if ($redirectUrl) {
        // Optionally save Koko transaction id into notes
        if (!empty($data['transaction_id']) || !empty($data['koko_order_id'])) {
            $kokoTxnId = $data['transaction_id'] ?? $data['koko_order_id'];
            $note = 'Koko Txn: ' . $kokoTxnId;
            $stmt = $conn->prepare("UPDATE activated_vocabulary_packages SET notes = ? WHERE id = ?");
            $stmt->bind_param("si", $note, $pendingId);
            $stmt->execute();
            $stmt->close();
        }

        jsonResponse(true, 'Redirecting to Koko', [
            'redirect_url' => $redirectUrl
        ]);
    }
}

// ========== FAILED ==========
$conn->query("UPDATE activated_vocabulary_packages SET status = 'cancelled' WHERE id = " . (int)$pendingId);

$errorMsg = $data['message'] 
         ?? $data['error'] 
         ?? $data['error_message'] 
         ?? 'Could not create Koko payment session. Please try again.';

jsonResponse(false, $errorMsg, [
    'http_code' => $httpCode,
    'raw'       => $KOKO_SANDBOX ? $data : null
]);