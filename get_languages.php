<?php
/**
 * get_languages.php - Sipway Campus
 * Register form එකට is_active = 1 විතරක් return කරයි.
 * Admin disable කළ languages පෙන්නන්නේ නැහැ.
 */
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $err['message'] . ' in ' . basename($err['file']) . ' line ' . $err['line']
        ]);
    }
});

if (!file_exists(__DIR__ . '/db.php')) {
    echo json_encode(['success' => false, 'message' => 'db.php not found next to get_languages.php']);
    exit;
}
require_once __DIR__ . '/db.php';

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Check db.php.']);
    exit;
}

// ★ is_active = 1 විතරක්
$sql = "SELECT id, code, label, flag, is_active, sort_order
        FROM languages
        WHERE is_active = 1
        ORDER BY sort_order ASC, label ASC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

$languages = [];
while ($row = $result->fetch_assoc()) {
    $languages[] = [
        'id'         => (int)$row['id'],
        'code'       => $row['code'],
        'label'      => $row['label'],
        'flag'       => $row['flag'] ?: 'GB',
        'is_enabled' => true,   // මේවා හැමෝම active
        'is_active'  => 1,
        'sort_order' => (int)$row['sort_order'],
    ];
}

echo json_encode([
    'success'   => true,
    'languages' => $languages
], JSON_UNESCAPED_UNICODE);

exit;