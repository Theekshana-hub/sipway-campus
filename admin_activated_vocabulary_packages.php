<?php
session_start();

?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vocabulary Activations - Admin | Sipway</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy:#0f2a4a; --navy-2:#16385f; --navy-soft:#eef2f7;
    --coral:#e8825f; --coral-dark:#d66c47; --coral-soft:#fdece5;
    --bg:#f6f5f3; --card:#ffffff; --line:#e6e2da; --line-soft:#f0ede7;
    --muted:#6b7280; --muted-2:#8a93a3; --text:#1b2430;
    --danger:#c0392b; --danger-soft:#fdecea;
    --success:#1f9d55; --success-soft:#e8f8ee;
    --warning:#f2994a; --warning-soft:#fdf1e4;
    --info:#2563eb; --info-soft:#e6eef7;
    --purple:#7c3aed; --purple-soft:#f3e8ff;
    --radius-lg:16px; --radius-md:10px; --radius-sm:8px;
    --shadow-card:0 10px 34px -12px rgba(15,42,74,0.14);
    --ease:cubic-bezier(.4,0,.2,1); --sidebar-w:250px;
  }
  *{ box-sizing:border-box; }
  body{ margin:0; font-family:'Inter','Noto Sans Sinhala',sans-serif; background:var(--bg); color:var(--text); }
  a{ text-decoration:none; color:inherit; }

  .sidebar{
    position:fixed; top:0; left:0; bottom:0; width:var(--sidebar-w);
    background:linear-gradient(180deg, var(--navy) 0%, #0b2039 100%);
    color:#fff; display:flex; flex-direction:column; z-index:50;
    transition:transform .25s var(--ease);
  }
  .sidebar-brand{
    display:flex; align-items:center; gap:10px; padding:22px 22px 20px;
    font-weight:800; font-size:15px; border-bottom:1px solid rgba(255,255,255,0.08);
  }
  .brand-mark{
    width:34px; height:34px; border-radius:9px;
    background:linear-gradient(135deg, var(--coral), var(--coral-dark));
    display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:800;
  }
  .sidebar-brand .sub{ display:block; font-size:10.5px; font-weight:600; color:rgba(255,255,255,0.55); margin-top:2px; }
  .nav-group{ padding:18px 12px; flex:1; overflow-y:auto; }
  .nav-label{ font-size:10.5px; font-weight:700; letter-spacing:1.2px; color:rgba(255,255,255,0.35); text-transform:uppercase; padding:8px 12px 6px; }
  .nav-item{
    display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
    font-size:13.5px; font-weight:600; color:rgba(255,255,255,0.75); margin-bottom:3px;
    transition:background .15s, color .15s; position:relative;
  }
  .nav-item svg{ width:18px; height:18px; flex-shrink:0; }
  .nav-item:hover{ background:rgba(255,255,255,0.06); color:#fff; }
  .nav-item.active{ background:rgba(232,130,95,0.16); color:#fff; }
  .nav-item.active::before{
    content:""; position:absolute; left:-12px; top:8px; bottom:8px;
    width:3px; border-radius:3px; background:var(--coral);
  }
  .nav-item .badge-count{
    margin-left:auto; background:var(--coral); color:#fff;
    font-size:10.5px; font-weight:800; padding:2px 7px; border-radius:20px; flex-shrink:0;
  }
  .sidebar-foot{ padding:16px 14px 20px; border-top:1px solid rgba(255,255,255,0.08); }
 .logout-btn{
    display:flex;
    align-items:center;
    gap:10px;
    width:100%;
    padding:11px 14px;
    border-radius:10px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.1);
    color:#fff;
    font-weight:700;
    font-size:13px;
    cursor:pointer;
    transition:background .15s var(--ease);
  }
  .logout-btn:hover{ background:rgba(192,57,43,0.35); border-color:rgba(192,57,43,0.5); }
  .logout-btn svg{ width:16px; height:16px; }

  .main{ margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
  .topbar{
    height:68px; display:flex; align-items:center; justify-content:space-between;
    padding:0 28px; background:rgba(255,255,255,0.9); border-bottom:1px solid var(--line-soft);
    position:sticky; top:0; z-index:30;
  }
  .menu-toggle{ display:none; background:none; border:none; cursor:pointer; color:var(--navy); padding:6px; }
  .topbar-title h2{ margin:0; font-size:18px; font-weight:800; color:var(--navy); }
  .topbar-title p{ margin:2px 0 0; font-size:12.5px; color:var(--muted); }

  .content{ padding:26px 28px 60px; flex:1; }

  .greeting{ margin-bottom:22px; }
  .greeting h1{ font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--navy); margin:0 0 4px; }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }

  .panel{
    background:var(--card); border:1px solid var(--line-soft);
    border-radius:var(--radius-lg); box-shadow:var(--shadow-card); overflow:hidden;
  }
  .panel-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:18px 20px; border-bottom:1px solid var(--line-soft); gap:10px; flex-wrap:wrap;
  }
  .panel-head h3{ margin:0; font-size:15.5px; font-weight:800; color:var(--navy); }
  .panel-head p{ margin:2px 0 0; font-size:12px; color:var(--muted); }
  .head-controls{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

  .filter-pills, .pay-pills{
    display:flex; gap:6px; background:var(--bg); padding:4px;
    border-radius:9px; border:1px solid var(--line-soft); flex-wrap:wrap;
  }
  .filter-pill, .pay-pill{
    padding:7px 13px; border-radius:7px; font-size:12px; font-weight:700;
    color:var(--muted); cursor:pointer; border:none; background:none; white-space:nowrap;
  }
  .filter-pill:hover, .pay-pill:hover{ color:var(--navy); }
  .filter-pill.active{ background:#fff; color:var(--coral-dark); box-shadow:0 2px 8px rgba(15,42,74,0.08); }
  .pay-pill.active{ background:#fff; color:var(--purple); box-shadow:0 2px 8px rgba(15,42,74,0.08); }

  .search-box{
    padding:9px 14px; border:1.5px solid var(--line); border-radius:8px;
    font-size:13px; background:var(--bg); min-width:200px; font-family:inherit;
  }
  .search-box:focus{ outline:none; border-color:var(--coral); background:#fff; }
  .row-count{
    font-size:11.5px; font-weight:700; color:var(--coral-dark);
    background:var(--coral-soft); padding:6px 13px; border-radius:999px;
  }

  .table-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; min-width:900px; }
  thead th{
    text-align:left; font-size:11px; font-weight:700; color:var(--muted-2);
    text-transform:uppercase; letter-spacing:0.6px; padding:12px 22px;
    background:#fbfaf9; border-bottom:1px solid var(--line-soft);
  }
  tbody td{ padding:14px 22px; font-size:13.5px; border-bottom:1px solid var(--line-soft); vertical-align:middle; }

  .student-row{ cursor:pointer; }
  .student-row:hover{ background:#fbfaf9; }
  .student-row.expanded{ background:var(--navy-soft); }
  .student-row-inner{ display:flex; align-items:center; gap:10px; }
  .expand-chevron{
    width:20px; height:20px; color:var(--muted-2); transition:transform .2s;
    display:flex; align-items:center; justify-content:center;
  }
  .student-row.expanded .expand-chevron{ transform:rotate(90deg); color:var(--coral-dark); }
  .person-avatar{
    width:32px; height:32px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:11.5px; font-weight:800; color:#fff;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
  }
  .person-name{ font-weight:700; font-size:13.5px; display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
  .person-sub{ font-size:11.5px; color:var(--muted-2); }

  .group-status-tag{
    display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px;
    font-size:10px; font-weight:800; text-transform:uppercase;
  }
  .group-status-tag::before{ content:""; width:5px; height:5px; border-radius:50%; background:currentColor; }
  .group-status-tag.status-pending{ background:var(--warning-soft); color:var(--warning); }
  .group-status-tag.status-active{ background:var(--success-soft); color:var(--success); }
  .group-status-tag.status-expired{ background:var(--info-soft); color:var(--info); }
  .group-status-tag.status-cancelled{ background:var(--danger-soft); color:var(--danger); }

  .detail-row td{ padding:0; }
  .detail-wrap{ background:#fbfaf9; padding:6px 22px 18px 58px; }
  .nested-table{ width:100%; border-collapse:collapse; }
  .nested-table thead th{ background:transparent; padding:8px 14px; font-size:10.5px; border-bottom:1px solid var(--line); }
  .nested-table tbody td{ padding:12px 14px; font-size:13px; border-bottom:1px solid var(--line-soft); background:#fff; }
  .nested-table tbody tr:last-child td{ border-bottom:none; }
  .nested-table tbody tr.row-pending td{ background:#fffdf8; }

  .status-badge{
    display:inline-flex; padding:5px 11px; border-radius:20px; font-size:11px; font-weight:800; text-transform:capitalize;
  }
  .status-pending{ background:var(--warning-soft); color:var(--warning); }
  .status-active{ background:var(--success-soft); color:var(--success); }
  .status-expired{ background:var(--info-soft); color:var(--info); }
  .status-cancelled{ background:var(--danger-soft); color:var(--danger); }

  .pay-badge{
    display:inline-flex; padding:5px 11px; border-radius:20px; font-size:11px; font-weight:800;
  }
  .pay-online{ background:var(--info-soft); color:var(--info); }
  .pay-bank{ background:var(--purple-soft); color:var(--purple); }

  .action-cell{ display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
  .icon-btn{
    width:30px; height:30px; border-radius:8px; border:none;
    display:flex; align-items:center; justify-content:center; cursor:pointer;
  }
  .icon-btn svg{ width:15px; height:15px; }
  .btn-edit{ background:var(--navy-soft); color:var(--navy); }
  .btn-delete{ background:var(--danger-soft); color:var(--danger); }
  .btn-approve{ background:var(--success-soft); color:var(--success); }
  .btn-receipt{ background:var(--purple-soft); color:var(--purple); }

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
  .modal-box.receipt-box{ max-width:560px; }
  .modal-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 22px; border-bottom:1px solid var(--line-soft);
  }
  .modal-head h3{ margin:0; font-size:16px; font-weight:800; color:var(--navy); }
  .modal-close{
    background:var(--navy-soft); border:none; width:30px; height:30px;
    border-radius:8px; cursor:pointer; color:var(--navy); font-size:16px;
  }
  .modal-body{ padding:22px; }
  .form-group{ margin-bottom:16px; }
  .form-group label{ display:block; font-size:12.5px; font-weight:700; color:var(--navy); margin-bottom:6px; }
  .form-group select{
    width:100%; padding:11px 13px; border:1.5px solid var(--line);
    border-radius:9px; font-size:13.5px; background:var(--bg); font-family:inherit;
  }
  .form-group select:focus{ outline:none; border-color:var(--coral); background:#fff; }
  .info-line{
    font-size:13px; color:var(--muted); margin:0 0 16px; line-height:1.6;
    background:var(--navy-soft); padding:10px 13px; border-radius:9px;
  }
  .info-line b{ color:var(--navy); }
  .confirm-text{ font-size:13.5px; line-height:1.6; margin:0; }
  .confirm-text b{ color:var(--navy); }
  .modal-foot{ display:flex; gap:10px; padding:18px 22px 22px; }
  .btn-cancel, .btn-save{
    flex:1; padding:12px; border-radius:9px; font-weight:700; font-size:13.5px;
    cursor:pointer; border:none;
  }
  .btn-cancel{ background:var(--navy-soft); color:var(--navy); }
  .btn-save{ background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%); color:#fff; }
  .btn-save.btn-danger{ background:linear-gradient(135deg, #d9534f 0%, var(--danger) 100%); }
  .btn-save.btn-success{ background:linear-gradient(135deg, #2fb872 0%, var(--success) 100%); }

  .receipt-meta{ font-size:13px; margin-bottom:14px; line-height:1.7; color:var(--muted); }
  .receipt-meta b{ color:var(--navy); }
  .receipt-preview{
    border:1px solid var(--line); border-radius:12px; overflow:hidden;
    background:#f8fafc; min-height:200px; display:flex; align-items:center; justify-content:center;
  }
  .receipt-preview img{ max-width:100%; max-height:420px; display:block; }
  .receipt-preview iframe{ width:100%; height:420px; border:none; }

  .toast{
    position:fixed; top:20px; left:50%; transform:translateX(-50%) translateY(-16px);
    background:var(--navy); color:#fff; padding:13px 22px; border-radius:10px;
    font-size:13.5px; font-weight:600; opacity:0; pointer-events:none;
    transition:opacity .25s, transform .25s; z-index:100;
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error-toast{ background:var(--danger); }
  .empty-state{ padding:40px 20px; text-align:center; color:var(--muted-2); font-size:13px; font-weight:600; }
  .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45; }

  @media (max-width:880px){
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
    .sidebar-backdrop.show{ display:block; }
  }
  @media (max-width:560px){
    .panel-head{ flex-direction:column; align-items:stretch; }
    .detail-wrap{ padding-left:22px; }
  }
</style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-mark">SC</span>
    <span>Sipway English Accademy<span class="sub">ADMIN PANEL</span></span>
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
    <a class="nav-item" href="filter.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
      Filter
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
    <div style="display:flex;align-items:center;gap:14px;">
      <button class="menu-toggle" id="menuToggle" type="button">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title">
        <h2>Vocabulary Activations</h2>
        <p>Online / Bank Receipt filter කරන්න. Approve කළාම student videos unlock වේ.</p>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="greeting">
      <h1>Vocabulary Package Activations 📚</h1>
      <p>Bank receipt තියෙනවා නම් View Receipt ක්ලික් කරන්න. Approve කළාම student vocabulary videos unlock වේ.</p>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>All Vocabulary Activations</h3>
          <p>From activated_vocabulary_packages table</p>
        </div>
        <div class="head-controls">
          <div class="filter-pills" id="filterPills">
            <button class="filter-pill active" data-filter="all">All</button>
            <button class="filter-pill" data-filter="pending">Pending</button>
            <button class="filter-pill" data-filter="active">Active</button>
            <button class="filter-pill" data-filter="expired">Expired</button>
            <button class="filter-pill" data-filter="cancelled">Cancelled</button>
          </div>
          <div class="pay-pills" id="payPills">
            <button class="pay-pill active" data-pay="all">All Payments</button>
            <button class="pay-pill" data-pay="online">💳 Online</button>
            <button class="pay-pill" data-pay="bank_transfer">🏦 Bank Receipt</button>
          </div>
          <input type="text" class="search-box" id="searchBox" placeholder="Search student, package...">
          <span class="row-count" id="rowCount">0 records</span>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Student</th></tr>
          </thead>
          <tbody id="activationsBody">
            <tr><td><div class="empty-state">Loading...</div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Edit Vocabulary Activation</h3>
      <button class="modal-close" id="closeModalBtn">✕</button>
    </div>
    <div class="modal-body">
      <p class="info-line" id="editInfoLine">Student — Package</p>
      <div class="form-group">
        <label>Status</label>
        <select id="statusSelect">
          <option value="pending">Pending</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
      <button type="button" class="btn-save" id="saveBtn">Save Changes</button>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModalOverlay">
  <div class="modal-box confirm-box">
    <div class="modal-head">
      <h3>Delete Activation</h3>
      <button class="modal-close" id="closeDeleteModalBtn">✕</button>
    </div>
    <div class="modal-body">
      <p class="confirm-text">Confirm delete <b id="deleteRecordName"></b>? This cannot be undone.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
      <button type="button" class="btn-save btn-danger" id="confirmDeleteBtn">Delete</button>
    </div>
  </div>
</div>

<!-- Approve Modal -->
<div class="modal-overlay" id="approveModalOverlay">
  <div class="modal-box confirm-box">
    <div class="modal-head">
      <h3>Approve Vocabulary Package</h3>
      <button class="modal-close" id="closeApproveModalBtn">✕</button>
    </div>
    <div class="modal-body">
      <p class="confirm-text">Approve <b id="approveRecordName"></b>? Student will unlock vocabulary videos.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-cancel" id="cancelApproveBtn">Cancel</button>
      <button type="button" class="btn-save btn-success" id="confirmApproveBtn">Approve ✓</button>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="receiptModalOverlay">
  <div class="modal-box receipt-box">
    <div class="modal-head">
      <h3>Bank Receipt</h3>
      <button class="modal-close" id="closeReceiptModalBtn">✕</button>
    </div>
    <div class="modal-body">
      <div class="receipt-meta" id="receiptMeta"></div>
      <div class="receipt-preview" id="receiptPreview">Loading...</div>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-cancel" id="closeReceiptBtn" style="flex:1;">Close</button>
      <a id="receiptDownloadLink" href="#" target="_blank" class="btn-save" style="flex:1;text-align:center;display:flex;align-items:center;justify-content:center;">Open Full File</a>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
  const BADGE_POLL_INTERVAL_MS = 15000;

  let allActivations = [];
  let currentFilter = 'all';
  let currentPayFilter = 'all';
  let pendingDeleteId = null;
  let pendingApproveId = null;
  let editingId = null;

  // Admin session check
  const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
  if (!adminSession || !adminSession.username) {
    window.location.href = 'admin_login.php';
  }

  function showToast(msg, type = '') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = 'toast show' + (type ? ' ' + type : '');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function statusBadge(status) {
    const s = status || 'pending';
    return `<span class="status-badge status-${s}">${s}</span>`;
  }

  function payBadge(method) {
    const m = (method || 'online').toLowerCase();
    if (m === 'bank_transfer') return `<span class="pay-badge pay-bank">🏦 Bank Receipt</span>`;
    return `<span class="pay-badge pay-online">💳 Online</span>`;
  }

  async function loadActivations() {
    const tbody = document.getElementById('activationsBody');
    tbody.innerHTML = `<tr><td><div class="empty-state">Loading...</div></td></tr>`;
    try {
      const res = await fetch('get_vocab_activations.php');
      const data = await res.json();
      if (data.success) {
        allActivations = data.data || [];
        applyFilters();
        updateVocabBadgeLocal(allActivations);
      } else {
        tbody.innerHTML = `<tr><td><div class="empty-state">Error: ${escapeHtml(data.message || 'Failed')}</div></td></tr>`;
      }
    } catch (err) {
      tbody.innerHTML = `<tr><td><div class="empty-state">Server error. Check get_vocab_activations.php</div></td></tr>`;
    }
  }

  function updateVocabBadgeLocal(list) {
    const pending = list.filter(a => (a.status || 'pending') === 'pending').length;
    const badge = document.getElementById('pendingVocabBadge');
    if (pending > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = pending;
    } else {
      badge.style.display = 'none';
    }
  }

  function applyFilters() {
    const q = document.getElementById('searchBox').value.toLowerCase().trim();
    let list = allActivations;
    if (currentFilter !== 'all') list = list.filter(a => (a.status || 'pending') === currentFilter);
    if (currentPayFilter === 'online') {
      list = list.filter(a => (a.payment_method || 'online').toLowerCase() !== 'bank_transfer');
    } else if (currentPayFilter === 'bank_transfer') {
      list = list.filter(a => (a.payment_method || '').toLowerCase() === 'bank_transfer');
    }
    if (q) {
      list = list.filter(a =>
        (a.student_name || '').toLowerCase().includes(q) ||
        (a.package_name || '').toLowerCase().includes(q) ||
        (a.transaction_ref || '').toLowerCase().includes(q)
      );
    }
    renderTable(list);
  }

  function groupStatus(records) {
    const s = records.map(a => a.status || 'pending');
    if (s.includes('pending')) return 'pending';
    if (s.includes('active')) return 'active';
    if (s.includes('expired')) return 'expired';
    if (s.includes('cancelled')) return 'cancelled';
    return 'pending';
  }

  function groupByStudent(list) {
    const map = new Map();
    list.forEach(a => {
      if (!map.has(a.student_id)) {
        map.set(a.student_id, { student_id: a.student_id, student_name: a.student_name, records: [] });
      }
      map.get(a.student_id).records.push(a);
    });
    return Array.from(map.values());
  }

  function renderTable(list) {
    const tbody = document.getElementById('activationsBody');
    document.getElementById('rowCount').textContent = list.length + ' record' + (list.length !== 1 ? 's' : '');
    if (!list.length) {
      tbody.innerHTML = `<tr><td><div class="empty-state">No activations found</div></td></tr>`;
      return;
    }

    const groups = groupByStudent(list);
    tbody.innerHTML = groups.map(g => {
      const initials = g.student_name ? g.student_name.charAt(0).toUpperCase() : '?';
      const overall = groupStatus(g.records);
      const detailRows = g.records.map(a => {
        const status = a.status || 'pending';
        const isPending = status === 'pending';
        const isBank = (a.payment_method || '').toLowerCase() === 'bank_transfer';
        const price = 'Rs. ' + Number(a.price || 0).toLocaleString('en-US', {maximumFractionDigits:0});
        const dateStr = a.activated_at
          ? new Date(a.activated_at).toLocaleDateString('en-GB')
          : (a.created_at ? new Date(a.created_at).toLocaleDateString('en-GB') : '-');
        const ref = a.transaction_ref
          ? `<div style="font-size:11px;color:var(--muted-2);margin-top:3px;">Ref: ${escapeHtml(a.transaction_ref)}</div>`
          : '';

        return `
          <tr class="${isPending ? 'row-pending' : ''}">
            <td>${escapeHtml(a.package_name)}${ref}</td>
            <td>${price}</td>
            <td>${escapeHtml(a.duration_label || '-')}</td>
            <td>${payBadge(a.payment_method)}</td>
            <td>${statusBadge(status)}</td>
            <td>${dateStr}</td>
            <td>
              <div class="action-cell">
                ${isBank && a.receipt_path ? `
                <button class="icon-btn btn-receipt" title="View Receipt" data-action="receipt" data-id="${a.id}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                </button>` : ''}
                ${isPending ? `
                <button class="icon-btn btn-approve" title="Approve" data-action="approve" data-id="${a.id}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </button>` : ''}
                <button class="icon-btn btn-edit" title="Edit" data-action="edit" data-id="${a.id}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </button>
                <button class="icon-btn btn-delete" title="Delete" data-action="delete" data-id="${a.id}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z"/></svg>
                </button>
              </div>
            </td>
          </tr>`;
      }).join('');

      return `
        <tr class="student-row" data-student-id="${g.student_id}">
          <td>
            <div class="student-row-inner">
              <span class="expand-chevron"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg></span>
              <span class="person-avatar">${initials}</span>
              <div>
                <div class="person-name"><span class="group-status-tag status-${overall}">${overall}</span>${escapeHtml(g.student_name)}</div>
                <div class="person-sub">ID: ${g.student_id} · ${g.records.length} package${g.records.length === 1 ? '' : 's'}</div>
              </div>
            </div>
          </td>
        </tr>
        <tr class="detail-row" data-student-id="${g.student_id}" style="display:none;">
          <td>
            <div class="detail-wrap">
              <table class="nested-table">
                <thead>
                  <tr>
                    <th>Package</th><th>Price</th><th>Duration</th>
                    <th>Payment</th><th>Status</th><th>Date</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody>${detailRows}</tbody>
              </table>
            </div>
          </td>
        </tr>`;
    }).join('');

    document.querySelectorAll('.student-row').forEach(row => {
      row.addEventListener('click', () => {
        const sid = row.dataset.studentId;
        const detail = document.querySelector(`.detail-row[data-student-id="${sid}"]`);
        if (!detail) return;
        const open = detail.style.display !== 'none';
        detail.style.display = open ? 'none' : 'table-row';
        row.classList.toggle('expanded', !open);
      });
    });
  }

  // Event listeners
  document.getElementById('searchBox').addEventListener('input', applyFilters);
  document.getElementById('filterPills').addEventListener('click', e => {
    const btn = e.target.closest('.filter-pill');
    if (!btn) return;
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    applyFilters();
  });
  document.getElementById('payPills').addEventListener('click', e => {
    const btn = e.target.closest('.pay-pill');
    if (!btn) return;
    document.querySelectorAll('.pay-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    currentPayFilter = btn.dataset.pay;
    applyFilters();
  });

  // Edit
  const modal = document.getElementById('modalOverlay');
  function editActivation(id) {
    const r = allActivations.find(a => a.id === id);
    if (!r) return;
    editingId = id;
    document.getElementById('editInfoLine').innerHTML = `<b>${escapeHtml(r.student_name)}</b> — ${escapeHtml(r.package_name)}`;
    document.getElementById('statusSelect').value = r.status || 'pending';
    modal.classList.add('show');
  }
  document.getElementById('closeModalBtn').onclick = document.getElementById('cancelBtn').onclick = () => modal.classList.remove('show');
  modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('show'); });

  document.getElementById('saveBtn').addEventListener('click', async () => {
    if (!editingId) return;
    const btn = document.getElementById('saveBtn');
    const status = document.getElementById('statusSelect').value;
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
      const res = await fetch('update_vocab_activation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: editingId, status })
      });
      const data = await res.json();
      if (data.success) { showToast('Updated ✅'); modal.classList.remove('show'); loadActivations(); }
      else showToast(data.message || 'Failed', 'error-toast');
    } catch (e) { showToast('Server error', 'error-toast'); }
    finally { btn.disabled = false; btn.textContent = 'Save Changes'; }
  });

  // Delete
  const deleteModal = document.getElementById('deleteModalOverlay');
  function openDeleteModal(r) {
    pendingDeleteId = r.id;
    document.getElementById('deleteRecordName').textContent = r.student_name + ' — ' + r.package_name;
    deleteModal.classList.add('show');
  }
  function closeDeleteModal() { deleteModal.classList.remove('show'); pendingDeleteId = null; }
  document.getElementById('closeDeleteModalBtn').onclick = document.getElementById('cancelDeleteBtn').onclick = closeDeleteModal;
  deleteModal.addEventListener('click', e => { if (e.target === deleteModal) closeDeleteModal(); });

  document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true; btn.textContent = 'Deleting...';
    try {
      const res = await fetch('delete_vocab_activation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: pendingDeleteId })
      });
      const data = await res.json();
      if (data.success) { showToast('Deleted 🗑️'); closeDeleteModal(); loadActivations(); }
      else showToast(data.message || 'Failed', 'error-toast');
    } catch (e) { showToast('Delete failed', 'error-toast'); }
    finally { btn.disabled = false; btn.textContent = 'Delete'; }
  });

  // Approve
  const approveModal = document.getElementById('approveModalOverlay');
  function openApproveModal(r) {
    pendingApproveId = r.id;
    document.getElementById('approveRecordName').textContent = r.student_name + ' — ' + r.package_name;
    approveModal.classList.add('show');
  }
  function closeApproveModal() { approveModal.classList.remove('show'); pendingApproveId = null; }
  document.getElementById('closeApproveModalBtn').onclick = document.getElementById('cancelApproveBtn').onclick = closeApproveModal;
  approveModal.addEventListener('click', e => { if (e.target === approveModal) closeApproveModal(); });

  document.getElementById('confirmApproveBtn').addEventListener('click', async () => {
    if (!pendingApproveId) return;
    const btn = document.getElementById('confirmApproveBtn');
    btn.disabled = true; btn.textContent = 'Approving...';
    try {
      const res = await fetch('update_vocab_activation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: pendingApproveId, status: 'active' })
      });
      const data = await res.json();
      if (data.success) {
        showToast('Approved ✅ Videos unlocked for student');
        closeApproveModal();
        loadActivations();
      } else showToast(data.message || 'Failed', 'error-toast');
    } catch (e) { showToast('Server error', 'error-toast'); }
    finally { btn.disabled = false; btn.textContent = 'Approve ✓'; }
  });

  // Receipt
  const receiptModal = document.getElementById('receiptModalOverlay');
  function openReceiptModal(r) {
    const path = r.receipt_path || '';
    document.getElementById('receiptMeta').innerHTML = `
      <div><b>Student:</b> ${escapeHtml(r.student_name)}</div>
      <div><b>Package:</b> ${escapeHtml(r.package_name)}</div>
      <div><b>Ref:</b> ${escapeHtml(r.transaction_ref || '-')}</div>
      ${r.notes ? `<div><b>Notes:</b> ${escapeHtml(r.notes)}</div>` : ''}
    `;
    const dl = document.getElementById('receiptDownloadLink');
    dl.href = path;
    dl.style.display = path ? 'flex' : 'none';
    const preview = document.getElementById('receiptPreview');
    const lower = path.toLowerCase();
    if (!path) preview.innerHTML = '<span style="color:var(--muted);">No receipt</span>';
    else if (lower.endsWith('.pdf')) preview.innerHTML = `<iframe src="${escapeHtml(path)}"></iframe>`;
    else if (/\.(jpg|jpeg|png|webp|gif)$/i.test(lower)) preview.innerHTML = `<img src="${escapeHtml(path)}" alt="Receipt">`;
    else preview.innerHTML = `<a href="${escapeHtml(path)}" target="_blank">Open file</a>`;
    receiptModal.classList.add('show');
  }
  function closeReceiptModal() { receiptModal.classList.remove('show'); }
  document.getElementById('closeReceiptModalBtn').onclick = document.getElementById('closeReceiptBtn').onclick = closeReceiptModal;
  receiptModal.addEventListener('click', e => { if (e.target === receiptModal) closeReceiptModal(); });

  // Action buttons
  document.getElementById('activationsBody').addEventListener('click', e => {
    const btn = e.target.closest('.icon-btn');
    if (!btn) return;
    e.stopPropagation();
    const id = parseInt(btn.dataset.id, 10);
    const r = allActivations.find(a => a.id === id);
    if (!r) return;
    const action = btn.dataset.action;
    if (action === 'edit') editActivation(id);
    else if (action === 'delete') openDeleteModal(r);
    else if (action === 'approve') openApproveModal(r);
    else if (action === 'receipt') openReceiptModal(r);
  });

  // Mobile sidebar
  document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarBackdrop').classList.toggle('show');
  });
  document.getElementById('sidebarBackdrop')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('show');
  });

  // Logout
  document.getElementById('logoutBtn')?.addEventListener('click', () => {
    localStorage.removeItem('sipwayAdmin');
    window.location.href = 'admin_login.php';
  });

  // ========== Sidebar badge counts (same as vocabulary videos) ==========
  async function updateBookingsBadge() {
    try {
      const res = await fetch('get_bookings.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = data.data.filter(b => (b.status || '').toLowerCase() === 'pending').length;
      const badge = document.getElementById('pendingBookingsBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) {}
  }
  async function updateSupportBadge() {
    try {
      const res = await fetch('get_support_requests.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = data.data.filter(r => r.status === 'pending').length;
      const badge = document.getElementById('pendingBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) {}
  }
  async function updateStudentsBadge() {
    try {
      const res = await fetch('get_students.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = (data.data || []).filter(s => (s.status || 'pending') === 'pending').length;
      const badge = document.getElementById('pendingStudentsBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) {}
  }
  async function updateActivationsBadge() {
    try {
      const res = await fetch('get_activations.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = (data.data || []).filter(a => (a.status || 'pending') === 'pending').length;
      const badge = document.getElementById('pendingActivationsBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) {}
  }
  async function updateTeachersBadge() {
    try {
      const res = await fetch('get-teachers.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = (data.data || []).filter(t => (t.status || 'pending') === 'pending').length;
      const badge = document.getElementById('pendingTeachersBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) {}
  }
  async function updateAvailabilityBadge() {
    try {
      const res = await fetch('admin_get_pending_availability.php?filter=all');
      const data = await res.json();
      if (!data.success) return;
      const pending = (data.data || []).filter(r => r.approval_status === 'pending').length;
      const badge = document.getElementById('pendingAvailabilityBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) {}
  }
  async function updateVocabBadge() {
    try {
      const res = await fetch('get_vocab_activations.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = (data.data || []).filter(a => (a.status || 'pending') === 'pending').length;
      const badge = document.getElementById('pendingVocabBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) {}
  }

  // Initial load
  loadActivations();
  updateBookingsBadge();
  updateSupportBadge();
  updateStudentsBadge();
  updateActivationsBadge();
  updateTeachersBadge();
  updateAvailabilityBadge();
  updateVocabBadge();

  setInterval(updateBookingsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateSupportBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateStudentsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateActivationsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateTeachersBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateAvailabilityBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateVocabBadge, BADGE_POLL_INTERVAL_MS);
})();
</script>
</body>
</html>