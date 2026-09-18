<?php
header('Content-Type: text/plain');

echo "=== PHP curl extension ===\n";
echo function_exists('curl_init') ? "curl_init: AVAILABLE\n" : "curl_init: MISSING (this is likely your problem)\n";
echo extension_loaded('curl') ? "curl extension loaded: YES\n" : "curl extension loaded: NO\n";
echo "PHP version: " . phpversion() . "\n\n";

if (!function_exists('curl_init')) {
    echo "STOP: curl is not available on this host. The PHP payment code cannot run here.\n";
    exit;
}

echo "=== Testing outbound connection to DFCC gateway ===\n";
$ch = curl_init('https://paycorp-dfcc.prod.aws.paycorp.lk/rest/service/proxy');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => '{}',
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "curl errno: $errno\n";
echo "curl error: " . ($error ?: '(none)') . "\n";
echo "HTTP code: $httpCode\n";
echo "Response (first 500 chars):\n" . substr((string) $response, 0, 500) . "\n";