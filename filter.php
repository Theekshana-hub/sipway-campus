<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Filter Reports - Sipway Campus Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
  .nav-item.active{ background:rgba(232,130,95,0.16); color:#fff; }
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
  .sidebar-foot{ padding:16px 14px 20px; border-top:1px solid rgba(255,255,255,0.08); }
  .logout-btn{
    display:flex; align-items:center; gap:10px; width:100%;
    padding:11px 14px; border-radius:10px;
    background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
    color:#fff; font-weight:700; font-size:13px; cursor:pointer;
    transition:background .15s var(--ease);
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
  .greeting{ margin-bottom:22px; }
  .greeting h1{
    font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--navy);
    margin:0 0 4px; letter-spacing:-0.3px;
  }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }
  .filter-bar{
    background:var(--card); border:1px solid var(--line-soft);
    border-radius:var(--radius-lg); box-shadow:var(--shadow-card);
    padding:22px; margin-bottom:22px;
    display:flex; align-items:flex-end; gap:16px; flex-wrap:wrap;
  }
  .filter-field{ display:flex; flex-direction:column; gap:6px; }
  .filter-field label{ font-size:12px; font-weight:700; color:var(--navy); }
  .filter-field input[type="date"]{
    padding:10px 13px; border:1px solid var(--line); border-radius:9px;
    font-size:13.5px; font-family:inherit; background:var(--bg); min-width:170px;
  }
  .filter-field input[type="date"]:focus{ outline:none; border-color:var(--coral); background:#fff; }
  .apply-btn{
    padding:11px 22px; border-radius:9px; border:none;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    color:#fff; font-weight:700; font-size:13.5px; cursor:pointer;
    transition:opacity .15s var(--ease);
  }
  .apply-btn:hover{ opacity:.9; }
  .apply-btn:disabled{ opacity:.6; cursor:not-allowed; }
  .quick-ranges{ display:flex; gap:6px; flex-wrap:wrap; }
  .quick-btn{
    padding:9px 14px; border-radius:8px; border:1px solid var(--line);
    background:#fff; color:var(--navy); font-size:12.5px; font-weight:700;
    cursor:pointer; transition:all .15s var(--ease); white-space:nowrap;
  }
  .quick-btn:hover{ background:var(--navy-soft); border-color:#d7d2c8; }
  .download-btn{
    margin-left:auto;
    display:flex; align-items:center; gap:8px;
    padding:11px 20px; border-radius:9px; border:none;
    background:linear-gradient(135deg, var(--success) 0%, #17824a 100%);
    color:#fff; font-weight:700; font-size:13.5px; cursor:pointer;
    box-shadow:0 8px 18px -6px rgba(31,157,85,0.5);
    transition:opacity .15s var(--ease);
  }
  .download-btn:hover{ opacity:.9; }
  .download-btn:disabled{ opacity:.5; cursor:not-allowed; box-shadow:none; }
  .download-btn svg{ width:16px; height:16px; }
  .stats-grid{
    display:grid; grid-template-columns:repeat(auto-fit, minmax(175px, 1fr)); gap:18px; margin-bottom:22px;
  }
  .stat-card{
    background:var(--card); border:1px solid var(--line-soft);
    border-radius:var(--radius-lg); padding:20px 22px; box-shadow:var(--shadow-card);
    display:flex; flex-direction:column; gap:10px;
  }
  .stat-icon{
    width:40px; height:40px; border-radius:11px;
    display:flex; align-items:center; justify-content:center;
  }
  .stat-icon svg{ width:19px; height:19px; }
  .stat-icon.blue{ background:var(--navy-soft); color:var(--navy-2); }
  .stat-icon.coral{ background:var(--coral-soft); color:var(--coral-dark); }
  .stat-icon.green{ background:var(--success-soft); color:var(--success); }
  .stat-icon.orange{ background:var(--warning-soft); color:var(--warning); }
  .stat-icon.purple{ background:#f3e8ff; color:#7c3aed; }
  .stat-value{ font-size:22px; font-weight:800; color:var(--text); letter-spacing:-0.5px; }
  .stat-label{ font-size:12.5px; color:var(--muted); font-weight:600; }
  .panel{
    background:var(--card); border:1px solid var(--line-soft);
    border-radius:var(--radius-lg); box-shadow:var(--shadow-card);
    overflow:hidden; margin-bottom:22px;
  }
  .tab-buttons{
    display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap;
  }
  .tab-btn{
    display:flex; align-items:center; gap:8px;
    padding:11px 18px; border-radius:10px; border:1px solid var(--line);
    background:#fff; color:var(--muted); font-size:13px; font-weight:700;
    cursor:pointer; transition:all .15s var(--ease);
  }
  .tab-btn svg{ width:16px; height:16px; flex-shrink:0; }
  .tab-btn:hover:not(.active){ border-color:#d7d2c8; color:var(--navy); }
  .tab-btn.active{
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    color:#fff; border-color:var(--navy);
    box-shadow:0 8px 18px -6px rgba(15,42,74,0.4);
  }
  .panel-head{
    padding:20px 22px; border-bottom:1px solid var(--line-soft);
  }
  .panel-head h3{ margin:0; font-size:15.5px; font-weight:800; color:var(--navy); }
  .panel-head p{ margin:2px 0 0; font-size:12px; color:var(--muted); }
  .table-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; min-width:700px; }
  thead th{
    text-align:left; font-size:11px; font-weight:700; color:var(--muted-2);
    text-transform:uppercase; letter-spacing:0.6px; padding:12px 22px;
    background:#fbfaf9; border-bottom:1px solid var(--line-soft); white-space:nowrap;
  }
  tbody td{
    padding:14px 22px; font-size:13.5px; color:var(--text);
    border-bottom:1px solid var(--line-soft); vertical-align:middle;
  }
  tbody tr:last-child td{ border-bottom:none; }
  tbody tr:hover{ background:#fbfaf9; }
  .person-cell{ display:flex; align-items:center; gap:10px; }
  .person-avatar{
    width:30px; height:30px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:800; color:#fff; flex-shrink:0;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
  }
  .person-name{ font-weight:700; font-size:13.5px; }
  .person-sub{ font-size:11px; color:var(--muted-2); font-weight:500; }
  .hours-badge{
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 11px; border-radius:20px; font-size:11.5px; font-weight:800;
    background:var(--coral-soft); color:var(--coral-dark);
  }
  .salary-badge{
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 11px; border-radius:20px; font-size:11.5px; font-weight:800;
    background:var(--success-soft); color:var(--success);
  }
  .sessions-badge{
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 11px; border-radius:20px; font-size:11.5px; font-weight:800;
    background:var(--navy-soft); color:var(--navy-2);
  }
  .sessions-badge.low{ background:var(--danger-soft); color:var(--danger); }
  .vocab-badge{
    display:inline-flex; align-items:center;
    padding:2px 8px; border-radius:10px; font-size:10px; font-weight:800;
    background:#f3e8ff; color:#7c3aed; margin-left:6px;
  }
  .empty-state{ padding:40px 20px; text-align:center; color:var(--muted-2); font-size:13px; }
  .placeholder-state{
    padding:60px 20px; text-align:center; color:var(--muted-2); font-size:13.5px;
  }
  .toast{
    position:fixed; top:20px; left:50%; transform:translateX(-50%) translateY(-16px);
    background:var(--navy); color:#fff; padding:13px 22px; border-radius:10px;
    font-size:13.5px; font-weight:600; opacity:0; pointer-events:none;
    transition:opacity .25s var(--ease), transform .25s var(--ease); z-index:100;
    box-shadow:0 12px 30px rgba(15,42,74,0.3);
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error-toast{ background:var(--danger); }
  .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45; }

  .month-row{ cursor:pointer; transition:background .12s var(--ease); }
  .month-row:hover{ background:#fbfaf9; }
  .month-row.expanded{ background:var(--navy-soft); }
  .month-row td{ padding:12px 22px; }
  .month-row-inner{ display:flex; align-items:center; gap:10px; }
  .expand-chevron{
    width:20px; height:20px; flex-shrink:0; color:var(--muted-2);
    transition:transform .2s var(--ease);
    display:flex; align-items:center; justify-content:center;
  }
  .month-row.expanded .expand-chevron{ transform:rotate(90deg); color:var(--coral-dark); }
  .month-icon{
    width:38px; height:38px; border-radius:10px;
    background:var(--navy); color:#fff;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
  }
  .month-icon svg{ width:18px; height:18px; }
  .month-icon.current{ background:var(--coral-dark); }
  .month-label{ font-weight:700; font-size:13.5px; }
  .current-tag{
    display:inline-flex; align-items:center;
    padding:2px 9px; border-radius:999px;
    font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.4px;
    background:var(--coral-soft); color:var(--coral-dark); flex-shrink:0;
  }
  .month-detail-row td{ padding:0; border-bottom:1px solid var(--line-soft); }
  .month-detail-wrap{ background:#fbfaf9; padding:6px 22px 18px 58px; }
  .nested-table{ width:100%; border-collapse:collapse; min-width:0; }
  .nested-table thead th{ background:transparent; padding:8px 14px; font-size:10.5px; border-bottom:1px solid var(--line); }
  .nested-table tbody td{ padding:12px 14px; font-size:13px; border-bottom:1px solid var(--line-soft); background:#fff; vertical-align:middle; }
  .nested-table tbody tr:last-child td{ border-bottom:none; }
  .nested-table tbody tr:first-child td:first-child{ border-top-left-radius:10px; }
  .nested-table tbody tr:first-child td:last-child{ border-top-right-radius:10px; }
  .nested-table tbody tr:last-child td:first-child{ border-bottom-left-radius:10px; }
  .nested-table tbody tr:last-child td:last-child{ border-bottom-right-radius:10px; }
  .month-summary-pill{ font-size:12.5px; font-weight:700; color:var(--muted); }

  @media (max-width:1200px){ .stats-grid{ grid-template-columns:repeat(3,1fr); } }
  @media (max-width:1100px){ .stats-grid{ grid-template-columns:repeat(2,1fr); } }
  @media (max-width:880px){
    :root{ --sidebar-w:230px; }
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,0.3); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
    .sidebar-backdrop.show{ display:block; }
  }
  @media (max-width:560px){
    .stats-grid{ grid-template-columns:1fr; }
    .topbar{ padding:0 16px; }
    .content{ padding:18px 16px 40px; }
    .admin-chip .name, .admin-chip .role{ display:none; }
    .filter-bar{ flex-direction:column; align-items:stretch; }
    .download-btn{ margin-left:0; width:100%; justify-content:center; }
    .month-detail-wrap{ padding-left:22px; }
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
    <a class="nav-item active" href="filter.php">
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
  </nav>
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
        <h2>Filter Reports</h2>
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
      <h1>Filter Reports 📊</h1>
      <p>
        Date range එකක් තෝරලා <strong>Accepted student bookings</strong> වලින් lecture hours + salary බලන්න.<br>
        <strong>Admin add කරපු slots ගන්නේ නැහැ</strong> — Student කෙනෙක් book කරලා <strong>Accepted</strong> වුණාම විතරක් count වෙනවා.<br>
        එකම time slot එකට කී දෙනෙක් book කළත් → <strong>එක වතාවක් විතරයි</strong> (30 min).<br>
        <strong>Normal:</strong> 30 min = Rs. 500 &nbsp;|&nbsp; 
        <strong>Dilini Tharushika Kumari:</strong> 30 min = Rs. 300
      </p>
    </div>
    <div class="filter-bar">
      <div class="filter-field">
        <label for="startDate">From Date</label>
        <input type="date" id="startDate">
      </div>
      <div class="filter-field">
        <label for="endDate">To Date</label>
        <input type="date" id="endDate">
      </div>
      <button class="apply-btn" id="applyBtn">Apply Filter</button>
      <div class="quick-ranges">
        <button class="quick-btn" data-range="7">Last 7 days</button>
        <button class="quick-btn" data-range="30">Last 30 days</button>
        <button class="quick-btn" data-range="thisMonth">This Month</button>
        <button class="quick-btn" data-range="allTime">All Time</button>
      </div>
      <button class="download-btn" id="downloadBtn" disabled>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
        Download Excel
      </button>
    </div>
    <div class="stats-grid" id="statsGrid" style="display:none;">
      <div class="stat-card">
        <span class="stat-icon coral">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </span>
        <div class="stat-value" id="statTotalHours">0</div>
        <div class="stat-label">Total Lecture Hours (Accepted only)</div>
      </div>
      <div class="stat-card">
        <span class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </span>
        <div class="stat-value" id="statTotalSalary">Rs. 0</div>
        <div class="stat-label">Total Salary</div>
      </div>
      <div class="stat-card">
        <span class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        </span>
        <div class="stat-value" id="statLecturers">0</div>
        <div class="stat-label">Lecturers Involved</div>
      </div>
      <div class="stat-card">
        <span class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        </span>
        <div class="stat-value" id="statStudents">0</div>
        <div class="stat-label">Students Registered</div>
      </div>
      <div class="stat-card">
        <span class="stat-icon orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
        </span>
        <div class="stat-value" id="statSlots">0</div>
        <div class="stat-label">Student Bookings (Accepted)</div>
      </div>
      <div class="stat-card">
        <span class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </span>
        <div class="stat-value" id="statPaymentsRevenue">Rs. 0</div>
        <div class="stat-label">Student Payments (Normal + Vocab)</div>
      </div>
      <div class="stat-card">
        <span class="stat-icon coral">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
        </span>
        <div class="stat-value" id="statSessionsRemaining">0</div>
        <div class="stat-label">Sessions Remaining (Normal)</div>
      </div>
    </div>
    <div class="tab-buttons" id="tabButtons" style="display:none;">
      <button class="tab-btn active" data-tab="lecturer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        Lecture Hours & Salary by Lecturer
      </button>
      <button class="tab-btn" data-tab="payments">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Student Payments (by Month)
      </button>
      <button class="tab-btn" data-tab="students">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        Students Registered
      </button>
    </div>
    <div class="panel" id="lecturerPanel" style="display:none;">
      <div class="panel-head">
        <h3>Lecture Hours & Salary by Lecturer (Accepted Student Bookings only)</h3>
        <p>
          <strong>Admin add කරපු slots ගන්නේ නැහැ.</strong><br>
          Student කෙනෙක් book කරලා status = <strong>Accepted</strong> වුණාම විතරක් count වෙනවා.<br>
          එකම time එකට කී දෙනෙක් book කළත් → එක unique slot එකක් විතරයි (30 min).<br>
          Normal: Rs. 500 &nbsp;|&nbsp; <strong>Dilini Tharushika Kumari</strong>: Rs. 300
        </p>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Lecturer</th>
              <th>Subjects</th>
              <th>Student Bookings</th>
              <th>Total Hours (unique Accepted)</th>
              <th>Salary (Rs.)</th>
            </tr>
          </thead>
          <tbody id="lecturerBody"></tbody>
        </table>
      </div>
    </div>
    <div class="panel" id="paymentsPanel" style="display:none;">
      <div class="panel-head">
        <h3>Student Payments (by Month) — Normal + Vocabulary</h3>
        <p>Month එක click කරාම detail පේනවා. Vocabulary packages වල <span class="vocab-badge">VOCAB</span> badge එකක් තියෙනවා.</p>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Month</th>
              <th>Summary</th>
            </tr>
          </thead>
          <tbody id="paymentsBody">
            <tr><td colspan="2"><div class="empty-state">Date range එකක් filter කරලා බලන්න.</div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="panel" id="studentsPanel" style="display:none;">
      <div class="panel-head">
        <h3>Students Registered</h3>
        <p>Selected range එකේ register උනු students</p>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Email</th>
              <th>Mobile</th>
              <th>Registered On</th>
            </tr>
          </thead>
          <tbody id="studentsBody"></tbody>
        </table>
      </div>
    </div>
    <div class="panel" id="placeholderPanel">
      <div class="placeholder-state">
        Date range එක select කරලා "Apply Filter" click කරන්න, data පෙනේ.
      </div>
    </div>
  </div>
</div>
<div class="toast" id="toast"></div>
<script>
(function(){
  const BADGE_POLL_INTERVAL_MS = 15000;

  // ========== SALARY RATES ==========
  // IMPORTANT:
  // - Only Accepted STUDENT BOOKINGS count (not admin-added availability slots)
  // - Backend total_hours must be unique accepted time slots
  // - 1 unique accepted slot = 30 minutes
  // - Multiple students on same slot → still counts as 1 × 30 min
  const HOURS_PER_SLOT = 0.5;

  const NORMAL_SESSION_RATE = 500;   // Rs.500 per unique 30-min accepted slot
  const SPECIAL_LECTURER_NAME = 'Dilini Tharushika Kumari';
  const SPECIAL_SESSION_RATE = 300;  // Rs.300 per unique 30-min accepted slot

  function getSessionRate(fullName) {
    if (!fullName) return NORMAL_SESSION_RATE;
    const name = fullName.trim().toLowerCase();
    if (name === SPECIAL_LECTURER_NAME.toLowerCase()) {
      return SPECIAL_SESSION_RATE;
    }
    return NORMAL_SESSION_RATE;
  }

  const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
  if (!adminSession || !adminSession.username) {
    window.location.href = 'admin-login.html';
    return;
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

  function formatMoney(amount) {
    return 'Rs. ' + Number(amount || 0).toLocaleString('en-LK');
  }

  /* ================= Sidebar pending badges ================= */
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
  updateBookingsBadge(); updateStudentsBadge(); updateActivationsBadge();
  updateTeachersBadge(); updateAvailabilityBadge(); updateSupportBadge();
  setInterval(updateBookingsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateStudentsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateActivationsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateTeachersBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateAvailabilityBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateSupportBadge, BADGE_POLL_INTERVAL_MS);

  function pad(n){ return n < 10 ? '0' + n : '' + n; }
  function fmtDate(d){ return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }

  const startDateEl = document.getElementById('startDate');
  const endDateEl = document.getElementById('endDate');
  const applyBtn = document.getElementById('applyBtn');
  const downloadBtn = document.getElementById('downloadBtn');

  const today = new Date();
  const monthAgo = new Date(); monthAgo.setDate(today.getDate() - 30);
  const monthAhead = new Date(); monthAhead.setDate(today.getDate() + 30);
  startDateEl.value = fmtDate(monthAgo);
  endDateEl.value = fmtDate(monthAhead);

  document.querySelectorAll('.quick-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const range = btn.dataset.range;
      const now = new Date();
      if (range === 'thisMonth') {
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        startDateEl.value = fmtDate(first);
        endDateEl.value = fmtDate(now);
      } else if (range === 'allTime') {
        startDateEl.value = '2020-01-01';
        const farFuture = new Date();
        farFuture.setFullYear(now.getFullYear() + 2);
        endDateEl.value = fmtDate(farFuture);
      } else {
        const days = parseInt(range, 10);
        const past = new Date();
        past.setDate(now.getDate() - days);
        startDateEl.value = fmtDate(past);
        endDateEl.value = fmtDate(now);
      }
      applyFilter();
    });
  });

  let lastData = null;
  let lastGroupedLecturers = [];
  let lastPayments = [];
  let lastGroupedMonths = [];

  function groupByLecturer(lecturerHours) {
    const map = {};
    (lecturerHours || []).forEach(l => {
      const id = l.lecturer_id;
      if (!map[id]) {
        map[id] = {
          lecturer_id: id,
          full_name: l.full_name || 'Unknown',
          subjects: new Set(),
          slot_count: 0,        // how many students booked (display only)
          total_hours: 0,       // unique accepted time slots from backend (drives salary)
          details: []
        };
      }
      if (l.subject) map[id].subjects.add(l.subject);
      map[id].slot_count += parseInt(l.slot_count) || 0;
      map[id].total_hours += parseFloat(l.total_hours) || 0;
      map[id].details.push(l);
    });
    return Object.values(map).map(item => ({
      ...item,
      subjects: Array.from(item.subjects).join(', ') || '-'
    })).sort((a, b) => a.full_name.localeCompare(b.full_name));
  }

  function safeSheetName(name, index) {
    let safe = (name || 'Lecturer').replace(/[\\\/\?\*\[\]:]/g, '').trim();
    if (safe.length > 28) safe = safe.substring(0, 28);
    if (!safe) safe = 'Lecturer';
    return safe + (index > 0 ? '_' + index : '');
  }

  function monthKeyFromRaw(raw) {
    if (!raw) return 'unknown';
    const d = new Date(raw);
    if (isNaN(d.getTime())) return 'unknown';
    return d.getFullYear() + '-' + pad(d.getMonth() + 1);
  }
  function monthLabel(key) {
    if (key === 'unknown') return 'Date not set';
    const [y, m] = key.split('-');
    const d = new Date(parseInt(y, 10), parseInt(m, 10) - 1, 1);
    return d.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
  }
  function currentMonthKey() {
    const d = new Date();
    return d.getFullYear() + '-' + pad(d.getMonth() + 1);
  }

  function groupPaymentsByMonth(payments) {
    const map = new Map();
    (payments || []).forEach(p => {
      const key = monthKeyFromRaw(p.purchased_on || p.created_at);
      if (!map.has(key)) {
        map.set(key, { month_key: key, rows: [], total_amount: 0, total_sessions_remaining: 0 });
      }
      const bucket = map.get(key);
      bucket.rows.push(p);
      bucket.total_amount += parseFloat(p.amount) || 0;
      bucket.total_sessions_remaining += parseInt(p.sessions_remaining) || 0;
    });
    const cKey = currentMonthKey();
    return Array.from(map.values()).sort((a, b) => {
      if (a.month_key === cKey) return -1;
      if (b.month_key === cKey) return 1;
      if (a.month_key === 'unknown') return 1;
      if (b.month_key === 'unknown') return -1;
      return b.month_key.localeCompare(a.month_key);
    });
  }

  async function applyFilter() {
    const startDate = startDateEl.value;
    const endDate = endDateEl.value;
    if (!startDate || !endDate) {
      showToast('Date range එකක් select කරන්න', 'error-toast');
      return;
    }
    if (startDate > endDate) {
      showToast('Start date එක end date එකට පස්සේ වෙන්න බෑ', 'error-toast');
      return;
    }
    applyBtn.disabled = true;
    applyBtn.textContent = 'Loading...';
    try {
      // This API MUST return only Accepted student bookings
      // (not admin-added availability slots)
      const res = await fetch(`get_filter_data.php?start_date=${startDate}&end_date=${endDate}`);
      const data = await res.json();
      if (!data.success) {
        showToast(data.message || 'Data load කරන්න බෑ උනා', 'error-toast');
        return;
      }
      lastData = data;
      lastGroupedLecturers = groupByLecturer(data.lecturer_hours);

      let normalPayments = [];
      try {
        const payRes = await fetch(`get_student_payments.php?start_date=${startDate}&end_date=${endDate}`);
        const payData = await payRes.json();
        normalPayments = payData.success ? (payData.data || []) : [];
      } catch (payErr) {
        console.error('get_student_payments.php fetch failed', payErr);
      }

      let vocabPayments = [];
      try {
        const vocabRes = await fetch(`get_vocabulary_payments.php?start_date=${startDate}&end_date=${endDate}`);
        const vocabData = await vocabRes.json();
        vocabPayments = vocabData.success ? (vocabData.data || []) : [];
      } catch (vocabErr) {
        console.error('get_vocabulary_payments.php fetch failed', vocabErr);
      }

      lastPayments = [
        ...normalPayments.map(p => ({ ...p, type: 'normal' })),
        ...vocabPayments.map(p => ({ ...p, type: 'vocabulary', sessions_remaining: 0 }))
      ];
      lastGroupedMonths = groupPaymentsByMonth(lastPayments);

      renderResults(data);
      downloadBtn.disabled = false;
    } catch (err) {
      console.error(err);
      showToast('Server connect උනේ නෑ', 'error-toast');
    } finally {
      applyBtn.disabled = false;
      applyBtn.textContent = 'Apply Filter';
    }
  }

  function renderResults(data) {
    document.getElementById('placeholderPanel').style.display = 'none';
    document.getElementById('statsGrid').style.display = 'grid';
    document.getElementById('tabButtons').style.display = 'flex';

    const activeTab = document.querySelector('.tab-btn.active')?.dataset.tab || 'lecturer';
    document.getElementById('lecturerPanel').style.display = activeTab === 'lecturer' ? 'block' : 'none';
    document.getElementById('paymentsPanel').style.display = activeTab === 'payments' ? 'block' : 'none';
    document.getElementById('studentsPanel').style.display = activeTab === 'students' ? 'block' : 'none';

    const lecturerCount = lastGroupedLecturers.length;
    const studentCount = Array.isArray(data.students) ? data.students.length : (data.summary?.total_students ?? 0);

    // ===== Core logic =====
    // total_hours (from backend) = unique Accepted time slots only
    // salary = unique slots × rate
    // slot_count = number of students who booked (display only)
    // Admin-added slots that nobody booked must NOT appear in total_hours

    let totalSalary = 0;
    let totalDisplayHours = 0;
    let totalStudentBookings = 0;

    lastGroupedLecturers.forEach(l => {
      const rate = getSessionRate(l.full_name);
      const uniqueAcceptedSlots = l.total_hours;   // must come only from Accepted bookings
      totalSalary += uniqueAcceptedSlots * rate;
      totalDisplayHours += uniqueAcceptedSlots * HOURS_PER_SLOT;
      totalStudentBookings += l.slot_count;
    });

    let totalRevenue = 0;
    let totalSessionsRemaining = 0;
    lastPayments.forEach(p => {
      totalRevenue += parseFloat(p.amount) || 0;
      if (p.type !== 'vocabulary') {
        totalSessionsRemaining += parseInt(p.sessions_remaining) || 0;
      }
    });

    document.getElementById('statTotalHours').textContent = totalDisplayHours.toFixed(1);
    document.getElementById('statTotalSalary').textContent = formatMoney(totalSalary);
    document.getElementById('statLecturers').textContent = lecturerCount;
    document.getElementById('statStudents').textContent = studentCount;
    document.getElementById('statSlots').textContent = totalStudentBookings;
    document.getElementById('statPaymentsRevenue').textContent = formatMoney(totalRevenue);
    document.getElementById('statSessionsRemaining').textContent = totalSessionsRemaining;

    const lecturerBody = document.getElementById('lecturerBody');
    if (lastGroupedLecturers.length === 0) {
      lecturerBody.innerHTML = `<tr><td colspan="5"><div class="empty-state">මේ range එකේ Accepted student bookings නෑ.<br>Student කෙනෙක් book කරලා status = Accepted කළාම විතරක් මෙතන පේනවා.<br>(Admin add කරපු slots ගන්නේ නැහැ)</div></td></tr>`;
    } else {
      lecturerBody.innerHTML = lastGroupedLecturers.map(l => {
        const initials = (l.full_name || '?').charAt(0).toUpperCase();
        const rate = getSessionRate(l.full_name);
        const uniqueAcceptedSlots = l.total_hours;
        const displayHours = uniqueAcceptedSlots * HOURS_PER_SLOT;
        const salary = uniqueAcceptedSlots * rate;
        const isSpecial = rate === SPECIAL_SESSION_RATE;
        return `<tr>
          <td>
            <div class="person-cell">
              <span class="person-avatar">${initials}</span>
              <div>
                <div class="person-name">${l.full_name}${isSpecial ? ' <span style="font-size:10px;background:#fdece5;color:#d66c47;padding:1px 6px;border-radius:8px;margin-left:4px;">SPECIAL</span>' : ''}</div>
                <div class="person-sub">ID: ${l.lecturer_id} · Rs.${rate} / 30 min</div>
              </div>
            </div>
          </td>
          <td>${l.subjects}</td>
          <td>${l.slot_count}</td>
          <td><span class="hours-badge">${displayHours} hrs</span></td>
          <td><span class="salary-badge">${formatMoney(salary)}</span></td>
        </tr>`;
      }).join('');
    }

    renderPaymentsTable();

    const studentsBody = document.getElementById('studentsBody');
    if (!data.students || data.students.length === 0) {
      studentsBody.innerHTML = `<tr><td colspan="4"><div class="empty-state">මේ range එකේ students register වෙලා නෑ.</div></td></tr>`;
    } else {
      studentsBody.innerHTML = data.students.map(s => {
        const initials = (s.full_name || '?').charAt(0).toUpperCase();
        const joined = new Date(s.created_at).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' });
        return `<tr>
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
          <td>${joined}</td>
        </tr>`;
      }).join('');
    }
  }

  function renderPaymentsTable() {
    const tbody = document.getElementById('paymentsBody');
    if (!lastGroupedMonths || lastGroupedMonths.length === 0) {
      tbody.innerHTML = `<tr><td colspan="2"><div class="empty-state">මේ range එකේ student payments record වෙලා නෑ.</div></td></tr>`;
      return;
    }
    const cKey = currentMonthKey();
    tbody.innerHTML = lastGroupedMonths.map(m => {
      const isCurrent = m.month_key === cKey;
      const label = monthLabel(m.month_key);
      const rowCount = m.rows.length;
      const detailRowsHtml = m.rows.map(p => {
        const initials = (p.student_name || '?').charAt(0).toUpperCase();
        const remaining = parseInt(p.sessions_remaining) || 0;
        const remainingCls = remaining <= 2 ? 'sessions-badge low' : 'sessions-badge';
        const purchased = p.purchased_on ? new Date(p.purchased_on).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) : '-';
        const typeBadge = p.type === 'vocabulary' ? '<span class="vocab-badge">VOCAB</span>' : '';
        return `<tr>
          <td>
            <div class="person-cell">
              <span class="person-avatar">${initials}</span>
              <div>
                <div class="person-name">${p.student_name || 'Unknown'}</div>
                <div class="person-sub">ID: ${p.student_id ?? '-'}</div>
              </div>
            </div>
          </td>
          <td>${p.package_name || '-'}${typeBadge}</td>
          <td><span class="salary-badge">${formatMoney(p.amount)}</span></td>
          <td>${p.type === 'vocabulary' ? '<span style="color:#8a93a3;font-size:12px;">—</span>' : `<span class="${remainingCls}">${remaining} left</span>`}</td>
          <td>${purchased}</td>
        </tr>`;
      }).join('');
      return `
        <tr class="month-row" data-month-key="${m.month_key}">
          <td>
            <div class="month-row-inner">
              <span class="expand-chevron">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg>
              </span>
              <span class="month-icon ${isCurrent ? 'current' : ''}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
              </span>
              <div>
                <div class="person-name" style="display:flex; align-items:center; gap:7px;">
                  <span class="month-label">${label}</span>
                  ${isCurrent ? '<span class="current-tag">This Month</span>' : ''}
                </div>
                <div class="person-sub">${rowCount} payment${rowCount === 1 ? '' : 's'} · ${formatMoney(m.total_amount)}</div>
              </div>
            </div>
          </td>
          <td>
            <span class="month-summary-pill">${m.total_sessions_remaining} sessions remaining (normal)</span>
          </td>
        </tr>
        <tr class="month-detail-row" data-month-key="${m.month_key}" style="display:${isCurrent ? 'table-row' : 'none'};">
          <td colspan="2">
            <div class="month-detail-wrap">
              <table class="nested-table">
                <thead>
                  <tr>
                    <th>Student</th>
                    <th>Package</th>
                    <th>Amount Paid</th>
                    <th>Sessions Remaining</th>
                    <th>Purchased On</th>
                  </tr>
                </thead>
                <tbody>
                  ${detailRowsHtml}
                </tbody>
              </table>
            </div>
          </td>
        </tr>`;
    }).join('');

    document.querySelectorAll('.month-row').forEach(row => {
      if (row.dataset.monthKey === cKey) row.classList.add('expanded');
      row.addEventListener('click', () => {
        const key = row.dataset.monthKey;
        const detailRow = document.querySelector(`.month-detail-row[data-month-key="${CSS.escape(key)}"]`);
        if (!detailRow) return;
        const isOpen = detailRow.style.display !== 'none';
        detailRow.style.display = isOpen ? 'none' : 'table-row';
        row.classList.toggle('expanded', !isOpen);
      });
    });
  }

  downloadBtn.addEventListener('click', () => {
    if (!lastData) return;

    function autoSizeColumns(rows) {
      const colWidths = [];
      rows.forEach(row => {
        row.forEach((cell, i) => {
          const len = cell === null || cell === undefined ? 0 : String(cell).length;
          colWidths[i] = Math.max(colWidths[i] || 10, len + 2);
        });
      });
      return colWidths.map(w => ({ wch: Math.min(w, 50) }));
    }

    let totalSalary = 0;
    let totalDisplayHours = 0;
    let totalStudentBookings = 0;

    lastGroupedLecturers.forEach(l => {
      const rate = getSessionRate(l.full_name);
      const uniqueAcceptedSlots = l.total_hours;
      totalSalary += uniqueAcceptedSlots * rate;
      totalDisplayHours += uniqueAcceptedSlots * HOURS_PER_SLOT;
      totalStudentBookings += l.slot_count;
    });

    let totalRevenue = 0;
    let totalSessionsRemaining = 0;
    lastPayments.forEach(p => {
      totalRevenue += parseFloat(p.amount) || 0;
      if (p.type !== 'vocabulary') {
        totalSessionsRemaining += parseInt(p.sessions_remaining) || 0;
      }
    });

    const lecturerCount = lastGroupedLecturers.length;
    const studentCount = Array.isArray(lastData.students) ? lastData.students.length : (lastData.summary?.total_students ?? 0);

    const wb = XLSX.utils.book_new();

    // Sheet 1: Summary
    const summaryData = [
      ['Sipway Campus - Filter Report (Accepted Student Bookings only)'],
      [`Range: ${lastData.range.start} to ${lastData.range.end}`],
      ['Admin-added slots that were NEVER booked by a student are NOT counted'],
      ['1 unique Accepted time slot = 30 minutes'],
      [`Normal: Rs.${NORMAL_SESSION_RATE} / 30 min  |  Dilini Tharushika Kumari: Rs.${SPECIAL_SESSION_RATE} / 30 min`],
      [],
      ['Metric', 'Value'],
      ['Total Student Bookings (Accepted)', totalStudentBookings],
      ['Total Lecture Hours (unique Accepted slots)', totalDisplayHours],
      ['Total Salary (Rs.)', totalSalary],
      ['Lecturers Involved', lecturerCount],
      ['Students Registered', studentCount],
      ['Total Student Payments (Normal + Vocab) Rs.', totalRevenue],
      ['Total Sessions Remaining (Normal only)', totalSessionsRemaining],
    ];
    const summarySheet = XLSX.utils.aoa_to_sheet(summaryData);
    summarySheet['!cols'] = [{ wch: 60 }, { wch: 40 }];
    XLSX.utils.book_append_sheet(wb, summarySheet, 'Summary');

    // Sheet 2: All Lecturers
    const allLecturerRows = [
      [`All Lecturers - Accepted Student Bookings only | Range: ${lastData.range.start} to ${lastData.range.end}`],
      [],
      ['Lecturer ID', 'Full Name', 'Subjects', 'Student Bookings', 'Unique Accepted Slots', 'Hours (30 min)', 'Rate (Rs / 30 min)', 'Salary (Rs.)']
    ];
    lastGroupedLecturers.forEach(l => {
      const rate = getSessionRate(l.full_name);
      const uniqueAcceptedSlots = l.total_hours;
      const displayHours = uniqueAcceptedSlots * HOURS_PER_SLOT;
      const salary = uniqueAcceptedSlots * rate;
      allLecturerRows.push([
        l.lecturer_id,
        l.full_name,
        l.subjects,
        l.slot_count,
        uniqueAcceptedSlots,
        displayHours,
        rate,
        salary
      ]);
    });
    if (lastGroupedLecturers.length > 0) {
      allLecturerRows.push([]);
      allLecturerRows.push(['', '', '', 'TOTAL', '', totalDisplayHours, '', totalSalary]);
    }
    const allLecturerSheet = XLSX.utils.aoa_to_sheet(allLecturerRows);
    allLecturerSheet['!cols'] = autoSizeColumns(allLecturerRows);
    allLecturerSheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 7 } }];
    XLSX.utils.book_append_sheet(wb, allLecturerSheet, 'All Lecturers');

    // Sheet 3: All Student Payments
    const allPaymentsRows = [
      [`All Student Payments (Normal + Vocabulary) | Range: ${lastData.range.start} to ${lastData.range.end}`],
      [],
      ['Student ID', 'Student Name', 'Package', 'Type', 'Amount Paid (Rs.)', 'Sessions Remaining', 'Purchased On', 'Month']
    ];
    lastPayments.forEach(p => {
      allPaymentsRows.push([
        p.student_id ?? '-',
        p.student_name || '-',
        p.package_name || '-',
        p.type === 'vocabulary' ? 'Vocabulary' : 'Normal',
        parseFloat(p.amount) || 0,
        p.type === 'vocabulary' ? '—' : (parseInt(p.sessions_remaining) || 0),
        p.purchased_on || '-',
        monthLabel(monthKeyFromRaw(p.purchased_on || p.created_at))
      ]);
    });
    if (lastPayments.length > 0) {
      allPaymentsRows.push([]);
      allPaymentsRows.push(['', '', '', 'TOTAL', totalRevenue, totalSessionsRemaining, '', '']);
    }
    const allPaymentsSheet = XLSX.utils.aoa_to_sheet(allPaymentsRows);
    allPaymentsSheet['!cols'] = autoSizeColumns(allPaymentsRows);
    allPaymentsSheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 7 } }];
    XLSX.utils.book_append_sheet(wb, allPaymentsSheet, 'All Payments');

    // Month-wise sheets
    const usedMonthNames = {};
    lastGroupedMonths.forEach(m => {
      const label = monthLabel(m.month_key);
      let baseName = 'Pay_' + label.replace(/[\\\/\?\*\[\]:]/g, '').trim();
      if (baseName.length > 28) baseName = baseName.substring(0, 28);
      let sheetName = baseName;
      let counter = 1;
      while (usedMonthNames[sheetName]) {
        sheetName = (baseName.length > 25 ? baseName.substring(0, 25) : baseName) + '_' + counter;
        counter++;
      }
      usedMonthNames[sheetName] = true;

      const monthRows = [
        [`Student Payments - ${label}`],
        [`Total: ${m.rows.length} payments | Amount: Rs. ${m.total_amount} | Sessions Remaining: ${m.total_sessions_remaining}`],
        [],
        ['Student ID', 'Student Name', 'Package', 'Type', 'Amount Paid (Rs.)', 'Sessions Remaining', 'Purchased On']
      ];
      m.rows.forEach(p => {
        monthRows.push([
          p.student_id ?? '-',
          p.student_name || '-',
          p.package_name || '-',
          p.type === 'vocabulary' ? 'Vocabulary' : 'Normal',
          parseFloat(p.amount) || 0,
          p.type === 'vocabulary' ? '—' : (parseInt(p.sessions_remaining) || 0),
          p.purchased_on || '-'
        ]);
      });
      monthRows.push([]);
      monthRows.push(['', '', '', 'TOTAL', m.total_amount, m.total_sessions_remaining, '']);

      const monthSheet = XLSX.utils.aoa_to_sheet(monthRows);
      monthSheet['!cols'] = autoSizeColumns(monthRows);
      monthSheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 6 } }];
      XLSX.utils.book_append_sheet(wb, monthSheet, sheetName);
    });

    // Students sheet
    const studentRows = [
      [`Students Registered: ${Array.isArray(lastData.students) ? lastData.students.length : 0}`],
      [],
      ['Student ID', 'Full Name', 'Mobile Number', 'Email', 'Registered On']
    ];
    (lastData.students || []).forEach(s => {
      studentRows.push([
        s.id,
        s.full_name || '-',
        s.mobile || '-',
        s.email || '-',
        s.created_at
      ]);
    });
    const studentSheet = XLSX.utils.aoa_to_sheet(studentRows);
    studentSheet['!cols'] = autoSizeColumns(studentRows);
    studentSheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 4 } }];
    XLSX.utils.book_append_sheet(wb, studentSheet, 'Students');

    // Individual Lecturer sheets
    const usedNames = {};
    lastGroupedLecturers.forEach((l) => {
      const rate = getSessionRate(l.full_name);
      const uniqueAcceptedSlots = l.total_hours;
      const displayHours = uniqueAcceptedSlots * HOURS_PER_SLOT;
      const salary = uniqueAcceptedSlots * rate;
      let baseName = safeSheetName(l.full_name, 0);
      let sheetName = baseName;
      let counter = 1;
      while (usedNames[sheetName]) {
        sheetName = safeSheetName(l.full_name, counter);
        counter++;
      }
      usedNames[sheetName] = true;

      const lecturerSheetData = [
        ['Sipway Campus - Lecturer Salary (Accepted Student Bookings only)'],
        [`Range: ${lastData.range.start} to ${lastData.range.end}`],
        ['Admin-added slots with NO student booking are NOT counted'],
        [`Rate: Rs. ${rate} per unique Accepted 30-min slot`],
        [],
        ['Lecturer Details'],
        ['Lecturer ID', l.lecturer_id],
        ['Full Name', l.full_name],
        ['Subjects', l.subjects],
        ['Student Bookings (how many students)', l.slot_count],
        ['Unique Accepted Time Slots', uniqueAcceptedSlots],
        ['Total Hours (30 min × unique slots)', displayHours],
        ['Rate (Rs / 30 min)', rate],
        ['Salary (Rs.)', salary],
        [],
        ['Subject-wise Breakdown'],
        ['Subject', 'Student Bookings', 'Unique Accepted Slots', 'Hours (30 min)', 'Salary (Rs.)']
      ];

      if (l.details && l.details.length > 0) {
        l.details.forEach(d => {
          const students = parseInt(d.slot_count) || 0;
          const unique = parseFloat(d.total_hours) || 0;
          const h = unique * HOURS_PER_SLOT;
          lecturerSheetData.push([
            d.subject || '-',
            students,
            unique,
            h,
            unique * rate
          ]);
        });
      } else {
        lecturerSheetData.push(['-', l.slot_count, uniqueAcceptedSlots, displayHours, salary]);
      }

      lecturerSheetData.push([]);
      lecturerSheetData.push(['TOTAL', l.slot_count, uniqueAcceptedSlots, displayHours, salary]);

      const lecturerSheet = XLSX.utils.aoa_to_sheet(lecturerSheetData);
      lecturerSheet['!cols'] = [
        { wch: 28 }, { wch: 20 }, { wch: 18 }, { wch: 16 }, { wch: 14 }
      ];
      XLSX.utils.book_append_sheet(wb, lecturerSheet, sheetName);
    });

    const filename = `Sipway_Filter_Report_${lastData.range.start}_to_${lastData.range.end}.xlsx`;
    XLSX.writeFile(wb, filename);
    showToast(`Excel download උනා ✅`);
  });

  document.getElementById('tabButtons').addEventListener('click', (e) => {
    const btn = e.target.closest('.tab-btn');
    if (!btn) return;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const tab = btn.dataset.tab;
    document.getElementById('lecturerPanel').style.display = tab === 'lecturer' ? 'block' : 'none';
    document.getElementById('paymentsPanel').style.display = tab === 'payments' ? 'block' : 'none';
    document.getElementById('studentsPanel').style.display = tab === 'students' ? 'block' : 'none';
  });

  applyBtn.addEventListener('click', applyFilter);
  document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarBackdrop').classList.toggle('show');
  });
  document.getElementById('sidebarBackdrop')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('show');
  });
document.getElementById('logoutBtn')?.addEventListener('click', () => {
  window.location.href = 'admin_logout.php';
});

  applyFilter();
})();
</script>
</body>
</html>