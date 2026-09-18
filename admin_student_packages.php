<?php
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}

// ==================== FETCH ALL ACTIVATED PACKAGES WITH STUDENT INFO ====================
$rows = [];
$sql = "
    SELECT
        ap.id AS package_row_id,
        ap.student_id,
        ap.package_name,
        ap.total_sessions,
        ap.sessions_remaining,
        ap.status,
        ap.activated_at,
        s.full_name
    FROM activated_packages ap
    INNER JOIN students s ON s.id = ap.student_id
    ORDER BY s.full_name ASC, ap.activated_at DESC
";
$result = $conn->query($sql);
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
}
$conn->close();

// Quick stats
$totalPackages   = count($rows);
$activeCount     = count(array_filter($rows, fn($r) => $r['status'] === 'active'));
$completedCount  = count(array_filter($rows, fn($r) => $r['status'] === 'completed'));
$uniqueStudents  = count(array_unique(array_column($rows, 'student_id')));

// ==================== GROUP PACKAGES BY STUDENT ====================
$students = [];
foreach ($rows as $r) {
    $sid = $r['student_id'];
    if (!isset($students[$sid])) {
        $students[$sid] = [
            'student_id' => $sid,
            'full_name'  => $r['full_name'],
            'packages'   => [],
        ];
    }
    $students[$sid]['packages'][] = $r;
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Packages - Sipway Campus Admin</title>
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

  .sidebar{
    position:fixed; top:0; left:0; bottom:0;
    width:var(--sidebar-w);
    background:linear-gradient(180deg, var(--navy) 0%, #0b2039 100%);
    color:#fff;
    display:flex; flex-direction:column;
    z-index:50;
    transition:transform .25s var(--ease);
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
    color:rgba(255,255,255,0.35); text-transform:uppercase; padding:8px 12px 6px;
  }
  .nav-item{
    display:flex; align-items:center; gap:12px;
    padding:11px 14px; border-radius:10px;
    font-size:13.5px; font-weight:600;
    color:rgba(255,255,255,0.75); cursor:pointer;
    margin-bottom:3px;
    transition:background .15s var(--ease), color .15s var(--ease);
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
    padding:0 28px; background:rgba(255,255,255,0.9);
    backdrop-filter:saturate(180%) blur(10px);
    border-bottom:1px solid var(--line-soft);
    position:sticky; top:0; z-index:30; gap:16px;
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
    color:#fff; font-weight:800; font-size:12.5px; flex-shrink:0;
  }
  .admin-chip .name{ font-size:13px; font-weight:700; color:var(--navy); line-height:1.2; }
  .admin-chip .role{ font-size:10.5px; color:var(--muted-2); font-weight:600; }

  .content{ padding:26px 28px 60px; flex:1; }

  .greeting{ margin-bottom:22px; }
  .greeting h1{ font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--navy); margin:0 0 4px; letter-spacing:-0.3px; }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }

  .stats-grid{
    display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; margin-bottom:26px;
  }
  .stat-card{
    background:var(--card); border:1px solid var(--line-soft); border-radius:var(--radius-lg);
    padding:20px 22px; box-shadow:var(--shadow-card);
    display:flex; flex-direction:column; gap:12px;
    transition:transform .15s var(--ease), box-shadow .15s var(--ease);
  }
  .stat-card:hover{ transform:translateY(-2px); box-shadow:0 16px 40px -14px rgba(15,42,74,0.2); }
  .stat-icon{
    width:42px; height:42px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
  }
  .stat-icon svg{ width:20px; height:20px; }
  .stat-icon.blue{ background:var(--navy-soft); color:var(--navy-2); }
  .stat-icon.coral{ background:var(--coral-soft); color:var(--coral-dark); }
  .stat-icon.green{ background:var(--success-soft); color:var(--success); }
  .stat-icon.orange{ background:var(--warning-soft); color:var(--warning); }
  .stat-value{ font-size:26px; font-weight:800; color:var(--text); letter-spacing:-0.5px; }
  .stat-label{ font-size:12.5px; color:var(--muted); font-weight:600; }

  .panel{
    background:var(--card); border:1px solid var(--line-soft); border-radius:var(--radius-lg);
    box-shadow:var(--shadow-card); overflow:hidden;
  }
  .panel-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 22px; border-bottom:1px solid var(--line-soft); gap:12px; flex-wrap:wrap;
  }
  .panel-head h3{ margin:0; font-size:15.5px; font-weight:800; color:var(--navy); }
  .panel-head p{ margin:2px 0 0; font-size:12px; color:var(--muted); }

  .search-box{
    display:flex; align-items:center; gap:8px;
    border:1px solid var(--line); border-radius:10px;
    padding:8px 12px; background:#fff; min-width:220px;
  }
  .search-box svg{ width:16px; height:16px; color:var(--muted-2); flex-shrink:0; }
  .search-box input{
    border:none; outline:none; font-size:13px; font-family:inherit;
    width:100%; color:var(--text);
  }

  .filter-pills{ display:flex; gap:8px; flex-wrap:wrap; }
  .pill{
    padding:6px 13px; border-radius:999px; font-size:11.5px; font-weight:700;
    border:1px solid var(--line); background:#fff; color:var(--muted);
    cursor:pointer; transition:all .15s var(--ease);
  }
  .pill.active{ background:var(--navy); border-color:var(--navy); color:#fff; }
  .pill:hover:not(.active){ border-color:#d7d2c8; }

  .table-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; min-width:860px; }
  thead th{
    text-align:left; font-size:11px; font-weight:700; color:var(--muted-2);
    text-transform:uppercase; letter-spacing:0.6px;
    padding:12px 22px; background:#fbfaf9;
    border-bottom:1px solid var(--line-soft); white-space:nowrap;
  }
  tbody td{
    padding:14px 22px; font-size:13.5px; color:var(--text);
    border-bottom:1px solid var(--line-soft); vertical-align:middle;
  }
  tbody tr:last-child td{ border-bottom:none; }

  /* ---------- Student (group) row ---------- */
  .student-row{ cursor:pointer; transition:background .12s var(--ease); }
  .student-row:hover{ background:#fbfaf9; }
  .student-row.expanded{ background:var(--navy-soft); }
  .student-row td{ border-bottom:1px solid var(--line-soft); }

  .expand-chevron{
    width:20px; height:20px; flex-shrink:0; color:var(--muted-2);
    transition:transform .2s var(--ease);
    display:flex; align-items:center; justify-content:center;
  }
  .student-row.expanded .expand-chevron{ transform:rotate(90deg); color:var(--coral-dark); }

  .person-cell{ display:flex; align-items:center; gap:10px; }
  .person-avatar{
    width:32px; height:32px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:11.5px; font-weight:800; color:#fff; flex-shrink:0;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
  }
  .person-name{ font-weight:700; font-size:13.5px; }
  .person-sub{ font-size:11.5px; color:var(--muted-2); font-weight:500; }

  .pkg-count-chip{
    display:inline-flex; align-items:center; gap:6px;
    padding:5px 12px; border-radius:999px; font-size:11.5px; font-weight:800;
    background:var(--coral-soft); color:var(--coral-dark);
  }
  .mini-stat{
    display:inline-flex; align-items:center; gap:5px;
    font-size:12px; font-weight:700; color:var(--muted);
  }
  .mini-stat .dot{ width:6px; height:6px; border-radius:50%; flex-shrink:0; }
  .mini-stat.active .dot{ background:var(--success); }
  .mini-stat.completed .dot{ background:var(--navy-2); }

  /* ---------- Detail (nested packages) row ---------- */
  .detail-row td{ padding:0; border-bottom:1px solid var(--line-soft); }
  .detail-wrap{
    background:#fbfaf9;
    padding:6px 22px 18px 58px;
  }
  .nested-table{ width:100%; border-collapse:collapse; min-width:0; }
  .nested-table thead th{
    background:transparent; padding:8px 14px; font-size:10.5px;
    border-bottom:1px solid var(--line);
  }
  .nested-table tbody td{
    padding:12px 14px; font-size:13px; border-bottom:1px solid var(--line-soft);
    background:#fff;
  }
  .nested-table tbody tr:first-child td{ border-top:1px solid var(--line-soft); }
  .nested-table tbody tr:first-child td:first-child{ border-top-left-radius:10px; }
  .nested-table tbody tr:first-child td:last-child{ border-top-right-radius:10px; }
  .nested-table tbody tr:last-child td:first-child{ border-bottom-left-radius:10px; }
  .nested-table tbody tr:last-child td:last-child{ border-bottom-right-radius:10px; }

  .pkg-name{ font-weight:700; font-size:13px; }

  .progress-cell{ display:flex; align-items:center; gap:10px; min-width:150px; }
  .progress-bar-track{
    flex:1; height:8px; border-radius:6px; background:var(--line-soft); overflow:hidden;
  }
  .progress-bar-fill{
    height:100%; border-radius:6px;
    background:linear-gradient(90deg, var(--coral), var(--coral-dark));
    transition:width .4s var(--ease);
  }
  .progress-pct{ font-size:11.5px; font-weight:800; color:var(--navy); width:34px; text-align:right; flex-shrink:0; }

  .sessions-count{ font-size:12.5px; font-weight:700; color:var(--text); }
  .sessions-count .rem{ color:var(--success); }
  .sessions-count .sep{ color:var(--muted-2); margin:0 3px; }

  .status-badge{
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 11px; border-radius:20px; font-size:11px;
    font-weight:800; letter-spacing:0.2px;
  }
  .status-badge::before{ content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
  .status-badge.active{ background:var(--success-soft); color:var(--success); }
  .status-badge.completed{ background:var(--navy-soft); color:var(--navy-2); }
  .status-badge.other{ background:var(--warning-soft); color:var(--warning); }

  .empty-state{ padding:40px 20px; text-align:center; color:var(--muted-2); font-size:13px; }

  .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45; }

  /* ============ Add / Reduce Sessions buttons + Modal ============ */
  .actions-cell{ display:flex; gap:6px; flex-wrap:wrap; }
  .add-session-btn, .remove-session-btn{
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 13px; border-radius:8px;
    border:1px solid transparent;
    font-size:12px; font-weight:800; cursor:pointer;
    transition:background .15s var(--ease), transform .1s var(--ease);
    white-space:nowrap;
  }
  .add-session-btn{
    background:var(--coral-soft); color:var(--coral-dark);
    border-color:rgba(214,108,71,0.25);
  }
  .add-session-btn:hover{ background:var(--coral); color:#fff; }
  .add-session-btn:active{ transform:scale(0.96); }
  .add-session-btn svg{ width:14px; height:14px; flex-shrink:0; }

  .remove-session-btn{
    background:var(--danger-soft); color:var(--danger);
    border-color:rgba(192,57,43,0.25);
  }
  .remove-session-btn:hover{ background:var(--danger); color:#fff; }
  .remove-session-btn:active{ transform:scale(0.96); }
  .remove-session-btn svg{ width:14px; height:14px; flex-shrink:0; }

  .modal-overlay{
    display:none; position:fixed; inset:0; z-index:100;
    background:rgba(15,42,74,0.45);
    align-items:center; justify-content:center;
    padding:20px;
  }
  .modal-overlay.show{ display:flex; }
  .modal-box{
    background:#fff; border-radius:var(--radius-lg);
    width:100%; max-width:380px;
    box-shadow:0 24px 60px -12px rgba(15,42,74,0.35);
    padding:24px 24px 22px;
    animation:modalIn .18s var(--ease);
  }
  @keyframes modalIn{
    from{ opacity:0; transform:translateY(10px) scale(0.98); }
    to{ opacity:1; transform:translateY(0) scale(1); }
  }
  .modal-box h3{ margin:0 0 4px; font-size:16.5px; font-weight:800; color:var(--navy); }
  .modal-box p.modal-sub{ margin:0 0 18px; font-size:12.5px; color:var(--muted); font-weight:500; }
  .modal-pkg-tag{
    display:inline-block; padding:4px 10px; border-radius:999px;
    background:var(--navy-soft); color:var(--navy-2); font-size:11.5px;
    font-weight:700; margin-bottom:16px;
  }
  .modal-field label{
    display:block; font-size:12px; font-weight:700; color:var(--text);
    margin-bottom:6px;
  }
  .modal-field input{
    width:100%; padding:11px 13px; border-radius:10px;
    border:1px solid var(--line); font-size:14px; font-family:inherit;
    outline:none; color:var(--text);
  }
  .modal-field input:focus{ border-color:var(--coral); }
  .modal-current-info{
    font-size:11.5px; color:var(--muted); margin-top:8px; font-weight:500;
  }
  .modal-error{
    display:none; margin-top:10px; padding:9px 12px; border-radius:8px;
    background:var(--danger-soft); color:var(--danger); font-size:12px; font-weight:700;
  }
  .modal-error.show{ display:block; }
  .modal-actions{
    display:flex; gap:10px; margin-top:20px;
  }
  .modal-btn{
    flex:1; padding:11px 14px; border-radius:10px; border:none;
    font-size:13.5px; font-weight:800; cursor:pointer;
    transition:opacity .15s var(--ease), background .15s var(--ease);
  }
  .modal-btn:disabled{ opacity:0.6; cursor:not-allowed; }
  .modal-btn.cancel{ background:var(--navy-soft); color:var(--navy-2); }
  .modal-btn.confirm{ background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff; }
  .modal-btn.confirm.reduce-mode{ background:linear-gradient(135deg, #d9564a, var(--danger)); }
  .modal-btn.cancel:hover{ background:#e3e9f1; }
  .modal-btn.confirm:hover{ opacity:0.92; }

  .toast{
    position:fixed; bottom:24px; right:24px; z-index:120;
    background:var(--navy); color:#fff; padding:13px 20px;
    border-radius:10px; font-size:13px; font-weight:700;
    box-shadow:0 14px 30px -8px rgba(15,42,74,0.4);
    display:flex; align-items:center; gap:10px;
    transform:translateY(20px); opacity:0; pointer-events:none;
    transition:all .25s var(--ease);
  }
  .toast.show{ transform:translateY(0); opacity:1; }
  .toast.error{ background:var(--danger); }

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
    .admin-chip{ padding:6px; }
    .panel-head{ flex-direction:column; align-items:flex-start; }
    .search-box{ width:100%; }
    .detail-wrap{ padding-left:22px; }
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
    <a class="nav-item active" href="admin_student_packages.php">
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
        <h2>Student Packages</h2>
        <p>Student ekak click kළoth, eyage okkoma activated packages yatin penewi</p>
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
      <h1>Student Packages 📦</h1>
      <p>සියලුම ශිෂ්‍යයන් එක්වර පෙන්වනු ලැබේ. ශිෂ්‍යයාගේ පේළිය (Row) මත ක්ලික් කළ විට, සක්‍රීය කර ඇති සියලුම පැකේජ පහළින් විවෘත වී පෙන්වනු ඇත.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        </div>
        <div class="stat-value"><?php echo $uniqueStudents; ?></div>
        <div class="stat-label">Students with Packages</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon coral">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
        </div>
        <div class="stat-value"><?php echo $totalPackages; ?></div>
        <div class="stat-label">Total Packages Activated</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <div class="stat-value"><?php echo $activeCount; ?></div>
        <div class="stat-label">Active Packages</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </div>
        <div class="stat-value"><?php echo $completedCount; ?></div>
        <div class="stat-label">Completed Packages</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>All Students</h3>
          <p><?php echo $uniqueStudents; ?> students &middot; <?php echo $totalPackages; ?> packages</p>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
          <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="text" id="searchInput" placeholder="Search student or package...">
          </div>
          <div class="filter-pills">
            <span class="pill active" data-filter="all">All</span>
            <span class="pill" data-filter="active">Active</span>
            <span class="pill" data-filter="completed">Completed</span>
          </div>
        </div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:36px;"></th>
              <th>Student</th>
              <th>Packages</th>
              <th>Breakdown</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php if (empty($students)): ?>
              <tr><td colspan="4"><div class="empty-state">Package activate karapu students kawruwath naha.</div></td></tr>
            <?php else: ?>
              <?php foreach ($students as $stu):
                  $pkgs = $stu['packages'];
                  $pkgCount = count($pkgs);
                  $stuActive = count(array_filter($pkgs, fn($p) => $p['status'] === 'active'));
                  $stuCompleted = count(array_filter($pkgs, fn($p) => $p['status'] === 'completed'));
                  $initials = strtoupper(substr(trim($stu['full_name']), 0, 1));

                  // combined search text: student name + every package name
                  $searchParts = [$stu['full_name']];
                  foreach ($pkgs as $p) { $searchParts[] = $p['package_name']; }
                  $searchText = strtolower(implode(' ', $searchParts));

                  // distinct statuses present under this student, for the pill filter
                  $statusList = array_unique(array_column($pkgs, 'status'));
              ?>
              <tr class="student-row"
                  data-student-id="<?php echo (int)$stu['student_id']; ?>"
                  data-search="<?php echo htmlspecialchars($searchText); ?>"
                  data-statuses="<?php echo htmlspecialchars(implode(',', $statusList)); ?>">
                <td>
                  <span class="expand-chevron">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg>
                  </span>
                </td>
                <td>
                  <div class="person-cell">
                    <span class="person-avatar"><?php echo $initials ?: '?'; ?></span>
                    <div>
                      <div class="person-name"><?php echo htmlspecialchars($stu['full_name']); ?></div>
                      <div class="person-sub">ID: <?php echo (int)$stu['student_id']; ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="pkg-count-chip"><?php echo $pkgCount; ?> package<?php echo $pkgCount === 1 ? '' : 's'; ?></span>
                </td>
                <td>
                  <span class="mini-stat active"><span class="dot"></span><?php echo $stuActive; ?> active</span>
                  &nbsp;&nbsp;
                  <span class="mini-stat completed"><span class="dot"></span><?php echo $stuCompleted; ?> completed</span>
                </td>
              </tr>

              <tr class="detail-row" data-student-id="<?php echo (int)$stu['student_id']; ?>" style="display:none;">
                <td colspan="4">
                  <div class="detail-wrap">
                    <table class="nested-table">
                      <thead>
                        <tr>
                          <th>Package</th>
                          <th>Progress</th>
                          <th>Sessions</th>
                          <th>Status</th>
                          <th>Activated On</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($pkgs as $r):
                            $total = (int)$r['total_sessions'];
                            $left  = (int)$r['sessions_remaining'];
                            $done  = max(0, $total - $left);
                            $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
                            $statusRaw = $r['status'];
                            $statusClass = in_array($statusRaw, ['active','completed']) ? $statusRaw : 'other';
                        ?>
                        <tr class="pkg-row" data-status="<?php echo htmlspecialchars($statusRaw); ?>"
                            data-package-id="<?php echo (int)$r['package_row_id']; ?>">
                          <td><span class="pkg-name"><?php echo htmlspecialchars($r['package_name']); ?></span></td>
                          <td>
                            <div class="progress-cell">
                              <div class="progress-bar-track">
                                <div class="progress-bar-fill" data-role="progress-fill" style="width:<?php echo $pct; ?>%;"></div>
                              </div>
                              <span class="progress-pct" data-role="progress-pct"><?php echo $pct; ?>%</span>
                            </div>
                          </td>
                          <td>
                            <span class="sessions-count" data-role="sessions-text">
                              <span class="rem" data-role="sessions-left"><?php echo $left; ?></span>
                              <span class="sep">/</span>
                              <span data-role="sessions-total"><?php echo $total; ?></span> left
                            </span>
                          </td>
                          <td><span class="status-badge <?php echo $statusClass; ?>" data-role="status-badge"><?php echo htmlspecialchars($statusRaw); ?></span></td>
                          <td><?php echo date('d M Y', strtotime($r['activated_at'])); ?></td>
                          <td>
                            <div class="actions-cell">
                              <button type="button" class="add-session-btn"
                                      data-mode="add"
                                      data-id="<?php echo (int)$r['package_row_id']; ?>"
                                      data-name="<?php echo htmlspecialchars($r['package_name']); ?>"
                                      data-student="<?php echo htmlspecialchars($stu['full_name']); ?>"
                                      data-total="<?php echo $total; ?>"
                                      data-left="<?php echo $left; ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                                Add
                              </button>
                              <button type="button" class="remove-session-btn"
                                      data-mode="reduce"
                                      data-id="<?php echo (int)$r['package_row_id']; ?>"
                                      data-name="<?php echo htmlspecialchars($r['package_name']); ?>"
                                      data-student="<?php echo htmlspecialchars($stu['full_name']); ?>"
                                      data-total="<?php echo $total; ?>"
                                      data-left="<?php echo $left; ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>
                                Reduce
                              </button>
                            </div>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ============ Add / Reduce Sessions Modal (shared) ============ -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-box">
    <h3 id="modalTitle">Add Extra Sessions</h3>
    <p class="modal-sub" id="modalStudentLine">Student ekata mona package ekatada sessions add karanne</p>
    <span class="modal-pkg-tag" id="modalPkgTag">Package Name</span>

    <div class="modal-field">
      <label id="modalInputLabel" for="extraSessionsInput">Extra Sessions Ganana</label>
      <input type="number" id="extraSessionsInput" min="1" max="100" step="1" placeholder="Eg: 2" autocomplete="off">
      <div class="modal-current-info" id="modalCurrentInfo"></div>
      <div class="modal-error" id="modalError"></div>
    </div>

    <div class="modal-actions">
      <button type="button" class="modal-btn cancel" id="modalCancelBtn">Cancel</button>
      <button type="button" class="modal-btn confirm" id="modalConfirmBtn">Add Sessions</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
  // How often (ms) this page silently refreshes the sidebar badges
  // for pages that aren't loaded here (Booking Time, Students, Activated
  // Packages, Support Requests).
  const BADGE_POLL_INTERVAL_MS = 15000;

  const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
  if (adminSession && adminSession.username) {
    document.getElementById('adminName').textContent = adminSession.username;
    document.getElementById('adminAvatar').textContent = adminSession.username.charAt(0).toUpperCase();
  }

  /* ================= Sidebar pending badges ================= */
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

  /* ================= Expand / Collapse student rows ================= */
  document.querySelectorAll('.student-row').forEach(row => {
    row.addEventListener('click', () => {
      const sid = row.dataset.studentId;
      const detailRow = document.querySelector(`.detail-row[data-student-id="${sid}"]`);
      if (!detailRow) return;

      const isOpen = detailRow.style.display !== 'none';
      detailRow.style.display = isOpen ? 'none' : 'table-row';
      row.classList.toggle('expanded', !isOpen);
    });
  });

  /* ================= Search + Status Filter ================= */
  document.querySelectorAll('.pill').forEach(pill => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      applyFilters();
    });
  });

  document.getElementById('searchInput').addEventListener('input', applyFilters);

  function applyFilters() {
    const activeFilter = document.querySelector('.pill.active').dataset.filter;
    const searchTerm = document.getElementById('searchInput').value.trim().toLowerCase();

    document.querySelectorAll('.student-row').forEach(row => {
      const sid = row.dataset.studentId;
      const detailRow = document.querySelector(`.detail-row[data-student-id="${sid}"]`);
      const searchData = row.dataset.search;
      const statuses = row.dataset.statuses.split(',');

      const matchesSearch = !searchTerm || searchData.includes(searchTerm);
      const matchesFilter = activeFilter === 'all' || statuses.includes(activeFilter);

      // show/hide individual package rows inside the detail table by status
      if (detailRow) {
        detailRow.querySelectorAll('.pkg-row').forEach(pkgRow => {
          const show = activeFilter === 'all' || pkgRow.dataset.status === activeFilter;
          pkgRow.style.display = show ? '' : 'none';
        });
      }

      const shouldShow = matchesSearch && matchesFilter;
      row.style.display = shouldShow ? '' : 'none';
      if (!shouldShow && detailRow) {
        detailRow.style.display = 'none';
        row.classList.remove('expanded');
      }
    });
  }

  document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarBackdrop').classList.toggle('show');
  });
  document.getElementById('sidebarBackdrop')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('show');
  });

  document.getElementById('logoutBtn')?.addEventListener('click', () => {
    localStorage.removeItem('sipwayAdmin');
    window.location.href = '/index.html';
  });

  /* ================= Add / Reduce Sessions Modal Logic ================= */
  const modalOverlay      = document.getElementById('modalOverlay');
  const modalTitle        = document.getElementById('modalTitle');
  const modalStudentLine  = document.getElementById('modalStudentLine');
  const modalPkgTag       = document.getElementById('modalPkgTag');
  const modalInputLabel   = document.getElementById('modalInputLabel');
  const modalCurrentInfo  = document.getElementById('modalCurrentInfo');
  const modalError        = document.getElementById('modalError');
  const extraSessionsInput= document.getElementById('extraSessionsInput');
  const modalCancelBtn    = document.getElementById('modalCancelBtn');
  const modalConfirmBtn   = document.getElementById('modalConfirmBtn');
  const toast             = document.getElementById('toast');

  let activeRow = null;      // <tr class="pkg-row"> currently being edited
  let activePackageId = null;
  let activeMode = 'add';    // 'add' or 'reduce'

  function showToast(message, isError = false) {
    toast.textContent = message;
    toast.classList.toggle('error', isError);
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  }

  function openModal(btn, evt) {
    if (evt) evt.stopPropagation(); // don't let the click bubble up and collapse the student row
    activeRow = btn.closest('tr.pkg-row');
    activePackageId = btn.dataset.id;
    activeMode = btn.dataset.mode === 'reduce' ? 'reduce' : 'add';

    const studentName = btn.dataset.student;
    const pkgName = btn.dataset.name;
    const total = parseInt(btn.dataset.total, 10) || 0;
    const left = parseInt(btn.dataset.left, 10) || 0;

    modalPkgTag.textContent = pkgName;
    modalCurrentInfo.textContent = `Dan tiyenne: ${total} total, ${left} remaining`;
    extraSessionsInput.value = '';
    modalError.classList.remove('show');
    modalError.textContent = '';
    modalConfirmBtn.disabled = false;

    if (activeMode === 'add') {
      modalTitle.textContent = 'Add Extra Sessions';
      modalStudentLine.textContent = studentName + ' ge package ekata sessions add karanawa';
      modalInputLabel.textContent = 'Extra Sessions Ganana';
      extraSessionsInput.max = 100;
      modalConfirmBtn.textContent = 'Add Sessions';
      modalConfirmBtn.classList.remove('reduce-mode');
    } else {
      modalTitle.textContent = 'Reduce Sessions';
      modalStudentLine.textContent = studentName + ' ge package eken sessions adu karanawa';
      modalInputLabel.textContent = 'Adu Karana Sessions Ganana';
      extraSessionsInput.max = Math.max(left, 1);
      modalConfirmBtn.textContent = 'Reduce Sessions';
      modalConfirmBtn.classList.add('reduce-mode');
    }

    modalOverlay.classList.add('show');
    setTimeout(() => extraSessionsInput.focus(), 100);
  }

  function closeModal() {
    modalOverlay.classList.remove('show');
    activeRow = null;
    activePackageId = null;
  }

  document.querySelectorAll('.add-session-btn, .remove-session-btn').forEach(btn => {
    btn.addEventListener('click', (e) => openModal(btn, e));
  });

  modalCancelBtn.addEventListener('click', closeModal);
  modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalOverlay.classList.contains('show')) closeModal();
  });

  modalConfirmBtn.addEventListener('click', async () => {
    const amount = parseInt(extraSessionsInput.value, 10);
    const currentLeft = activeRow ? parseInt(activeRow.querySelector('[data-role="sessions-left"]').textContent, 10) : 0;

    if (!amount || amount <= 0) {
      modalError.textContent = 'Karunakara 1 ta wada ankayak danna.';
      modalError.classList.add('show');
      return;
    }
    if (activeMode === 'add' && amount > 100) {
      modalError.textContent = 'Ekwarakata sessions 100 ta wada wadi karanna baha.';
      modalError.classList.add('show');
      return;
    }
    if (activeMode === 'reduce' && amount > currentLeft) {
      modalError.textContent = `Danata thiyena remaining sessions (${currentLeft}) ta wada adu karanna baha.`;
      modalError.classList.add('show');
      return;
    }

    modalError.classList.remove('show');
    modalConfirmBtn.disabled = true;
    modalConfirmBtn.textContent = activeMode === 'add' ? 'Adding...' : 'Reducing...';

    const endpoint = activeMode === 'add' ? 'add_extra_sessions.php' : 'remove_extra_sessions.php';
    const bodyPayload = activeMode === 'add'
      ? { package_id: activePackageId, extra_sessions: amount }
      : { package_id: activePackageId, reduce_sessions: amount };

    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bodyPayload)
      });

      const result = await res.json();

      if (!result.success) {
        modalError.textContent = result.message || 'Update eka fail una.';
        modalError.classList.add('show');
        modalConfirmBtn.disabled = false;
        modalConfirmBtn.textContent = activeMode === 'add' ? 'Add Sessions' : 'Reduce Sessions';
        return;
      }

      // Update the row in-place, no full page reload needed
      if (activeRow) {
        const d = result.data;
        activeRow.querySelector('[data-role="sessions-left"]').textContent = d.sessions_remaining;
        activeRow.querySelector('[data-role="sessions-total"]').textContent = d.total_sessions;
        activeRow.querySelector('[data-role="progress-fill"]').style.width = d.percent + '%';
        activeRow.querySelector('[data-role="progress-pct"]').textContent = d.percent + '%';

        const badge = activeRow.querySelector('[data-role="status-badge"]');
        badge.textContent = d.status;
        badge.className = 'status-badge ' + (['active','completed'].includes(d.status) ? d.status : 'other');
        activeRow.dataset.status = d.status;

        // keep both buttons' cached totals in sync for next open
        const addBtn = activeRow.querySelector('.add-session-btn');
        const removeBtn = activeRow.querySelector('.remove-session-btn');
        [addBtn, removeBtn].forEach(b => {
          if (!b) return;
          b.dataset.total = d.total_sessions;
          b.dataset.left = d.sessions_remaining;
        });
      }

      if (activeMode === 'add') {
        showToast(`${amount} sessions add kළා! Dan total ${result.data.total_sessions}, remaining ${result.data.sessions_remaining}.`);
      } else {
        showToast(`${amount} sessions adu kළා! Dan total ${result.data.total_sessions}, remaining ${result.data.sessions_remaining}.`);
      }
      closeModal();

    } catch (err) {
      modalError.textContent = 'Network error ekak una. Ayet try karanna.';
      modalError.classList.add('show');
      modalConfirmBtn.disabled = false;
      modalConfirmBtn.textContent = activeMode === 'add' ? 'Add Sessions' : 'Reduce Sessions';
    }
  });

document.getElementById('logoutBtn')?.addEventListener('click', () => {
  window.location.href = 'admin_logout.php';
});
</script>
</body>
</html>