<?php

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$response = ['success' => false];
$errors = [];

$full_name      = trim($_POST['full_name'] ?? '');
$gender         = trim($_POST['gender'] ?? '');
$subject        = trim($_POST['subject'] ?? '');          
$language       = trim($_POST['language'] ?? 'en');
$qualifications = trim($_POST['qualifications'] ?? '');
$email          = trim($_POST['email'] ?? '');
$nic            = trim($_POST['nic'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$username       = trim($_POST['username'] ?? '');
$password       = trim($_POST['password'] ?? '');

$allowedGenders   = ['male', 'female'];
$allowedLanguages = ['en', 'de', 'zh', 'ja', 'fr', 'hi', 'ru', 'ar', 'ta', 'si', 'it'];

// ---------- Validations ----------
if ($full_name === '') {
    $errors['full_name'] = 'Full name required';
}

if ($gender === '' || !in_array($gender, $allowedGenders, true)) {
    $errors['gender'] = 'Valid gender required';
}


if ($subject === '') {
    $errors['subject'] = 'At least one subject is required';
}

if (!in_array($language, $allowedLanguages, true)) {
    $language = 'en';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Valid email required';
}


if ($nic === '' || !preg_match('/^([0-9]{9}[vVxX]|[0-9]{12})$/', $nic)) {
    $errors['nic'] = 'Valid NIC required (e.g. 991234567V or 199912345678)';
}

// Phone validation
if ($phone === '' || !preg_match('/^[0-9+\s\-]{9,15}$/', $phone)) {
    $errors['phone'] = 'Valid phone number required';
}

if ($username === '') {
    $errors['username'] = 'Username required';
}

if (strlen($password) < 6) {
    $errors['password'] = 'Password must be at least 6 characters';
}

// Duplicate checks
if (empty($errors)) {
    $check = $conn->prepare("SELECT id FROM lecturers WHERE username = ? LIMIT 1");
    $check->bind_param('s', $username);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) $errors['username'] = 'Username already exists';
    $check->close();
}

if (empty($errors)) {
    $checkEmail = $conn->prepare("SELECT id FROM lecturers WHERE email = ? LIMIT 1");
    $checkEmail->bind_param('s', $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) $errors['email'] = 'Email already exists';
    $checkEmail->close();
}

if (empty($errors)) {
    $checkNic = $conn->prepare("SELECT id FROM lecturers WHERE nic = ? LIMIT 1");
    $checkNic->bind_param('s', $nic);
    $checkNic->execute();
    $checkNic->store_result();
    if ($checkNic->num_rows > 0) $errors['nic'] = 'NIC already registered';
    $checkNic->close();
}

if (empty($errors)) {
    $checkPhone = $conn->prepare("SELECT id FROM lecturers WHERE phone = ? LIMIT 1");
    $checkPhone->bind_param('s', $phone);
    $checkPhone->execute();
    $checkPhone->store_result();
    if ($checkPhone->num_rows > 0) $errors['phone'] = 'Phone number already registered';
    $checkPhone->close();
}

// ---------- Photo upload handling ----------
$photoFileName = null;

if (empty($errors) && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $uploadDir = __DIR__ . '/uploads/lecturers/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors['photo'] = 'Photo upload failed';
    } else {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        $maxSize = 3 * 1024 * 1024; // 3MB

        if (!isset($allowedTypes[$mime])) {
            $errors['photo'] = 'Only JPG, PNG, WEBP allowed';
        } elseif ($_FILES['photo']['size'] > $maxSize) {
            $errors['photo'] = 'Max file size is 3MB';
        } else {
            $ext = $allowedTypes[$mime];
            $photoFileName = 'lect_' . preg_replace('/[^a-zA-Z0-9_]/', '', $username) . '_' . time() . '.' . $ext;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoFileName)) {
                $errors['photo'] = 'Could not save photo';
                $photoFileName = null;
            }
        }
    }
}

if (!empty($errors)) {
    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

// ---------- Insert ----------
$hashed = password_hash($password, PASSWORD_DEFAULT);
$status = 'pending';

$stmt = $conn->prepare("INSERT INTO lecturers 
    (full_name, gender, subject, language, qualifications, email, nic, phone, username, password, photo, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    'ssssssssssss',
    $full_name,
    $gender,
    $subject,          
    $language,
    $qualifications,
    $email,
    $nic,
    $phone,
    $username,
    $hashed,
    $photoFileName,
    $status
);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['id'] = $stmt->insert_id;
} else {
    $response['message'] = 'Database error: ' . $stmt->error;

   
    if ($photoFileName && file_exists(__DIR__ . '/uploads/lecturers/' . $photoFileName)) {
        unlink(__DIR__ . '/uploads/lecturers/' . $photoFileName);
    }
}

$stmt->close();
$conn->close();

echo json_encode($response);