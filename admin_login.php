<?php
session_start();
require_once 'db.php';

/* ==========================================================
   ADMIN LOGIN — SERVER-SIDE SESSION CHECK
   -----------------------------------------------------------
   Credentials mekath dan hard-code karala thiyenne (kalin JS
   ekema thibba widihatama). Ithin ekath danata wenas welak nathi
   witharai — dan check eka PHP eken, server side, sidu wenawa.
   Egyanma $_SESSION['admin_id'] eka real widihata set wenawa,
   ithin chat_api.php ekatath, admin_chat.php ekatath egollo
   dennama SAME session eka penenawa.

   Production ekakata giyoth: admin username/password DB table
   ekaka thiyala, password_hash()/password_verify() use karala
   check karanna. Dan therenna witharak simple widihata thiyenne.
   ============================================================ */
const ADMIN_USERNAME = "admin";
const ADMIN_PASSWORD = "Admin@123";
const ADMIN_ID       = 1;      // must match the '1' hardcoded as admin id in chat_api.php
const ADMIN_NAME     = "Admin";

// If already logged in, skip straight to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin-dashboard.html');
    exit;
}

// Handle the login POST (AJAX) request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        // Regenerate session id on login to avoid session fixation
        session_regenerate_id(true);

        $_SESSION['admin_id']        = ADMIN_ID;
        $_SESSION['admin_name']      = ADMIN_NAME;
        $_SESSION['admin_logged_in'] = true;

        // Make sure this browser isn't also carrying a stale student session
        unset($_SESSION['student_id'], $_SESSION['student_name']);

        echo json_encode(['success' => true, 'redirect' => 'admin-dashboard.html']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect admin username or password.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sipway Campus - Admin Login</title>
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
      radial-gradient(1100px 500px at 85% -10%, rgba(15,42,74,0.10), transparent),
      radial-gradient(900px 500px at -5% 110%, rgba(15,42,74,0.06), transparent),
      var(--bg);
    color:var(--text);
    min-height:100vh;
    -webkit-font-smoothing:antialiased;
  }
  ::selection{ background:var(--navy-soft); color:var(--navy-2); }

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
    gap:6px;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    color:#fff;
    font-weight:800;
    padding:5px 14px;
    border-radius:6px;
    font-size:16px;
    letter-spacing:0.2px;
    box-shadow:0 6px 16px rgba(15,42,74,0.3);
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
    background:var(--navy-2);
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
  .admin-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:var(--navy-soft);
    color:var(--navy-2);
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
    border-color:var(--navy-2);
    background:#fff;
    box-shadow:0 0 0 4px rgba(15,56,95,0.14);
  }
  .field input.invalid{
    border-color:var(--danger);
    background:var(--danger-soft);
  }
  .field input.invalid:focus{
    box-shadow:0 0 0 4px rgba(192,57,43,0.12);
  }
  .field input.valid{
    border-color:var(--success);
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

  .error{
    font-size:12px;
    color:var(--danger);
    margin-top:6px;
    display:none;
    align-items:center;
    gap:5px;
    font-weight:500;
  }
  .error.show{ display:flex; animation:shake .3s var(--ease); }
  @keyframes shake{
    0%,100%{ transform:translateX(0); }
    25%{ transform:translateX(-3px); }
    75%{ transform:translateX(3px); }
  }

  .row-inline{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:2px 0 20px 0;
    flex-wrap:wrap;
    gap:8px;
  }
  .checkbox-label{
    font-size:13px;
    color:var(--muted);
    display:flex;
    align-items:center;
    gap:7px;
    font-weight:500;
    cursor:pointer;
    user-select:none;
  }
  .checkbox-label input{
    width:16px; height:16px;
    accent-color:var(--navy-2);
    cursor:pointer;
  }

  .btn-primary{
    width:100%;
    padding:14.5px;
    border:none;
    border-radius:var(--radius-sm);
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    color:#fff;
    font-weight:800;
    font-size:14px;
    letter-spacing:0.4px;
    cursor:pointer;
    transition:transform .12s var(--ease), box-shadow .2s var(--ease), filter .15s;
    box-shadow:0 10px 24px -6px rgba(15,42,74,0.45);
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
  }
  .btn-primary:hover{ filter:brightness(1.06); box-shadow:0 14px 28px -6px rgba(15,42,74,0.55); }
  .btn-primary:active{ transform:translateY(1px) scale(.995); }
  .btn-primary:disabled{ opacity:.7; cursor:not-allowed; }
  .btn-primary .spinner{
    width:16px; height:16px;
    border:2px solid rgba(255,255,255,0.4);
    border-top-color:#fff;
    border-radius:50%;
    animation:spin .7s linear infinite;
    display:none;
  }
  .btn-primary.loading .spinner{ display:inline-block; }
  .btn-primary.loading .btn-text{ opacity:0.85; }
  @keyframes spin{ to{ transform:rotate(360deg); } }

  .switch-row{
    text-align:center;
    margin-top:26px;
    font-size:13.5px;
    color:var(--muted);
  }
  .switch-row a{
    color:var(--navy-2);
    font-weight:800;
    text-decoration:none;
  }
  .switch-row a:hover{ text-decoration:underline; }

  a:focus-visible, button:focus-visible, input:focus-visible{
    outline:2.5px solid var(--navy-2);
    outline-offset:2px;
  }

  .toast{
    position:fixed;
    top:20px;
    left:50%;
    transform:translateX(-50%) translateY(-16px);
    background:var(--navy);
    color:#fff;
    padding:13px 22px;
    border-radius:10px;
    font-size:13.5px;
    font-weight:600;
    opacity:0;
    pointer-events:none;
    transition:opacity .25s var(--ease), transform .25s var(--ease);
    z-index:50;
    box-shadow:0 12px 30px rgba(15,42,74,0.3);
    display:flex;
    align-items:center;
    gap:10px;
    max-width:90vw;
  }
  .toast.show{
    opacity:1;
    transform:translateX(-50%) translateY(0);
  }
  .toast.error-toast{ background:var(--danger); }

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
        <rect x="0" y="10" width="140" height="100" rx="10" fill="#0f2a4a"/>
        <rect x="10" y="20" width="120" height="80" rx="5" fill="#e9eef4"/>
        <path d="M70 40 L88 50 V70 C88 82 80 90 70 94 C60 90 52 82 52 70 V50 Z" fill="#16385f"/>
        <path d="M62 68 L68 74 L80 60" stroke="#fff" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        <rect x="30" y="90" width="80" height="14" rx="4" fill="#16385f"/>
        <circle cx="190" cy="110" r="40" fill="#e9eef4"/>
        <path d="M170 150 q20 -26 40 0" stroke="#16385f" stroke-width="8" fill="none" stroke-linecap="round"/>
        <rect x="150" y="60" width="14" height="80" rx="7" fill="#16385f"/>
      </svg>
    </div>
    <p class="brand-line">Control panel for</p>
    <span class="brand-tag">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l8 4v6c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-4z"/></svg>
      Sipway Campus Admin
    </span>
    <p class="headline">Manage students, lecturers and sessions from one dashboard</p>
    <div class="feature-row">
      <span class="feature-chip"><span class="dot"></span>Bookings</span>
      <span class="feature-chip"><span class="dot"></span>Packages</span>
      <span class="feature-chip"><span class="dot"></span>Lecturers</span>
    </div>
  </div>

  <div class="auth-panel">
    <div class="card" id="adminCard">
      <div class="card-head">
        <span class="admin-badge">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l8 4v6c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-4z"/></svg>
          Admin Panel
        </span>
        <h1>Admin Login</h1>
        <p class="subtext">Administrator access only. සුදුසුකම් නොමැති පුද්ගලයන් ඇතුළත් වීම තහනම්.</p>
      </div>

      <form id="adminForm" novalidate>
        <div class="field">
          <label for="adminUser">Username</label>
          <div class="input-shell">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            </span>
            <input type="text" id="adminUser" placeholder="Admin username" autocomplete="username" required>
          </div>
          <div class="error" id="adminUserErr">⚠ Please enter the admin username.</div>
        </div>

        <div class="field">
          <label for="adminPass">Password</label>
          <div class="input-shell">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            </span>
            <input type="password" id="adminPass" placeholder="Admin password" autocomplete="current-password" required>
            <button type="button" class="toggle-pass" data-target="adminPass" aria-label="Show password">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="error" id="adminPassErr">⚠ Incorrect admin credentials.</div>
        </div>

        <div class="row-inline">
          <label class="checkbox-label"><input type="checkbox" id="adminRemember"> Remember me on this device</label>
        </div>

        <button type="submit" class="btn-primary" id="adminBtn">
          <span class="spinner"></span>
          <span class="btn-text">ADMIN LOGIN</span>
        </button>
      </form>


    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
  const toast = document.getElementById('toast');

  function showToast(msg, isError){
    toast.textContent = (isError ? '⚠ ' : '✓ ') + msg;
    toast.classList.toggle('error-toast', !!isError);
    toast.classList.add('show');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(()=> toast.classList.remove('show'), 2600);
  }

  function setFieldState(input, errEl, valid){
    input.classList.toggle('invalid', !valid);
    input.classList.toggle('valid', valid);
    errEl.classList.toggle('show', !valid);
  }

  document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      const isPass = target.type === 'password';
      target.type = isPass ? 'text' : 'password';
      btn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
    });
  });

  /* ==========================================================
     ADMIN LOGIN — now goes through the server (PHP session)
     instead of only localStorage, so chat_api.php / admin_chat.php
     can actually see who is logged in.
     ============================================================ */
  const adminUser = document.getElementById('adminUser');
  const adminPass = document.getElementById('adminPass');
  const adminForm = document.getElementById('adminForm');
  const adminBtn = document.getElementById('adminBtn');

  adminUser.addEventListener('input', () => {
    if(adminUser.value.length) setFieldState(adminUser, document.getElementById('adminUserErr'), adminUser.value.trim().length > 0);
  });
  adminPass.addEventListener('input', () => {
    if(adminPass.value.length) setFieldState(adminPass, document.getElementById('adminPassErr'), adminPass.value.length > 0);
  });

  adminForm.addEventListener('submit', async function(e){
    e.preventDefault();
    let ok = true;

    const userOk = adminUser.value.trim().length > 0;
    setFieldState(adminUser, document.getElementById('adminUserErr'), userOk);
    if(!userOk) ok = false;

    const passOk = adminPass.value.length > 0;
    setFieldState(adminPass, document.getElementById('adminPassErr'), passOk);
    if(!passOk) ok = false;

    if(!ok) return;

    adminBtn.classList.add('loading');
    adminBtn.disabled = true;

    try {
      const res = await fetch('admin_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          username: adminUser.value.trim(),
          password: adminPass.value
        })
      });
      const data = await res.json();

      if (data.success) {
        showToast('Admin login successful! Redirecting...');
        adminForm.reset();
        [adminUser, adminPass].forEach(i => i.classList.remove('valid','invalid'));
        setTimeout(() => { window.location.href = data.redirect || 'admin-dashboard.html'; }, 500);
      } else {
        document.getElementById('adminPassErr').textContent = '⚠ ' + (data.message || 'Incorrect admin username or password.');
        setFieldState(adminUser, document.getElementById('adminUserErr'), false);
        setFieldState(adminPass, document.getElementById('adminPassErr'), false);
        showToast(data.message || 'Incorrect admin username or password.', true);
        adminBtn.classList.remove('loading');
        adminBtn.disabled = false;
      }
    } catch (err) {
      showToast('Server error. Please try again.', true);
      adminBtn.classList.remove('loading');
      adminBtn.disabled = false;
    }
  });
})();
</script>

</body>
</html>