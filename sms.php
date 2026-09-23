<?php
require_once __DIR__ . '/sms-config.php';
require_once __DIR__ . '/ESMSlib.php'; // ★ official Mobitel functions: createSession(), sendMessages(), closeSession()

/**
 * Send an SMS via the Mobitel mSMS Enterprise (ESMS) API.
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
        $session = createSession(SMS_USER_ID, SMS_USERNAME, SMS_PASSWORD, SMS_CUSTOMER);

        if (empty($session)) {
            return ['success' => false, 'message' => 'Session eka hadaganna baha (username/password check karanna)'];
        }

        // messageType: 0 = normal message, 1 = promotional
        $result = sendMessages($session, SMS_MASK, $message, [$toNumber], 0);

        closeSession($session);

        return ['success' => true, 'message' => is_scalar($result) ? (string)$result : json_encode($result)];

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

/**
 * Build and send the booking confirmation SMS to the STUDENT who made the booking.
 *
 * @param string $studentPhone
 * @param string $studentName
 * @param string $lecturerName
 * @param string $sessionDate
 * @param string $sessionTime
 * @param string $status  'Accepted' or 'Pending'
 * @return array ['success' => bool, 'message' => string]
 */
function sendBookingConfirmationSmsToStudent($studentPhone, $studentName, $lecturerName, $sessionDate, $sessionTime, $status) {
    if (empty($studentPhone)) {
        return ['success' => false, 'message' => 'Student phone number empty'];
    }

    $dateFormatted = date('Y-m-d', strtotime($sessionDate));
    $timeFormatted = date('h:i A', strtotime($sessionTime));
    $lecturerName  = $lecturerName ?: 'your teacher';

    if ($status === 'Accepted') {
        $text = "Sipway Campus: Oyage session eka {$lecturerName} samaga {$dateFormatted} {$timeFormatted} confirm wela thiyenawa. Dashboard eken join karanna.";
    } else {
        $text = "Sipway Campus: Oyage booking request eka ({$lecturerName} - {$dateFormatted} {$timeFormatted}) yawuna. Teacher approve karaganna tikak inna.";
    }

    return sendSmsNotification($studentPhone, $text);
}