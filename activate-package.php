<?php
// activate-package.php
// Student ek package eka "Activate" click karama meka call wenawa.
// Meken karanne DIRECT ACTIVE karanne na -- status = 'pending' widihata save karala
// admin approve karana kam wait karanawa.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

include 'db.php'; // <-- CHANGED from db-connect.php -> db.php (match get_activations.php)

// ---------- 1. Student session check ----------
$studentId = $_SESSION['student_id'] ?? 0;
if ($studentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Session eka expire welada. Please login again.']);
    exit;
}

// ---------- 2. Read JSON body sent from packages.php ----------
$input = json_decode(file_get_contents('php://input'), true);

$packageId = isset($input['id']) ? (int)$input['id'] : 0;
$packageName = isset($input['name']) ? trim($input['name']) : '';
$price = isset($input['price']) ? (float)$input['price'] : 0;
$totalSessions = isset($input['sessions']) ? (int)$input['sessions'] : 0;

if ($packageId <= 0 || $packageName === '' || $price <= 0 || $totalSessions <= 0) {
    echo json_encode(['success' => false, 'message' => 'Package details wrong widihatai awe. Try again.']);
    exit;
}

// ---------- 3. Package eka mulinma active/exists dakaranawa (safety check) ----------
$pkgCheckStmt = $conn->prepare("SELECT id FROM packages WHERE id = ? AND status = 'active' LIMIT 1");
$pkgCheckStmt->bind_param('i', $packageId);
$pkgCheckStmt->execute();
$pkgExists = $pkgCheckStmt->get_result()->fetch_assoc();
$pkgCheckStmt->close();

if (!$pkgExists) {
    echo json_encode(['success' => false, 'message' => 'Meka package eka dæntam available na.']);
    exit;
}

// ---------- 4. Student ta dæntam active OR pending package thiyenawada balanawa ----------
$existingStmt = $conn->prepare("SELECT id, status FROM activated_packages WHERE student_id = ? AND status IN ('active','pending') LIMIT 1");
$existingStmt->bind_param('i', $studentId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();
$existingStmt->close();

if ($existing) {
    if ($existing['status'] === 'active') {
        echo json_encode(['success' => false, 'message' => 'Oyata dæntam active package ekak thiyenawa.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Oyage kalin package request eka dæntam pending widihata thiyenawa.']);
    }
    exit;
}

// ---------- 5. Fetch student's real name from students table ----------
// FIX: student_name eka INSERT eke thibbema na, ithin DB eke blank/NULL widihata
// save wela thibba -- eka nisa admin dashboard eke name eka penne na (ID witharai penne).
$studentNameStmt = $conn->prepare("SELECT full_name FROM students WHERE id = ? LIMIT 1");
$studentNameStmt->bind_param('i', $studentId);
$studentNameStmt->execute();
$studentRow = $studentNameStmt->get_result()->fetch_assoc();
$studentNameStmt->close();

if (!$studentRow) {
    echo json_encode(['success' => false, 'message' => 'Student record eka hoyaganna baa.']);
    exit;
}
$studentName = $studentRow['full_name'];


$insertStmt = $conn->prepare(
    "INSERT INTO activated_packages (student_id, student_name, package_id, package_name, price, total_sessions, sessions_remaining, status, activated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
);
$insertStmt->bind_param('isisdii', $studentId, $studentName, $packageId, $packageName, $price, $totalSessions, $totalSessions);

if ($insertStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Request eka admin ta yawuna. Approval ekak wait karanna.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$insertStmt->close();
$conn->close();