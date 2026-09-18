<?php
header('Content-Type: application/json');
require_once 'db.php';

// Note: admin auth here is handled client-side via localStorage ('sipwayAdmin'),
// same pattern as get_bookings.php / update_booking_status.php in this project.

$result = $conn->query("SELECT id, full_name,subject, gender, language, qualifications, email, nic, username, status, photo, created_at FROM lecturers ORDER BY full_name ASC");

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = [
        'id'             => (int)$row['id'],
        'full_name'      => $row['full_name'],
        'subject'      => $row['subject'],
        'gender'         => $row['gender'],
        'language'       => $row['language'] ?? 'en',
        'qualifications' => $row['qualifications'],
        'email'          => $row['email'],
        'nic'            => $row['nic'],
        'username'       => $row['username'],
        'status'         => $row['status'],
        'photo'          => $row['photo'],
        'created_at'     => $row['created_at'],
    ];
}

echo json_encode(['success' => true, 'data' => $teachers]);

$conn->close();