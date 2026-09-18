<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host     = 'localhost';
$dbname   = 'sipway';
$username = 'root';
$password = '';

$start = $_GET['start_date'] ?? null;
$end   = $_GET['end_date'] ?? null;

if (!$start || !$end) {
    echo json_encode(['success' => false, 'message' => 'start_date and end_date required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $sql = "
        SELECT
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
        WHERE DATE(COALESCE(avp.activated_at, avp.created_at)) BETWEEN :start AND :end
          AND avp.status = 'active'
        ORDER BY COALESCE(avp.activated_at, avp.created_at) DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $rows = $stmt->fetchAll();

    // sessions_remaining + type add කරනවා
    foreach ($rows as &$r) {
        $r['sessions_remaining'] = 0;
        $r['type'] = 'vocabulary';
    }
    unset($r);

    echo json_encode([
        'success' => true,
        'data'    => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}