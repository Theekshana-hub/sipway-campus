<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Packages - Sipway Campus Admin</title>
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
    --amber-price:#e0421d;
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
    margin-left:auto;
    background:var(--coral);
    color:#fff;
    font-size:10.5px;
    font-weight:800;
    padding:2px 7px;
    border-radius:20px;
    flex-shrink:0;
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
    margin-bottom:18px; display:flex; align-items:flex-end; justify-content:space-between;
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
  .type-switch{
    display:inline-flex; gap:6px; background:var(--bg); padding:4px;
    border-radius:10px; border:1px solid var(--line-soft); margin-bottom:18px; flex-wrap:wrap;
  }
  .type-btn{
    padding:10px 18px; border-radius:8px; font-size:13px; font-weight:700;
    color:var(--muted); cursor:pointer; border:none; background:none;
    transition:background .15s var(--ease), color .15s var(--ease); white-space:nowrap;
  }
  .type-btn:hover{ color:var(--navy); }
  .type-btn.active{
    background:#fff; color:var(--coral-dark);
    box-shadow:0 2px 8px rgba(15,42,74,0.08);
  }
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
  .filter-pills{
    display:flex; gap:6px; background:var(--bg); padding:4px;
    border-radius:9px; border:1px solid var(--line-soft); flex-wrap:wrap;
  }
  .filter-pill{
    padding:7px 13px; border-radius:7px; font-size:12px; font-weight:700;
    color:var(--muted); cursor:pointer; border:none; background:none;
    transition:background .15s var(--ease), color .15s var(--ease); white-space:nowrap;
  }
  .filter-pill:hover{ color:var(--navy); }
  .filter-pill.active{ background:#fff; color:var(--coral-dark); box-shadow:0 2px 8px rgba(15,42,74,0.08); }
  .search-box{
    padding:9px 14px; border:1px solid var(--line); border-radius:8px;
    font-size:13px; background:var(--bg); min-width:200px;
  }
  .search-box:focus{ outline:none; border-color:var(--coral); }
  .pkg-count{
    font-size:11.5px; font-weight:700; color:var(--coral-dark);
    background:var(--coral-soft); padding:6px 13px; border-radius:999px; white-space:nowrap;
  }
  .table-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; min-width:980px; }
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
  .pkg-cell .pkg-name{ font-weight:700; font-size:13.5px; }
  .pkg-cell .pkg-desc{ font-size:11.5px; color:var(--muted-2); font-weight:500; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .price-cell{ font-weight:800; color:var(--amber-price); }
  .status-badge{
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 11px; border-radius:20px; font-size:11px;
    font-weight:800; text-transform:capitalize;
  }
  .status-active{ background:var(--success-soft); color:var(--success); }
  .status-inactive{ background:#f0f0f0; color:#777; }
  .offer-badge{
    display:inline-flex; align-items:center; margin-left:6px;
    padding:5px 11px; border-radius:20px; font-size:11px;
    font-weight:800; background:var(--warning-soft); color:var(--warning);
  }
  .type-badge{
    display:inline-flex; align-items:center; gap:4px;
    padding:5px 10px; border-radius:20px; font-size:11px; font-weight:800;
  }
  .type-individual{ background:#f3e8ff; color:#6d28d9; border:1px solid #ddd6fe; }
  .type-group{ background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
  .lang-chip{
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; border-radius:999px; font-size:12px; font-weight:800;
    background:var(--navy-soft); color:var(--navy);
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
  .btn-approve{ background:var(--success-soft); color:var(--success); }
  .btn-approve:hover{ background:#d3f2df; }
  .btn-reject{ background:var(--danger-soft); color:var(--danger); }
  .btn-reject:hover{ background:#fadbd8; }
  .modal-overlay{
    display:none; position:fixed; inset:0; background:rgba(15,42,74,0.45);
    z-index:200; align-items:center; justify-content:center; padding:20px;
  }
  .modal-overlay.show{ display:flex; }
  .modal-box{
    background:#fff; border-radius:var(--radius-lg); width:100%; max-width:520px;
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
  .form-group input, .form-group textarea, .form-group select{
    width:100%; padding:11px 13px; border:1px solid var(--line);
    border-radius:9px; font-size:13.5px; background:var(--bg); font-family:inherit; color:var(--text);
  }
  .form-group textarea{ resize:vertical; min-height:60px; }
  .form-group input:focus, .form-group textarea:focus, .form-group select:focus{ outline:none; border-color:var(--coral); background:#fff; }
  .form-row{ display:flex; gap:12px; }
  .form-row .form-group{ flex:1; }
  .checkbox-row{ display:flex; align-items:center; gap:8px; margin-bottom:16px; }
  .checkbox-row input{ width:16px; height:16px; }
  .checkbox-row label{ font-size:13px; font-weight:600; margin:0; color:var(--text); }
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
  .lang-picker{
    display:grid; grid-template-columns:repeat(auto-fill, minmax(110px, 1fr));
    gap:8px; margin-bottom:8px;
  }
  .lang-option{
    display:flex; align-items:center; gap:8px; padding:10px 12px;
    border-radius:10px; border:1.5px solid var(--line); background:#fff;
    cursor:pointer; font-size:12.5px; font-weight:700; user-select:none;
    transition:all .15s var(--ease);
  }
  .lang-option:hover{ border-color:var(--coral); background:var(--coral-soft); }
  .lang-option.selected{
    border-color:var(--coral); background:var(--coral-soft);
    box-shadow:0 0 0 1px var(--coral);
  }
  .lang-option .flag{ font-size:20px; line-height:1; }
  .lang-option input{ display:none; }
  .lang-hint{
    font-size:12px; color:var(--muted); margin:0 0 14px;
    padding:8px 12px; background:var(--navy-soft); border-radius:8px;
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
    .form-row{ flex-direction:column; }
    .type-switch{ width:100%; }
    .type-btn{ flex:1; text-align:center; }
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
    <a class="nav-item" href="students.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
      Students
      <span class="badge-count" id="pendingStudentsBadge" style="display:none;">0</span>
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
    <a class="nav-item active" href="admin_packages.php">
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
    <div class="nav-label">Register Video</div>
    <a class="nav-item" href="admin_register_video.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="2" y="4" width="20" height="14" rx="2"/>
        <path d="M10 9l5 3-5 3V9z"/>
      </svg>
      Register Video
    </a>
  </nav>
  <div class="sidebar-foot">
    <button class="logout-btn" id="logoutBtn" type="button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Logout
    </button>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:14px;">
      <button class="menu-toggle" id="menuToggle" type="button">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title">
        <h2 id="topbarH2">Packages</h2>
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
        <h1 id="pageMainTitle">Packages 📦</h1>
        <p id="pageMainSub">Language අනුව students ට packages manage කරන්න.</p>
      </div>
      <button class="add-btn" id="openAddModalBtn" type="button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        <span id="addBtnLabel">Add Package</span>
      </button>
    </div>

    <!-- Normal / Vocabulary / AI Video switch -->
    <div class="type-switch" id="typeSwitch">
      <button class="type-btn active" type="button" data-type="normal">Normal Packages</button>
      <button class="type-btn" type="button" data-type="vocabulary">Vocabulary Packages</button>
      <button class="type-btn" type="button" data-type="ai_video">AI Video Packages</button>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div>
          <h3 id="panelTitle">All Packages</h3>
          <p id="panelSub">Packages shown on the student packages page (by language)</p>
        </div>
        <div class="head-controls">
          <div class="filter-pills" id="filterPills">
            <button class="filter-pill active" type="button" data-filter="all">All</button>
            <button class="filter-pill" type="button" data-filter="active">Active</button>
            <button class="filter-pill" type="button" data-filter="inactive">Inactive</button>
            <button class="filter-pill" type="button" data-filter="offer" id="filterOffer">Offers</button>
            <button class="filter-pill" type="button" data-filter="individual" id="filterIndividual">Individual</button>
            <button class="filter-pill" type="button" data-filter="group" id="filterGroup">Group</button>
          </div>
          <div class="filter-pills" id="langFilterPills" style="margin-top:6px;">
            <button class="filter-pill active" type="button" data-lang="all">🌐 All Lang</button>
          </div>
          <input type="text" class="search-box" id="searchBox" placeholder="Search package name...">
          <span class="pkg-count" id="pkgCount">0 packages</span>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Package</th>
              <th id="thLanguage">Language</th>
              <th id="thType">Type</th>
              <th>Price</th>
              <th id="thSessions">Sessions</th>
              <th id="thDuration">Duration</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="pkgBody">
            <tr><td colspan="8"><div class="empty-state">Loading packages...</div></td></tr>
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
      <h3 id="modalTitle">Add New Package</h3>
      <button class="modal-close" id="closeModalBtn" type="button">✕</button>
    </div>
    <form id="pkgForm">
      <div class="modal-body">
        <input type="hidden" id="pkgId">
        <input type="hidden" id="pkgLanguage" value="en">

        <div class="form-group">
          <label>Package Name</label>
          <input type="text" id="pkgName" placeholder="e.g. Package 04 / AI Video 30 Days" required>
        </div>

        <!-- Target Language (hidden for AI Video) -->
        <div class="form-group" id="langPickerGroup">
          <label>Target Language *</label>
          <div class="lang-picker" id="langPicker"></div>
          <p class="lang-hint" id="langHint">🇬🇧 English — මේ package එක English students ට විතරක් පේනවා.</p>
        </div>

        <!-- Package Type: Individual / Group (Normal only) -->
        <div class="form-group" id="packageTypeGroup">
          <label>Package Type</label>
          <select id="pkgPackageType">
            <option value="individual">👤 Individual Package</option>
            <option value="group">👥 Group Package</option>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Price (Rs.)</label>
            <input type="number" id="pkgPrice" min="0" step="0.01" required>
          </div>
          <div class="form-group" id="sessionsGroup">
            <label>Total Sessions</label>
            <input type="number" id="pkgSessions" min="0" value="1">
          </div>
          <div class="form-group" id="durationDaysGroup" style="display:none;">
            <label>Duration (Days)</label>
            <input type="number" id="pkgDurationDays" min="1" value="30">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group" id="durationLabelGroup">
            <label>Duration Label</label>
            <input type="text" id="pkgDuration" placeholder="e.g. 1 hour / Unlimited Access" value="1 hour">
          </div>
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" id="pkgSort" min="0" value="0">
          </div>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea id="pkgDesc" placeholder="Short description"></textarea>
        </div>

        <div class="form-group">
          <label>Status</label>
          <select id="pkgStatus">
            <option value="active">Active (visible to students)</option>
            <option value="inactive">Inactive (hidden)</option>
          </select>
        </div>

        <div class="checkbox-row" id="offerRow">
          <input type="checkbox" id="pkgOffer">
          <label for="pkgOffer">Mark as special offer (highlighted + badge)</label>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
        <button type="submit" class="btn-save" id="saveBtn">Save Package</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModalOverlay">
  <div class="modal-box confirm-box">
    <div class="modal-head">
      <h3>Delete Package</h3>
      <button class="modal-close" id="closeDeleteModalBtn" type="button">✕</button>
    </div>
    <div class="modal-body">
      <p class="confirm-text">Ownata confirm da <b id="deletePkgName">meka package eka</b> delete karanna one kiyala? Meka undo karanna baa.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
      <button type="button" class="btn-save btn-danger" id="confirmDeleteBtn">Delete</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const LANG_OPTIONS = {
  en: { flag: '🇬🇧', label: 'English' },
  de: { flag: '🇩🇪', label: 'German' },
  zh: { flag: '🇨🇳', label: 'Chinese' },
  ja: { flag: '🇯🇵', label: 'Japanese' },
  fr: { flag: '🇫🇷', label: 'French' },
  hi: { flag: '🇮🇳', label: 'Hindi' },
  ru: { flag: '🇷🇺', label: 'Russian' },
  ar: { flag: '🇸🇦', label: 'Arabic' },
  ta: { flag: '🇮🇳', label: 'Tamil' },
  it: { flag: '🇮🇹', label: 'Italian' },
  si: { flag: '🇱🇰', label: 'Sinhala' }
};

let packageType = 'normal'; // 'normal' | 'vocabulary' | 'ai_video'
let allPackages = [];
let currentFilter = 'all';
let currentLangFilter = 'all';
let pendingDeleteId = null;
let editingId = null;

const BADGE_POLL_INTERVAL_MS = 15000;

const endpoints = {
  normal: {
    list: 'get_packages.php',
    save: 'save_package.php',
    del:  'delete_package.php'
  },
  vocabulary: {
    list: 'get_vocabulary_packages.php',
    save: 'save_vocabulary_package.php',
    del:  'delete_vocabulary_package.php'
  },
  ai_video: {
    list: 'get_ai_video_packages.php',
    save: 'save_ai_video_package.php',
    del:  'delete_ai_video_package.php'
  }
};

const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
if (!adminSession || !adminSession.username) {
  window.location.href = 'admin-login.html';
}
document.getElementById('adminName').textContent = adminSession.username;
document.getElementById('adminAvatar').textContent = adminSession.username.charAt(0).toUpperCase();
document.getElementById('todayDate').textContent = new Date().toLocaleDateString('en-GB', {
  weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
});

function showToast(msg, type = '') {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.className = 'toast show' + (type ? ' ' + type : '');
  setTimeout(() => toast.classList.remove('show'), 2500);
}

function money(n) {
  return 'Rs. ' + Number(n).toLocaleString('en-LK', { minimumFractionDigits: 0 });
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function getLangMeta(code) {
  const c = (code || 'en').toLowerCase();
  return LANG_OPTIONS[c] || { flag: '🌐', label: c };
}

function buildLangPicker(selected = 'en') {
  const picker = document.getElementById('langPicker');
  picker.innerHTML = '';
  Object.keys(LANG_OPTIONS).forEach(code => {
    const m = LANG_OPTIONS[code];
    const lab = document.createElement('label');
    lab.className = 'lang-option' + (code === selected ? ' selected' : '');
    lab.dataset.lang = code;
    lab.innerHTML = `
      <input type="radio" name="lang_radio" value="${code}" ${code === selected ? 'checked' : ''}>
      <span class="flag">${m.flag}</span>
      <span>${m.label}</span>
    `;
    lab.addEventListener('click', () => {
      document.querySelectorAll('.lang-option').forEach(o => o.classList.remove('selected'));
      lab.classList.add('selected');
      document.getElementById('pkgLanguage').value = code;
      const meta = getLangMeta(code);
      document.getElementById('langHint').textContent =
        `${meta.flag} ${meta.label} — මේ package එක ${meta.label} students ට විතරක් පේනවා.`;
    });
    picker.appendChild(lab);
  });
  document.getElementById('pkgLanguage').value = selected;
  const meta = getLangMeta(selected);
  document.getElementById('langHint').textContent =
    `${meta.flag} ${meta.label} — මේ package එක ${meta.label} students ට විතරක් පේනවා.`;
}

function buildLangFilterPills() {
  const wrap = document.getElementById('langFilterPills');
  wrap.innerHTML = `<button class="filter-pill ${currentLangFilter === 'all' ? 'active' : ''}" type="button" data-lang="all">🌐 All Lang</button>`;
  Object.keys(LANG_OPTIONS).forEach(code => {
    const m = LANG_OPTIONS[code];
    const btn = document.createElement('button');
    btn.className = 'filter-pill' + (currentLangFilter === code ? ' active' : '');
    btn.type = 'button';
    btn.dataset.lang = code;
    btn.textContent = `${m.flag} ${m.label}`;
    wrap.appendChild(btn);
  });
}

function setTypeUI() {
  const isVocab   = packageType === 'vocabulary';
  const isAiVideo = packageType === 'ai_video';

  document.getElementById('pageMainTitle').textContent =
    isAiVideo ? 'AI Video Packages 🎬' :
    isVocab   ? 'Vocabulary Packages 📚' : 'Packages 📦';

  document.getElementById('pageMainSub').textContent =
    isAiVideo ? 'Practice with AI Video page එකේ packages manage කරන්න.' :
    isVocab   ? 'Vocabulary Practice page එකේ language අනුව packages manage කරන්න.' :
                'Language අනුව students ට learning packages manage කරන්න.';

  document.getElementById('topbarH2').textContent =
    isAiVideo ? 'AI Video Packages' :
    isVocab   ? 'Vocabulary Packages' : 'Packages';

  document.getElementById('addBtnLabel').textContent =
    isAiVideo ? 'Add AI Video Package' :
    isVocab   ? 'Add Vocabulary Package' : 'Add Package';

  document.getElementById('panelTitle').textContent =
    isAiVideo ? 'AI Video Packages' :
    isVocab   ? 'Vocabulary Packages' : 'All Packages';

  document.getElementById('panelSub').textContent =
    isAiVideo ? 'Packages for Practice with AI Video feature' :
    isVocab   ? 'Packages shown on student Vocabulary Practice page (by language)' :
                'Packages shown on the student packages page (by language)';

  // Table columns
  document.getElementById('thLanguage').style.display = isAiVideo ? 'none' : '';
  document.getElementById('thType').style.display     = (isVocab || isAiVideo) ? 'none' : '';
  document.getElementById('thSessions').style.display = (isVocab || isAiVideo) ? 'none' : '';
  document.getElementById('thDuration').textContent   = isAiVideo ? 'Days' : 'Duration';

  // Form fields
  document.getElementById('langPickerGroup').style.display   = isAiVideo ? 'none' : '';
  document.getElementById('packageTypeGroup').style.display  = (isVocab || isAiVideo) ? 'none' : '';
  document.getElementById('sessionsGroup').style.display     = (isVocab || isAiVideo) ? 'none' : '';
  document.getElementById('durationDaysGroup').style.display = isAiVideo ? '' : 'none';
  document.getElementById('durationLabelGroup').style.display = isAiVideo ? 'none' : '';
  document.getElementById('offerRow').style.display          = isAiVideo ? 'none' : '';
  document.getElementById('langFilterPills').style.display   = isAiVideo ? 'none' : '';

  // Filter pills
  document.getElementById('filterOffer').style.display      = isAiVideo ? 'none' : '';
  document.getElementById('filterIndividual').style.display = isAiVideo ? 'none' : '';
  document.getElementById('filterGroup').style.display      = isAiVideo ? 'none' : '';
}

async function loadPackages() {
  const tbody = document.getElementById('pkgBody');
  tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state">Loading packages...</div></td></tr>`;
  try {
    const res = await fetch(endpoints[packageType].list);
    const data = await res.json();
    if (data.success) {
      allPackages = data.packages || [];
      applyFilters();
    } else {
      tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state">Error: ${escapeHtml(data.message || 'Data load unuwe na')}</div></td></tr>`;
    }
  } catch (err) {
    console.error(err);
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state">Server connect unuwe na. API file check karanna.</div></td></tr>`;
  }
}

function applyFilters() {
  const q = document.getElementById('searchBox').value.toLowerCase().trim();
  let list = allPackages;

  if (currentFilter === 'active') list = list.filter(p => p.status === 'active');
  else if (currentFilter === 'inactive') list = list.filter(p => p.status === 'inactive');
  else if (currentFilter === 'offer') list = list.filter(p => p.is_offer == 1);
  else if (currentFilter === 'individual') list = list.filter(p => (p.package_type || 'individual').toLowerCase() === 'individual');
  else if (currentFilter === 'group') list = list.filter(p => (p.package_type || '').toLowerCase() === 'group');

  if (currentLangFilter !== 'all' && packageType !== 'ai_video') {
    list = list.filter(p => (p.language || 'en').toLowerCase() === currentLangFilter);
  }

  if (q !== '') {
    list = list.filter(p => (p.package_name || '').toLowerCase().includes(q));
  }
  renderTable(list);
}

function renderTable(list) {
  const tbody = document.getElementById('pkgBody');
  const isVocab   = packageType === 'vocabulary';
  const isAiVideo = packageType === 'ai_video';

  document.getElementById('pkgCount').textContent = list.length + ' package' + (list.length !== 1 ? 's' : '');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state">No packages found</div></td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(p => {
    const status = p.status || 'active';
    const pType = (p.package_type || 'individual').toLowerCase();
    const langCode = (p.language || 'en').toLowerCase();
    const langMeta = getLangMeta(langCode);

    const typeBadge = (isVocab || isAiVideo)
      ? '—'
      : (pType === 'group'
          ? '<span class="type-badge type-group">👥 Group</span>'
          : '<span class="type-badge type-individual">👤 Individual</span>');

    const durationCell = isAiVideo
      ? (p.duration_days ? p.duration_days + ' days' : '—')
      : escapeHtml(p.duration_label || '');

    const toggleBtn = status === 'active'
      ? `<button class="icon-btn btn-reject" title="Deactivate" data-action="deactivate" data-id="${p.id}">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
         </button>`
      : `<button class="icon-btn btn-approve" title="Activate" data-action="activate" data-id="${p.id}">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
         </button>`;

    return `
      <tr>
        <td>
          <div class="pkg-cell">
            <div class="pkg-name">${escapeHtml(p.package_name)}</div>
            <div class="pkg-desc">${escapeHtml(p.description || '')}</div>
          </div>
        </td>
        <td style="${isAiVideo ? 'display:none' : ''}"><span class="lang-chip">${langMeta.flag} ${escapeHtml(langMeta.label)}</span></td>
        <td style="${(isVocab || isAiVideo) ? 'display:none' : ''}">${typeBadge}</td>
        <td class="price-cell">${money(p.price)}</td>
        <td style="${(isVocab || isAiVideo) ? 'display:none' : ''}">${p.total_sessions != null ? p.total_sessions : '—'}</td>
        <td>${durationCell}</td>
        <td>
          <span class="status-badge status-${status}">${status}</span>
          ${p.is_offer == 1 && !isAiVideo ? '<span class="offer-badge">OFFER</span>' : ''}
        </td>
        <td>
          <div class="action-cell">
            ${toggleBtn}
            <button class="icon-btn btn-edit" title="Edit" data-action="edit" data-id="${p.id}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </button>
            <button class="icon-btn btn-delete" title="Delete" data-action="delete" data-id="${p.id}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z"/></svg>
            </button>
          </div>
        </td>
      </tr>`;
  }).join('');
}

/* Type switch */
document.getElementById('typeSwitch').addEventListener('click', (e) => {
  const btn = e.target.closest('.type-btn');
  if (!btn || !btn.dataset.type) return;
  document.querySelectorAll('#typeSwitch .type-btn').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  packageType = btn.dataset.type;
  currentFilter = 'all';
  currentLangFilter = 'all';
  document.querySelectorAll('#filterPills .filter-pill').forEach(p => {
    p.classList.toggle('active', p.dataset.filter === 'all');
  });
  buildLangFilterPills();
  document.getElementById('searchBox').value = '';
  setTypeUI();
  loadPackages();
});

document.getElementById('searchBox').addEventListener('input', applyFilters);

document.getElementById('filterPills').addEventListener('click', (e) => {
  const btn = e.target.closest('.filter-pill');
  if (!btn || !btn.dataset.filter) return;
  document.querySelectorAll('#filterPills .filter-pill').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  currentFilter = btn.dataset.filter;
  applyFilters();
});

document.getElementById('langFilterPills').addEventListener('click', (e) => {
  const btn = e.target.closest('.filter-pill');
  if (!btn || !btn.dataset.lang) return;
  document.querySelectorAll('#langFilterPills .filter-pill').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  currentLangFilter = btn.dataset.lang;
  applyFilters();
});

/* Add/Edit Modal */
const modal = document.getElementById('modalOverlay');
document.getElementById('openAddModalBtn').onclick = () => {
  editingId = null;
  document.getElementById('modalTitle').textContent =
    packageType === 'ai_video' ? 'Add AI Video Package' :
    packageType === 'vocabulary' ? 'Add Vocabulary Package' : 'Add New Package';
  document.getElementById('pkgForm').reset();
  document.getElementById('pkgDuration').value = packageType === 'vocabulary' ? 'Unlimited Access' : '1 hour';
  document.getElementById('pkgSort').value = '0';
  document.getElementById('pkgSessions').value = '1';
  document.getElementById('pkgDurationDays').value = '30';
  document.getElementById('pkgPackageType').value = 'individual';
  if (packageType !== 'ai_video') buildLangPicker('en');
  setTypeUI();
  modal.classList.add('show');
};
document.getElementById('closeModalBtn').onclick =
document.getElementById('cancelBtn').onclick = () => modal.classList.remove('show');
modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('show'); });

document.getElementById('pkgForm').onsubmit = async (e) => {
  e.preventDefault();
  const formData = new FormData();
  formData.append('id', editingId || '');
  formData.append('package_name', document.getElementById('pkgName').value.trim());
  formData.append('price', document.getElementById('pkgPrice').value);
  formData.append('description', document.getElementById('pkgDesc').value.trim());
  formData.append('status', document.getElementById('pkgStatus').value);
  formData.append('sort_order', document.getElementById('pkgSort').value);

  if (packageType === 'ai_video') {
    formData.append('duration_days', document.getElementById('pkgDurationDays').value || '30');
  } else {
    formData.append('duration_label', document.getElementById('pkgDuration').value.trim());
    formData.append('language', document.getElementById('pkgLanguage').value || 'en');
    if (document.getElementById('pkgOffer').checked) formData.append('is_offer', '1');
    if (packageType === 'normal') {
      formData.append('total_sessions', document.getElementById('pkgSessions').value || '1');
      formData.append('package_type', document.getElementById('pkgPackageType').value || 'individual');
    }
  }

  const saveBtn = document.getElementById('saveBtn');
  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving...';
  try {
    const res = await fetch(endpoints[packageType].save, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      showToast(editingId ? 'Package updated successfully!' : 'Package added successfully! ✅');
      modal.classList.remove('show');
      loadPackages();
    } else {
      showToast(data.message || 'Failed', 'error-toast');
    }
  } catch (err) {
    showToast('Server error', 'error-toast');
  } finally {
    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Package';
  }
};

function editPackage(id) {
  const pkg = allPackages.find(p => p.id == id);
  if (!pkg) return;
  editingId = id;
  document.getElementById('modalTitle').textContent =
    packageType === 'ai_video' ? 'Edit AI Video Package' :
    packageType === 'vocabulary' ? 'Edit Vocabulary Package' : 'Edit Package';

  document.getElementById('pkgName').value  = pkg.package_name || '';
  document.getElementById('pkgPrice').value = pkg.price;
  document.getElementById('pkgDesc').value  = pkg.description || '';
  document.getElementById('pkgStatus').value = pkg.status || 'active';
  document.getElementById('pkgSort').value  = pkg.sort_order || 0;

  if (packageType === 'ai_video') {
    document.getElementById('pkgDurationDays').value = pkg.duration_days || 30;
  } else {
    document.getElementById('pkgDuration').value = pkg.duration_label || '';
    document.getElementById('pkgSessions').value = pkg.total_sessions != null ? pkg.total_sessions : 0;
    document.getElementById('pkgOffer').checked = (pkg.is_offer == 1);
    document.getElementById('pkgPackageType').value = (pkg.package_type || 'individual').toLowerCase();
    buildLangPicker((pkg.language || 'en').toLowerCase());
  }
  setTypeUI();
  modal.classList.add('show');
}

/* Delete Modal */
const deleteModalOverlay = document.getElementById('deleteModalOverlay');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

function openDeleteModal(pkg) {
  pendingDeleteId = pkg.id;
  document.getElementById('deletePkgName').textContent = pkg.package_name;
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
    const formData = new FormData();
    formData.append('id', pendingDeleteId);
    const res = await fetch(endpoints[packageType].del, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      showToast('Package deleted successfully 🗑️');
      closeDeleteModal();
      loadPackages();
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

async function toggleStatus(id, newStatus) {
  const pkg = allPackages.find(p => p.id == id);
  if (!pkg) return;
  const formData = new FormData();
  formData.append('id', pkg.id);
  formData.append('package_name', pkg.package_name);
  formData.append('price', pkg.price);
  formData.append('description', pkg.description || '');
  formData.append('status', newStatus);
  formData.append('sort_order', pkg.sort_order || 0);

  if (packageType === 'ai_video') {
    formData.append('duration_days', pkg.duration_days || 30);
  } else {
    formData.append('duration_label', pkg.duration_label || '');
    formData.append('language', (pkg.language || 'en').toLowerCase());
    if (pkg.is_offer == 1) formData.append('is_offer', '1');
    if (packageType === 'normal') {
      formData.append('total_sessions', pkg.total_sessions != null ? pkg.total_sessions : 1);
      formData.append('package_type', (pkg.package_type || 'individual').toLowerCase());
    }
  }

  try {
    const res = await fetch(endpoints[packageType].save, { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      showToast(newStatus === 'active' ? 'Package activate unuwa ✅' : 'Package deactivate unuwa');
      loadPackages();
    } else {
      showToast(data.message || 'Status update karanna baa una', 'error-toast');
    }
  } catch (err) {
    console.error(err);
    showToast('Server connect unuwe na', 'error-toast');
  }
}

document.getElementById('pkgBody').addEventListener('click', (e) => {
  const btn = e.target.closest('.icon-btn');
  if (!btn) return;
  const action = btn.dataset.action;
  const id = parseInt(btn.dataset.id, 10);
  const pkg = allPackages.find(p => p.id == id);
  if (!pkg) return;
  if (action === 'edit') editPackage(id);
  else if (action === 'delete') openDeleteModal(pkg);
  else if (action === 'activate') toggleStatus(id, 'active');
  else if (action === 'deactivate') toggleStatus(id, 'inactive');
});

/* Badges */
async function updateBookingsBadge() {
  try {
    const res = await fetch('get_bookings.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = data.data.filter(b => (b.status || '').toLowerCase() === 'pending').length;
    const badge = document.getElementById('pendingBookingsBadge');
    if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
    else { badge.style.display = 'none'; }
  } catch (err) { console.error(err); }
}
async function updateStudentsBadge() {
  try {
    const res = await fetch('get_students.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = (data.data || []).filter(s => (s.status || 'pending') === 'pending').length;
    const badge = document.getElementById('pendingStudentsBadge');
    if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
    else { badge.style.display = 'none'; }
  } catch (err) { console.error(err); }
}
async function updateActivationsBadge() {
  try {
    const res = await fetch('get_activations.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = (data.data || []).filter(a => (a.status || 'pending') === 'pending').length;
    const badge = document.getElementById('pendingActivationsBadge');
    if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
    else { badge.style.display = 'none'; }
  } catch (err) { console.error(err); }
}
async function updateTeachersBadge() {
  try {
    const res = await fetch('get-teachers.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = (data.data || []).filter(t => (t.status || 'pending') === 'pending').length;
    const badge = document.getElementById('pendingTeachersBadge');
    if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
    else { badge.style.display = 'none'; }
  } catch (err) { console.error(err); }
}
async function updateAvailabilityBadge() {
  try {
    const res = await fetch('admin_get_pending_availability.php?filter=all');
    const data = await res.json();
    if (!data.success) return;
    const pending = (data.data || []).filter(r => r.approval_status === 'pending').length;
    const badge = document.getElementById('pendingAvailabilityBadge');
    if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
    else { badge.style.display = 'none'; }
  } catch (err) { console.error(err); }
}
async function updateSupportBadge() {
  try {
    const res = await fetch('get_support_requests.php');
    const data = await res.json();
    if (!data.success) return;
    const pending = data.data.filter(r => r.status === 'pending').length;
    const badge = document.getElementById('pendingBadge');
    if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
    else { badge.style.display = 'none'; }
  } catch (err) { console.error(err); }
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

buildLangFilterPills();
setTypeUI();
loadPackages();
updateBookingsBadge();
updateStudentsBadge();
updateActivationsBadge();
updateTeachersBadge();
updateAvailabilityBadge();
updateSupportBadge();
setInterval(updateBookingsBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateStudentsBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateActivationsBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateTeachersBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateAvailabilityBadge, BADGE_POLL_INTERVAL_MS);
setInterval(updateSupportBadge, BADGE_POLL_INTERVAL_MS);
</script>
</body>
</html>