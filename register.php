<?php


header('Content-Type: application/json');


ini_set('display_errors', '0');
error_reporting(E_ALL);


register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $err['message'] . ' in ' . basename($err['file']) . ' line ' . $err['line']
        ]);
    }
});


if (!file_exists(__DIR__ . '/db.php')) {
    echo json_encode(['success' => false, 'message' => 'db.php not found next to register.php']);
    exit;
}
require_once __DIR__ . '/db.php';

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
        echo json_encode(['success' => false, 'message' => 'PHPMailer not found. Run "composer require phpmailer/phpmailer" in the cp folder, or manually place PHPMailer at cp/PHPMailer/src/.']);
        exit;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Check db.php.']);
    exit;
}

$response = ['success' => false];

// ---------- Collect + sanitize input ----------
$fullName = trim($_POST['fullName'] ?? '');
$email    = trim($_POST['email'] ?? '');
$mobile   = trim($_POST['mobile'] ?? '');
$language = trim($_POST['language'] ?? 'en');
$gender   = trim($_POST['gender'] ?? 'Other');
$address  = trim($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

if ($fullName === '') {
    $errors['fullName'] = 'Please enter your name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if (!preg_match('/^0\d{9}$/', $mobile)) {
    $errors['mobile'] = 'Please enter a valid 10-digit mobile number.';
}
if (strlen($password) < 6) {
    $errors['password'] = 'Password must be at least 6 characters.';
}

if (!empty($errors)) {
    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}


$stmt = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $response['errors'] = ['email' => 'This email is already registered.'];
    echo json_encode($response);
    exit;
}
$stmt->close();


$photoPath = null;

if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tmpName = $_FILES['profilePhoto']['tmp_name'];
    $mime    = mime_content_type($tmpName);

    if (isset($allowed[$mime])) {
        $uploadDir = __DIR__ . '/uploads/profile_photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext      = $allowed[$mime];
        $fileName = 'student_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $photoPath = 'uploads/profile_photos/' . $fileName; 
        }
    } else {
        $response['errors'] = ['profilePhoto' => 'Only JPG, PNG or WEBP images are allowed.'];
        echo json_encode($response);
        exit;
    }
}


$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$status = 'active'; 

$stmt = $conn->prepare(
    "INSERT INTO students (full_name, email, mobile, language, gender, address, profile_photo, password, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);
$stmt->bind_param(
    "sssssssss",
    $fullName,
    $email,
    $mobile,
    $language,
    $gender,
    $address,
    $photoPath,
    $hashedPassword,
    $status
);

if (!$stmt->execute()) {
    $stmt->close();
    $response['message'] = 'Could not create account. Please try again.';
    echo json_encode($response);
    exit;
}
$stmt->close();

$mailError = '';
$mailSent = sendWelcomeEmail($fullName, $email, $password, $language, $mailError);


if (!$mailSent) {
    error_log("Sipway Campus: welcome email failed to send to {$email} — {$mailError}");
}

$response['success'] = true;
$response['message'] = 'Account created! You can now log in.';
if (!$mailSent) {
  
    $response['message'] .= ' (Note: welcome email failed — ' . $mailError . ')';
}
echo json_encode($response);
exit;



function sendWelcomeEmail(string $fullName, string $email, string $password, string $language, string &$mailError = ''): bool
{
    $mail = new PHPMailer(true);

    
    $smtpUser = 'sadrunipun48@gmail.com';   
    $smtpPass = 'glgh ydou hztu obis';      

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

      
        $mail->setFrom($smtpUser, 'Sipway Campus');
        $mail->addAddress($email, $fullName);

        $mail->isHTML(true);
        $mail->Subject = 'Thank you for registering at Sipway Campus!';

        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePass  = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;font-size:15px;color:#1b2430;'>
                <h2 style='color:#0f2a4a;'>Welcome to Sipway Campus, {$safeName}! 🎉</h2>
                <p>Thank you for registering. Your account has been created successfully and is ready to use.</p>
                <p><strong>Your login details:</strong></p>
                <table style='border-collapse:collapse;margin:12px 0;'>
                    <tr>
                        <td style='padding:6px 10px;font-weight:bold;'>Email:</td>
                        <td style='padding:6px 10px;'>{$safeEmail}</td>
                    </tr>
                    <tr>
                        <td style='padding:6px 10px;font-weight:bold;'>Password:</td>
                        <td style='padding:6px 10px;'>{$safePass}</td>
                    </tr>
                </table>
                <p>We recommend keeping this email private and changing your password after your first login.</p>
                <p style='margin-top:24px;'>Happy learning!<br><strong>Sipway Campus Team</strong></p>
            </div>
        ";
        $mail->AltBody = "Welcome to Sipway Campus, {$fullName}!\n\n"
            . "Your account has been created successfully.\n"
            . "Email: {$email}\n"
            . "Password: {$password}\n\n"
            . "Thank you for registering!\nSipway Campus Team";

        $mail->send();
        return true;

    } catch (Exception $e) {
        $mailError = $mail->ErrorInfo ?: $e->getMessage();
        error_log('PHPMailer error: ' . $mailError);
        return false;
    }
}