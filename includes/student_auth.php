<?php
// ==================== SHARED STUDENT AUTH BOOTSTRAP ====================
// Include this at the very top of every student-area page (dashboard,
// purchase-history, faq-support, class-details, etc.) BEFORE any HTML output.
// It starts the session, verifies login, connects to the DB, and loads the
// current student's name into $studentName / $firstName / $fullNameSafe.

session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.php");
    exit();
}

require_once __DIR__ . '/../db.php';

if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}

$studentId   = $_SESSION['student_id'];
$studentName = $_SESSION['student_name'] ?? '';

$stmt = $conn->prepare("SELECT full_name FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $studentName = $row['full_name'];
} else {
    session_destroy();
    header("Location: student-login.php");
    exit();
}
$stmt->close();

$firstName     = htmlspecialchars(explode(' ', trim($studentName))[0]);
$fullNameSafe  = htmlspecialchars($studentName);
$studentNameJs = json_encode($studentName);