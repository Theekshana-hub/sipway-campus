<?php
/**
 * send-session-reminders.php - Sipway Campus
 * Sends "session starts in ≤ 3 hours" email to student.
 * Run every 5 minutes via Windows Task Scheduler.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Colombo');

require_once __DIR__ . '/db.php';

// ---------- PHPMailer loader (same as your file) ----------
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
} else {
    $nested = glob(__DIR__ . '/PHPMailer/PHPMailer-*/src/PHPMailer.php');
    if (!empty($nested)) {
        $base = dirname($nested[0]);
        require_once $base . '/PHPMailer.php';
        require_once $base . '/SMTP.php';
        require_once $base . '/Exception.php';
    } else {
        fwrite(STDERR, "PHPMailer not found\n");
        exit(1);
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($conn) || $conn->connect_error) {
    fwrite(STDERR, "DB connection failed\n");
    exit(1);
}

// ---------- FIND bookings in the 0–3 hour window ----------
$sql = "
    SELECT
        b.id            AS booking_id,
        b.student_id,
        b.student_name,
        b.lecturer_id,
        b.session_date,
        b.session_time,
        b.meeting_link,
        b.booking_type,
        s.email         AS student_email,
        l.full_name     AS lecturer_name
    FROM bookings b
    INNER JOIN students s  ON s.id = b.student_id
    LEFT  JOIN lecturers l ON l.id = b.lecturer_id
    WHERE b.status = 'Accepted'
      AND b.reminder_sent = 0
      AND TIMESTAMP(b.session_date, b.session_time) > NOW()
      AND TIMESTAMP(b.session_date, b.session_time) <= DATE_ADD(NOW(), INTERVAL 3 HOUR)
";

$result = $conn->query($sql);

if ($result === false) {
    fwrite(STDERR, "Query failed: " . $conn->error . "\n");
    exit(1);
}

$sentCount = 0;
$failCount = 0;

while ($row = $result->fetch_assoc()) {
    $studentEmail = trim($row['student_email'] ?? '');

    if ($studentEmail === '' || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Reminder skipped (bad email) — booking_id={$row['booking_id']}");
        markReminderSent($conn, $row['booking_id']);
        continue;
    }

    $mailError = '';
    $ok = sendReminderEmail(
        $row['student_name'] ?: 'Student',
        $studentEmail,
        $row['lecturer_name'] ?: 'your teacher',
        $row['session_date'],
        $row['session_time'],
        $row['meeting_link'] ?? '',
        $row['booking_type'] ?? 'individual',
        $mailError
    );

    if ($ok) {
        markReminderSent($conn, $row['booking_id']);
        $sentCount++;
        echo "Sent → {$studentEmail} (booking #{$row['booking_id']})\n";
    } else {
        error_log("Reminder FAILED booking_id={$row['booking_id']} — {$mailError}");
        $failCount++;
    }
}

echo "Done. Sent: {$sentCount}, Failed: {$failCount}\n";
$conn->close();
exit(0);


// ---------- HELPERS ----------
function markReminderSent(mysqli $conn, int $bookingId): void
{
    $upd = $conn->prepare("UPDATE bookings SET reminder_sent = 1 WHERE id = ?");
    $upd->bind_param("i", $bookingId);
    $upd->execute();
    $upd->close();
}

function sendReminderEmail(
    string $studentName,
    string $studentEmail,
    string $lecturerName,
    string $sessionDate,
    string $sessionTime,
    string $meetingLink,
    string $bookingType,
    string &$mailError = ''
): bool {
    $mail = new PHPMailer(true);

    // !!! Move these to a secure config file later !!!
    $smtpUser = 'sadrunipun48@gmail.com';
    $smtpPass = 'glgh ydou hztu obis';   // Gmail App Password

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtpUser, 'Sipway Campus');
        $mail->addAddress($studentEmail, $studentName);

        $mail->isHTML(true);
        $mail->Subject = 'Reminder: Your Sipway session starts in 3 hours';

        $safeName     = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        $safeLecturer = htmlspecialchars($lecturerName, ENT_QUOTES, 'UTF-8');
        $niceDate     = date('l, F j, Y', strtotime($sessionDate));
        $niceTime     = date('g:i A', strtotime($sessionTime));
        $typeLabel    = ($bookingType === 'group') ? 'Group' : 'Individual';

        $linkHtml = '';
        if (!empty($meetingLink)) {
            $safeLink = htmlspecialchars($meetingLink, ENT_QUOTES, 'UTF-8');
            $linkHtml = "
                <tr>
                    <td style='padding:6px 10px;font-weight:bold;'>Meeting Link:</td>
                    <td style='padding:6px 10px;'><a href='{$safeLink}'>{$safeLink}</a></td>
                </tr>";
        }

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;font-size:15px;color:#1b2430;max-width:560px;'>
                <h2 style='color:#0f2a4a;'>⏰ Your session starts in 3 hours!</h2>
                <p>Hi {$safeName},</p>
                <p>This is a friendly reminder that your English conversation session is coming up.</p>
                <table style='border-collapse:collapse;margin:16px 0;background:#f8fafc;border-radius:8px;'>
                    <tr>
                        <td style='padding:8px 12px;font-weight:bold;'>Date:</td>
                        <td style='padding:8px 12px;'>{$niceDate}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px 12px;font-weight:bold;'>Time:</td>
                        <td style='padding:8px 12px;'>{$niceTime}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px 12px;font-weight:bold;'>Type:</td>
                        <td style='padding:8px 12px;'>{$typeLabel}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px 12px;font-weight:bold;'>Lecturer:</td>
                        <td style='padding:8px 12px;'>{$safeLecturer}</td>
                    </tr>
                    {$linkHtml}
                </table>
                <p>Please log in to your dashboard a few minutes early.</p>
                <p style='margin-top:28px;'>See you soon!<br><strong>Sipway Campus Team</strong></p>
            </div>
        ";

        $mail->AltBody = "Hi {$studentName},\n\n"
            . "Reminder: your session with {$lecturerName} starts in 3 hours.\n"
            . "Date: {$niceDate}\nTime: {$niceTime}\nType: {$typeLabel}\n"
            . ($meetingLink ? "Meeting Link: {$meetingLink}\n" : "")
            . "\nSipway Campus Team";

        $mail->send();
        return true;

    } catch (Exception $e) {
        $mailError = $mail->ErrorInfo ?: $e->getMessage();
        return false;
    }
}