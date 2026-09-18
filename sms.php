<?php
require_once __DIR__ . '/sms-config.php';

/**
 * Get a fresh SOAP client for Mobitel's Enterprise SMS Web Service.
 */
function getMobitelSmsClient() {
    ini_set("soap.wsdl_cache_enabled", "0");
    return new SoapClient(SMS_WSDL_URL, [
        'connection_timeout' => 10,
        'exceptions'         => true,
    ]);
}

/**
 * Open a Mobitel SMS session. Returns the session token (string).
 */
function mobitelCreateSession($client) {
    $user = new stdClass();
    $user->id       = SMS_USER_ID;
    $user->username = SMS_USERNAME;
    $user->password = SMS_PASSWORD;
    $user->customer = SMS_CUSTOMER;

    $req = new stdClass();
    $req->user = $user;

    $res = $client->createSession($req);
    return $res->return;
}

/**
 * Close a Mobitel SMS session (always call this after sending).
 */
function mobitelCloseSession($client, $session) {
    $req = new stdClass();
    $req->session = $session;
    $client->closeSession($req);
}

/**
 * Send an SMS via the Mobitel Enterprise SMS (SOAP) API.
 *
 * @param string $toNumber  Recipient mobile number (0771234567 wage widihakata)
 * @param string $message   SMS text content
 * @return array ['success' => bool, 'message' => string]
 */
function sendSmsNotification($toNumber, $message) {
    if (!SMS_ENABLED) {
        return ['success' => false, 'message' => 'SMS sending disabled (SMS_ENABLED = false)'];
    }

    if (empty($toNumber)) {
        return ['success' => false, 'message' => 'Empty phone number'];
    }

    // Number eka 94XXXXXXXXX international format ekata normalize karanawa
    $toNumber = preg_replace('/\D/', '', $toNumber); // spaces/dashes/+ ain karanawa
    if (substr($toNumber, 0, 1) === '0') {
        $toNumber = '94' . substr($toNumber, 1);
    } elseif (substr($toNumber, 0, 2) !== '94') {
        $toNumber = '94' . $toNumber;
    }

    try {
        $client  = getMobitelSmsClient();
        $session = mobitelCreateSession($client);

        if (empty($session)) {
            return ['success' => false, 'message' => 'Mobitel session creation failed (check username/password)'];
        }

        $smsMessage = new stdClass();
        $smsMessage->message     = $message;
        $smsMessage->messageId   = "";
        $smsMessage->recipients  = [$toNumber];
        $smsMessage->retries     = "";
        $smsMessage->sender      = SMS_MASK;
        $smsMessage->messageType = 0; // 0 = normal text SMS
        $smsMessage->sequenceNum = "";
        $smsMessage->status      = "";
        $smsMessage->time        = "";
        $smsMessage->type        = "";
        $smsMessage->user        = "";

        $req = new stdClass();
        $req->session    = $session;
        $req->smsMessage = $smsMessage;

        $res = $client->sendMessages($req);

        mobitelCloseSession($client, $session);

        return ['success' => true, 'message' => is_scalar($res->return) ? (string)$res->return : 'sent'];

    } catch (\SoapFault $e) {
        error_log('Mobitel SMS SOAP error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'SOAP error: ' . $e->getMessage()];
    } catch (\Throwable $e) {
        error_log('Mobitel SMS error: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Build and send the "new booking" SMS to a lecturer.
 *
 * @param string $lecturerPhone
 * @param string $lecturerName
 * @param string $studentName
 * @param string $sessionDate
 * @param string $sessionTime
 * @param string $status  'Accepted' or 'Pending'
 * @return array ['success' => bool, 'message' => string]
 */
function sendBookingNotificationSms($lecturerPhone, $lecturerName, $studentName, $sessionDate, $sessionTime, $status) {
    if (empty($lecturerPhone)) {
        return ['success' => false, 'message' => 'Lecturer phone number empty'];
    }

    $dateFormatted = date('Y-m-d', strtotime($sessionDate));
    $timeFormatted = date('h:i A', strtotime($sessionTime));

    if ($status === 'Accepted') {
        $text = "Sipway Campus: {$studentName} - {$dateFormatted} {$timeFormatted} session eka book karala thiyenawa (Confirmed). Dashboard eken check karanna.";
    } else {
        $text = "Sipway Campus: {$studentName} - {$dateFormatted} {$timeFormatted} session ekakata request ekak dala thiyenawa. Approve karanna dashboard eken.";
    }

    return sendSmsNotification($lecturerPhone, $text);
}