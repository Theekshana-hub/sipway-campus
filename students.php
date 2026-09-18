<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students - Sipway Campus Admin</title>
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
    --success-soft:#e8f8ee;
    --warning:#f2994a;
    --warning-soft:#fdf1e4;
    --radius-lg:16px;
    --radius-md:10px;
    --radius-sm:8px;
    --shadow-card:0 10px 34px -12px rgba(15,42,74,0.14), 0 2px 8px rgba(15,42,74,0.05);
    --ease:cubic-bezier(.4,0,.2,1);
    --sidebar-w:250px;
  }
  *{ box-sizing:border-box; }
  body{
    margin:0;
    font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    -webkit-font-smoothing:antialiased;
  }
  a{ text-decoration:none; color:inherit; }
  ::selection{ background:var(--coral-soft); color:var(--coral-dark); }
  .sidebar{
    position:fixed; top:0; left:0; bottom:0;
    width:var(--sidebar-w);
    background:linear-gradient(180deg, var(--navy) 0%, #0b2039 100%);
    color:#fff;
    display:flex; flex-direction:column;
    z-index:50; transition:transform .25s var(--ease);
  }
  .sidebar-brand{
    display:flex; align-items:center; gap:10px;
    padding:22px 22px 20px;
    font-weight:800; font-size:15px; letter-spacing:0.3px;
    border-bottom:1px solid rgba(255,255,255,0.08);
  }
  .brand-mark{
    width:34px; height:34px; border-radius:9px;
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:800; flex-shrink:0;
    box-shadow:0 6px 14px rgba(214,108,71,0.4);
  }
  .sidebar-brand .sub{
    display:block; font-size:10.5px; font-weight:600;
    color:rgba(255,255,255,0.55); letter-spacing:1px; margin-top:2px;
  }
  .nav-group{ padding:18px 12px; flex:1; overflow-y:auto; }
  .nav-label{
    font-size:10.5px; font-weight:700; letter-spacing:1.2px;
    color:rgba(255,255,255,0.35); text-transform:uppercase;
    padding:8px 12px 6px;
  }
  .nav-item{
    display:flex; align-items:center; gap:12px;
    padding:11px 14px; border-radius:10px;
    font-size:13.5px; font-weight:600;
    color:rgba(255,255,255,0.75); cursor:pointer;
    margin-bottom:3px; transition:all .15s var(--ease);
    position:relative;
  }
  .nav-item svg{ width:18px; height:18px; flex-shrink:0; }
  .nav-item:hover{ background:rgba(255,255,255,0.06); color:#fff; }
  .nav-item.active{
    background:rgba(232,130,95,0.16); color:#fff;
  }
  .nav-item.active::before{
    content:""; position:absolute; left:-12px; top:8px; bottom:8px;
    width:3px; border-radius:3px; background:var(--coral);
  }
  .nav-item .badge-count{
    margin-left:auto; background:var(--coral); color:#fff;
    font-size:10.5px; font-weight:800; padding:2px 7px;
    border-radius:20px; flex-shrink:0;
  }
  .sidebar-foot{
    padding:16px 14px 20px;
    border-top:1px solid rgba(255,255,255,0.08);
  }
  .logout-btn{
    display:flex; align-items:center; gap:8px; width:100%;
    padding:8px 12px; border-radius:8px;
    background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12);
    color:#fff; font-weight:600; font-size:13px; cursor:pointer;
    transition:background .15s var(--ease), border-color .15s var(--ease);
  }
  .logout-btn:hover{ background:rgba(192,57,43,0.35); border-color:rgba(192,57,43,0.5); }
  .logout-btn svg{ width:16px; height:16px; }
  .main{ margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
  .topbar{
    height:68px; display:flex; align-items:center; justify-content:space-between;
    padding:0 28px; background:rgba(255,255,255,0.9); backdrop-filter:saturate(180%) blur(10px);
    border-bottom:1px solid var(--line-soft); position:sticky; top:0; z-index:30; gap:16px;
  }
  .menu-toggle{ display:none; background:none; border:none; cursor:pointer; color:var(--navy); padding:6px; }
  .topbar-title h2{ margin:0; font-size:18px; font-weight:800; color:var(--navy); letter-spacing:-0.2px; }
  .topbar-title p{ margin:2px 0 0; font-size:12.5px; color:var(--muted); font-weight:500; }
  .topbar-right{ display:flex; align-items:center; gap:16px; }
  .admin-chip{
    display:flex; align-items:center; gap:10px;
    padding:6px 14px 6px 6px; border-radius:999px;
    background:var(--navy-soft); cursor:default;
  }
  .admin-avatar{
    width:30px; height:30px; border-radius:50%;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:12.5px;
  }
  .admin-chip .name{ font-size:13px; font-weight:700; color:var(--navy); }
  .admin-chip .role{ font-size:10.5px; color:var(--muted-2); font-weight:600; }
  .content{ padding:26px 28px 60px; flex:1; }
  .greeting{
    margin-bottom:22px; display:flex; align-items:flex-end; justify-content:space-between;
    gap:16px; flex-wrap:wrap;
  }
  .greeting h1{
    font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--navy);
    margin:0 0 4px; letter-spacing:-0.3px;
  }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }
  .add-btn{
    display:flex; align-items:center; gap:8px; padding:11px 20px;
    border-radius:10px; background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff; font-weight:700; font-size:13.5px; border:none; cursor:pointer;
    box-shadow:0 8px 20px -6px rgba(214,108,71,0.5);
    transition:transform .15s var(--ease), box-shadow .15s var(--ease);
  }
  .add-btn:hover{ transform:translateY(-1px); box-shadow:0 10px 24px -6px rgba(214,108,71,0.6); }
  .panel{
    background:var(--card); border:1px solid var(--line-soft);
    border-radius:var(--radius-lg); box-shadow:var(--shadow-card); overflow:hidden;
  }
  .panel-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 22px; border-bottom:1px solid var(--line-soft); gap:10px; flex-wrap:wrap;
  }
  .panel-head h3{ margin:0; font-size:15.5px; font-weight:800; color:var(--navy); }
  .panel-head p{ margin:2px 0 0; font-size:12px; color:var(--muted); }
  .head-controls{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
  .search-box{
    padding:9px 14px; border:1px solid var(--line); border-radius:8px;
    font-size:13px; background:var(--bg); min-width:200px;
    font-family:inherit; color:var(--text);
  }
  .search-box:focus{ outline:none; border-color:var(--coral); }
  .student-count{
    font-size:11.5px; font-weight:700; color:var(--coral-dark);
    background:var(--coral-soft); padding:6px 13px; border-radius:999px; white-space:nowrap;
  }
  .table-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; min-width:880px; }
  thead th{
    text-align:left; font-size:11px; font-weight:700; color:var(--muted-2);
    text-transform:uppercase; letter-spacing:0.6px; padding:12px 22px;
    background:#fbfaf9; border-bottom:1px solid var(--line-soft); white-space:nowrap;
  }
  tbody td{
    padding:14px 22px; font-size:13.5px; border-bottom:1px solid var(--line-soft);
    vertical-align:middle;
  }
  tbody tr:hover{ background:#fbfaf9; }
  .person-cell{ display:flex; align-items:center; gap:10px; }
  .person-avatar{
    width:32px; height:32px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:11.5px; font-weight:800; color:#fff;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
  }
  .person-name{ font-weight:700; font-size:13.5px; }
  .person-sub{ font-size:11.5px; color:var(--muted-2); font-weight:500; }
  .status-badge{
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 11px; border-radius:20px; font-size:11px;
    font-weight:800; text-transform:capitalize;
  }
  .status-pending{ background:var(--warning-soft); color:var(--warning); }
  .status-approved{ background:var(--success-soft); color:var(--success); }
  .status-active{ background:var(--success-soft); color:var(--success); }
  .status-rejected{ background:var(--danger-soft); color:var(--danger); }
  .lang-badge{
    display:inline-flex; align-items:center; gap:6px;
    padding:5px 11px; border-radius:20px; font-size:12px;
    font-weight:700; background:var(--navy-soft); color:var(--navy-2);
    white-space:nowrap;
  }
  .lang-badge .lang-flag{
    width:20px; height:14px; border-radius:2px;
    box-shadow:0 0 0 1px rgba(0,0,0,0.1);
    display:inline-block; vertical-align:middle; flex-shrink:0;
  }
  .action-cell{ display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
  .icon-btn{
    width:30px; height:30px; border-radius:8px; border:none;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:all .15s var(--ease); flex-shrink:0;
  }
  .icon-btn svg{ width:15px; height:15px; }
  .icon-btn:active{ transform:scale(.92); }
  .btn-edit{ background:var(--navy-soft); color:var(--navy); }
  .btn-edit:hover{ background:#dfe7f0; }
  .btn-delete{ background:var(--danger-soft); color:var(--danger); }
  .btn-delete:hover{ background:#fadbd8; }
  .modal-overlay{
    display:none; position:fixed; inset:0; background:rgba(15,42,74,0.45);
    z-index:200; align-items:center; justify-content:center; padding:20px;
  }
  .modal-overlay.show{ display:flex; }
  .modal-box{
    background:#fff; border-radius:var(--radius-lg); width:100%; max-width:440px;
    box-shadow:0 30px 70px -20px rgba(15,42,74,0.4); overflow-y:auto; max-height:90vh;
  }
  .modal-box.confirm-box{ max-width:380px; }
  .modal-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 22px; border-bottom:1px solid var(--line-soft);
  }
  .modal-head h3{ margin:0; font-size:16px; font-weight:800; color:var(--navy); }
  .modal-close{
    background:var(--navy-soft); border:none; width:30px; height:30px;
    border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center;
    color:var(--navy);
  }
  .modal-body{ padding:22px; }
  .form-group{ margin-bottom:16px; }
  .form-group label{ display:block; font-size:12.5px; font-weight:700; color:var(--navy); margin-bottom:6px; }
  .form-group input, .form-group select{
    width:100%; padding:11px 13px; border:1px solid var(--line);
    border-radius:9px; font-size:13.5px; background:var(--bg);
    font-family:inherit;
  }
  .form-group input:focus, .form-group select:focus{ outline:none; border-color:var(--coral); background:#fff; }
  .confirm-text{ font-size:13.5px; color:var(--text); line-height:1.6; margin:0; }
  .confirm-text b{ color:var(--navy); }
  .modal-foot{ display:flex; gap:10px; padding:18px 22px 22px; }
  .btn-cancel, .btn-save{
    flex:1; padding:12px; border-radius:9px; font-weight:700; font-size:13.5px;
    cursor:pointer; border:none; transition:opacity .15s var(--ease);
  }
  .btn-cancel{ background:var(--navy-soft); color:var(--navy); }
  .btn-save{ background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%); color:#fff; }
  .btn-save.btn-danger{ background:linear-gradient(135deg, #d9534f 0%, var(--danger) 100%); }
  .btn-cancel:hover, .btn-save:hover{ opacity:.88; }
  .toast{
    position:fixed; top:20px; left:50%; transform:translateX(-50%) translateY(-16px);
    background:var(--navy); color:#fff; padding:13px 22px; border-radius:10px;
    font-size:13.5px; font-weight:600; opacity:0; transition:all .25s var(--ease); z-index:100;
    pointer-events:none;
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error-toast{ background:var(--danger); }
  .empty-state{ padding:40px 20px; text-align:center; color:var(--muted-2); font-size:13px; }
  .sidebar-backdrop{
    display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45;
  }
  @media (max-width:880px){
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,0.3); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
    .sidebar-backdrop.show{ display:block; }
  }
  @media (max-width:560px){
    .greeting{ flex-direction:column; align-items:flex-start; }
    .add-btn{ width:100%; justify-content:center; }
    .panel-head{ flex-direction:column; align-items:stretch; }
    .head-controls{ justify-content:space-between; }
  }
</style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-mark">SC</span>
    <span>
      Sipway English Accademy
      <span class="sub">ADMIN PANEL</span>
    </span>
  </div>
  <nav class="nav-group">

    <div class="nav-label">Students</div>
    <a class="nav-item" href="admin-dashboard.html">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
      Student Booking Time
      <span class="badge-count" id="pendingBookingsBadge" style="display:none;">0</span>
    </a>
    <a class="nav-item active" href="students.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
      Students
    </a>
    <a class="nav-item" href="admin_practice_videos.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>
      Practice Videos
    </a>
 
    <a class="nav-item" href="admin_activated_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
      Student Activated Packages
      <span class="badge-count" id="pendingActivationsBadge" style="display:none;">0</span>
    </a>
    <a class="nav-item" href="admin_student_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 2a10 10 0 0 1 10 10h-10z"/></svg>
      Student Packages
    </a>
    <a class="nav-item" href="admin_mobile_gate.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <rect x="5" y="2" width="14" height="20" rx="2"/>
    <line x1="12" y1="18" x2="12.01" y2="18"/>
  </svg>
  Mobile Gate Logs
</a>
<a class="nav-item" href="admin_languages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        <path d="M8 7h8M8 11h6"/>
      </svg>
      Languages
    </a>
      <div class="nav-label">Vocabulary</div>
       <a class="nav-item" href="admin_vocabulary_videos.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        <path d="M8 7h8M8 11h6"/>
      </svg>
      Vocabulary Videos
    </a>

<a class="nav-item" href="admin_activated_vocabulary_packages.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    <path d="M8 7h8M8 11h6"/>
  </svg>
  Vocabulary Activations
  <span class="badge-count" id="pendingVocabBadge" style="display:none;">0</span>
</a>
  <div class="nav-label">AI Videos</div>
    <a class="nav-item" href="admin_activated_ai_video_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>
      AI Video Activations
     
    </a>
    <div class="nav-label">Lecturers</div>
    <a class="nav-item" href="teachers.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      Teachers
      <span class="badge-count" id="pendingTeachersBadge" style="display:none;">0</span>
    </a>
    <a class="nav-item" href="admin_availability_requests.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
      Lecture Time Requests
      <span class="badge-count" id="pendingAvailabilityBadge" style="display:none;">0</span>
    </a>

    <div class="nav-label">Packages</div>
    <a class="nav-item" href="admin_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
      Packages
    </a>
   <a class="nav-item" href="admin_subjects.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Subjects / Types
    </a>

    <div class="nav-label">Support</div>
    <a class="nav-item" href="admin_support_requests.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Support Requests
      <span class="badge-count" id="pendingBadge" style="display:none;">0</span>
    </a>

    <div class="nav-label">Reports</div>
    <a class="nav-item" href="filter.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
      Filter
    </a>
        <div class="nav-label">Chat</div>
<a class="nav-item" href="admin_chat.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
  Student Chat
</a>

    <div class="nav-label"> Register Video</div>
<a class="nav-item" href="admin_register_video.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <rect x="2" y="4" width="20" height="14" rx="2"/>
    <path d="M10 9l5 3-5 3V9z"/>
  </svg>
  Register Video
</a>
  <div class="sidebar-foot">
    <button class="logout-btn" id="logoutBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Logout
    </button>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:14px;">
      <button class="menu-toggle" id="menuToggle">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title">
        <h2>Students</h2>
        <p id="todayDate">Loading...</p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="admin-chip">
        <span class="admin-avatar" id="adminAvatar">A</span>
        <div>
          <div class="name" id="adminName">Admin</div>
          <div class="role">Administrator</div>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="greeting">
      <div>
        <h1>Students 👨‍🎓</h1>
        <p>Sipway Campus හි ලියාපදිංචි students සියල්ල මෙතන.</p>
      </div>
      <button class="add-btn" id="openAddModalBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        Add Student
      </button>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>All Students</h3>
          <p>Registered student accounts in the system</p>
        </div>
        <div class="head-controls">
          <input type="text" class="search-box" id="searchBox" placeholder="Search name, email, mobile...">
          <span class="student-count" id="studentCount">0 students</span>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Email</th>
              <th>Mobile</th>
              <th>Language</th>
              <th>Status</th>
              <th>Registered On</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="studentsBody">
            <tr><td colspan="7"><div class="empty-state">Loading students...</div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="modalTitle">Add New Student</h3>
      <button class="modal-close" id="closeModalBtn">✕</button>
    </div>
    <form id="studentForm">
      <div class="modal-body">
        <input type="hidden" id="studentId">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" id="fullName" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="email" required>
        </div>
        <div class="form-group">
          <label>Mobile Number</label>
          <input type="text" id="mobile">
        </div>
        <div class="form-group">
          <label>Language</label>
          <select id="language">
            <!-- JS එකෙන් languages fill කරනවා -->
          </select>
        </div>
        <div class="form-group">
          <label>Password <small>(Add කරන වෙලාවේ විතරක්, Edit කරද්දි wenas karanna one nam witharak)</small></label>
          <input type="password" id="password" minlength="6">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
        <button type="submit" class="btn-save" id="saveBtn">Save Student</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModalOverlay">
  <div class="modal-box confirm-box">
    <div class="modal-head">
      <h3>Delete Student</h3>
      <button class="modal-close" id="closeDeleteModalBtn">✕</button>
    </div>
    <div class="modal-body">
      <p class="confirm-text">Ownata confirm da <b id="deleteStudentName">meka student eka</b> delete karanna one kiyala? Meka undo karanna baa.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
      <button type="button" class="btn-save btn-danger" id="confirmDeleteBtn">Delete</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
let allStudents = [];
let pendingDeleteId = null;
const BADGE_POLL_INTERVAL_MS = 15000;

/* ========== FLAGS + LANGUAGE MAP (Arabic, Tamil & Italian included) ========== */
const FLAG_SVGS = {
  GB: '<rect width="60" height="40" fill="#012169"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#fff" stroke-width="10"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#C8102E" stroke-width="6"/><path d="M30,0 V40 M0,20 H60" stroke="#fff" stroke-width="16"/><path d="M30,0 V40 M0,20 H60" stroke="#C8102E" stroke-width="10"/>',
  DE: '<rect width="60" height="13.34" y="0" fill="#000"/><rect width="60" height="13.33" y="13.33" fill="#DD0000"/><rect width="60" height="13.33" y="26.67" fill="#FFCE00"/>',
  FR: '<rect width="20" height="40" x="0" fill="#002395"/><rect width="20" height="40" x="20" fill="#fff"/><rect width="20" height="40" x="40" fill="#ED2939"/>',
  CN: '<rect width="60" height="40" fill="#DE2910"/><polygon points="10,6 11.8,11.5 17.5,11.5 12.8,14.8 14.5,20.2 10,17 5.5,20.2 7.2,14.8 2.5,11.5 8.2,11.5" fill="#FFDE00"/>',
  JP: '<rect width="60" height="40" fill="#fff"/><circle cx="30" cy="20" r="12" fill="#BC002D"/>',
  IN: '<rect width="60" height="13.34" y="0" fill="#FF9933"/><rect width="60" height="13.33" y="13.33" fill="#fff"/><rect width="60" height="13.33" y="26.67" fill="#138808"/><circle cx="30" cy="20" r="4.5" fill="none" stroke="#000080" stroke-width="1"/><circle cx="30" cy="20" r="1" fill="#000080"/>',
  RU: '<rect width="60" height="13.34" y="0" fill="#fff"/><rect width="60" height="13.33" y="13.33" fill="#0039A6"/><rect width="60" height="13.33" y="26.67" fill="#D52B1E"/>',
  SA: '<rect width="60" height="40" fill="#006C35"/><rect x="8" y="26" width="36" height="3" fill="#fff"/><polygon points="48,24.5 56,27.5 48,30.5" fill="#fff"/>',
  LK: '<rect width="60" height="40" fill="#FFB714"/><rect x="0" y="0" width="10" height="40" fill="#8D153A"/><rect x="10" y="0" width="8" height="40" fill="#00534E"/><rect x="20" y="4" width="36" height="32" fill="#8D153A"/>',
  IT: '<rect width="20" height="40" x="0" fill="#009246"/><rect width="20" height="40" x="20" fill="#FFF"/><rect width="20" height="40" x="40" fill="#CE2B37"/>',
  DEFAULT: '<rect width="60" height="40" fill="#e6e2da"/><circle cx="30" cy="20" r="12" fill="none" stroke="#8a93a3" stroke-width="2"/>'
};

const LANGUAGE_MAP = {
  en: { flag: 'GB', label: 'English' },
  de: { flag: 'DE', label: 'German' },
  zh: { flag: 'CN', label: 'Chinese' },
  ja: { flag: 'JP', label: 'Japanese' },
  fr: { flag: 'FR', label: 'French' },
  hi: { flag: 'IN', label: 'Hindi' },
  ru: { flag: 'RU', label: 'Russian' },
  ar: { flag: 'SA', label: 'Arabic' },
  ta: { flag: 'IN', label: 'Tamil' },
  si: { flag: 'LK', label: 'Sinhala' },
  it: { flag: 'IT', label: 'Italian' }
};

function normalizeCode(code) {
  return (code || '').toString().trim().toLowerCase();
}

function flagSvg(flagCode) {
  const key = (flagCode || 'DEFAULT').toUpperCase();
  const inner = FLAG_SVGS[key] || FLAG_SVGS.DEFAULT;
  return `<svg class="lang-flag" viewBox="0 0 60 40" width="20" height="14" aria-hidden="true">${inner}</svg>`;
}

function langBadge(code) {
  const key = normalizeCode(code);
  const info = LANGUAGE_MAP[key] || LANGUAGE_MAP.en;
  return `<span class="lang-badge">${flagSvg(info.flag)}${info.label}</span>`;
}

function populateLanguageSelect() {
  const sel = document.getElementById('language');
  sel.innerHTML = Object.keys(LANGUAGE_MAP).map(code => {
    const info = LANGUAGE_MAP[code];
    return `<option value="${code}">${info.label}</option>`;
  }).join('');
}

const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
if (!adminSession || !adminSession.username) {
  window.location.href = 'admin-login.html';
}
document.getElementById('adminName').textContent = adminSession.username;
document.getElementById('adminAvatar').textContent = adminSession.username.charAt(0).toUpperCase();
document.getElementById('todayDate').textContent = new Date().toLocaleDateString('en-GB', {
  weekday:'long', year:'numeric', month:'long', day:'numeric'
});

function showToast(msg, type = '') {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.className = 'toast show' + (type ? ' ' + type : '');
  setTimeout(() => toast.classList.remove('show'), 2500);
}

function statusBadge(status) {
  const s = status || 'active';
  return `<span class="status-badge status-${s}">${s}</span>`;
}

async function loadStudents() {
  const tbody = document.getElementById('studentsBody');
  tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">Loading students...</div></td></tr>`;
  try {
    const res = await fetch('get_students.php');
    const data = await res.json();
    if (data.success) {
      allStudents = data.data || [];
      applyFilters();
    } else {
      tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">Error: ${data.message || 'Data load unuwe na'}</div></td></tr>`;
    }
  } catch (err) {
    console.error(err);
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">Server connect unuwe na. get_students.php check karanna.</div></td></tr>`;
  }
}

function applyFilters() {
  const q = document.getElementById('searchBox').value.toLowerCase().trim();
  let list = allStudents;
  if (q !== '') {
    list = list.filter(s =>
      (s.full_name || '').toLowerCase().includes(q) ||
      (s.email || '').toLowerCase().includes(q) ||
      (s.mobile || '').toLowerCase().includes(q)
    );
  }
  renderTable(list);
}

function renderTable(list) {
  const tbody = document.getElementById('studentsBody');
  document.getElementById('studentCount').textContent = list.length + ' student' + (list.length !== 1 ? 's' : '');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">No students found</div></td></tr>`;
    return;
  }
  tbody.innerHTML = list.map(s => {
    const initials = s.full_name ? s.full_name.charAt(0).toUpperCase() : '?';
    const status = s.status || 'active';
    return `
      <tr>
        <td>
          <div class="person-cell">
            <span class="person-avatar">${initials}</span>
            <div>
              <div class="person-name">${s.full_name}</div>
              <div class="person-sub">ID: ${s.id}</div>
            </div>
          </div>
        </td>
        <td>${s.email || '-'}</td>
        <td>${s.mobile || '-'}</td>
        <td>${langBadge(s.language)}</td>
        <td>${statusBadge(status)}</td>
        <td>${s.created_at ? new Date(s.created_at).toLocaleDateString('en-GB') : '-'}</td>
        <td>
          <div class="action-cell">
            <button class="icon-btn btn-edit" title="Edit" data-action="edit" data-id="${s.id}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </button>
            <button class="icon-btn btn-delete" title="Delete" data-action="delete" data-id="${s.id}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z"/></svg>
            </button>
          </div>
        </td>
      </tr>`;
  }).join('');
}

document.getElementById('searchBox').addEventListener('input', applyFilters);

/* ===== Add/Edit Modal ===== */
let editingId = null;
const modal = document.getElementById('modalOverlay');

populateLanguageSelect();

document.getElementById('openAddModalBtn').onclick = () => {
  editingId = null;
  document.getElementById('modalTitle').textContent = 'Add New Student';
  document.getElementById('studentForm').reset();
  document.getElementById('language').value = 'en';
  modal.classList.add('show');
};
document.getElementById('closeModalBtn').onclick =
document.getElementById('cancelBtn').onclick = () => modal.classList.remove('show');
modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('show'); });

document.getElementById('studentForm').onsubmit = async (e) => {
  e.preventDefault();
  const payload = {
    id: editingId,
    full_name: document.getElementById('fullName').value.trim(),
    email: document.getElementById('email').value.trim(),
    mobile: document.getElementById('mobile').value.trim(),
    language: normalizeCode(document.getElementById('language').value),
    password: document.getElementById('password').value
  };
  const url = editingId ? 'update_student.php' : 'add_student.php';
  const saveBtn = document.getElementById('saveBtn');
  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving...';
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      showToast(editingId ? 'Student updated successfully!' : 'Student added successfully! ✅');
      modal.classList.remove('show');
      loadStudents();
    } else {
      showToast(data.message || 'Failed', 'error-toast');
    }
  } catch (err) {
    showToast('Server error', 'error-toast');
  } finally {
    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Student';
  }
};

function editStudent(id) {
  const student = allStudents.find(s => s.id === id);
  if (!student) return;
  editingId = id;
  document.getElementById('modalTitle').textContent = 'Edit Student';
  document.getElementById('fullName').value = student.full_name || '';
  document.getElementById('email').value = student.email || '';
  document.getElementById('mobile').value = student.mobile || '';
  const langCode = normalizeCode(student.language);
  document.getElementById('language').value = LANGUAGE_MAP[langCode] ? langCode : 'en';
  document.getElementById('password').value = '';
  modal.classList.add('show');
}

/* ===== Delete Modal ===== */
const deleteModalOverlay = document.getElementById('deleteModalOverlay');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

function openDeleteModal(student) {
  pendingDeleteId = student.id;
  document.getElementById('deleteStudentName').textContent = student.full_name;
  deleteModalOverlay.classList.add('show');
}
function closeDeleteModal() {
  deleteModalOverlay.classList.remove('show');
  pendingDeleteId = null;
}
document.getElementById('closeDeleteModalBtn').onclick = closeDeleteModal;
document.getElementById('cancelDeleteBtn').onclick = closeDeleteModal;
deleteModalOverlay.addEventListener('click', (e) => {
  if (e.target === deleteModalOverlay) closeDeleteModal();
});

confirmDeleteBtn.addEventListener('click', async () => {
  if (!pendingDeleteId) return;
  confirmDeleteBtn.disabled = true;
  confirmDeleteBtn.textContent = 'Deleting...';
  try {
    const res = await fetch('delete_student.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: pendingDeleteId })
    });
    const data = await res.json();
    if (data.success) {
      showToast('Student deleted successfully 🗑️');
      closeDeleteModal();
      loadStudents();
    } else {
      showToast(data.message || 'Delete failed', 'error-toast');
    }
  } catch (err) {
    showToast('Delete failed', 'error-toast');
  } finally {
    confirmDeleteBtn.disabled = false;
    confirmDeleteBtn.textContent = 'Delete';
  }
});

document.getElementById('studentsBody').addEventListener('click', (e) => {
  const btn = e.target.closest('.icon-btn');
  if (!btn) return;
  const action = btn.dataset.action;
  const id = parseInt(btn.dataset.id, 10);
  const student = allStudents.find(s => s.id === id);
  if (!student) return;
  if (action === 'edit') editStudent(id);
  else if (action === 'delete') openDeleteModal(student);
});

/* ===== Sidebar badges ===== */
async function updateBookingsBadge() {
  try {
    const res = await fetch('get_bookings.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = data.data.filter(b => (b.status || '').toLowerCase() === 'pending').length;
    const badge = document.getElementById('pendingBookingsBadge');
    if (pending > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = pending;
    } else {
      badge.style.display = 'none';
    }
  } catch (err) {
    console.error('Bookings badge update failed', err);
  }
}

async function updateSupportBadge() {
  try {
    const res = await fetch('get_support_requests.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = data.data.filter(r => r.status === 'pending').length;
    const badge = document.getElementById('pendingBadge');
    if (pending > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = pending;
    } else {
      badge.style.display = 'none';
    }
  } catch (err) {
    console.error('Support badge update failed', err);
  }
}

async function updateActivationsBadge() {
  try {
    const res = await fetch('get_activations.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = (data.data || []).filter(a => (a.status || 'pending') === 'pending').length;
    const badge = document.getElementById('pendingActivationsBadge');
    if (pending > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = pending;
    } else {
      badge.style.display = 'none';
    }
  } catch (err) {
    console.error('Activations badge update failed', err);
  }
}

async function updateTeachersBadge() {
  try {
    const res = await fetch('get-teachers.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = (data.data || []).filter(t => (t.status || 'pending') === 'pending').length;
    const badge = document.getElementById('pendingTeachersBadge');
    if (pending > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = pending;
    } else {
      badge.style.display = 'none';
    }
  } catch (err) {
    console.error('Teachers badge update failed', err);
  }
}

async function updateAvailabilityBadge() {
  try {
    const res = await fetch('admin_get_pending_availability.php?filter=all');
    const data = await res.json();
    if (!data.success) return;
    const pending = (data.data || []).filter(r => r.approval_status === 'pending').length;
    const badge = document.getElementById('pendingAvailabilityBadge');
    if (pending > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = pending;
    } else {
      badge.style.display = 'none';
    }
  } catch (err) {
    console.error('Availability badge update failed', err);
  }
}

document.getElementById('menuToggle').addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarBackdrop').classList.toggle('show');
});
document.getElementById('sidebarBackdrop').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarBackdrop').classList.remove('show');
});
document.getElementById('logoutBtn')?.addEventListener('click', () => {
  window.location.href = 'admin_logout.php';
});

loadStudents();
updateBookingsBadge();
updateSupportBadge();
updateActivationsBadge();
updateTeachersBadge();
updateAvailabilityBadge();

setInterval(updateBookingsBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateSupportBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateActivationsBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateTeachersBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateAvailabilityBadge, BADGE_POLL_INTERVAL_MS);
</script>
</body>
</html>
