<?php
// ==================== SHARED HEADER (CSS + Topbar + Sidebar) ====================
// Expects $activePage to be set before include, one of:
// 'dashboard' | 'packages' | 'progress' | 'purchase-history' | 'faq-support' | 'class-details'
// Expects $pageTitle to be set (shown as the <h1 class="page-title">).
// Expects student_auth.php to already have run ($firstName, $fullNameSafe available).

$activePage = $activePage ?? '';
$pageTitle  = $pageTitle ?? 'Dashboard';

function navClass($key, $activePage) {
    return $key === $activePage ? 'nav-item active' : 'nav-item';
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?> - Sipway Campus</title>
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
    --amber:#faedcb;
    --amber-line:#f0d9a0;
    --success:#1f9d55;
    --success-soft:#e8f8ee;
    --radius-lg:16px;
    --radius-md:10px;
    --radius-sm:8px;
    --shadow-card:0 8px 24px -8px rgba(15,42,74,0.10);
    --ease:cubic-bezier(.4,0,.2,1);
  }
  *{ box-sizing:border-box; }
  body{
    margin:0;
    font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    -webkit-font-smoothing:antialiased;
  }
  a{ color:inherit; }

  .topbar{
    height:64px; display:flex; align-items:center; gap:16px; padding:0 20px;
    background:#fff; border-bottom:1px solid var(--line-soft);
    position:sticky; top:0; z-index:50;
  }
  .burger{ background:none; border:none; cursor:pointer; padding:8px; display:flex; border-radius:8px; color:var(--navy); flex-shrink:0; }
  .burger:hover{ background:var(--navy-soft); }
  .burger svg{ width:22px; height:22px; }

  .logo{ display:flex; align-items:center; gap:10px; font-weight:800; color:var(--navy); font-size:18px; letter-spacing:-0.2px; text-decoration:none; flex-shrink:0; }
  .logo-mark{ width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg, var(--coral), var(--coral-dark)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:14px; font-weight:800; }

  .top-links{ margin-left:auto; display:flex; align-items:center; gap:28px; }
  .top-links a{ font-size:13.5px; font-weight:600; color:var(--muted); text-decoration:none; white-space:nowrap; }
  .top-links a:hover{ color:var(--navy); }

  .user-menu{ display:flex; align-items:center; gap:8px; cursor:pointer; padding:6px 10px; border-radius:999px; position:relative; }
  .user-menu:hover{ background:var(--navy-soft); }
  .avatar{ width:30px; height:30px; border-radius:50%; background:var(--navy-soft); display:flex; align-items:center; justify-content:center; color:var(--navy-2); flex-shrink:0; }
  .avatar svg{ width:18px; height:18px; }
  .user-menu .chev{ width:14px; height:14px; color:var(--muted); }
  .user-name{ font-size:13.5px; font-weight:700; color:var(--text); }

  .dropdown{ position:absolute; top:calc(100% + 8px); right:0; background:#fff; border:1px solid var(--line-soft); border-radius:var(--radius-md); box-shadow:0 16px 40px -8px rgba(15,42,74,0.18); min-width:180px; padding:6px; display:none; z-index:60; }
  .dropdown.show{ display:block; }
  .dropdown a{ display:block; padding:10px 12px; font-size:13.5px; font-weight:600; border-radius:8px; text-decoration:none; color:var(--text); }
  .dropdown a:hover{ background:var(--navy-soft); }
  .dropdown a.danger{ color:#c0392b; }

  .shell{ display:flex; min-height:calc(100vh - 64px); }

  .sidebar{
    width:270px; flex-shrink:0; background:#fff; border-right:1px solid var(--line-soft);
    padding:20px 16px; display:flex; flex-direction:column; gap:4px;
    position:sticky; top:64px; align-self:flex-start; height:calc(100vh - 64px);
    overflow-y:auto; transition:transform .25s var(--ease);
  }
  .nav-item{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:var(--radius-sm); font-weight:700; font-size:14px; color:var(--navy-2); text-decoration:none; cursor:pointer; }
  .nav-item svg{ width:19px; height:19px; flex-shrink:0; }
  .nav-item:hover{ background:var(--navy-soft); }
  .nav-item.active{ background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff; box-shadow:0 8px 18px -4px rgba(214,108,71,0.4); }

  .side-divider{ height:1px; background:var(--line-soft); margin:14px 6px; }

  .side-link{ display:block; padding:11px 14px; font-size:13.5px; font-weight:600; color:var(--muted); text-decoration:none; border-radius:var(--radius-sm); }
  .side-link:hover{ background:var(--navy-soft); color:var(--navy-2); }
  .side-link.active{ background:var(--navy-soft); color:var(--navy-2); }

  .backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.35); z-index:45; }
  .backdrop.show{ display:block; }

  .main{ flex:1; padding:28px clamp(16px, 3vw, 32px) 48px; min-width:0; }
  .page-title{ font-size:26px; font-weight:800; color:var(--text); margin:0 0 22px 0; letter-spacing:-0.3px; }

  .panel{ background:var(--card); border:1px solid var(--line-soft); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); padding:26px; margin-bottom:22px; }
  .panel h2{ font-size:17px; font-weight:800; margin:0 0 20px 0; color:var(--text); }
  .panel-subtitle{ font-size:12px; color:var(--muted); font-weight:600; margin:-14px 0 18px 0; }

  .empty-state{ display:flex; flex-direction:column; align-items:center; text-align:center; padding:30px 6px 10px; }
  .empty-state .msg{ font-size:15px; font-weight:700; color:var(--text); margin:0 0 18px 0; }
  .empty-state .sub{ font-size:12.5px; color:var(--muted); margin:-12px 0 18px 0; }

  .btn-primary{
    padding:12px 26px; border:none; border-radius:var(--radius-sm);
    background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff;
    font-weight:800; font-size:13px; letter-spacing:0.4px; cursor:pointer;
    box-shadow:0 10px 22px -6px rgba(214,108,71,0.5); transition:filter .15s, transform .1s;
  }
  .btn-primary:hover{ filter:brightness(1.05); }
  .btn-primary:active{ transform:translateY(1px); }

  @media (max-width:820px){
    .top-links{ display:none; }
    .sidebar{ position:fixed; left:0; top:64px; transform:translateX(-100%); width:270px; height:calc(100vh - 64px); z-index:46; box-shadow:0 0 40px rgba(0,0,0,0.15); }
    .sidebar.open{ transform:translateX(0); }
  }
  @media (max-width:480px){
    .main{ padding:20px 14px 40px; }
    .page-title{ font-size:22px; }
    .panel{ padding:20px; }
  }
</style>
</head>
<body>

<div class="topbar">
  <button class="burger" id="burgerBtn" aria-label="Toggle menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
  </button>
  <a href="dashboard.php" class="logo"><span class="logo-mark">SC</span>Sipway English Accademy</a>

  <div class="top-links">
 <a href="about_sipway_campus.php">About Sipway Campus</a>
    <a href="terms_of_use.php">Terms of Use</a>
    <a href="privacy_policy.php">Privacy Policy</a>
  </div>

  <div class="user-menu" id="userMenu">
    <span class="avatar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
    </span>
    <span class="user-name" id="userNameLabel">Hi, <?php echo $firstName; ?></span>
    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>

    <div class="dropdown" id="userDropdown">
      <a href="#"><?php echo $fullNameSafe; ?></a>
      <a href="purchase-history.php">Purchase history</a>
      <a href="student_logout.php" class="danger" id="logoutBtn">Log out</a>
    </div>
  </div>
</div>

<div class="backdrop" id="backdrop"></div>

<div class="shell">

  <aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="<?php echo navClass('dashboard', $activePage); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/></svg>
      Dashboard
    </a>
    <a href="packages.php" class="<?php echo navClass('packages', $activePage); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
      Packages
    </a>
    <a href="session_progress.php" class="<?php echo navClass('progress', $activePage); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 2a10 10 0 0 1 10 10h-10z"/></svg>
      My Progress
    </a>

    <div class="side-divider"></div>

    <a href="purchase-history.php" class="side-link<?php echo $activePage === 'purchase-history' ? ' active' : ''; ?>">Purchase History</a>
    <a href="faq-support.php" class="side-link<?php echo $activePage === 'faq-support' ? ' active' : ''; ?>">FAQs &amp; Support</a>
    <a href="class-details.php" class="side-link<?php echo $activePage === 'class-details' ? ' active' : ''; ?>">Class Details</a>

  </aside>

  <main class="main">
    <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>