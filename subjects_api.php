<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS subject_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $conn->query("SELECT id, name FROM subject_types ORDER BY name ASC");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }
    echo json_encode(['success' => true, 'data' => $data]);
    $conn->close();
    exit;
}

if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'add') {
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Subject name එකක් දෙන්න']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id FROM subject_types WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'මේ subject එක දැනටමත් තියෙනවා']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO subject_types (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'message' => 'Subject එක add කළා']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Add කරන්න බැරි උනා']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'update') {
        $id   = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        if ($id <= 0 || $name === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id FROM subject_types WHERE LOWER(name) = LOWER(?) AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'මේ නමින් තව subject එකක් තියෙනවා']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE subject_types SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Update කළා']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update කරන්න බැරි උනා']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM subject_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Delete කළා']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete කරන්න බැරි උනා']);
        }
        $stmt->close();
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid method']);