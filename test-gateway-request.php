<?php
/**
 * test-gateway-request.php
 *
 * DFCC / Paycorp PayCentreWeb4 gateway ekt POST request ekak
 * yawala test karana script eka.
 *
 * ⚠️ IMPORTANT: Mema script eke pahatha thiyena request format eka
 * (field names, HMAC calculate karana widiya) "typical" Paycorp-style
 * pattern ekak - eka DFCC ge official Technical Integration Guide
 * document eken 100% confirm karanna one. Doc eka nathnam
 * support@bancstac.com walata illanna.
 *
 * Run karanna: php test-gateway-request.php  (command line eken)
 * Browser eken open karanna epa - CLI/terminal eken run karanna.
 */

define('SIPWAY_APP', true);
require_once 'payment-config.php';

// ---- 1. Test payload eka (real integration ekedi meka dynamic wenawa) ----
$orderId = 'TEST-' . time();
$amount  = '100.00'; // LKR

$payload = [
    'client_id'  => DFCC_MERCHANT_ID,   // 10000158
    'order_id'   => $orderId,
    'amount'     => $amount,
    'currency'   => DFCC_CURRENCY,      // LKR
    'return_url' => DFCC_RETURN_URL,
    'cancel_url' => DFCC_CANCEL_URL,
    'notify_url' => DFCC_NOTIFY_URL,
];

// ---- 2. HMAC signature calculate karanawa ----
// ⚠️ TODO: Meka DFCC docs eken confirm karanna. Below eka COMMON pattern
// ekak witharai (fields ordered + concatenated, HMAC-SHA256 signed).
// Docs eke "signature generation" / "hash calculation" kiyana section eka balanna.
ksort($payload); // fields alphabetical order ekata sort karanawa (typical requirement ekak)
$signatureBase = implode('|', $payload);
$hmac = hash_hmac('sha256', $signatureBase, DFCC_API_SECRET);

$payload['hash'] = $hmac;

// ---- 3. Request eka JSON widihata prepare karanawa ----
$jsonPayload = json_encode($payload);

echo "----- REQUEST BEING SENT -----\n";
echo "URL: " . DFCC_GATEWAY_URL . "\n";
echo "Payload: " . $jsonPayload . "\n";
echo "Signature base: " . $signatureBase . "\n";
echo "HMAC: " . $hmac . "\n\n";

// ---- 4. cURL eken POST request eka yawanawa ----
$ch = curl_init(DFCC_GATEWAY_URL);

curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $jsonPayload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        // ⚠️ TODO: Authtoken eka evanna one widiya docs eken confirm karanna.
        // Paycorp gateways walata common headers dekak thiyenawa:
        'Authorization: Bearer ' . DFCC_AUTH_TOKEN,
        // Sometimes eka custom header ekak widihata thiyenawa, e.g:
        // 'Authtoken: ' . DFCC_AUTH_TOKEN,
    ],

    // ⚠️ TRY THIS: "Invalid username or password" error eka HTTP Basic Auth
    // ekakට wenna puluwan. Ehema unoth Bearer header eka wenuwata meka
    // uncomment karala try karanna (Client ID = username, Authtoken = password):
    // CURLOPT_HTTPAUTH  => CURLAUTH_BASIC,
    // CURLOPT_USERPWD   => DFCC_MERCHANT_ID . ':' . DFCC_AUTH_TOKEN,
]);

$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

echo "----- RESPONSE -----\n";
echo "HTTP Status Code: " . $httpCode . "\n";

if ($curlError) {
    echo "cURL Error: " . $curlError . "\n";
} else {
    echo "Raw Response: " . $response . "\n\n";

    // JSON widihata parse karanna try karanawa
    $decoded = json_decode($response, true);
    if ($decoded !== null) {
        echo "Parsed Response:\n";
        print_r($decoded);
    }
}

// ---- 5. Result summary ----
echo "\n----- SUMMARY -----\n";
if ($httpCode === 200) {
    echo "✅ Request successful (HTTP 200)\n";
} elseif ($httpCode === 401) {
    echo "❌ 401 Unauthorized - Authtoken/HMAC signature eka wrong wenna puluwan.\n";
    echo "   Check karanna:\n";
    echo "   1. Authtoken header eka correct format ekenda evanne\n";
    echo "   2. HMAC eke field order eka / concatenation format eka docs ekata match wenawada\n";
    echo "   3. DFCC_API_SECRET eka correctda (extra space/newline nathida)\n";
} else {
    echo "⚠️ Unexpected response - HTTP $httpCode\n";
}