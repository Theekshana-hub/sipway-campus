<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php'; 

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection not available');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    
    if ($method === 'GET') {
        $sql = "SELECT id, code, label, flag, is_active, sort_order
                FROM languages
                ORDER BY sort_order ASC, label ASC";
        $result = $conn->query($sql);

        if ($result === false) {
            throw new Exception($conn->error);
        }

        $languages = [];
        while ($row = $result->fetch_assoc()) {
            $languages[] = $row;
        }

        echo json_encode([
            'success' => true,
            'data' => $languages
        ]);
        exit;
    }

    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['action'])) {
            throw new Exception('Invalid request');
        }

        $action = $input['action'];

        // ADD
        if ($action === 'add') {
            $code   = trim($input['code'] ?? '');
            $label  = trim($input['label'] ?? '');
            $flag   = trim($input['flag'] ?? '🌐');
            $order  = intval($input['sort_order'] ?? 0);
            $active = intval($input['is_active'] ?? 1);

            if ($code === '' || $label === '') {
                throw new Exception('Code and Label required');
            }

            $stmt = $conn->prepare(
                "INSERT INTO languages (code, label, flag, sort_order, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param('sssii', $code, $label, $flag, $order, $active);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            echo json_encode(['success' => true, 'message' => 'Language added']);
            exit;
        }

        // UPDATE
        if ($action === 'update') {
            $id     = intval($input['id'] ?? 0);
            $code   = trim($input['code'] ?? '');
            $label  = trim($input['label'] ?? '');
            $flag   = trim($input['flag'] ?? '🌐');
            $order  = intval($input['sort_order'] ?? 0);
            $active = intval($input['is_active'] ?? 1);

            if ($id <= 0 || $code === '' || $label === '') {
                throw new Exception('Invalid data for update');
            }

            $stmt = $conn->prepare(
                "UPDATE languages
                 SET code = ?, label = ?, flag = ?, sort_order = ?, is_active = ?
                 WHERE id = ?"
            );
            $stmt->bind_param('sssiii', $code, $label, $flag, $order, $active, $id);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            echo json_encode(['success' => true, 'message' => 'Language updated']);
            exit;
        }

        // DELETE
        if ($action === 'delete') {
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid id');
            }

            $stmt = $conn->prepare("DELETE FROM languages WHERE id = ?");
            $stmt->bind_param('i', $id);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            echo json_encode(['success' => true, 'message' => 'Language deleted']);
            exit;
        }

        // TOGGLE (active/inactive)
        if ($action === 'toggle') {
            $id = intval($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid id');
            }

            $stmt = $conn->prepare(
                "UPDATE languages SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?"
            );
            $stmt->bind_param('i', $id);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            echo json_encode(['success' => true, 'message' => 'Status updated']);
            exit;
        }

        throw new Exception('Unknown action');
    }

    // ---------- Unsupported method ----------
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() // testing කරද්දී විතරක්; production එකේ generic message එකකට වෙනස් කරන්න
    ]);
}

if (isset($conn)) {
    $conn->close();
}