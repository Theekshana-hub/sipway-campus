<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');


$isStudent = isset($_SESSION['student_id']);
$isAdmin   = isset($_SESSION['admin_id']);

if (!$isStudent && !$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}


if ($isStudent && $isAdmin) {
    $isStudent = false;
}

$myId   = $isStudent ? (int)$_SESSION['student_id'] : (int)$_SESSION['admin_id'];
$myType = $isStudent ? 'student' : 'admin';

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($action === 'get_or_create_admin_chat') {
    if (!$isStudent) {
        echo json_encode(['success' => false, 'message' => 'Only students can use this']);
        exit;
    }

    
    $stmt = $conn->prepare("
        SELECT id FROM chat_conversations 
        WHERE (
            (user1_id = ? AND user1_type = 'student' AND user2_id = 1 AND user2_type = 'admin')
            OR
            (user1_id = 1 AND user1_type = 'admin' AND user2_id = ? AND user2_type = 'student')
        )
        LIMIT 1
    ");
    $stmt->bind_param("ii", $myId, $myId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        echo json_encode(['success' => true, 'conversation_id' => (int)$row['id']]);
        exit;
    }

    $ins = $conn->prepare("
        INSERT INTO chat_conversations (user1_id, user2_id, user1_type, user2_type) 
        VALUES (?, 1, 'student', 'admin')
    ");
    $ins->bind_param("i", $myId);
    $ins->execute();

    echo json_encode([
        'success' => true,
        'conversation_id' => $conn->insert_id
    ]);
    exit;
}

if ($action === 'list_conversations') {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Only admin can use this']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT c.id, c.last_message_at,
               CASE 
                 WHEN c.user1_type = 'student' THEN c.user1_id 
                 ELSE c.user2_id 
               END AS student_id
        FROM chat_conversations c
        WHERE (c.user1_type = 'admin' OR c.user2_type = 'admin')
        ORDER BY c.last_message_at DESC
    ");
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $result = [];
    foreach ($rows as $r) {
        $studentId = (int)$r['student_id'];

        $s = $conn->prepare("SELECT full_name FROM students WHERE id = ?");
        $s->bind_param("i", $studentId);
        $s->execute();
        $student = $s->get_result()->fetch_assoc();
        $partnerName = $student['full_name'] ?? 'Student #' . $studentId;

        $m = $conn->prepare("SELECT message FROM chat_messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 1");
        $m->bind_param("i", $r['id']);
        $m->execute();
        $last = $m->get_result()->fetch_assoc();

        $result[] = [
            'id'               => $r['id'],
            'partner_name'     => $partnerName,
            'last_message'     => $last['message'] ?? '',
            'last_message_at'  => $r['last_message_at']
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

if ($action === 'start_conversation') {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Only admin can use this']);
        exit;
    }

    $search = trim($input['partner_search'] ?? '');
    if ($search === '') {
        echo json_encode(['success' => false, 'message' => 'Please enter a student name or email']);
        exit;
    }

    $like = '%' . $search . '%';
    $s = $conn->prepare("SELECT id, full_name FROM students WHERE full_name LIKE ? OR email LIKE ? LIMIT 1");
    $s->bind_param("ss", $like, $like);
    $s->execute();
    $student = $s->get_result()->fetch_assoc();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    $studentId = (int)$student['id'];

    $stmt = $conn->prepare("
        SELECT id FROM chat_conversations 
        WHERE (
            (user1_id = ? AND user1_type = 'student' AND user2_id = 1 AND user2_type = 'admin')
            OR
            (user1_id = 1 AND user1_type = 'admin' AND user2_id = ? AND user2_type = 'student')
        )
        LIMIT 1
    ");
    $stmt->bind_param("ii", $studentId, $studentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        echo json_encode([
            'success' => true,
            'conversation_id' => (int)$row['id'],
            'partner_name' => $student['full_name']
        ]);
        exit;
    }

    $ins = $conn->prepare("
        INSERT INTO chat_conversations (user1_id, user2_id, user1_type, user2_type) 
        VALUES (1, ?, 'admin', 'student')
    ");
    $ins->bind_param("i", $studentId);
    $ins->execute();

    echo json_encode([
        'success' => true,
        'conversation_id' => $conn->insert_id,
        'partner_name' => $student['full_name']
    ]);
    exit;
}

if ($action === 'get_messages') {
    $cid = (int)($_GET['conversation_id'] ?? 0);
    if (!$cid) {
        echo json_encode(['success' => false, 'message' => 'Invalid conversation']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $msgs]);
    exit;
}

if ($action === 'send') {
    $cid = (int)($input['conversation_id'] ?? 0);
    $msg = trim($input['message'] ?? '');

    if (!$cid || $msg === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiss", $cid, $myId, $myType, $msg);
    $stmt->execute();

    $conn->query("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = $cid");

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);