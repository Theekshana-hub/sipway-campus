<?php


require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('SMTP_HOST'))      define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_USERNAME'))  define('SMTP_USERNAME', 'sadrunipun48@gmail.com');
if (!defined('SMTP_PASSWORD'))  define('SMTP_PASSWORD', 'glghydouhztuobis');
if (!defined('SMTP_FROM_EMAIL'))define('SMTP_FROM_EMAIL', 'sadrunipun@gmail.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Sipway Campus');
if (!defined('SMTP_PORT'))      define('SMTP_PORT', 587);


if (!defined('SMTP_DEBUG_MODE')) define('SMTP_DEBUG_MODE', true);

/**

 * @return array{success: bool, message: string}
 */
function sendBookingNotificationEmail(
    string $lecturerEmail,
    string $lecturerName,
    string $studentName,
    string $sessionDate,
    string $sessionTime,
    ?string $subject = null,
    string $bookingStatus = 'Pending'
): array {
    if (empty($lecturerEmail) || !filter_var($lecturerEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid or missing lecturer email'];
    }

    $mail = new PHPMailer(true);
    $debugLog = [];

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        if (SMTP_DEBUG_MODE) {
            $mail->SMTPDebug   = 2;
            $mail->Debugoutput = function ($str, $level) use (&$debugLog) {
                $debugLog[] = trim($str);
            };
        }

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($lecturerEmail, $lecturerName);

        $dateTs  = strtotime($sessionDate);
        $timeTs  = strtotime($sessionTime);
        $dateFmt = $dateTs ? date('l, d M Y', $dateTs) : $sessionDate;
        $timeFmt = $timeTs ? date('h:i A', $timeTs) : $sessionTime;

        $isAccepted  = (strtolower($bookingStatus) === 'accepted');
        $statusColor = $isAccepted ? '#10b981' : '#f59e0b';
        $statusLabel = $isAccepted ? 'Confirmed' : 'Pending Your Approval';

        $mail->isHTML(true);
        $mail->Subject = 'New Session Booked - ' . $dateFmt . ' at ' . $timeFmt;

        $subjectRow = $subject
            ? '<tr>
                 <td style="padding:8px 0;color:#6b7280;font-size:13px;font-weight:700;">Subject</td>
                 <td style="padding:8px 0;color:#1e1b4b;font-size:13.5px;font-weight:700;">' . htmlspecialchars($subject) . '</td>
               </tr>'
            : '';

        $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;background:#f4f0ff;padding:24px;">
              <div style="background:#fff;border-radius:14px;padding:28px;border:1px solid #e9e5f5;">
                <h2 style="color:#1e1b4b;margin:0 0 4px 0;">New Session Booked</h2>
                <p style="color:#6b7280;font-size:13.5px;margin:0 0 20px 0;">Sipway Campus</p>
                <p style="font-size:14.5px;color:#1e1b4b;">Hi <b>' . htmlspecialchars($lecturerName) . '</b>,</p>
                <p style="font-size:14.5px;color:#1e1b4b;">A student has booked a session with you. Details below:</p>
                <table style="width:100%;border-collapse:collapse;margin:18px 0;">
                  <tr>
                    <td style="padding:8px 0;color:#6b7280;font-size:13px;font-weight:700;">Student</td>
                    <td style="padding:8px 0;color:#1e1b4b;font-size:13.5px;font-weight:700;">' . htmlspecialchars($studentName) . '</td>
                  </tr>
                  <tr>
                    <td style="padding:8px 0;color:#6b7280;font-size:13px;font-weight:700;">Date</td>
                    <td style="padding:8px 0;color:#1e1b4b;font-size:13.5px;font-weight:700;">' . htmlspecialchars($dateFmt) . '</td>
                  </tr>
                  <tr>
                    <td style="padding:8px 0;color:#6b7280;font-size:13px;font-weight:700;">Time</td>
                    <td style="padding:8px 0;color:#1e1b4b;font-size:13.5px;font-weight:700;">' . htmlspecialchars($timeFmt) . '</td>
                  </tr>
                  ' . $subjectRow . '
                  <tr>
                    <td style="padding:8px 0;color:#6b7280;font-size:13px;font-weight:700;">Status</td>
                    <td style="padding:8px 0;">
                      <span style="background:' . $statusColor . '22;color:' . $statusColor . ';padding:4px 12px;border-radius:999px;font-size:12px;font-weight:800;">' . $statusLabel . '</span>
                    </td>
                  </tr>
                </table>
                <p style="font-size:12.5px;color:#9ca3af;margin-top:22px;">Login to your Sipway Campus lecturer dashboard to view full details.</p>
              </div>
            </div>
        ';

        $mail->AltBody = "New Session Booked\n\n"
            . "Student: {$studentName}\n"
            . "Date: {$dateFmt}\n"
            . "Time: {$timeFmt}\n"
            . ($subject ? "Subject: {$subject}\n" : '')
            . "Status: {$statusLabel}";

        $mail->send();
        return ['success' => true, 'message' => 'Email sent'];
    } catch (Exception $e) {
        $msg = 'Mail error: ' . $mail->ErrorInfo;
        if (SMTP_DEBUG_MODE && !empty($debugLog)) {
            $msg .= "\n\n---- SMTP DEBUG LOG ----\n" . implode("\n", $debugLog);
        }
        return ['success' => false, 'message' => $msg];
    }
}