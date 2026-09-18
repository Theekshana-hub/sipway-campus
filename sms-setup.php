<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);

$configPath = __DIR__ . '/sms-config.php';
$message    = '';
$messageType = '';
$currentUrl  = '';
$currentUsername = '';
$currentPassword = '';
$currentMask = '';


if (file_exists($configPath)) {
    $content = file_get_contents($configPath);
    if (preg_match("/SMS_WSDL_URL',\s*'([^']*)'/", $content, $m)) $currentUrl = $m[1];
    if (preg_match("/SMS_USERNAME',\s*'([^']*)'/", $content, $m)) $currentUsername = $m[1];
    if (preg_match("/SMS_PASSWORD',\s*'([^']*)'/", $content, $m)) $currentPassword = $m[1];
    if (preg_match("/SMS_MASK',\s*'([^']*)'/", $content, $m)) $currentMask = $m[1];
}

$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiUrl   = trim($_POST['api_url'] ?? '');
    $username = trim($_POST['username'] ?? $currentUsername);
    $password = trim($_POST['password'] ?? $currentPassword);
    $mask     = trim($_POST['mask'] ?? $currentMask);
    $action   = $_POST['action'] ?? 'test';

    if (empty($apiUrl)) {
        $message = 'API URL eka danna one';
        $messageType = 'error';
    } else {
        
        try {
            ini_set("soap.wsdl_cache_enabled", "0");
            $client = new SoapClient($apiUrl, [
                'connection_timeout' => 10,
                'exceptions'         => true,
            ]);

            $testResult = "✅ WSDL eka load una! Methana thibba functions:\n\n";
            $functions = $client->__getFunctions();
            foreach ($functions as $f) {
                $testResult .= "- $f\n";
            }

            
            $user = new stdClass();
            $user->id = '';
            $user->username = $username;
            $user->password = $password;
            $user->customer = '';

            $req = new stdClass();
            $req->user = $user;

            $sessionOk = false;
            $sessionMsg = '';
            try {
                $res = $client->createSession($req);
                if (!empty($res->return)) {
                    $sessionOk = true;
                    $sessionMsg = "✅ Session eka hadagaththa! Session ID: " . htmlspecialchars($res->return);
                    try {
                        $closeReq = new stdClass();
                        $closeReq->session = $res->return;
                        $client->closeSession($closeReq);
                    } catch (\Throwable $e) { /* ignore cleanup errors */ }
                } else {
                    $sessionMsg = "⚠️ WSDL eka load una, habai session eka empty return una. Username/password check karanna.";
                }
            } catch (\Throwable $e) {
                $sessionMsg = "⚠️ WSDL eka load una, habai session eka hadaganna baha: " . $e->getMessage() . "\n(Parameter format eka wenas wenna puluwan — arg0/arg1 style ekak try karanna one wenna puluwan)";
            }

            $testResult .= "\n" . $sessionMsg;

            if ($action === 'save' && $sessionOk) {
                $configContent = "<?php
// ============================================================
// Mobitel Enterprise SMS (SOAP API) Configuration
// sms-setup.php eken auto-generate karapu eka — " . date('Y-m-d H:i:s') . "
// ============================================================
define('SMS_WSDL_URL', '" . addslashes($apiUrl) . "');

define('SMS_USERNAME', '" . addslashes($username) . "');
define('SMS_PASSWORD', '" . addslashes($password) . "');
define('SMS_USER_ID', '');
define('SMS_CUSTOMER', '');

define('SMS_MASK', '" . addslashes($mask) . "');

define('SMS_ENABLED', true);
";
                if (file_put_contents($configPath, $configContent)) {
                    $message = '✅ sms-config.php eka save una! Dan booking test karala balanna.';
                    $messageType = 'success';
                    $currentUrl = $apiUrl;
                    $currentUsername = $username;
                    $currentPassword = $password;
                    $currentMask = $mask;
                } else {
                    $message = '❌ sms-config.php eka save karanna baha (file permissions check karanna)';
                    $messageType = 'error';
                }
            } elseif ($action === 'save' && !$sessionOk) {
                $message = '❌ Session eka hadaganna bari unu nisa save karanne na. Uda thiyena error eka balanna.';
                $messageType = 'error';
            }

        } catch (\SoapFault $e) {
            $testResult = "❌ WSDL eka load karanna baha:\n" . $e->getMessage();
        } catch (\Throwable $e) {
            $testResult = "❌ Error eka:\n" . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>SMS API Setup — Sipway Campus</title>
<style>
  body{ font-family: -apple-system, Arial, sans-serif; max-width: 700px; margin: 30px auto; padding: 0 16px; background:#f4f0ff; }
  h1{ font-size: 20px; color:#1e1b4b; }
  .card{ background:#fff; border-radius:12px; padding:22px; box-shadow:0 4px 14px rgba(124,58,237,0.1); margin-bottom:20px; }
  label{ display:block; font-weight:700; font-size:13px; margin:14px 0 5px; color:#4c1d95; }
  input[type=text], input[type=password]{ width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:14px; box-sizing:border-box; }
  .hint{ font-size:12px; color:#777; margin-top:3px; }
  button{ margin-top:18px; padding:11px 18px; border:none; border-radius:8px; font-weight:700; cursor:pointer; margin-right:8px; }
  .btn-test{ background:#e5e7eb; color:#1e1b4b; }
  .btn-save{ background:linear-gradient(135deg,#a855f7,#ec4899); color:#fff; }
  pre{ background:#1e1b4b; color:#c9f9d0; padding:14px; border-radius:8px; white-space:pre-wrap; font-size:12.5px; max-height:320px; overflow:auto; }
  .msg{ padding:12px 14px; border-radius:8px; font-weight:700; margin-bottom:14px; }
  .msg.success{ background:#d1fae5; color:#065f46; }
  .msg.error{ background:#fee2e2; color:#991b1b; }
</style>
</head>
<body>
  <h1>🔧 Mobitel SMS API Setup / Test Tool</h1>

  <?php if ($message): ?>
    <div class="msg <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST">
      <label>API / WSDL URL</label>
      <input type="text" name="api_url" value="<?= htmlspecialchars($_POST['api_url'] ?? $currentUrl) ?>" placeholder="http://... ekak methanata paste karanna">
      <div class="hint">Portal eke hoyagath link eka methanata paste karanna</div>

      <label>Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($currentUsername) ?>">

      <label>Password</label>
      <input type="text" name="password" value="<?= htmlspecialchars($currentPassword) ?>">

      <label>Sender ID / Mask</label>
      <input type="text" name="mask" value="<?= htmlspecialchars($currentMask ?: 'SipwayCampus') ?>">
      <div class="hint">Mobitel approve karapu sender name eka (nathnam default ekama thiyanna)</div>

      <button type="submit" name="action" value="test" class="btn-test">🧪 Test Connection</button>
      <button type="submit" name="action" value="save" class="btn-save">💾 Test &amp; Save Config</button>
    </form>
  </div>

  <?php if ($testResult): ?>
    <div class="card">
      <strong>Test Result:</strong>
      <pre><?= htmlspecialchars($testResult) ?></pre>
    </div>
  <?php endif; ?>

</body>
</html>