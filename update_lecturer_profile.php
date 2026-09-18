<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['lecturer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. කරුණාකර ආයෙත් login වෙන්න.']);
    exit();
}

require_once 'db.php';

$lecturerId = (int)$_SESSION['lecturer_id'];

// ---------- Read input (JSON body OR multipart form-data) ----------
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isMultipart = stripos($contentType, 'multipart/form-data') !== false;

if ($isMultipart) {
    $name           = trim($_POST['name'] ?? '');
    $gender         = trim($_POST['gender'] ?? '');
    $subject        = trim($_POST['subject'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $nic            = trim($_POST['nic'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $username       = trim($_POST['username'] ?? '');
    $newPassword    = trim($_POST['new_password'] ?? '');
} else {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];
    $name           = trim($data['name'] ?? '');
    $gender         = trim($data['gender'] ?? '');
    $subject        = trim($data['subject'] ?? '');
    $qualifications = trim($data['qualifications'] ?? '');
    $email          = trim($data['email'] ?? '');
    $nic            = trim($data['nic'] ?? '');
    $phone          = trim($data['phone'] ?? '');
    $username       = trim($data['username'] ?? '');
    $newPassword    = trim($data['new_password'] ?? '');
}

// ---------- Required field validation ----------
if ($name === '' || $email === '' || $nic === '' || $phone === '' || $username === '') {
    echo json_encode(['success' => false, 'message' => '❌ Name, Email, NIC, Phone, Username අනිවාර්යයි.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '❌ වලංගු Email ලිපිනයක් ඇතුලත් කරන්න.']);
    exit();
}

// NIC validation - Old format: 9 digits + V/X, New format: 12 digits
if (!preg_match('/^([0-9]{9}[vVxX]|[0-9]{12})$/', $nic)) {
    echo json_encode(['success' => false, 'message' => '❌ වලංගු NIC අංකයක් ඇතුලත් කරන්න. (උදා: 991234567V හෝ 199912345678)']);
    exit();
}

// Phone validation
if (!preg_match('/^[0-9+\s\-]{9,15}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => '❌ වලංගු දුරකථන අංකයක් ඇතුලත් කරන්න.']);
    exit();
}

if (!in_array($gender, ['', 'Male', 'Female', 'Other'], true)) {
    $gender = '';
}

// ---------- Duplicate checks (excluding this lecturer's own row) ----------
$check = $conn->prepare("SELECT id FROM lecturers WHERE username = ? AND id != ? LIMIT 1");
$check->bind_param('si', $username, $lecturerId);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => "❌ Username '$username' දැනටමත් වෙන කෙනෙක් භාවිතා කරනවා."]);
    $check->close();
    exit();
}
$check->close();

$checkEmail = $conn->prepare("SELECT id FROM lecturers WHERE email = ? AND id != ? LIMIT 1");
$checkEmail->bind_param('si', $email, $lecturerId);
$checkEmail->execute();
$checkEmail->store_result();
if ($checkEmail->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => "❌ Email '$email' දැනටමත් වෙන කෙනෙක් භාවිතා කරනවා."]);
    $checkEmail->close();
    exit();
}
$checkEmail->close();

$checkNic = $conn->prepare("SELECT id FROM lecturers WHERE nic = ? AND id != ? LIMIT 1");
$checkNic->bind_param('si', $nic, $lecturerId);
$checkNic->execute();
$checkNic->store_result();
if ($checkNic->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => "❌ NIC '$nic' දැනටමත් වෙන කෙනෙක් register කරලා."]);
    $checkNic->close();
    exit();
}
$checkNic->close();

// ---------- Photo upload handling (optional) ----------
$newPhotoFileName = null;
$uploadDir = __DIR__ . '/uploads/lecturers/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($isMultipart && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '❌ Photo upload කිරීමේදී error එකක් ආවා.']);
        exit();
    }

    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['photo']['tmp_name']);
    finfo_close($finfo);

    $maxSize = 3 * 1024 * 1024; // 3MB limit

    if (!isset($allowedTypes[$mime])) {
        echo json_encode(['success' => false, 'message' => '❌ Photo එක JPG, PNG, හෝ WEBP විතරක් වෙන්න ඕන.']);
        exit();
    }
    if ($_FILES['photo']['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => '❌ Photo size එක 3MB ට වඩා අඩු වෙන්න ඕන.']);
        exit();
    }

    $ext = $allowedTypes[$mime];
    $newPhotoFileName = 'lect_' . preg_replace('/[^a-zA-Z0-9_]/', '', $username) . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $newPhotoFileName;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
        echo json_encode(['success' => false, 'message' => '❌ Photo save කිරීමේදී error එකක් ආවා.']);
        exit();
    }
}

// ---------- Get old photo (to delete if replaced) ----------
$oldPhoto = null;
$stmtOld = $conn->prepare("SELECT photo FROM lecturers WHERE id = ? LIMIT 1");
$stmtOld->bind_param('i', $lecturerId);
$stmtOld->execute();
$stmtOld->bind_result($oldPhoto);
$stmtOld->fetch();
$stmtOld->close();

// ---------- Build UPDATE query dynamically ----------
$fields = "full_name = ?, gender = ?, subject = ?, qualifications = ?, email = ?, nic = ?, phone = ?, username = ?";
$types  = 'ssssssss';
$params = [$name, $gender, $subject, $qualifications, $email, $nic, $phone, $username];

if ($newPassword !== '') {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $fields .= ", password = ?";
    $types  .= 's';
    $params[] = $hashed;
}

if ($newPhotoFileName !== null) {
    $fields .= ", photo = ?";
    $types  .= 's';
    $params[] = $newPhotoFileName;
}

$types .= 'i';
$params[] = $lecturerId;

$sql = "UPDATE lecturers SET $fields WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    // Delete old photo file if a new one was uploaded
    if ($newPhotoFileName !== null && $oldPhoto && file_exists($uploadDir . $oldPhoto)) {
        unlink($uploadDir . $oldPhoto);
    }

    // Keep session in sync
    $_SESSION['lecturer_name']    = $name;
    $_SESSION['lecturer_subject'] = $subject;

    echo json_encode([
        'success' => true,
        'message' => '✅ Profile එක සාර්ථකව update කරා.',
        'photo'   => $newPhotoFileName
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '❌ Error: ' . $stmt->error]);
    // rollback newly uploaded photo if DB update failed
    if ($newPhotoFileName !== null && file_exists($uploadDir . $newPhotoFileName)) {
        unlink($uploadDir . $newPhotoFileName);
    }
}

$stmt->close();
$conn->close();