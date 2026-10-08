<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // optional, remove if not needed

// Turn off display of errors (so they don't break JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once 'db.php';

    // Check if connection exists
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
    }

    // First check if language column exists
    $checkCol = $conn->query("SHOW COLUMNS FROM students LIKE 'language'");
    $hasLanguage = $checkCol && $checkCol->num_rows > 0;

    if ($hasLanguage) {
        $sql = "SELECT id, full_name, email, mobile, language, status, created_at 
                FROM students 
                ORDER BY created_at DESC";
    } else {
        // Fallback if language column is missing
        $sql = "SELECT id, full_name, email, mobile, status, created_at 
                FROM students 
                ORDER BY created_at DESC";
    }

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id'         => (int)$row['id'],
            'full_name'  => $row['full_name'] ?? '',
            'email'      => $row['email'] ?? '',
            'mobile'     => $row['mobile'] ?? '',
            'language'   => $row['language'] ?? 'en',
            'status'     => $row['status'] ?? 'active',
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $students
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}