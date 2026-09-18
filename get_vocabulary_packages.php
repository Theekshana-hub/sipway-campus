<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ===== Database Config =====
$host     = 'localhost';
$dbname   = 'sipway';          // ඔයාගේ database name
$username = 'root';            // ඔයාගේ username
$password = '';                // ඔයාගේ password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT 
            id,
            package_name,
            price,
            duration_label,
            description,
            is_offer,
            status,
            sort_order,
            created_at
        FROM vocabulary_packages
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $packages = $stmt->fetchAll();

    echo json_encode([
        'success'  => true,
        'packages' => $packages
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}