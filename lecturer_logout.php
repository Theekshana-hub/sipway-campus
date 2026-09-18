<?php
session_start();

// Session eke thiyena okkoma variables clear karanawa
$_SESSION = [];

// Session cookie eka thiyenawa nam, eeka expire karanawa
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Session eka fully destroy karanawa
session_destroy();

// Logout una gaman lecturer login pagekt redirect karanawa
header("Location: lecturer-login.php");
exit();