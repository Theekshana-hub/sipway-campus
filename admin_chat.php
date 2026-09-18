<?php
session_start();
require_once 'db.php';


if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}


unset($_SESSION['student_id'], $_SESSION['student_name'], $_SESSION['student_language']);

$isAdmin   = true;
$adminId   = (int)$_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Chat - Sipway Campus</title>
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
  *{ box-sizing:border-box; margin:0; padding:0; }
  body{
    font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    height:100vh;
    overflow:hidden;
    -webkit-font-smoothing:antialiased;
  }
  a{ text-decoration:none; color:inherit; }
  ::selection{ background:var(--coral-soft); color:var(--coral-dark); }

  /* ========== ADMIN SIDEBAR (shared) ========== */
  .sidebar{
    position:fixed;
    top:0; left:0; bottom:0;
    width:var(--sidebar-w);
    background:linear-gradient(180deg, var(--navy) 0%, #0b2039 100%);
    color:#fff;
    display:flex;
    flex-direction:column;
    z-index:50;
    transition:transform .25s var(--ease);
  }
  .sidebar-brand{
    display:flex;
    align-items:center;
    gap:10px;
    padding:22px 22px 20px;
    font-weight:800;
    font-size:15px;
    letter-spacing:0.3px;
    border-bottom:1px solid rgba(255,255,255,0.08);
  }
  .brand-mark{
    width:34px; height:34px;
    border-radius:9px;
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:800;
    flex-shrink:0;
    box-shadow:0 6px 14px rgba(214,108,71,0.4);
  }
  .sidebar-brand .sub{
    display:block;
    font-size:10.5px;
    font-weight:600;
    color:rgba(255,255,255,0.55);
    letter-spacing:1px;
    margin-top:2px;
  }
  .nav-group{ padding:18px 12px; flex:1; overflow-y:auto; }
  .nav-label{
    font-size:10.5px;
    font-weight:700;
    letter-spacing:1.2px;
    color:rgba(255,255,255,0.35);
    text-transform:uppercase;
    padding:8px 12px 6px;
  }
  .nav-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:11px 14px;
    border-radius:10px;
    font-size:13.5px;
    font-weight:600;
    color:rgba(255,255,255,0.75);
    cursor:pointer;
    margin-bottom:3px;
    transition:background .15s var(--ease), color .15s var(--ease);
    position:relative;
  }
  .nav-item svg{ width:18px; height:18px; flex-shrink:0; }
  .nav-item:hover{ background:rgba(255,255,255,0.06); color:#fff; }
  .nav-item.active{
    background:rgba(232,130,95,0.16);
    color:#fff;
  }
  .nav-item.active::before{
    content:"";
    position:absolute;
    left:-12px;
    top:8px; bottom:8px;
    width:3px;
    border-radius:3px;
    background:var(--coral);
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

  /* ========== MAIN LAYOUT ========== */
  .main{
    margin-left:var(--sidebar-w);
    height:100vh;
    display:flex;
    flex-direction:column;
    overflow:hidden;
  }
  .topbar{
    height:68px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 28px;
    background:rgba(255,255,255,0.9);
    backdrop-filter:saturate(180%) blur(10px);
    border-bottom:1px solid var(--line-soft);
    flex-shrink:0;
    gap:16px;
  }
  .menu-toggle{
    display:none;
    background:none;
    border:none;
    cursor:pointer;
    color:var(--navy);
    padding:6px;
  }
  .topbar-title h2{
    margin:0;
    font-size:18px;
    font-weight:800;
    color:var(--navy);
    letter-spacing:-0.2px;
  }
  .topbar-title p{
    margin:2px 0 0;
    font-size:12.5px;
    color:var(--muted);
    font-weight:500;
  }
  .topbar-right{
    display:flex;
    align-items:center;
    gap:16px;
  }
  .admin-chip{
    display:flex;
    align-items:center;
    gap:10px;
    padding:6px 14px 6px 6px;
    border-radius:999px;
    background:var(--navy-soft);
  }
  .admin-avatar{
    width:30px; height:30px;
    border-radius:50%;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:12.5px;
    flex-shrink:0;
  }
  .admin-chip .name{ font-size:13px; font-weight:700; color:var(--navy); line-height:1.2; }
  .admin-chip .role{ font-size:10.5px; color:var(--muted-2); font-weight:600; }

  /* ========== CHAT SHELL ========== */
  .chat-shell{
    flex:1;
    display:flex;
    overflow:hidden;
    min-height:0;
  }

  /* Conversation sidebar (left of chat) */
  .conv-sidebar{
    width:320px;
    background:var(--card);
    border-right:1px solid var(--line);
    display:flex;
    flex-direction:column;
    flex-shrink:0;
  }
  .conv-sidebar-header{
    padding:18px 18px 14px;
    border-bottom:1px solid var(--line);
    font-weight:800;
    font-size:15px;
    color:var(--navy);
    display:flex;
    align-items:center;
    justify-content:space-between;
  }
  .new-chat-btn{
    margin:14px 16px;
    padding:12px;
    border:none;
    border-radius:10px;
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff;
    font-weight:800;
    font-size:13.5px;
    cursor:pointer;
    transition:filter .15s, transform .12s;
  }
  .new-chat-btn:hover{
    filter:brightness(1.06);
    transform:translateY(-1px);
  }
  .conv-list{
    flex:1;
    overflow-y:auto;
  }
  .conv-item{
    padding:14px 16px;
    border-bottom:1px solid var(--line);
    cursor:pointer;
    display:flex;
    gap:12px;
    align-items:center;
    transition:background .15s;
    position:relative;
  }
  .conv-item:hover{ background:#f8f6f2; }
  .conv-item.active{
    background:#fdece5;
    border-left:3px solid var(--coral);
  }
  .conv-avatar{
    width:44px;
    height:44px;
    border-radius:50%;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    font-size:16px;
    flex-shrink:0;
  }
  .conv-info{ flex:1; min-width:0; }
  .conv-name{
    font-weight:700;
    font-size:14px;
    color:var(--text);
    margin-bottom:2px;
  }
  .conv-preview{
    font-size:12.5px;
    color:var(--muted);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .conv-time{
    font-size:11px;
    color:#9ca3af;
    flex-shrink:0;
  }
  .conv-unread-dot{
    position:absolute;
    top:16px;
    right:14px;
    min-width:18px;
    height:18px;
    padding:0 5px;
    border-radius:20px;
    background:var(--coral);
    color:#fff;
    font-size:10.5px;
    font-weight:800;
    display:flex;
    align-items:center;
    justify-content:center;
  }

  /* Chat area */
  .chat-area{
    flex:1;
    display:flex;
    flex-direction:column;
    background:#faf9f7;
    min-width:0;
  }
  .chat-header{
    padding:16px 22px;
    background:var(--card);
    border-bottom:1px solid var(--line);
    font-weight:800;
    font-size:15.5px;
    color:var(--navy);
    display:flex;
    align-items:center;
    gap:12px;
  }
  .chat-header-avatar{
    width:38px;
    height:38px;
    border-radius:50%;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    font-size:15px;
  }
  .messages{
    flex:1;
    overflow-y:auto;
    padding:22px;
    display:flex;
    flex-direction:column;
    gap:12px;
  }
  .msg{
    max-width:68%;
    padding:11px 15px;
    border-radius:16px;
    font-size:14px;
    line-height:1.5;
    word-wrap:break-word;
  }
  .msg.mine{
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    color:#fff;
    align-self:flex-end;
    border-bottom-right-radius:4px;
  }
  .msg.theirs{
    background:#fff;
    border:1px solid var(--line);
    align-self:flex-start;
    border-bottom-left-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
  }
  .msg-time{
    font-size:10.5px;
    opacity:0.7;
    margin-top:5px;
  }
  .msg.mine .msg-time{ text-align:right; }
  .empty-chat{
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    color:var(--muted);
    font-weight:600;
    gap:10px;
    text-align:center;
    padding:40px;
  }
  .empty-chat svg{
    width:64px;
    height:64px;
    opacity:0.4;
  }
  .input-area{
    padding:14px 18px;
    background:var(--card);
    border-top:1px solid var(--line);
    display:flex;
    gap:10px;
    align-items:center;
  }
  .input-area input{
    flex:1;
    padding:13px 16px;
    border:1.5px solid var(--line);
    border-radius:12px;
    font-size:14px;
    font-family:inherit;
    outline:none;
    background:#faf9f7;
    transition:border-color .15s, background .15s;
  }
  .input-area input:focus{
    border-color:var(--coral);
    background:#fff;
  }
  .input-area button{
    padding:0 24px;
    height:46px;
    border:none;
    border-radius:12px;
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff;
    font-weight:800;
    font-size:14px;
    cursor:pointer;
    transition:filter .15s;
  }
  .input-area button:hover{ filter:brightness(1.06); }
  .input-area button:disabled{
    opacity:0.6;
    cursor:not-allowed;
  }

  /* Modal */
  .modal-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(15,42,74,0.55);
    z-index:100;
    align-items:center;
    justify-content:center;
    padding:20px;
  }
  .modal-overlay.show{ display:flex; }
  .modal-box{
    background:#fff;
    border-radius:16px;
    width:100%;
    max-width:420px;
    padding:26px;
    box-shadow:0 25px 60px rgba(0,0,0,0.25);
  }
  .modal-box h3{
    margin:0 0 6px;
    font-size:18px;
    color:var(--navy);
  }
  .modal-box p{
    margin:0 0 18px;
    font-size:13.5px;
    color:var(--muted);
  }
  .modal-box input{
    width:100%;
    padding:12px 14px;
    border:1.5px solid var(--line);
    border-radius:10px;
    font-size:14px;
    margin-bottom:16px;
    outline:none;
  }
  .modal-box input:focus{ border-color:var(--coral); }
  .modal-actions{
    display:flex;
    gap:10px;
    justify-content:flex-end;
  }
  .modal-actions button{
    padding:10px 18px;
    border-radius:9px;
    font-weight:700;
    font-size:13.5px;
    cursor:pointer;
    border:none;
  }
  .btn-cancel{
    background:#f3f4f6;
    color:var(--muted);
  }
  .btn-start{
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff;
  }

  .sidebar-backdrop{
    display:none;
    position:fixed; inset:0;
    background:rgba(15,42,74,0.4);
    z-index:45;
  }

  /* Responsive */
  @media (max-width:880px){
    :root{ --sidebar-w:230px; }
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,0.3); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
    .sidebar-backdrop.show{ display:block; }
  }
  @media (max-width:760px){
    .conv-sidebar{
      width:100%;
      position:absolute;
      z-index:20;
      height:100%;
      transform:translateX(-100%);
      transition:transform .25s ease;
    }
    .conv-sidebar.open{ transform:translateX(0); }
    .chat-header .back-btn{ display:flex !important; }
  }
  @media (max-width:560px){
    .topbar{ padding:0 16px; }
    .admin-chip .name, .admin-chip .role{ display:none; }
    .admin-chip{ padding:6px; }
  }
  @media (prefers-reduced-motion: reduce){
    *{ animation-duration:0.001ms !important; transition-duration:0.001ms !important; }
  }
</style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- ========== ADMIN SIDEBAR ========== -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-mark">SC</span>
    <span>
      Sipway English Accademy
      <span class="sub">ADMIN PANEL</span>
    </span>
  </div>
  <nav class="nav-group">

    <!-- Students -->
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
    <!-- Lecturers -->
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

    <!-- Packages -->
    <div class="nav-label">Packages</div>
    <a class="nav-item" href="admin_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
      Packages
    </a>
    <a class="nav-item" href="admin_subjects.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Subjects / Types
    </a>

    <!-- Support -->
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
    <a class="nav-item active" href="admin_chat.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      Student Chat
      <span class="badge-count" id="pendingChatBadge" style="display:none;">0</span>
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
    <button class="logout-btn" id="logoutBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Logout
    </button>
  </div>
</aside>

<!-- ========== MAIN ========== -->
<div class="main">
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:14px;">
      <button class="menu-toggle" id="menuToggle">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title">
        <h2>Student Chat</h2>
        <p>Chat with students</p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="admin-chip">
        <span class="admin-avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></span>
        <div>
          <div class="name"><?php echo htmlspecialchars($adminName); ?></div>
          <div class="role">Administrator</div>
        </div>
      </div>
    </div>
  </div>

  <div class="chat-shell">
    <!-- Conversation list -->
    <div class="conv-sidebar" id="convSidebar">
      <div class="conv-sidebar-header">
        Conversations
        <span id="convCount" style="font-size:12px;font-weight:600;color:#9ca3af;"></span>
      </div>
      <button class="new-chat-btn" id="newChatBtn">+ Start Chat with Student</button>
      <div class="conv-list" id="convList">
        <div style="padding:30px 16px;text-align:center;color:#9ca3af;font-size:13px;">
          Loading...
        </div>
      </div>
    </div>

    <!-- Chat area -->
    <div class="chat-area">
      <div class="chat-header" id="chatHeader">
        <button class="back-btn" id="backBtn" style="display:none;background:none;border:none;cursor:pointer;margin-right:6px;color:var(--navy);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <span id="chatHeaderText">Select a conversation</span>
      </div>

      <div class="messages" id="messages">
        <div class="empty-chat">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <div>Select a student conversation<br>or start a new chat</div>
        </div>
      </div>

      <div class="input-area" id="inputArea" style="display:none;">
        <input type="text" id="msgInput" placeholder="Type your message..." autocomplete="off">
        <button id="sendBtn">Send</button>
      </div>
    </div>
  </div>
</div>

<!-- NEW CHAT MODAL -->
<div class="modal-overlay" id="newChatModal">
  <div class="modal-box">
    <h3>Start Chat with Student</h3>
    <p>Enter student name or email</p>
    <input type="text" id="searchStudentInput" placeholder="Name or email..." autocomplete="off">
    <div class="modal-actions">
      <button class="btn-cancel" id="cancelNewChat">Cancel</button>
      <button class="btn-start" id="startNewChat">Start Chat</button>
    </div>
  </div>
</div>

<script>
  // ========== CONFIG ==========
  const MY_ID   = <?php echo (int)$adminId; ?>;
  const MY_TYPE = 'admin';
  const MY_NAME = <?php echo json_encode($adminName); ?>;
  const CHAT_BADGE_POLL_INTERVAL_MS = 15000;

  let currentConvId  = null;
  let currentPartner = null;

  // ========== HELPERS ==========
  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  // ========== SIDEBAR "Student Chat" BADGE ==========
  // Sums unread_count across all conversations (chat_api.php's list_conversations
  // is expected to return an `unread_count` field per conversation).
  async function updateChatBadge() {
    try {
      const res = await fetch('chat_api.php?action=list_conversations');
      const data = await res.json();
      const badge = document.getElementById('pendingChatBadge');
      if (!badge) return;

      if (!data.success || !data.data) {
        badge.style.display = 'none';
        return;
      }

      const totalUnread = data.data.reduce((sum, c) => sum + (parseInt(c.unread_count, 10) || 0), 0);

      if (totalUnread > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = totalUnread;
      } else {
        badge.style.display = 'none';
      }
    } catch (err) {
      console.error('Chat badge update failed', err);
    }
  }

  // ========== LOAD CONVERSATIONS ==========
  async function loadConversations() {
    try {
      const res = await fetch('chat_api.php?action=list_conversations');
      const data = await res.json();
      const list = document.getElementById('convList');
      const countEl = document.getElementById('convCount');

      if (!data.success || !data.data || data.data.length === 0) {
        list.innerHTML = `
          <div style="padding:40px 16px;text-align:center;color:#9ca3af;font-size:13.5px;">
            No conversations yet.<br>
            Click the button above to start chatting with a student.
          </div>`;
        countEl.textContent = '';
        updateChatBadgeFromList([]);
        return;
      }

      countEl.textContent = data.data.length + ' chats';
      updateChatBadgeFromList(data.data);

      list.innerHTML = data.data.map(c => {
        const name = c.partner_name || 'Student';
        const initial = name.charAt(0).toUpperCase();
        const preview = c.last_message ? escapeHtml(c.last_message.substring(0, 42)) : 'No messages yet';
        const time = c.last_message_at ? formatTime(c.last_message_at) : '';
        const isActive = c.id == currentConvId ? 'active' : '';
        const unread = parseInt(c.unread_count, 10) || 0;
        const unreadHtml = unread > 0 ? `<span class="conv-unread-dot">${unread}</span>` : '';

        return `
          <div class="conv-item ${isActive}" data-id="${c.id}" data-name="${escapeHtml(name)}">
            <div class="conv-avatar">${initial}</div>
            <div class="conv-info">
              <div class="conv-name">${escapeHtml(name)}</div>
              <div class="conv-preview">${preview}</div>
            </div>
            <div class="conv-time">${time}</div>
            ${unreadHtml}
          </div>`;
      }).join('');

      list.querySelectorAll('.conv-item').forEach(el => {
        el.addEventListener('click', () => {
          openConversation(el.dataset.id, el.dataset.name);
        });
      });
    } catch (err) {
      console.error(err);
      document.getElementById('convList').innerHTML = `
        <div style="padding:30px;text-align:center;color:#c0392b;font-size:13px;">
          Failed to load conversations
        </div>`;
    }
  }

  // Small helper so we don't fetch twice (once from loadConversations, once from updateChatBadge)
  function updateChatBadgeFromList(list) {
    const badge = document.getElementById('pendingChatBadge');
    if (!badge) return;
    const totalUnread = (list || []).reduce((sum, c) => sum + (parseInt(c.unread_count, 10) || 0), 0);
    if (totalUnread > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = totalUnread;
    } else {
      badge.style.display = 'none';
    }
  }

  // ========== OPEN CONVERSATION ==========
  async function openConversation(id, name) {
    currentConvId = id;
    currentPartner = name;

    document.getElementById('chatHeaderText').textContent = name;
    document.getElementById('inputArea').style.display = 'flex';

    document.querySelectorAll('.conv-item').forEach(el => {
      el.classList.toggle('active', el.dataset.id == id);
    });

    if (window.innerWidth <= 760) {
      document.getElementById('convSidebar').classList.remove('open');
    }

    await loadMessages();
    // Opening a conversation typically marks it read server-side (if chat_api.php supports it);
    // refresh the list/badge either way so counts stay accurate.
    await loadConversations();
  }

  // ========== LOAD MESSAGES ==========
  async function loadMessages() {
    if (!currentConvId) return;

    try {
      const res = await fetch(`chat_api.php?action=get_messages&conversation_id=${currentConvId}`);
      const data = await res.json();
      const box = document.getElementById('messages');

      if (!data.success || !data.data || data.data.length === 0) {
        box.innerHTML = `
          <div class="empty-chat">
            <div>No messages yet.<br>Send the first message!</div>
          </div>`;
        return;
      }

      box.innerHTML = data.data.map(m => {
        const isMine = (m.sender_type === 'admin');
        const time = formatTime(m.created_at);
        return `
          <div class="msg ${isMine ? 'mine' : 'theirs'}">
            ${escapeHtml(m.message)}
            <div class="msg-time">${time}</div>
          </div>`;
      }).join('');

      box.scrollTop = box.scrollHeight;
    } catch (err) {
      console.error(err);
    }
  }

  // ========== SEND MESSAGE ==========
  async function sendMessage() {
    const input = document.getElementById('msgInput');
    const text = input.value.trim();
    if (!text || !currentConvId) return;

    input.value = '';
    document.getElementById('sendBtn').disabled = true;

    try {
      await fetch('chat_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'send',
          conversation_id: currentConvId,
          message: text
        })
      });
      await loadMessages();
      await loadConversations();
    } catch (err) {
      alert('Failed to send message');
    } finally {
      document.getElementById('sendBtn').disabled = false;
      input.focus();
    }
  }

  // ========== NEW CHAT ==========
  document.getElementById('newChatBtn').addEventListener('click', () => {
    document.getElementById('newChatModal').classList.add('show');
    document.getElementById('searchStudentInput').value = '';
    document.getElementById('searchStudentInput').focus();
  });

  document.getElementById('cancelNewChat').addEventListener('click', () => {
    document.getElementById('newChatModal').classList.remove('show');
  });

  document.getElementById('startNewChat').addEventListener('click', startNewChat);
  document.getElementById('searchStudentInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') startNewChat();
  });

  async function startNewChat() {
    const search = document.getElementById('searchStudentInput').value.trim();
    if (!search) {
      alert('Please enter student name or email');
      return;
    }

    try {
      const res = await fetch('chat_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'start_conversation',
          partner_search: search
        })
      });
      const data = await res.json();

      document.getElementById('newChatModal').classList.remove('show');

      if (data.success) {
        await loadConversations();
        openConversation(data.conversation_id, data.partner_name);
      } else {
        alert(data.message || 'Student not found');
      }
    } catch (err) {
      alert('Error starting conversation');
    }
  }

  // ========== EVENTS ==========
  document.getElementById('sendBtn').addEventListener('click', sendMessage);
  document.getElementById('msgInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') sendMessage();
  });

  document.getElementById('backBtn')?.addEventListener('click', () => {
    document.getElementById('convSidebar').classList.add('open');
  });

  // Mobile admin sidebar toggle
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
  // ========== INIT ==========
  loadConversations();

  // Auto refresh every 7 seconds
  setInterval(() => {
    loadConversations();
    if (currentConvId) loadMessages();
  }, 7000);
</script>
</body>
</html>