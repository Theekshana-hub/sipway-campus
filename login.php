<?php
header('Content-Type: application/json');

session_start();
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

$stmt = $conn->prepare("
    SELECT id, full_name, email, mobile, language, password, status
    FROM students
    WHERE email=?
");

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows == 1){

    $user = $result->fetch_assoc();

    if(password_verify($password, $user['password'])){

        // Regenerate session id on login to avoid session fixation
        session_regenerate_id(true);

        $_SESSION['student_id'] = $user['id'];
        $_SESSION['student_name'] = $user['full_name'];
        $_SESSION['student_language'] = $user['language'];

        // Make sure this browser isn't also carrying a stale admin session
        // (this is what was causing chat messages to be misattributed —
        // if admin_id was still set from an earlier admin login in the same
        // browser, chat_api.php would treat this student as the admin)
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_logged_in']);

        unset($user['password']);

        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => $user
        ]);

    }else{

        echo json_encode([
            "success" => false,
            "message" => "Invalid password"
        ]);
    }

}else{

    echo json_encode([
        "success" => false,
        "message" => "Email not found"
    ]);
}

$stmt->close();
$conn->close();
?>