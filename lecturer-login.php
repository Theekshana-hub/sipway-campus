<?php
session_start();
require_once __DIR__ . '/db.php';

// Note: auto-redirect to dashboard ඉවත් කළා —
// login page එක හැමවෙලේම පේන්න ඕන, පරණ session එකක් active උනත්.
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        // ✅ FIX: status column එකත් select කරලා login logic එකේදී check කරනවා
        $stmt = $conn->prepare("SELECT id, full_name, subject, password, status FROM lecturers WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {

                // ✅ FIX: Admin approve කරලා නැති (pending) හෝ reject කරපු
                // account එකකින් login වෙන්න ඉඩ දෙන්නේ නෑ
                if ($row['status'] === 'pending') {
                    $error = 'ඔබේ account එක තාම admin approve කරලා නෑ. කරුණාකර ටිකක් ඉන්න.';
                } elseif ($row['status'] === 'rejected') {
                    $error = 'ඔබේ account එක admin විසින් reject කරලා තියෙනවා. Admin අමතන්න.';
                } else {
                    // status = 'approved'
                    $_SESSION['lecturer_id']      = $row['id'];
                    $_SESSION['lecturer_name']    = $row['full_name'];
                    $_SESSION['lecturer_subject'] = $row['subject'];
                    header("Location: lecturer-dashboard.php");
                    exit();
                }
            } else {
                $error = 'වැරදි password එකක්';
            }
        } else {
            $error = 'Username එක සොයාගත නොහැක';
        }
        $stmt->close();
    } else {
        $error = 'Username සහ Password දෙකම දෙන්න';
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lecturer Login - Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy:#0f2a4a;
    --navy-2:#16385f;
    --navy-soft:#eef2f7;
    --coral:#e8825f;
    --coral-dark:#d66c47;
    --coral-soft:#fdece5;
    --bg:#f6f5f3;
    --card:#ffffff;
    --line:#e6e2da;
    --line-soft:#f0ede7;
    --muted:#6b7280;
    --muted-2:#8a93a3;
    --text:#1b2430;
    --danger:#c0392b;
    --danger-soft:#fdecea;
    --success:#1f9d55;
    --radius-lg:16px;
    --radius-md:10px;
    --radius-sm:8px;
    --shadow-card:0 24px 60px -12px rgba(15,42,74,0.14), 0 2px 8px rgba(15,42,74,0.06);
    --ease:cubic-bezier(.4,0,.2,1);
  }

  *{ box-sizing:border-box; }
  html{ -webkit-text-size-adjust:100%; }
  body{
    margin:0;
    font-family:'Inter', 'Noto Sans Sinhala', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    background:
      radial-gradient(1100px 500px at 85% -10%, rgba(232,130,95,0.08), transparent),
      radial-gradient(900px 500px at -5% 110%, rgba(15,42,74,0.05), transparent),
      var(--bg);
    color:var(--text);
    min-height:100vh;
    -webkit-font-smoothing:antialiased;
  }
  ::selection{ background:var(--coral-soft); color:var(--coral-dark); }

  .topbar{
    height:60px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 clamp(16px, 4vw, 40px);
    background:rgba(255,255,255,0.85);
    backdrop-filter:saturate(180%) blur(10px);
    border-bottom:1px solid var(--line-soft);
    position:sticky;
    top:0;
    z-index:40;
  }
  .topbar .logo{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:800;
    color:var(--navy);
    letter-spacing:0.2px;
    font-size:15px;
  }
  .logo-mark{
    width:30px; height:30px;
    border-radius:8px;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:13px; font-weight:800;
    flex-shrink:0;
  }
  .topbar .help-link{
    font-size:13px;
    color:var(--muted);
    text-decoration:none;
    font-weight:600;
  }
  .topbar .help-link:hover{ color:var(--navy); }

  .wrap{
    min-height:calc(100vh - 60px);
    display:flex;
    align-items:center;
    justify-content:center;
    gap:clamp(32px, 6vw, 96px);
    padding:clamp(28px, 5vw, 64px) clamp(16px, 4vw, 24px);
    flex-wrap:wrap;
  }

  .left{
    max-width:440px;
    width:100%;
    flex:1 1 380px;
  }
  .illustration{
    position:relative;
    display:flex;
    align-items:flex-end;
    gap:14px;
    margin-bottom:32px;
    filter:drop-shadow(0 18px 30px rgba(15,42,74,0.10));
  }
  .illustration svg{ display:block; max-width:100%; height:auto; }

  .brand-line{
    font-size:14px;
    font-weight:600;
    color:var(--muted);
    margin:0 0 8px 0;
  }
  .brand-tag{
    display:inline-flex;
    align-items:center;
    background:linear-gradient(135deg, #e63b3b, #c92a2a);
    color:#fff;
    font-weight:800;
    padding:5px 14px;
    border-radius:6px;
    font-size:16px;
    letter-spacing:0.2px;
    box-shadow:0 6px 16px rgba(201,42,42,0.28);
  }
  .headline{
    font-size:clamp(22px, 2.6vw, 30px);
    font-weight:800;
    color:var(--text);
    line-height:1.35;
    margin:22px 0 0 0;
    letter-spacing:-0.3px;
  }
  .feature-row{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:24px;
  }
  .feature-chip{
    display:flex;
    align-items:center;
    gap:6px;
    background:#fff;
    border:1px solid var(--line);
    padding:8px 14px;
    border-radius:999px;
    font-size:12.5px;
    font-weight:600;
    color:var(--navy-2);
  }
  .feature-chip .dot{
    width:6px; height:6px; border-radius:50%;
    background:var(--coral);
    flex-shrink:0;
  }

  .auth-panel{
    width:420px;
    max-width:100%;
    flex:0 1 420px;
  }
  .card{
    background:var(--card);
    border-radius:var(--radius-lg);
    padding:clamp(28px, 4vw, 44px) clamp(24px, 4vw, 40px);
    box-shadow:var(--shadow-card);
    border:1px solid var(--line-soft);
    animation:cardIn .45s var(--ease);
  }
  @keyframes cardIn{
    from{ opacity:0; transform:translateY(10px); }
    to{ opacity:1; transform:translateY(0); }
  }

  .card-head{ text-align:center; margin-bottom:26px; }
  .eyebrow-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:var(--coral-soft);
    color:var(--coral-dark);
    font-weight:800;
    font-size:11px;
    letter-spacing:0.6px;
    padding:5px 12px;
    border-radius:999px;
    margin-bottom:10px;
    text-transform:uppercase;
  }
  .card h1{
    font-size:clamp(24px, 3vw, 30px);
    color:var(--navy);
    margin:0 0 4px 0;
    font-weight:800;
    letter-spacing:-0.4px;
  }
  .subtext{
    color:var(--muted);
    font-size:14px;
    line-height:1.65;
    margin:0;
  }

  .error-box{
    display:flex;
    align-items:center;
    gap:9px;
    background:var(--danger-soft);
    color:var(--danger);
    padding:11px 14px;
    border-radius:var(--radius-sm);
    font-size:13px;
    font-weight:700;
    margin-bottom:20px;
  }
  .error-box svg{ width:16px; height:16px; flex-shrink:0; }

  .field{ margin-bottom:16px; position:relative; }
  .field label{
    display:block;
    font-size:12.5px;
    color:var(--text);
    margin-bottom:7px;
    font-weight:600;
  }
  .input-shell{
    position:relative;
    display:flex;
    align-items:center;
  }
  .input-icon{
    position:absolute;
    left:14px;
    width:18px; height:18px;
    color:var(--muted-2);
    pointer-events:none;
    display:flex;
    flex-shrink:0;
  }
  .field input{
    width:100%;
    padding:13px 14px 13px 40px;
    border-radius:var(--radius-sm);
    border:1.5px solid var(--line);
    font-size:14.5px;
    font-family:inherit;
    outline:none;
    background:#fbfaf9;
    color:var(--text);
    transition:border-color .15s var(--ease), box-shadow .15s var(--ease), background .15s var(--ease);
  }
  .field input::placeholder{ color:var(--muted-2); }
  .field input:hover{ border-color:#d7d2c8; }
  .field input:focus{
    border-color:var(--coral);
    background:#fff;
    box-shadow:0 0 0 4px rgba(232,130,95,0.14);
  }

  .toggle-pass{
    position:absolute;
    right:12px;
    background:none;
    border:none;
    cursor:pointer;
    color:var(--muted-2);
    padding:6px;
    display:flex;
    align-items:center;
    border-radius:6px;
  }
  .toggle-pass:hover{ color:var(--navy-2); background:var(--navy-soft); }
  .toggle-pass svg{ width:18px; height:18px; }

  .btn-primary{
    width:100%;
    padding:14.5px;
    border:none;
    border-radius:var(--radius-sm);
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff;
    font-weight:800;
    font-size:14px;
    letter-spacing:0.4px;
    cursor:pointer;
    transition:transform .12s var(--ease), box-shadow .2s var(--ease), filter .15s;
    box-shadow:0 10px 24px -6px rgba(214,108,71,0.55);
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    margin-top:6px;
  }
  .btn-primary:hover{ filter:brightness(1.04); box-shadow:0 14px 28px -6px rgba(214,108,71,0.6); }
  .btn-primary:active{ transform:translateY(1px) scale(.995); }
  .btn-primary svg{ width:16px; height:16px; }

  .switch-row{
    text-align:center;
    margin-top:26px;
    font-size:13.5px;
    color:var(--muted);
  }
  .switch-row a{
    color:var(--coral-dark);
    font-weight:800;
    text-decoration:none;
  }
  .switch-row a:hover{ text-decoration:underline; }

  a:focus-visible, button:focus-visible, input:focus-visible{
    outline:2.5px solid var(--coral);
    outline-offset:2px;
  }

  @media (max-width:1000px){
    .wrap{ justify-content:center; }
    .left{ text-align:center; order:1; }
    .illustration{ justify-content:center; }
    .feature-row{ justify-content:center; }
    .auth-panel{ order:0; }
  }
  @media (max-width:640px){
    .topbar{ padding:0 16px; }
    .wrap{ padding:20px 14px 40px; gap:28px; }
    .left{ display:none; }
    .card{ padding:30px 20px; border-radius:14px; }
    .card h1{ font-size:23px; }
  }
  @media (min-width:641px) and (max-width:1000px){
    .headline{ font-size:22px; }
    .illustration svg{ width:190px; height:auto; }
  }

  @media (prefers-reduced-motion: reduce){
    *{ animation-duration:0.001ms !important; transition-duration:0.001ms !important; }
  }
</style>
</head>
<body>

<div class="topbar">
  <span class="logo"><span class="logo-mark">SC</span>Sipway English Accademy</span>
  
</div>

<div class="wrap">

  <div class="left">
    <div class="illustration">
      <svg width="230" height="170" viewBox="0 0 230 170" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="0" y="10" width="140" height="100" rx="10" fill="#16385f"/>
        <rect x="10" y="20" width="120" height="80" rx="5" fill="#e9eef4"/>
        <circle cx="70" cy="60" r="22" fill="#3f6b9e"/>
        <rect x="30" y="90" width="80" height="14" rx="4" fill="#0f2a4a"/>
        <circle cx="190" cy="110" r="40" fill="#f0d9c8"/>
        <path d="M170 150 q20 -30 40 0" stroke="#e8825f" stroke-width="8" fill="none" stroke-linecap="round"/>
        <rect x="150" y="60" width="14" height="80" rx="7" fill="#e8825f"/>
      </svg>
    </div>
    <p class="brand-line">Teach on</p>
    <span class="brand-tag">Sipway Campus</span>
    <p class="headline">Manage your teaching schedule and grow your student base</p>
    <div class="feature-row">
      <span class="feature-chip"><span class="dot"></span>Set availability</span>
      <span class="feature-chip"><span class="dot"></span>Live sessions</span>
      <span class="feature-chip"><span class="dot"></span>Track bookings</span>
    </div>
  </div>

  <div class="auth-panel">
    <div class="card" id="loginCard">
      <div class="card-head">
        <span class="eyebrow-badge">Lecturer Portal</span>
        <h1>Welcome back</h1>
        <p class="subtext">ඔබේ availability schedule එක manage කරන්න login වන්න.</p>
      </div>

      <?php if ($error): ?>
        <div class="error-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="field">
          <label for="username">Username</label>
          <div class="input-shell">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            </span>
            <input type="text" id="username" name="username" placeholder="e.g. perera" autocomplete="username" required autofocus>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-shell">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            </span>
            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="toggle-pass" id="togglePw" aria-label="Show password" tabindex="-1">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eyeIcon"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-primary">
          LOG IN
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>

      <p class="switch-row">Account එකක් නැද්ද? <a href="lecturer-register.php">Register new Account</a></p>
    </div>
  </div>
</div>

<script>
  const togglePw = document.getElementById('togglePw');
  const pwInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');

  togglePw.addEventListener('click', () => {
    const isPassword = pwInput.type === 'password';
    pwInput.type = isPassword ? 'text' : 'password';
    eyeIcon.innerHTML = isPassword
      ? '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.6 21.6 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.6 21.6 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/>'
      : '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>';
    togglePw.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
  });
</script>

</body>
</html>