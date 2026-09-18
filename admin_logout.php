<?php
session_start();


$_SESSION = [];


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

session_destroy();
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<title>Logging out...</title>
<script>
 
  localStorage.removeItem('sipwayAdmin');
 
  window.location.href = 'admin_login.php';
</script>
</head>
<body>
  <p style="font-family:sans-serif; text-align:center; margin-top:40px; color:#555;">
    Logging out...
  </p>
</body>
</html>