<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

require_once 'db.php';
if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$studentId = $_SESSION['student_id'];

$fullName = trim($_POST['fullName'] ?? '');
$mobile   = trim($_POST['mobile'] ?? '');
$gender   = trim($_POST['gender'] ?? 'Other');
$address  = trim($_POST['address'] ?? '');
$newPass  = $_POST['newPassword'] ?? '';

if ($fullName === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your name.']);
    exit();
}
if (!preg_match('/^0\d{9}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.']);
    exit();
}
if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
    $gender = 'Other';
}
if ($newPass !== '' && strlen($newPass) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
    exit();
}

// ==================== PROFILE PHOTO (optional replace) ====================
$photoPath = null; // stays null unless a new photo was uploaded
if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tmpPath = $_FILES['profilePhoto']['tmp_name'];
    $mime    = mime_content_type($tmpPath);
    $size    = $_FILES['profilePhoto']['size'];

    if (!isset($allowed[$mime])) {
        echo json_encode(['success' => false, 'message' => 'Profile photo must be a JPG, PNG or WEBP image.']);
        exit();
    }
    if ($size > 3 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Profile photo must be under 3MB.']);
        exit();
    }

    $uploadDir = __DIR__ . '/uploads/students/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = 'student_' . uniqid('', true) . '.' . $allowed[$mime];
    if (move_uploaded_file($tmpPath, $uploadDir . $filename)) {
        $photoPath = 'uploads/students/' . $filename;

        // delete old photo file if it exists
        $oldStmt = $conn->prepare("SELECT profile_photo FROM students WHERE id = ? LIMIT 1");
        $oldStmt->bind_param("i", $studentId);
        $oldStmt->execute();
        $oldRow = $oldStmt->get_result()->fetch_assoc();
        $oldStmt->close();
        if (!empty($oldRow['profile_photo']) && file_exists(__DIR__ . '/' . $oldRow['profile_photo'])) {
            @unlink(__DIR__ . '/' . $oldRow['profile_photo']);
        }
    }
}
// ============================================================================

if ($photoPath !== null) {
    $stmt = $conn->prepare("UPDATE students SET full_name = ?, mobile = ?, gender = ?, address = ?, profile_photo = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $fullName, $mobile, $gender, $address, $photoPath, $studentId);
} else {
    $stmt = $conn->prepare("UPDATE students SET full_name = ?, mobile = ?, gender = ?, address = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $fullName, $mobile, $gender, $address, $studentId);
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Could not update profile. Please try again.']);
    exit();
}
$stmt->close();

// Optional password change
if ($newPass !== '') {
    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
    $pStmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
    $pStmt->bind_param("si", $hashed, $studentId);
    $pStmt->execute();
    $pStmt->close();
}

$_SESSION['student_name'] = $fullName;

$conn->close();
echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);