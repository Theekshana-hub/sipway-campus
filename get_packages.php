<?php
// get_packages.php
header('Content-Type: application/json');
include 'db.php';

$sql = "SELECT
            id,
            package_name,
            language,
            package_type,
            price,
            total_sessions,
            duration_label,
            description,
            is_offer,
            status,
            sort_order,
            created_at,
            updated_at
        FROM packages
        ORDER BY sort_order ASC, id ASC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

$packages = [];
while ($row = $result->fetch_assoc()) {
    // ensure package_type always has a value
    if (empty($row['package_type'])) {
        $row['package_type'] = 'individual';
    }
    $row['package_type'] = strtolower($row['package_type']);

    // ensure language always has a value
    if (empty($row['language'])) {
        $row['language'] = 'en';
    }
    $row['language'] = strtolower($row['language']);

    $packages[] = $row;
}

echo json_encode([
    'success'  => true,
    'packages' => $packages
]);

$conn->close();