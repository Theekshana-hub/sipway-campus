<?php


header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); 


register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $error['message'] . ' (line ' . $error['line'] . ' in ' . basename($error['file']) . ')'
        ]);
    }
});

require_once __DIR__ . '/db.php';

$phpmailerPath = __DIR__ . '/PHPMailer/src/';
$requiredFiles = ['PHPMailer.php', 'SMTP.php', 'Exception.php'];

foreach ($requiredFiles as $file) {
    if (!file_exists($phpmailerPath . $file)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "PHPMailer file eka hamu unne na: PHPMailer/src/{$file}. Folder structure eka check karanna."
        ]);
        exit;
    }
    require_once $phpmailerPath . $file;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'theekshananipun237@gmail.com'); 
define('SMTP_PASSWORD', 'webu cpch fpja yylg');   
define('SMTP_PORT', 587);
define('MAIL_FROM_EMAIL', 'theekshananipun237@gmail.com');
define('MAIL_FROM_NAME', 'sipway campus');
define('LOGIN_URL', 'http://localhost/cp/lecturer-login.php');

function respond($arr) {
    echo json_encode($arr);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['status'])) {
    http_response_code(400);
    respond(['success' => false, 'message' => 'id saha status one karanawa.']);
}

$id     = (int) $input['id'];
$status = trim($input['status']);

$allowedStatuses = ['approved', 'rejected', 'pending'];
if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    respond(['success' => false, 'message' => 'Wenas status value ekak.']);
}

if ($id <= 0) {
    http_response_code(400);
    respond(['success' => false, 'message' => 'Wenas teacher id ekak.']);
}

$stmt = $conn->prepare("SELECT id, full_name, email, username, status FROM lecturers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
$stmt->close();

if (!$teacher) {
    http_response_code(404);
    respond(['success' => false, 'message' => 'Teacher ekak hamu unne na.']);
}

$previousStatus = $teacher['status'];

$updateStmt = $conn->prepare("UPDATE lecturers SET status = ? WHERE id = ?");
$updateStmt->bind_param("si", $status, $id);

if (!$updateStmt->execute()) {
    $updateStmt->close();
    http_response_code(500);
    respond(['success' => false, 'message' => 'Status update karanna baa una: ' . $conn->error]);
}
$updateStmt->close();


$mailSent = false;
$mailError = null;

if ($status === 'approved' && $previousStatus !== 'approved') {

    if (empty($teacher['email'])) {
        $mailError = 'Teacher ge email address ekak DB eke na — mail yawanna baa.';
    } else {

       
        $smtpDebugLog = [];

        $mail = new PHPMailer(true);
        try {
           
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;

           
            $mail->Username   = trim(SMTP_USERNAME);
            $mail->Password   = str_replace(' ', '', trim(SMTP_PASSWORD));

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

           
            $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function ($str, $level) use (&$smtpDebugLog) {
                $smtpDebugLog[] = trim($str);
            };

            
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($teacher['email'], $teacher['full_name']);

            $mail->isHTML(true);
            $mail->Subject = 'Welcome Aboard — Your Sipway Campus Lecturer Account is Approved ✅';

            $loginUrl  = LOGIN_URL;
            $fullName  = htmlspecialchars($teacher['full_name']);
            $username  = htmlspecialchars($teacher['username']);

            $mail->Body = "
                <div style='font-family:Arial,Helvetica,sans-serif; max-width:520px; margin:0 auto; background:#ffffff;'>

                    <!-- Header banner -->
                    <div style='background:linear-gradient(135deg, #0f2a4a 0%, #16385f 100%); padding:28px 30px; border-radius:14px 14px 0 0; text-align:center;'>
                        <div style='font-size:22px; font-weight:800; color:#ffffff; letter-spacing:0.3px;'>Sipway Campus</div>
                        <div style='font-size:12px; color:rgba(255,255,255,0.65); letter-spacing:1px; text-transform:uppercase; margin-top:2px;'>Lecturer Portal</div>
                    </div>

                    <!-- Body card -->
                    <div style='border:1px solid #e6e2da; border-top:none; border-radius:0 0 14px 14px; padding:32px 30px;'>

                        <div style='display:inline-block; background:#e8f8ee; color:#1f9d55; font-size:12px; font-weight:700; padding:6px 14px; border-radius:20px; margin-bottom:18px;'>
                            ✓ Account Approved
                        </div>

                        <h2 style='color:#0f2a4a; font-size:21px; margin:0 0 14px;'>Congratulations, {$fullName}!</h2>

                        <p style='color:#4a5568; font-size:14.5px; line-height:1.7; margin:0 0 14px;'>
                            Great news — your lecturer account has been <b style='color:#1f9d55;'>reviewed and approved</b> by the Sipway Campus admin team. You're all set to get started.
                        </p>

                        <p style='color:#4a5568; font-size:14.5px; line-height:1.7; margin:0 0 22px;'>
                            You can now log in, set up your availability slots, and begin conducting English conversation sessions with students.
                        </p>

                        <!-- Credentials box -->
                        <div style='background:#f6f5f3; border:1px solid #e6e2da; border-radius:10px; padding:16px 18px; margin-bottom:26px;'>
                            <div style='font-size:11px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; color:#8a93a3; margin-bottom:4px;'>Your Username</div>
                            <div style='font-size:15px; font-weight:700; color:#0f2a4a;'>{$username}</div>
                        </div>

                        <!-- CTA button -->
                        <div style='text-align:center; margin-bottom:8px;'>
                            <a href='{$loginUrl}' style='display:inline-block; background:linear-gradient(135deg, #e8825f 0%, #d66c47 100%); color:#ffffff; padding:14px 34px; border-radius:9px; text-decoration:none; font-weight:700; font-size:14.5px;'>
                                Log In to Sipway Campus
                            </a>
                        </div>

                        <p style='color:#8a93a3; font-size:11.5px; text-align:center; margin:28px 0 0; line-height:1.6;'>
                            This is an automated message — please do not reply directly to this email.<br>
                            If you have any questions, feel free to reach out to the Sipway Campus admin team.
                        </p>
                    </div>
                </div>
            ";
            $mail->AltBody = "Congratulations {$fullName}!\n\nYour lecturer account at Sipway Campus has been approved. You can now log in using your username ({$username}) and start setting up your availability slots for student sessions.\n\nLog in here: {$loginUrl}\n\nThis is an automated message, please do not reply.";

            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
           
            $debugFull = implode(' | ', $smtpDebugLog);
            $mailError = 'Mail yawanna baa una: ' . $mail->ErrorInfo
                . ($debugFull ? ' [SMTP LOG: ' . $debugFull . ']' : '');
        }
    }
}

// ---------- Response ----------
$responseMsg = $status === 'approved'
    ? ($mailSent ? 'Teacher approve unuwa saha email ekak yawuwa ✅' : 'Teacher approve unuwa, ehema wunath email eka yawanna baa una.')
    : 'Teacher status update unuwa.';

respond([
    'success'    => true,
    'message'    => $responseMsg,
    'mail_sent'  => $mailSent,
    'mail_error' => $mailError,
]);