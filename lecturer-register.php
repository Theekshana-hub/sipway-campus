<?php
require_once __DIR__ . '/db.php';
// ⚠️ SECURITY: lecturer accounts හදාගත්තට පස්සේ මේ file එක SERVER එකෙන් DELETE කරන්න.
$message = '';
$isSuccess = false;
$uploadDir = __DIR__ . '/uploads/lecturers/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
// DB එකෙන් languages (lowercase codes)
$languagesFromDb = [];
$langResult = @$conn->query("SELECT code, label, flag FROM languages WHERE is_active = 1 ORDER BY sort_order ASC");
if ($langResult) {
    while ($row = $langResult->fetch_assoc()) {
        $row['code'] = strtolower(trim($row['code'] ?? ''));
        $row['flag'] = strtoupper(trim($row['flag'] ?? ''));
        $row['label'] = trim($row['label'] ?? '');
        if ($row['code'] !== '') {
            $languagesFromDb[] = $row;
        }
    }
}
$allowedLanguageCodes = array_column($languagesFromDb, 'code');
if (empty($allowedLanguageCodes)) {
    $allowedLanguageCodes = ['en'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = trim($_POST['full_name'] ?? '');
    $gender         = trim($_POST['gender'] ?? '');
    $subject        = trim($_POST['subject'] ?? '');
    $language       = strtolower(trim($_POST['language'] ?? 'en'));
    $qualifications = trim($_POST['qualifications'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $nic            = trim($_POST['nic'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $username       = trim($_POST['username'] ?? '');
    $password       = trim($_POST['password'] ?? '');
    if (!in_array($language, $allowedLanguageCodes, true)) {
        $language = 'en';
    }
    $photoFileName = null;
    if ($full_name && $gender && $email && $nic && $phone && $username && $password) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ වලංගු Email ලිපිනයක් ඇතුලත් කරන්න.";
        } elseif (!preg_match('/^([0-9]{9}[vVxX]|[0-9]{12})$/', $nic)) {
            $message = "❌ වලංගු NIC අංකයක් ඇතුලත් කරන්න. (උදා: 991234567V හෝ 199912345678)";
        } elseif (!preg_match('/^[0-9+\s\-]{9,15}$/', $phone)) {
            $message = "❌ වලංගු දුරකථන අංකයක් ඇතුලත් කරන්න.";
        } else {
            $check = $conn->prepare("SELECT id FROM lecturers WHERE username = ? LIMIT 1");
            $check->bind_param('s', $username);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $message = "❌ Username '$username' දැනටමත් තියෙනවා.";
            } else {
                $check_email = $conn->prepare("SELECT id FROM lecturers WHERE email = ? LIMIT 1");
                $check_email->bind_param('s', $email);
                $check_email->execute();
                $check_email->store_result();
                if ($check_email->num_rows > 0) {
                    $message = "❌ Email '$email' දැනටමත් භාවිතයේ තියෙනවා.";
                } else {
                    $check_nic = $conn->prepare("SELECT id FROM lecturers WHERE nic = ? LIMIT 1");
                    $check_nic->bind_param('s', $nic);
                    $check_nic->execute();
                    $check_nic->store_result();
                    if ($check_nic->num_rows > 0) {
                        $message = "❌ NIC '$nic' දැනටමත් register කරලා තියෙනවා.";
                    } else {
                        $photoError = null;
                        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                                $photoError = "❌ Photo upload කිරීමේදී error එකක් ආවා.";
                            } else {
                                $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $mime  = finfo_file($finfo, $_FILES['photo']['tmp_name']);
                                finfo_close($finfo);
                                $maxSize = 3 * 1024 * 1024;
                                if (!isset($allowedTypes[$mime])) {
                                    $photoError = "❌ Photo එක JPG, PNG, හෝ WEBP විතරක් වෙන්න ඕන.";
                                } elseif ($_FILES['photo']['size'] > $maxSize) {
                                    $photoError = "❌ Photo size එක 3MB ට වඩා අඩු වෙන්න ඕන.";
                                } else {
                                    $ext = $allowedTypes[$mime];
                                    $photoFileName = 'lect_' . preg_replace('/[^a-zA-Z0-9_]/', '', $username) . '_' . time() . '.' . $ext;
                                    $destination   = $uploadDir . $photoFileName;
                                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                                        $photoError = "❌ Photo save කිරීමේදී error එකක් ආවා.";
                                        $photoFileName = null;
                                    }
                                }
                            }
                        }
                        if ($photoError) {
                            $message = $photoError;
                        } else {
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            $status = 'pending';
                            $stmt = $conn->prepare("INSERT INTO lecturers (full_name, gender, subject, language, qualifications, email, nic, phone, username, password, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param('ssssssssssss', $full_name, $gender, $subject, $language, $qualifications, $email, $nic, $phone, $username, $hashed, $photoFileName, $status);
                            if ($stmt->execute()) {
                                $message = "✅ Account එක සාර්ථකව හදුනා — Username: $username | Email: $email. Admin approve කරගන්නකම් login වෙන්න බැහැ.";
                                $isSuccess = true;
                            } else {
                                $message = "❌ Error: " . $stmt->error;
                                if ($photoFileName && file_exists($uploadDir . $photoFileName)) {
                                    unlink($uploadDir . $photoFileName);
                                }
                            }
                            $stmt->close();
                        }
                    }
                    $check_nic->close();
                }
                $check_email->close();
            }
            $check->close();
        }
    } else {
        $message = "සියලුම අනිවාර්ය fields (Full Name, Gender, Email, NIC, Phone, Username, Password) පුරවන්න";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Lecturer Account - Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy:#0f2a4a; --navy-2:#16385f; --navy-soft:#eef2f7;
    --coral:#e8825f; --coral-dark:#d66c47; --coral-soft:#fdece5;
    --bg:#f6f5f3; --card:#ffffff; --line:#e6e2da; --line-soft:#f0ede7;
    --muted:#6b7280; --muted-2:#8a93a3; --text:#1b2430;
    --danger:#c0392b; --danger-soft:#fdecea; --success:#1f9d55; --success-soft:#e8f8ee;
    --warning:#f2994a; --warning-soft:#fdf1e4;
    --radius-lg:16px; --radius-sm:8px;
    --shadow-card:0 24px 60px -12px rgba(15,42,74,0.14), 0 2px 8px rgba(15,42,74,0.06);
    --ease:cubic-bezier(.4,0,.2,1);
  }
  *{ box-sizing:border-box; }
  body{
    margin:0;
    font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:radial-gradient(1100px 500px at 85% -10%, rgba(232,130,95,0.08), transparent),
               radial-gradient(900px 500px at -5% 110%, rgba(15,42,74,0.05), transparent), var(--bg);
    color:var(--text); min-height:100vh; -webkit-font-smoothing:antialiased;
  }
  .topbar{
    height:60px; display:flex; align-items:center; justify-content:space-between;
    padding:0 clamp(16px,4vw,40px); background:rgba(255,255,255,0.85);
    backdrop-filter:saturate(180%) blur(10px); border-bottom:1px solid var(--line-soft);
    position:sticky; top:0; z-index:40;
  }
  .topbar .logo{ display:flex; align-items:center; gap:10px; font-weight:800; color:var(--navy); font-size:15px; }
  .logo-mark{
    width:30px; height:30px; border-radius:8px;
    background:linear-gradient(135deg,var(--navy),var(--navy-2));
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:13px; font-weight:800;
  }
  .topbar .help-link{ font-size:13px; color:var(--muted); text-decoration:none; font-weight:600; }
  .topbar .help-link:hover{ color:var(--navy); }
  .wrap{
    min-height:calc(100vh - 60px); display:flex; align-items:center; justify-content:center;
    gap:clamp(32px,6vw,80px); padding:clamp(28px,5vw,64px) clamp(16px,4vw,24px); flex-wrap:wrap;
  }
  .left{ max-width:360px; width:100%; flex:1 1 320px; }
  .illustration{ margin-bottom:32px; filter:drop-shadow(0 18px 30px rgba(15,42,74,0.10)); }
  .brand-line{ font-size:14px; font-weight:600; color:var(--muted); margin:0 0 8px; }
  .brand-tag{
    display:inline-flex; background:linear-gradient(135deg,#e63b3b,#c92a2a); color:#fff;
    font-weight:800; padding:5px 14px; border-radius:6px; font-size:16px;
    box-shadow:0 6px 16px rgba(201,42,42,0.28);
  }
  .headline{ font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--text); line-height:1.35; margin:22px 0 0; }
  .subline{ font-size:13.5px; color:var(--muted); line-height:1.6; margin:12px 0 0; max-width:300px; }
  .feature-row{ display:flex; flex-wrap:wrap; gap:10px; margin-top:24px; }
  .feature-chip{
    display:flex; align-items:center; gap:6px; background:#fff; border:1px solid var(--line);
    padding:8px 14px; border-radius:999px; font-size:12.5px; font-weight:600; color:var(--navy-2);
  }
  .feature-chip .dot{ width:6px; height:6px; border-radius:50%; background:var(--coral); }
  .auth-panel{ width:520px; max-width:100%; flex:1 1 460px; }
  .card{
    background:var(--card); border-radius:var(--radius-lg);
    padding:clamp(28px,4vw,40px) clamp(24px,4vw,38px);
    box-shadow:var(--shadow-card); border:1px solid var(--line-soft);
  }
  .card-head{ margin-bottom:24px; }
  .eyebrow-badge{
    display:inline-flex; background:var(--coral-soft); color:var(--coral-dark);
    font-weight:800; font-size:11px; letter-spacing:0.6px; padding:5px 12px;
    border-radius:999px; margin-bottom:10px; text-transform:uppercase;
  }
  .card h1{ font-size:clamp(22px,2.6vw,27px); color:var(--navy); margin:0 0 4px; font-weight:800; }
  .subtext{ color:var(--muted); font-size:13.5px; line-height:1.6; margin:0; }
  .alert-box{ display:flex; align-items:flex-start; gap:9px; padding:12px 14px; border-radius:var(--radius-sm); font-size:13px; font-weight:700; margin-bottom:20px; }
  .alert-box.error{ background:var(--danger-soft); color:var(--danger); }
  .alert-box.success{ background:var(--success-soft); color:var(--success); }
  .alert-box svg{ width:17px; height:17px; flex-shrink:0; }
  .field{ margin-bottom:16px; position:relative; }
  .field-row{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .field label{ display:block; font-size:12.5px; color:var(--text); margin-bottom:7px; font-weight:600; }
  .req{ color:var(--coral); }
  .input-shell{ position:relative; display:flex; align-items:center; }
  .input-icon{ position:absolute; left:14px; width:18px; height:18px; color:var(--muted-2); pointer-events:none; display:flex; }
  .input-icon.textarea-icon{ top:14px; align-items:flex-start; }
  .field input, .field select{
    width:100%; padding:13px 14px 13px 40px; border-radius:var(--radius-sm); border:1.5px solid var(--line);
    font-size:14px; font-family:inherit; outline:none; background:#fbfaf9; color:var(--text); appearance:none;
  }
  .field select{
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%238a93a3' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 14px center; padding-right:36px;
  }
  .field textarea{
    width:100%; padding:13px 14px 13px 40px; border-radius:var(--radius-sm); border:1.5px solid var(--line);
    font-size:14px; font-family:inherit; outline:none; background:#fbfaf9; color:var(--text);
    resize:vertical; min-height:80px;
  }
  .field input:focus, .field select:focus, .field textarea:focus{
    border-color:var(--coral); background:#fff; box-shadow:0 0 0 4px rgba(232,130,95,0.14);
  }
  .toggle-pass{
    position:absolute; right:12px; background:none; border:none; cursor:pointer;
    color:var(--muted-2); padding:6px; display:flex; border-radius:6px;
  }
  .toggle-pass:hover{ color:var(--navy-2); background:var(--navy-soft); }
  .toggle-pass svg{ width:18px; height:18px; }
  .hint{ font-size:11px; color:var(--muted-2); margin-top:6px; font-weight:500; }
  .file-input-wrap{
    display:flex; border:1.5px dashed var(--line); border-radius:var(--radius-sm);
    padding:12px 14px; background:#fbfaf9;
  }
  .file-input-wrap input[type="file"]{ font-size:13px; width:100%; }
  .photo-preview{ display:none; margin-top:10px; width:70px; height:70px; border-radius:12px; object-fit:cover; border:1.5px solid var(--line); }
  .lang-select{ position:relative; }
  .lang-select-trigger{
    width:100%; padding:13px 14px; border-radius:var(--radius-sm); border:1.5px solid var(--line);
    font-size:14px; font-family:inherit; outline:none; background:#fbfaf9; color:var(--text);
    cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:8px; user-select:none;
  }
  .lang-select-trigger:hover{ border-color:#d7d2c8; }
  .lang-select-trigger.open, .lang-select-trigger:focus-visible{
    border-color:var(--coral); background:#fff; box-shadow:0 0 0 4px rgba(232,130,95,0.14);
  }
  .lang-current{ display:flex; align-items:center; gap:10px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
  .lang-flag{
    width:22px !important; height:16px !important; min-width:22px;
    flex-shrink:0; border-radius:3px; box-shadow:0 0 0 1px rgba(0,0,0,0.12);
    display:block !important; overflow:hidden;
  }
  .lang-caret{ width:16px; height:16px; color:var(--muted-2); flex-shrink:0; transition:transform .18s; }
  .lang-select-trigger.open .lang-caret{ transform:rotate(180deg); }
  .lang-options{
    position:absolute; top:calc(100% + 6px); left:0; right:0; background:#fff;
    border:1.5px solid var(--line); border-radius:var(--radius-sm);
    box-shadow:0 18px 40px -8px rgba(15,42,74,0.18); z-index:30;
    max-height:280px; overflow-y:auto; padding:6px;
    opacity:0; transform:translateY(-6px); pointer-events:none; transition:opacity .15s, transform .15s;
  }
  .lang-options.open{ opacity:1; transform:translateY(0); pointer-events:auto; }
  .lang-option{
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding:11px 12px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:500; color:var(--text);
  }
  .lang-option:hover{ background:var(--navy-soft); }
  .lang-option-left{ display:flex; align-items:center; gap:10px; }
  .lang-tick{ width:16px; height:16px; color:var(--coral-dark); flex-shrink:0; opacity:0; }
  .lang-option.selected{ background:var(--coral-soft); font-weight:700; color:var(--coral-dark); }
  .lang-option.selected .lang-tick{ opacity:1; }
  .lang-option-loading{ padding:14px; font-size:13px; color:var(--muted-2); text-align:center; }
  .btn-primary{
    width:100%; padding:14.5px; border:none; border-radius:var(--radius-sm);
    background:linear-gradient(135deg,var(--coral),var(--coral-dark)); color:#fff;
    font-weight:800; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; margin-top:6px;
    box-shadow:0 10px 24px -6px rgba(214,108,71,0.55);
  }
  .btn-primary:hover{ filter:brightness(1.04); }
  .btn-primary svg{ width:16px; height:16px; }
  .warn-strip{
    display:flex; gap:9px; background:var(--warning-soft); color:#8a5a1f;
    padding:11px 14px; border-radius:var(--radius-sm); font-size:11.5px; font-weight:600; margin-top:20px;
  }
  .warn-strip svg{ width:16px; height:16px; flex-shrink:0; color:var(--warning); }
  .switch-row{ text-align:center; margin-top:20px; font-size:13.5px; color:var(--muted); }
  .switch-row a{ color:var(--coral-dark); font-weight:800; text-decoration:none; }
  @media (max-width:1100px){
    .wrap{ justify-content:center; } .left{ text-align:center; order:1; }
    .feature-row{ justify-content:center; } .auth-panel{ order:0; }
  }
  @media (max-width:640px){
    .left{ display:none; } .card{ padding:28px 18px; } .field-row{ grid-template-columns:1fr; }
  }
</style>
</head>
<body>
<div class="topbar">
  <span class="logo"><span class="logo-mark">SC</span>Sipway English Accademy</span>
  <a href="lecturer-login.php" class="help-link">← Back to lecturer login</a>
</div>
<div class="wrap">
  <div class="left">
    <div class="illustration">
      <svg width="200" height="150" viewBox="0 0 230 170" fill="none">
        <rect x="0" y="10" width="140" height="100" rx="10" fill="#16385f"/>
        <rect x="10" y="20" width="120" height="80" rx="5" fill="#e9eef4"/>
        <circle cx="70" cy="60" r="22" fill="#3f6b9e"/>
        <rect x="30" y="90" width="80" height="14" rx="4" fill="#0f2a4a"/>
        <circle cx="190" cy="110" r="40" fill="#f0d9c8"/>
        <path d="M170 150 q20 -30 40 0" stroke="#e8825f" stroke-width="8" fill="none" stroke-linecap="round"/>
        <rect x="150" y="60" width="14" height="80" rx="7" fill="#e8825f"/>
      </svg>
    </div>
    <p class="brand-line">Join</p>
    <span class="brand-tag">Sipway Campus</span>
    <p class="headline">Become a lecturer and start teaching students across Sri Lanka</p>
    <p class="subline">Create your account below. An admin will review and approve it before you can log in.</p>
    <div class="feature-row">
      <span class="feature-chip"><span class="dot"></span>Flexible hours</span>
      <span class="feature-chip"><span class="dot"></span>Grow your reach</span>
    </div>
  </div>
  <div class="auth-panel">
    <div class="card">
      <div class="card-head">
        <span class="eyebrow-badge">New Account</span>
        <h1>Create Lecturer Account</h1>
        <p class="subtext">Lecturer login credentials හදන්න පහත form එක පුරවන්න.</p>
      </div>
      <?php if ($message): ?>
        <div class="alert-box <?php echo $isSuccess ? 'success' : 'error'; ?>">
          <span><?php echo htmlspecialchars($message); ?></span>
        </div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="field-row">
          <div class="field">
            <label>Full Name</label>
            <div class="input-shell">
              <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg></span>
              <input type="text" name="full_name" placeholder="e.g. Mr. Perera" required autofocus>
            </div>
          </div>
          <div class="field">
            <label>Gender</label>
            <div class="input-shell">
              <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M12 13v8M9 18h6"/></svg></span>
              <select name="gender" required>
                <option value="">Select...</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
        </div>
        <div class="field">
          <label>Email Address <span class="req">*</span></label>
          <div class="input-shell">
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l8.5 6L22 7"/></svg></span>
            <input type="email" name="email" placeholder="e.g. perera@sipway.edu.lk" required>
          </div>
        </div>
        <div class="field-row">
          <div class="field">
            <label>NIC Number</label>
            <div class="input-shell">
              <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h4M7 13h7"/></svg></span>
              <input type="text" name="nic" placeholder="e.g. 991234567V" required maxlength="12">
            </div>
          </div>
          <div class="field">
            <label>Phone Number</label>
            <div class="input-shell">
              <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
              <input type="tel" name="phone" placeholder="e.g. 0771234567" required maxlength="15">
            </div>
          </div>
        </div>

        <!-- ========== SUBJECT DROPDOWN (same source as lecturer-details.php) ========== -->
        <div class="field">
          <label>Subject</label>
          <div class="input-shell">
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
            <select name="subject" id="subjectSelect">
              <option value="">Select Subject / Type...</option>
              <!-- options loaded from subjects_api.php -->
            </select>
          </div>
          <p class="hint">Admin-managed subjects list එකෙන් තෝරන්න.</p>
        </div>

        <!-- LANGUAGE -->
        <div class="field">
          <label>Teaching Language</label>
          <div class="lang-select" id="langSelect">
            <button type="button" class="lang-select-trigger" id="langTrigger" aria-haspopup="listbox" aria-expanded="false">
              <span class="lang-current" id="langCurrent"><span>Loading...</span></span>
              <svg class="lang-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <ul class="lang-options" id="langOptions" role="listbox">
              <li class="lang-option-loading">Loading languages...</li>
            </ul>
          </div>
          <input type="hidden" id="language" name="language" value="en">
          <p class="hint">ඔබ classes ගන්නා ප්‍රධාන භාෂාව තෝරන්න.</p>
        </div>
        <div class="field">
          <label>Qualifications</label>
          <div class="input-shell">
            <span class="input-icon textarea-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/><path d="M6 12v5c0 1.5 2.5 3 6 3s6-1.5 6-3v-5"/></svg></span>
            <textarea name="qualifications" placeholder="e.g. BA in English (Hons), TESOL Certified"></textarea>
          </div>
        </div>
        <div class="field">
          <label>Lecturer Photo</label>
          <div class="file-input-wrap">
            <input type="file" id="photo" name="photo" accept="image/png,image/jpeg,image/webp">
          </div>
          <p class="hint">JPG, PNG, හෝ WEBP. Max 3MB.</p>
          <img id="photoPreview" class="photo-preview" alt="Preview">
        </div>
        <div class="field">
          <label>Username</label>
          <div class="input-shell">
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/></svg></span>
            <input type="text" name="username" placeholder="e.g. perera" required>
          </div>
        </div>
        <div class="field">
          <label>Password</label>
          <div class="input-shell">
            <span class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="toggle-pass" id="togglePw" aria-label="Show password">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eyeIcon"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn-primary">
          CREATE ACCOUNT
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        </button>
      </form>
      <div class="warn-strip">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <span>Admin approve කරගන්නකම් login වෙන්න බැහැ.</span>
      </div>
      <p class="switch-row">දැනටමත් account එකක් තියෙනවද? <a href="lecturer-login.php">Log In</a></p>
    </div>
  </div>
</div>
<script>
(function(){
  const DB_LANGUAGES = <?php echo json_encode($languagesFromDb, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  const togglePw = document.getElementById('togglePw');
  const pwInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');
  togglePw.addEventListener('click', () => {
    const show = pwInput.type === 'password';
    pwInput.type = show ? 'text' : 'password';
    eyeIcon.innerHTML = show
      ? '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.6 21.6 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.6 21.6 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/>'
      : '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>';
  });
  document.getElementById('photo').addEventListener('change', function(){
    const f = this.files[0];
    const prev = document.getElementById('photoPreview');
    if (f) {
      const r = new FileReader();
      r.onload = e => { prev.src = e.target.result; prev.style.display = 'block'; };
      r.readAsDataURL(f);
    } else prev.style.display = 'none';
  });

  // ========== SUBJECT DROPDOWN (loads from subjects_api.php) ==========
  (function loadSubjects() {
    const select = document.getElementById('subjectSelect');
    if (!select) return;

    fetch('subjects_api.php')
      .then(res => res.json())
      .then(data => {
        if (!data.success || !Array.isArray(data.data)) return;
        data.data.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.name;
          opt.textContent = s.name;
          select.appendChild(opt);
        });
      })
      .catch(err => {
        console.error('Subject list load failed:', err);
      });
  })();

  // ===== FLAGS (Italy, Arabic, Tamil included) =====
  const FLAG_SVGS = {
    GB:'<rect width="60" height="40" fill="#012169"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#fff" stroke-width="10"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#C8102E" stroke-width="6"/><path d="M30,0 V40 M0,20 H60" stroke="#fff" stroke-width="16"/><path d="M30,0 V40 M0,20 H60" stroke="#C8102E" stroke-width="10"/>',
    DE:'<rect width="60" height="13.34" y="0" fill="#000"/><rect width="60" height="13.33" y="13.33" fill="#DD0000"/><rect width="60" height="13.33" y="26.67" fill="#FFCE00"/>',
    FR:'<rect width="20" height="40" x="0" fill="#002395"/><rect width="20" height="40" x="20" fill="#fff"/><rect width="20" height="40" x="40" fill="#ED2939"/>',
    CN:'<rect width="60" height="40" fill="#DE2910"/><polygon points="10,6 11.8,11.5 17.5,11.5 12.8,14.8 14.5,20.2 10,17 5.5,20.2 7.2,14.8 2.5,11.5 8.2,11.5" fill="#FFDE00"/>',
    JP:'<rect width="60" height="40" fill="#fff"/><circle cx="30" cy="20" r="12" fill="#BC002D"/>',
    IN:'<rect width="60" height="13.34" y="0" fill="#FF9933"/><rect width="60" height="13.33" y="13.33" fill="#fff"/><rect width="60" height="13.33" y="26.67" fill="#138808"/><circle cx="30" cy="20" r="4.5" fill="none" stroke="#000080" stroke-width="1"/><circle cx="30" cy="20" r="1" fill="#000080"/>',
    RU:'<rect width="60" height="13.34" y="0" fill="#fff"/><rect width="60" height="13.33" y="13.33" fill="#0039A6"/><rect width="60" height="13.33" y="26.67" fill="#D52B1E"/>',
    SA:'<rect width="60" height="40" fill="#006C35"/><rect x="8" y="26" width="36" height="3" fill="#fff"/><polygon points="48,24.5 56,27.5 48,30.5" fill="#fff"/>',
    LK:'<rect width="60" height="40" fill="#FFB714"/><rect x="0" y="0" width="10" height="40" fill="#8D153A"/><rect x="10" y="0" width="8" height="40" fill="#00534E"/><rect x="20" y="4" width="36" height="32" fill="#8D153A"/>',
    IT:'<rect width="20" height="40" x="0" fill="#009246"/><rect width="20" height="40" x="20" fill="#FFF"/><rect width="20" height="40" x="40" fill="#CE2B37"/>',
    DEFAULT:'<rect width="60" height="40" fill="#e6e2da"/><circle cx="30" cy="20" r="12" fill="none" stroke="#8a93a3" stroke-width="2"/>'
  };
  const LANG_TO_FLAG = {
    en:'GB', de:'DE', zh:'CN', ja:'JP', fr:'FR', hi:'IN', ru:'RU',
    ar:'SA', ta:'IN', si:'LK', it:'IT',
    arabic:'SA', tamil:'IN', chinese:'CN', japanese:'JP', french:'FR',
    hindi:'IN', russian:'RU', german:'DE', english:'GB', italian:'IT',
    sinhala:'LK'
  };
  function norm(c){ return String(c||'').trim().toLowerCase(); }
  function resolveFlag(lang){
    let f = String(lang.flag||'').trim().toUpperCase();
    if (f && FLAG_SVGS[f]) return f;
    const c = norm(lang.code);
    if (LANG_TO_FLAG[c] && FLAG_SVGS[LANG_TO_FLAG[c]]) return LANG_TO_FLAG[c];
    const l = norm(lang.label);
    if (LANG_TO_FLAG[l] && FLAG_SVGS[LANG_TO_FLAG[l]]) return LANG_TO_FLAG[l];
    return 'DEFAULT';
  }
  function flagHtml(code){
    const key = String(code||'DEFAULT').toUpperCase();
    const inner = FLAG_SVGS[key] || FLAG_SVGS.DEFAULT;
    return '<svg class="lang-flag" viewBox="0 0 60 40" width="22" height="16" xmlns="http://www.w3.org/2000/svg">' + inner + '</svg>';
  }
  const langSelect = document.getElementById('langSelect');
  const langTrigger = document.getElementById('langTrigger');
  const langOptions = document.getElementById('langOptions');
  const langCurrent = document.getElementById('langCurrent');
  const languageInput = document.getElementById('language');
  function openDD(){ langOptions.classList.add('open'); langTrigger.classList.add('open'); langTrigger.setAttribute('aria-expanded','true'); }
  function closeDD(){ langOptions.classList.remove('open'); langTrigger.classList.remove('open'); langTrigger.setAttribute('aria-expanded','false'); }
  langTrigger.addEventListener('click', e => {
    e.stopPropagation();
    langOptions.classList.contains('open') ? closeDD() : openDD();
  });
  document.addEventListener('click', e => { if (!langSelect.contains(e.target)) closeDD(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDD(); });
  function selectOpt(li){
    langOptions.querySelectorAll('.lang-option').forEach(o => {
      o.classList.remove('selected');
      o.setAttribute('aria-selected','false');
    });
    li.classList.add('selected');
    li.setAttribute('aria-selected','true');
    langCurrent.innerHTML = flagHtml(li.dataset.flag) + '<span>' + li.dataset.label + '</span>';
    languageInput.value = norm(li.dataset.value);
  }
  function buildOpt(lang, isDefault){
    const flag = resolveFlag(lang);
    const code = norm(lang.code);
    const li = document.createElement('li');
    li.className = 'lang-option' + (isDefault ? ' selected' : '');
    li.setAttribute('role','option');
    li.setAttribute('aria-selected', isDefault ? 'true' : 'false');
    li.dataset.value = code;
    li.dataset.flag = flag;
    li.dataset.label = lang.label || code;
    li.innerHTML =
      '<span class="lang-option-left">' + flagHtml(flag) + '<span>' + (lang.label || code) + '</span></span>' +
      '<svg class="lang-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
    li.addEventListener('click', () => { selectOpt(li); closeDD(); });
    return li;
  }
  function initLangs(list){
    langOptions.innerHTML = '';
    if (!list || !list.length) {
      langOptions.innerHTML = '<li class="lang-option-loading">No languages found</li>';
      langCurrent.innerHTML = flagHtml('GB') + '<span>English</span>';
      languageInput.value = 'en';
      return;
    }
    const def = list.find(l => norm(l.code) === 'en') || list[0];
    list.forEach(lang => {
      langOptions.appendChild(buildOpt(lang, norm(lang.code) === norm(def.code)));
    });
    langCurrent.innerHTML = flagHtml(resolveFlag(def)) + '<span>' + (def.label || def.code) + '</span>';
    languageInput.value = norm(def.code);
  }
  if (DB_LANGUAGES && DB_LANGUAGES.length) {
    initLangs(DB_LANGUAGES);
  } else {
    fetch('get_languages.php')
      .then(r => r.json())
      .then(d => {
        const list = (d.languages || d.data || []).map(l => ({
          code: norm(l.code),
          label: l.label || l.code,
          flag: String(l.flag||'').toUpperCase()
        }));
        initLangs(list);
      })
      .catch(() => initLangs([{code:'en', label:'English', flag:'GB'}]));
  }
})();
</script>
</body>
</html>