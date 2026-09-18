<?php
// edit-teacher.php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$response = ['success' => false];
$errors = [];

$id             = intval($_POST['id'] ?? 0);
$full_name      = trim($_POST['full_name'] ?? '');
$gender         = trim($_POST['gender'] ?? '');
$subject        = trim($_POST['subject'] ?? '');   // multiple subjects as "English,IELTS,Spoken"
$language       = trim($_POST['language'] ?? 'en');
$qualifications = trim($_POST['qualifications'] ?? '');
$email          = trim($_POST['email'] ?? '');
$nic            = trim($_POST['nic'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$username       = trim($_POST['username'] ?? '');
$password       = trim($_POST['password'] ?? ''); // empty නම් password change කරන්නේ නෑ

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid teacher id']);
    exit;
}

// ---------- Validations ----------
if ($full_name === '') {
    $errors['full_name'] = 'Full name required';
}

// Gender: OPTIONAL from the form (keep old if empty)
if ($gender !== '' && !in_array($gender, ['male', 'female'], true)) {
    $errors['gender'] = 'Invalid gender value';
}

// Subject required
if ($subject === '') {
    $errors['subject'] = 'At least one subject is required';
}

// Language
$allowedLangs = ['en', 'de', 'zh', 'ja', 'fr', 'hi', 'ru', 'ar', 'ta', 'si', 'it'];
if (!in_array($language, $allowedLangs, true)) {
    $language = 'en';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Valid email required';
}

// NIC validation
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

if ($password !== '' && strlen($password) < 6) {
    $errors['password'] = 'Password must be at least 6 characters';
}

// Duplicate checks
if (empty($errors)) {
    $check = $conn->prepare("SELECT id FROM lecturers WHERE username = ? AND id != ? LIMIT 1");
    $check->bind_param('si', $username, $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) $errors['username'] = 'Username already exists';
    $check->close();
}

if (empty($errors)) {
    $checkEmail = $conn->prepare("SELECT id FROM lecturers WHERE email = ? AND id != ? LIMIT 1");
    $checkEmail->bind_param('si', $email, $id);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) $errors['email'] = 'Email already exists';
    $checkEmail->close();
}

if (empty($errors)) {
    $checkNic = $conn->prepare("SELECT id FROM lecturers WHERE nic = ? AND id != ? LIMIT 1");
    $checkNic->bind_param('si', $nic, $id);
    $checkNic->execute();
    $checkNic->store_result();
    if ($checkNic->num_rows > 0) $errors['nic'] = 'NIC already registered';
    $checkNic->close();
}

if (empty($errors)) {
    $checkPhone = $conn->prepare("SELECT id FROM lecturers WHERE phone = ? AND id != ? LIMIT 1");
    $checkPhone->bind_param('si', $phone, $id);
    $checkPhone->execute();
    $checkPhone->store_result();
    if ($checkPhone->num_rows > 0) $errors['phone'] = 'Phone number already registered';
    $checkPhone->close();
}

// Get existing photo + gender
$oldPhoto  = null;
$oldGender = null;

$getOld = $conn->prepare("SELECT photo, gender FROM lecturers WHERE id = ? LIMIT 1");
$getOld->bind_param('i', $id);
$getOld->execute();
$getOld->bind_result($oldPhoto, $oldGender);
$getOld->fetch();
$getOld->close();

$photoFileName = $oldPhoto;

if ($gender === '') {
    $gender = $oldGender ?? '';
}

// ---------- Photo upload handling ----------
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
            $newPhotoFileName = 'lect_' . preg_replace('/[^a-zA-Z0-9_]/', '', $username) . '_' . time() . '.' . $ext;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newPhotoFileName)) {
                // Delete old photo
                if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
                    unlink($uploadDir . $oldPhoto);
                }
                $photoFileName = $newPhotoFileName;
            } else {
                $errors['photo'] = 'Could not save photo';
            }
        }
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ---------- UPDATE ----------
if ($password !== '') {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE lecturers SET 
        full_name=?, gender=?, subject=?, language=?, qualifications=?, 
        email=?, nic=?, phone=?, username=?, password=?, photo=? 
        WHERE id=?");
    $stmt->bind_param(
        'sssssssssssi',
        $full_name, $gender, $subject, $language, $qualifications,
        $email, $nic, $phone, $username, $hashed, $photoFileName, $id
    );
} else {
    $stmt = $conn->prepare("UPDATE lecturers SET 
        full_name=?, gender=?, subject=?, language=?, qualifications=?, 
        email=?, nic=?, phone=?, username=?, photo=? 
        WHERE id=?");
    $stmt->bind_param(
        'ssssssssssi',
        $full_name, $gender, $subject, $language, $qualifications,
        $email, $nic, $phone, $username, $photoFileName, $id
    );
}

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['message'] = 'Database error: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);