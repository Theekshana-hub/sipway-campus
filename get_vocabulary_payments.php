<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function out($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    ob_start();
    require_once __DIR__ . '/db.php';   // $conn (mysqli) methanin enawa
    ob_end_clean();

    if (!isset($conn) || !($conn instanceof mysqli)) {
        out(['success' => false, 'message' => 'db.php eken $conn (mysqli) hadala naha. db.php eka check karanna.']);
    }
    $conn->set_charset('utf8mb4');

    $start = trim($_GET['start_date'] ?? '');
    $end   = trim($_GET['end_date'] ?? '');

    $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($dateRegex, $start) || !preg_match($dateRegex, $end)) {
        out(['success' => false, 'message' => 'start_date and end_date required (YYYY-MM-DD)']);
    }

    $sql = "SELECT
                avp.id,
                avp.student_id,
                s.full_name AS student_name,
                avp.package_id,
                vp.package_name,
                COALESCE(vp.price, 0) AS amount,
                avp.status,
                COALESCE(avp.activated_at, avp.created_at) AS purchased_on,
                avp.created_at
            FROM activated_vocabulary_packages avp
            LEFT JOIN students s ON s.id = avp.student_id
            LEFT JOIN vocabulary_packages vp ON vp.id = avp.package_id
            WHERE DATE(COALESCE(avp.activated_at, avp.created_at)) BETWEEN ? AND ?
              AND avp.status = 'active'
            ORDER BY COALESCE(avp.activated_at, avp.created_at) DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $r['amount']             = (float)$r['amount'];
        $r['sessions_remaining'] = 0;
        $r['type']               = 'vocabulary';
        $rows[] = $r;
    }
    $stmt->close();

    out(['success' => true, 'data' => $rows]);

} catch (Throwable $e) {
    out(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}