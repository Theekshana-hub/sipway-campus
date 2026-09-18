<?php


if (!defined('SIPWAY_APP')) {
    die('Direct access not allowed.');
}


define('DFCC_ENV', 'production');


if (DFCC_ENV === 'production') {

    define('DFCC_CLIENT_ID',   '10000158');
    define('DFCC_HMAC_SECRET', 'Pa1SOGifRpKk6E8R'); 
    define('DFCC_AUTH_TOKEN',  '6a741fd5-43e3-41c7-8e49-0cc3ee7ca56a');
    define('DFCC_GATEWAY_URL', 'https://paycorp-dfcc.prod.aws.paycorp.lk/rest/service/proxy');

} else {
    define('DFCC_CLIENT_ID',   'YOUR_SANDBOX_CLIENT_ID');
    define('DFCC_HMAC_SECRET', 'YOUR_SANDBOX_HMAC_SECRET');
    define('DFCC_AUTH_TOKEN',  'YOUR_SANDBOX_AUTHTOKEN');
    define('DFCC_GATEWAY_URL', 'https://YOUR_SANDBOX_GATEWAY_URL');
}


define('DFCC_SPP_HASH_ID', 'vc8jSi0VXQ9KPcZB0832ZXqn04');
define('DFCC_SPP_URL',     'https://support.paycorp.lk/dfcc.spp/pay.php');


$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

if ($isLocal) {
    define('DFCC_RETURN_URL', 'http://' . $host . '/cp/payment-return.php');
    define('DFCC_CANCEL_URL', 'http://' . $host . '/cp/payment-failed.php');
    define('DFCC_VERIFY_SSL', false);
} else {
    define('DFCC_RETURN_URL', 'https://' . $host . '/payment-return.php');
    define('DFCC_CANCEL_URL', 'https://' . $host . '/payment-failed.php');
    define('DFCC_VERIFY_SSL', true);
}

define('DFCC_NOTIFY_URL', DFCC_RETURN_URL);


define('DFCC_CURRENCY', 'LKR');
define('DFCC_TOKENIZE', false);
define('DFCC_3DS', true);