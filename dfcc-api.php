<?php


function dfcc_amount_to_cents(float $amount): int
{
    return (int) round($amount * 100);
}

/**
 * 
 * @param string E
 * @param array  
 * @return array 
 */
function dfcc_api_call(string $msgType, array $requestData): array
{
    if (isset($requestData['extraData']) && is_array($requestData['extraData'])) {
        $requestData['extraData'] = (object) $requestData['extraData'];
    } elseif (!isset($requestData['extraData'])) {
        $requestData['extraData'] = new stdClass();
    }

    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    $msgId = strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));

    $payload = [
        'version'      => '1.5',
        'msgId'        => $msgId,
        'operation'    => $msgType,
        'requestDate'  => date('Y-m-d\TH:i:s.vP'),
        'validateOnly' => false,
        'requestData'  => $requestData,
    ];

    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

    $verifySsl = defined('DFCC_VERIFY_SSL') ? DFCC_VERIFY_SSL : true;

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'AUTHTOKEN: ' . DFCC_AUTH_TOKEN,
        ],
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
    ];

    if (!$verifySsl || (ini_get('curl.cainfo') && is_dir(ini_get('curl.cainfo')))) {
        $options[CURLOPT_CAINFO] = '';
    }

    $ch = curl_init(DFCC_GATEWAY_URL);
    curl_setopt_array($ch, $options);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['ok' => false, 'body' => null, 'raw' => '', 'error' => $curlErr];
    }

    $body = json_decode($raw, true);

    if ($httpCode !== 200 || !is_array($body)) {
        return ['ok' => false, 'body' => $body, 'raw' => $raw, 'error' => "HTTP $httpCode"];
    }

    return ['ok' => true, 'body' => $body, 'raw' => $raw, 'error' => null];
}