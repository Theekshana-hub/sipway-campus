<?php


header('Content-Type: application/json');
include 'db.php'; 

$id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$package_name   = trim($_POST['package_name'] ?? '');
$price          = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$total_sessions = isset($_POST['total_sessions']) ? (int)$_POST['total_sessions'] : 0;
$duration_label = trim($_POST['duration_label'] ?? '1 hour');
$description    = trim($_POST['description'] ?? '');
$is_offer       = isset($_POST['is_offer']) ? 1 : 0;
$status         = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
$sort_order     = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;


$package_type = strtolower(trim($_POST['package_type'] ?? 'individual'));
if (!in_array($package_type, ['individual', 'group'], true)) {
    $package_type = 'individual';
}


$allowed_langs = ['en', 'de', 'zh', 'ja', 'fr', 'hi', 'ru', 'ar', 'ta', 'it'];
$language = strtolower(trim($_POST['language'] ?? 'en'));
if (!in_array($language, $allowed_langs, true)) {
    $language = 'en';
}

// Validation
if ($package_name === '' || $price <= 0 || $total_sessions <= 0) {
    echo json_encode(['success' => false, 'message' => 'Package name, price and sessions are required.']);
    exit;
}

if ($id > 0) {
    // UPDATE
    $stmt = $conn->prepare(
        "UPDATE packages
         SET package_name = ?,
             language = ?,
             package_type = ?,
             price = ?,
             total_sessions = ?,
             duration_label = ?,
             description = ?,
             is_offer = ?,
             status = ?,
             sort_order = ?
         WHERE id = ?"
    );

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    // s=package_name, s=language, s=package_type, d=price, i=total_sessions,
    // s=duration_label, s=description, i=is_offer, s=status, i=sort_order, i=id
    $stmt->bind_param(
        "sssdissisii",
        $package_name,
        $language,
        $package_type,
        $price,
        $total_sessions,
        $duration_label,
        $description,
        $is_offer,
        $status,
        $sort_order,
        $id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Package updated successfully.', 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
    }
    $stmt->close();

} else {
    // INSERT
    $stmt = $conn->prepare(
        "INSERT INTO packages
            (package_name, language, package_type, price, total_sessions, duration_label, description, is_offer, status, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    // s s s d i s s i s i
    $stmt->bind_param(
        "sssdissisi",
        $package_name,
        $language,
        $package_type,
        $price,
        $total_sessions,
        $duration_label,
        $description,
        $is_offer,
        $status,
        $sort_order
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Package added successfully.', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
    }
    $stmt->close();
}

$conn->close();