<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support Requests - Sipway Campus Admin</title>
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
    --radius-lg:16px; --radius-md:10px; --radius-sm:8px;
    --shadow-card:0 10px 34px -12px rgba(15,42,74,0.14), 0 2px 8px rgba(15,42,74,0.05);
    --ease:cubic-bezier(.4,0,.2,1); --sidebar-w:250px;
  }
  *{ box-sizing:border-box; }
  body{ margin:0; font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased; }
  a{ text-decoration:none; color:inherit; }

  .sidebar{ position:fixed; top:0; left:0; bottom:0; width:var(--sidebar-w); background:linear-gradient(180deg, var(--navy) 0%, #0b2039 100%); color:#fff; display:flex; flex-direction:column; z-index:50; transition:transform .25s var(--ease); }
  .sidebar-brand{ display:flex; align-items:center; gap:10px; padding:22px 22px 20px; font-weight:800; font-size:15px; letter-spacing:0.3px; border-bottom:1px solid rgba(255,255,255,0.08); }
  .brand-mark{ width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%); display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:800; flex-shrink:0; box-shadow:0 6px 14px rgba(214,108,71,0.4); }
  .sidebar-brand .sub{ display:block; font-size:10.5px; font-weight:600; color:rgba(255,255,255,0.55); letter-spacing:1px; margin-top:2px; }
  .nav-group{ padding:18px 12px; flex:1; overflow-y:auto; }
  .nav-label{ font-size:10.5px; font-weight:700; letter-spacing:1.2px; color:rgba(255,255,255,0.35); text-transform:uppercase; padding:8px 12px 6px; }
  .nav-item{ display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px; font-size:13.5px; font-weight:600; color:rgba(255,255,255,0.75); cursor:pointer; margin-bottom:3px; transition:background .15s var(--ease), color .15s var(--ease); position:relative; }
  .nav-item svg{ width:18px; height:18px; flex-shrink:0; }
  .nav-item:hover{ background:rgba(255,255,255,0.06); color:#fff; }
  .nav-item.active{ background:rgba(232,130,95,0.16); color:#fff; }
  .nav-item.active::before{ content:""; position:absolute; left:-12px; top:8px; bottom:8px; width:3px; border-radius:3px; background:var(--coral); }
  .nav-item .badge-count{ margin-left:auto; background:var(--coral); color:#fff; font-size:10.5px; font-weight:800; padding:2px 7px; border-radius:20px; flex-shrink:0; }
  .sidebar-foot{ padding:16px 14px 20px; border-top:1px solid rgba(255,255,255,0.08); }
  .logout-btn{ display:flex; align-items:center; gap:10px; width:100%; padding:11px 14px; border-radius:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff; font-weight:700; font-size:13px; cursor:pointer; transition:background .15s var(--ease); }
  .logout-btn:hover{ background:rgba(192,57,43,0.35); border-color:rgba(192,57,43,0.5); }
  .logout-btn svg{ width:16px; height:16px; }

  .main{ margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
  .topbar{ height:68px; display:flex; align-items:center; justify-content:space-between; padding:0 28px; background:rgba(255,255,255,0.9); backdrop-filter:saturate(180%) blur(10px); border-bottom:1px solid var(--line-soft); position:sticky; top:0; z-index:30; gap:16px; }
  .menu-toggle{ display:none; background:none; border:none; cursor:pointer; color:var(--navy); padding:6px; }
  .topbar-title h2{ margin:0; font-size:18px; font-weight:800; color:var(--navy); letter-spacing:-0.2px; }
  .topbar-title p{ margin:2px 0 0; font-size:12.5px; color:var(--muted); font-weight:500; }
  .admin-chip{ display:flex; align-items:center; gap:10px; padding:6px 14px 6px 6px; border-radius:999px; background:var(--navy-soft); }
  .admin-avatar{ width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:12.5px; flex-shrink:0; }
  .admin-chip .name{ font-size:13px; font-weight:700; color:var(--navy); line-height:1.2; }
  .admin-chip .role{ font-size:10.5px; color:var(--muted-2); font-weight:600; }

  .content{ padding:26px 28px 60px; flex:1; }
  .greeting{ margin-bottom:22px; }
  .greeting h1{ font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--navy); margin:0 0 4px; letter-spacing:-0.3px; }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }

  .stats-grid{ display:grid; grid-template-columns:repeat(3, 1fr); gap:18px; margin-bottom:26px; }
  .stat-card{ background:var(--card); border:1px solid var(--line-soft); border-radius:var(--radius-lg); padding:20px 22px; box-shadow:var(--shadow-card); display:flex; flex-direction:column; gap:12px; }
  .stat-icon{ width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .stat-icon svg{ width:20px; height:20px; }
  .stat-icon.orange{ background:var(--warning-soft); color:var(--warning); }
  .stat-icon.green{ background:var(--success-soft); color:var(--success); }
  .stat-icon.blue{ background:var(--navy-soft); color:var(--navy-2); }
  .stat-value{ font-size:26px; font-weight:800; color:var(--text); letter-spacing:-0.5px; }
  .stat-label{ font-size:12.5px; color:var(--muted); font-weight:600; }

  .panel{ background:var(--card); border:1px solid var(--line-soft); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); overflow:hidden; }
  .panel-head{ display:flex; align-items:center; justify-content:space-between; padding:20px 22px; border-bottom:1px solid var(--line-soft); gap:10px; flex-wrap:wrap; }
  .panel-head h3{ margin:0; font-size:15.5px; font-weight:800; color:var(--navy); }
  .panel-head p{ margin:2px 0 0; font-size:12px; color:var(--muted); }
  .filter-pills{ display:flex; gap:8px; flex-wrap:wrap; }
  .pill{ padding:6px 13px; border-radius:999px; font-size:11.5px; font-weight:700; border:1px solid var(--line); background:#fff; color:var(--muted); cursor:pointer; transition:all .15s var(--ease); white-space:nowrap; }
  .pill.active{ background:var(--navy); border-color:var(--navy); color:#fff; }
  .pill:hover:not(.active){ border-color:#d7d2c8; }

  .table-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; min-width:700px; }
  thead th{ text-align:left; font-size:11px; font-weight:700; color:var(--muted-2); text-transform:uppercase; letter-spacing:0.6px; padding:12px 22px; background:#fbfaf9; border-bottom:1px solid var(--line-soft); white-space:nowrap; }
  tbody td{ padding:14px 22px; font-size:13.5px; color:var(--text); border-bottom:1px solid var(--line-soft); vertical-align:top; }
  tbody tr:last-child td{ border-bottom:none; }
  tbody tr:hover{ background:#fbfaf9; }

  .person-cell{ display:flex; align-items:center; gap:10px; }
  .person-avatar{ width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11.5px; font-weight:800; color:#fff; flex-shrink:0; background:#0f2a4a; }
  .person-name{ font-weight:700; font-size:13.5px; }
  .person-sub{ font-size:11.5px; color:var(--muted-2); font-weight:500; }

  .msg-cell{ max-width:280px; white-space:pre-wrap; line-height:1.6; }
  .shot-link{ display:inline-flex; align-items:center; gap:5px; color:var(--coral-dark); font-weight:700; font-size:12px; }
  .shot-thumb{ width:60px; height:60px; object-fit:cover; border-radius:8px; border:1px solid var(--line); cursor:pointer; display:block; margin-top:6px; }

  .status-badge{ display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:20px; font-size:11px; font-weight:800; letter-spacing:0.2px; }
  .status-badge.pending{ background:var(--warning-soft); color:var(--warning); }
  .status-badge.resolved{ background:var(--success-soft); color:var(--success); }
  .status-badge::before{ content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }

  .row-actions button{ padding:6px 12px; border-radius:7px; border:1px solid var(--line); background:#fff; color:var(--muted); cursor:pointer; font-size:11.5px; font-weight:700; }
  .row-actions .resolve-btn{ background:var(--success-soft); color:var(--success); border-color:transparent; }
  .row-actions .resolve-btn:hover{ background:var(--success); color:#fff; }

  .empty-state{ padding:40px 20px; text-align:center; color:var(--muted-2); font-size:13px; }

  .toast{ position:fixed; top:20px; left:50%; transform:translateX(-50%) translateY(-16px); background:var(--navy); color:#fff; padding:13px 22px; border-radius:10px; font-size:13.5px; font-weight:600; opacity:0; pointer-events:none; transition:opacity .25s var(--ease), transform .25s var(--ease); z-index:100; box-shadow:0 12px 30px rgba(15,42,74,0.3); }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.success-toast{ background:var(--success); }
  .toast.error-toast{ background:var(--danger); }

  .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45; }

  /* Lightbox for screenshot preview */
  .lightbox{ display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:200; align-items:center; justify-content:center; padding:30px; }
  .lightbox.show{ display:flex; }
  .lightbox img{ max-width:90%; max-height:90%; border-radius:10px; }

  @media (max-width:880px){
    :root{ --sidebar-w:230px; }
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,0.3); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
    .sidebar-backdrop.show{ display:block; }
    .stats-grid{ grid-template-columns:1fr 1fr; }
  }
  @media (max-width:560px){
    .stats-grid{ grid-template-columns:1fr; }
    .content{ padding:18px 16px 40px; }
    .admin-chip .name, .admin-chip .role{ display:none; }
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
    
    <!-- ===================== STUDENT SECTION ===================== -->
   
    <div class="nav-label">Students</div>
            <a class="nav-item " href="admin-dashboard.html">
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
    <!-- ===================== LECTURER SECTION ===================== -->
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
    <!-- ===================== PACKAGES SECTION ===================== -->
    <div class="nav-label">Packages</div>
    <a class="nav-item" href="admin_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
      Packages
    </a>
  <a class="nav-item" href="admin_subjects.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Subjects / Types
    </a>

    <!-- ===================== SUPPORT SECTION ===================== -->
    <div class="nav-label">Support</div>
    <a class="nav-item active" href="admin_support_requests.php">
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
  </nav>

  <div class="sidebar-foot">
    <button class="logout-btn" id="logoutBtn" >
      <svg  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
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
        <h2>Support Requests</h2>
        <p id="todayDate">Loading...</p>
      </div>
    </div>
    <div class="admin-chip">
      <span class="admin-avatar" id="adminAvatar">A</span>
      <div>
        <div class="name" id="adminName">Admin</div>
        <div class="role">Administrator</div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="greeting">
      <h1>Student Support Requests</h1>
      <p>Students ලා FAQ Page එකෙන් යවපු Support messages මෙන්න.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </div>
        <div class="stat-value" id="statPending">0</div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <div class="stat-value" id="statResolved">0</div>
        <div class="stat-label">Resolved</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="stat-value" id="statTotal">0</div>
        <div class="stat-label">Total Requests</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>All Requests</h3>
          <p>Latest requests first</p>
        </div>
        <div class="filter-pills">
          <span class="pill active" data-filter="all">All</span>
          <span class="pill" data-filter="pending">Pending</span>
          <span class="pill" data-filter="resolved">Resolved</span>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Message</th>
              <th>Screenshot</th>
              <th>Status</th>
              <th>Sent On</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="requestsBody">
            <tr><td colspan="6"><div class="empty-state">Loading...</div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="lightbox" id="lightbox"><img id="lightboxImg" src="" alt="screenshot"></div>
<div class="toast" id="toast"></div>

<script>
(function(){
  let allRequests = [];
  let currentFilter = 'all';

  // Live-refresh interval (ms). Polls the backend periodically so a new
  // request shows up in the table + sidebar badges without a manual reload.
  const POLL_INTERVAL_MS = 15000;

  const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
  if (adminSession && adminSession.username) {
    document.getElementById('adminName').textContent = adminSession.username;
    document.getElementById('adminAvatar').textContent = adminSession.username.charAt(0).toUpperCase();
  }

  document.getElementById('todayDate').textContent = new Date().toLocaleDateString('en-GB', {
    weekday:'long', year:'numeric', month:'long', day:'numeric'
  });

  function showToast(msg, type = '') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = 'toast show' + (type ? ' ' + type : '');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }

  function updateStats(list) {
    const pending = list.filter(r => r.status === 'pending').length;
    const resolved = list.filter(r => r.status === 'resolved').length;
    document.getElementById('statPending').textContent = pending;
    document.getElementById('statResolved').textContent = resolved;
    document.getElementById('statTotal').textContent = list.length;

    const badge = document.getElementById('pendingBadge');
    if (pending > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = pending;
    } else {
      badge.style.display = 'none';
    }
  }

  function getFilteredList() {
    if (currentFilter === 'all') return allRequests;
    return allRequests.filter(r => r.status === currentFilter);
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }

  function renderTable() {
    const tbody = document.getElementById('requestsBody');
    const list = getFilteredList();

    if (list.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">Requests නැහැ.</div></td></tr>`;
      return;
    }

    let html = '';
    list.forEach(r => {
      const initials = (r.student_name || '?').charAt(0).toUpperCase();
      const shotCell = r.screenshot
        ? `<a class="shot-link" href="javascript:void(0)" onclick="openLightbox('${r.screenshot}')">📎 View</a><img class="shot-thumb" src="${r.screenshot}" onclick="openLightbox('${r.screenshot}')">`
        : '<span style="color:#8a93a3; font-size:11px;">—</span>';

      html += `<tr>
        <td>
          <div class="person-cell">
            <span class="person-avatar">${initials}</span>
            <div>
              <div class="person-name">${escapeHtml(r.student_name)}</div>
              <div class="person-sub">ID: ${r.student_id}</div>
            </div>
          </div>
        </td>
        <td class="msg-cell">${escapeHtml(r.message)}</td>
        <td>${shotCell}</td>
        <td><span class="status-badge ${r.status}">${r.status}</span></td>
        <td>${r.created_at}</td>
        <td>
          <div class="row-actions">
            ${r.status === 'pending'
              ? `<button class="resolve-btn" onclick="handleResolve(${r.id})">Mark Resolved</button>`
              : `<span style="color:#8a93a3; font-size:11px;">Done</span>`}
          </div>
        </td>
      </tr>`;
    });

    tbody.innerHTML = html;
  }

  window.openLightbox = function(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('show');
  };
  document.getElementById('lightbox').addEventListener('click', function(){
    this.classList.remove('show');
  });

  window.handleResolve = async function(id) {
    if (!confirm('මෙම request එක Resolved කියලා Mark කරන්නද?')) return;

    try {
      const res = await fetch('update_support_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, status: 'resolved' })
      });
      const data = await res.json();

      if (data.success) {
        showToast('✅ Marked as Resolved', 'success-toast');
        await loadRequests();
      } else {
        showToast(data.message || 'Update failed', 'error-toast');
      }
    } catch (err) {
      console.error(err);
      showToast('Server error. Check console.', 'error-toast');
    }
  };

  // This page doesn't show the bookings table, so just fetch pending
  // count to keep the "Student Booking Time" sidebar badge live.
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

  // This page doesn't show the students table, so the "Students" sidebar
  // badge (pending student registrations) also gets a lightweight fetch.
  async function updateStudentsBadge() {
    try {
      const res = await fetch('get_students.php');
      const data = await res.json();
      if (!data.success) return;

      const pending = (data.data || []).filter(s => (s.status || 'pending') === 'pending').length;
      const badge = document.getElementById('pendingStudentsBadge');
      if (pending > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = pending;
      } else {
        badge.style.display = 'none';
      }
    } catch (err) {
      console.error('Students badge update failed', err);
    }
  }

  // Sidebar "Student Activated Packages" badge — lightweight fetch since
  // this page doesn't load the activations list.
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

  // Sidebar "Teachers" badge — lightweight fetch.
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

  // Sidebar "Lecture Time Requests" badge — lightweight fetch.
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

  // silent = true skips the "Loading..." placeholder so background polling
  // doesn't flicker the table while the admin is reading it.
  async function loadRequests(silent) {
    const tbody = document.getElementById('requestsBody');
    if (!silent) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">Loading...</div></td></tr>`;
    }

    try {
      const res = await fetch('get_support_requests.php');
      const data = await res.json();

      if (data.success) {
        allRequests = data.data;
        updateStats(allRequests);
        renderTable();
      } else if (!silent) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">Error: ${data.message || 'Load unuwe na'}</div></td></tr>`;
      }
    } catch (err) {
      console.error(err);
      if (!silent) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">Server connect unuwe na.</div></td></tr>`;
      }
    }
  }

  document.querySelectorAll('.pill').forEach(pill => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      currentFilter = pill.dataset.filter;
      renderTable();
    });
  });

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

  loadRequests(false); // also sets pendingBadge via updateStats()
  updateBookingsBadge();
  updateStudentsBadge();
  updateActivationsBadge();
  updateTeachersBadge();
  updateAvailabilityBadge();

  // Keep polling in the background so new requests + all sidebar badges
  // update without the admin needing to refresh the page.
  setInterval(() => loadRequests(true), POLL_INTERVAL_MS);
  setInterval(updateBookingsBadge, POLL_INTERVAL_MS);
  setInterval(updateStudentsBadge, POLL_INTERVAL_MS);
  setInterval(updateActivationsBadge, POLL_INTERVAL_MS);
  setInterval(updateTeachersBadge, POLL_INTERVAL_MS);
  setInterval(updateAvailabilityBadge, POLL_INTERVAL_MS);
})();
</script>
</body>
</html>