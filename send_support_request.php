<?php
session_start();
header('Content-Type: application/json');
include 'db.php'; // your db connection file — should set $conn (mysqli)

// student session check
$studentId = $_SESSION['student_id'] ?? 0;
$studentName = $_SESSION['student_name'] ?? 'Guest';

if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Session expired. කරුණාකර ආයෙත් Login වෙන්න.']);
    exit;
}

$message = trim($_POST['message'] ?? '');

if ($message === '') {
    echo json_encode(['success' => false, 'message' => 'ගැටලුව ලියන්න.']);
    exit;
}

$screenshotPath = null;

// ---- Handle screenshot upload (optional) ----
if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/support/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($_FILES['screenshot']['tmp_name']);

    if (in_array($fileType, $allowedTypes)) {
        $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
        $safeName = 'sup_' . $studentId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $destination)) {
            // relative path saved to DB (adjust if your admin panel is in a different folder)
            $screenshotPath = 'uploads/support/' . $safeName;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'රූපයක් විතරයි Upload කරන්න පුළුවන් (jpg, png, gif, webp).']);
        exit;
    }
}

// ---- Insert into DB ----
$stmt = $conn->prepare("INSERT INTO support_requests (student_id, student_name, message, screenshot) VALUES (?, ?, ?, ?)");
$stmt->bind_param('isss', $studentId, $studentName, $message, $screenshotPath);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Support request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();