<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login karala nathnam session log karanna baha.']);
    exit();
}

require_once 'db.php';

if (!isset($conn) || $conn === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check db.php file.']);
    exit();
}

$studentId = (int)$_SESSION['student_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$bookingId   = (isset($input['booking_id']) && $input['booking_id'] !== '') ? (int)$input['booking_id'] : null;
$lecturerId  = (isset($input['lecturer_id']) && $input['lecturer_id'] !== '') ? (int)$input['lecturer_id'] : null;
$source      = (isset($input['source']) && $input['source'] === 'live') ? 'live' : 'booking';
$sessionDate = $input['session_date'] ?? date('Y-m-d');
$sessionTime = $input['session_time'] ?? date('H:i:s');

if ($bookingId) {
    $chk = $conn->prepare("SELECT id FROM session_logs WHERE booking_id = ? LIMIT 1");
    $chk->bind_param("i", $bookingId);
    $chk->execute();
    $chkRes = $chk->get_result();
    $alreadyLogged = $chkRes->num_rows > 0;
    $chk->close();

    if ($alreadyLogged) {
        $conn->close();
        echo json_encode([
            'success' => true,
            'already_logged' => true,
            'message' => 'Session eka mulinma log wela thiyenawa.'
        ]);
        exit();
    }
}

$conn->begin_transaction();

try {
    $pkgStmt = $conn->prepare("
        SELECT id, sessions_remaining
        FROM activated_packages
        WHERE student_id = ? AND status = 'active' AND sessions_remaining > 0
        ORDER BY activated_at ASC
        LIMIT 1
        FOR UPDATE
    ");
    $pkgStmt->bind_param("i", $studentId);
    $pkgStmt->execute();
    $pkgRes = $pkgStmt->get_result();
    $pkg = $pkgRes->fetch_assoc();
    $pkgStmt->close();

    if (!$pkg) {
        $conn->rollback();
        $conn->close();
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'Active package ekakath session ithuru na. Aluth package ekak activate karanna.'
        ]);
        exit();
    }

    $activatedPackageId = (int)$pkg['id'];
    $newRemaining = (int)$pkg['sessions_remaining'] - 1;
    if ($newRemaining < 0) $newRemaining = 0;
    $newStatus = $newRemaining <= 0 ? 'completed' : 'active';

    $upd = $conn->prepare("UPDATE activated_packages SET sessions_remaining = ?, status = ? WHERE id = ?");
    $upd->bind_param("isi", $newRemaining, $newStatus, $activatedPackageId);
    $upd->execute();
    $upd->close();

    $log = $conn->prepare("
        INSERT INTO session_logs (student_id, lecturer_id, booking_id, activated_package_id, session_date, session_time, source)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $log->bind_param(
        "iiiisss",
        $studentId,
        $lecturerId,
        $bookingId,
        $activatedPackageId,
        $sessionDate,
        $sessionTime,
        $source
    );
    $log->execute();
    $log->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'already_logged' => false,
        'activated_package_id' => $activatedPackageId,
        'sessions_remaining' => $newRemaining,
        'package_status' => $newStatus
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Session log karaddi error ekk una. Try again.']);
}

$conn->close();