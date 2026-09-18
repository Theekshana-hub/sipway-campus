<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lecture Time Requests - Sipway Admin</title>
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
  .main{
    margin-left:var(--sidebar-w);
    min-height:100vh;
    display:flex;
    flex-direction:column;
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
    position:sticky;
    top:0;
    z-index:30;
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
  .icon-btn{
    width:38px; height:38px;
    border-radius:10px;
    border:1px solid var(--line);
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    color:var(--muted);
    position:relative;
    flex-shrink:0;
  }
  .icon-btn:hover{ border-color:#d7d2c8; color:var(--navy-2); }
  .icon-btn svg{ width:18px; height:18px; }
  .icon-dot{
    position:absolute;
    top:8px; right:8px;
    width:7px; height:7px;
    border-radius:50%;
    background:var(--coral);
    border:1.5px solid #fff;
  }
  .admin-chip{
    display:flex;
    align-items:center;
    gap:10px;
    padding:6px 14px 6px 6px;
    border-radius:999px;
    background:var(--navy-soft);
    cursor:default;
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
  .content{
    padding:26px 28px 60px;
    flex:1;
  }
  .greeting{ margin-bottom:22px; }
  .greeting h1{
    font-size:clamp(20px,2.4vw,26px);
    font-weight:800;
    color:var(--navy);
    margin:0 0 4px;
    letter-spacing:-0.3px;
  }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }
  .stats-grid{
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:18px;
    margin-bottom:26px;
  }
  .stat-card{
    background:var(--card);
    border:1px solid var(--line-soft);
    border-radius:var(--radius-lg);
    padding:20px 22px;
    box-shadow:var(--shadow-card);
    display:flex;
    flex-direction:column;
    gap:12px;
    transition:transform .15s var(--ease), box-shadow .15s var(--ease);
  }
  .stat-card:hover{ transform:translateY(-2px); box-shadow:0 16px 40px -14px rgba(15,42,74,0.2); }
  .stat-top{ display:flex; align-items:flex-start; justify-content:space-between; }
  .stat-icon{
    width:42px; height:42px;
    border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
  }
  .stat-icon svg{ width:20px; height:20px; }
  .stat-icon.blue{ background:var(--navy-soft); color:var(--navy-2); }
  .stat-icon.coral{ background:var(--coral-soft); color:var(--coral-dark); }
  .stat-icon.green{ background:var(--success-soft); color:var(--success); }
  .stat-icon.orange{ background:var(--warning-soft); color:var(--warning); }
  .stat-value{ font-size:26px; font-weight:800; color:var(--text); letter-spacing:-0.5px; }
  .stat-label{ font-size:12.5px; color:var(--muted); font-weight:600; }
  .panel{
    background:var(--card);
    border:1px solid var(--line-soft);
    border-radius:var(--radius-lg);
    box-shadow:var(--shadow-card);
    overflow:hidden;
  }
  .panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:20px 22px;
    border-bottom:1px solid var(--line-soft);
    gap:10px;
    flex-wrap:wrap;
  }
  .panel-head h3{
    margin:0;
    font-size:15.5px;
    font-weight:800;
    color:var(--navy);
  }
  .panel-head p{ margin:2px 0 0; font-size:12px; color:var(--muted); }
  .pending-tag{
    padding:6px 13px;
    border-radius:999px;
    font-size:11.5px;
    font-weight:800;
    border:1px solid var(--warning);
    background:var(--warning-soft);
    color:var(--warning);
    white-space:nowrap;
  }
  .table-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; min-width:700px; }
  thead th{
    text-align:left;
    font-size:11px;
    font-weight:700;
    color:var(--muted-2);
    text-transform:uppercase;
    letter-spacing:0.6px;
    padding:12px 22px;
    background:#fbfaf9;
    border-bottom:1px solid var(--line-soft);
    white-space:nowrap;
  }
  tbody td{
    padding:14px 22px;
    font-size:13.5px;
    color:var(--text);
    border-bottom:1px solid var(--line-soft);
    vertical-align:middle;
  }
  tbody tr:last-child td{ border-bottom:none; }
  tbody tr{ transition:background .12s var(--ease); }
  tbody tr:hover{ background:#fbfaf9; }

  /* ---- Grouped lecturer rows ---- */
  .group-row{ cursor:pointer; user-select:none; }
  .group-row td{ background:#fbfaf9; }
  .group-row:hover td{ background:#f5f2ee; }
  .group-row.open td{ background:var(--navy-soft); }
  .group-cell{ display:flex; align-items:center; gap:10px; }
  .group-chevron{
    width:20px; height:20px;
    flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    border-radius:6px;
    background:#fff;
    border:1px solid var(--line);
    transition:transform .18s var(--ease), background .15s var(--ease);
  }
  .group-chevron svg{ width:12px; height:12px; stroke:var(--muted); transition:stroke .15s var(--ease); }
  .group-row.open .group-chevron{ transform:rotate(90deg); background:var(--navy); border-color:var(--navy); }
  .group-row.open .group-chevron svg{ stroke:#fff; }
  .lecturer-avatar{
    width:32px; height:32px;
    border-radius:50%;
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:12.5px; font-weight:800;
    flex-shrink:0;
    overflow:hidden;
  }
  .lecturer-avatar img{
    width:100%; height:100%;
    object-fit:cover;
    display:block;
  }
  .group-name{ font-weight:800; color:var(--navy); font-size:14px; }
  .group-count{
    margin-left:auto;
    display:flex;
    align-items:center;
    gap:6px;
  }
  .count-chip{
    background:var(--navy-soft);
    color:var(--navy-2);
    font-size:11px;
    font-weight:800;
    padding:4px 10px;
    border-radius:20px;
    white-space:nowrap;
  }
  .count-chip.has-pending{ background:var(--warning-soft); color:var(--warning); }

  /* Subject sub-header under lecturer */
  .subject-header-row{ display:none; }
  .subject-header-row.show{ display:table-row; }
  .subject-header-row td{
    background:#f0f4f8;
    padding:10px 22px;
    font-size:12.5px;
    font-weight:800;
    color:var(--navy-2);
  }
  .subject-header-label{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding-left:42px;
  }
  .subject-badge-inline{
    display:inline-flex;
    align-items:center;
    padding:4px 12px;
    border-radius:20px;
    font-size:11.5px;
    font-weight:800;
    background:var(--coral-soft);
    color:var(--coral-dark);
  }

  .slot-row{ display:none; }
  .slot-row.show{ display:table-row; }
  .slot-row td{ background:#fcfbfa; }
  .slot-row:hover td{ background:#f7f5f1; }
  .slot-indent{
    display:flex;
    align-items:center;
    gap:8px;
    padding-left:60px;
    color:var(--muted);
    font-size:12.5px;
  }
  .slot-indent svg{ width:14px; height:14px; flex-shrink:0; stroke:var(--muted-2); }

  .status-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:5px 11px;
    border-radius:20px;
    font-size:11px;
    font-weight:800;
    letter-spacing:0.2px;
  }
  .status-badge::before{
    content:"";
    width:6px; height:6px;
    border-radius:50%;
    background:currentColor;
  }
  .status-badge.pending{ background:var(--warning-soft); color:var(--warning); }
  .status-badge.approved{ background:var(--success-soft); color:var(--success); }
  .status-badge.rejected{ background:var(--danger-soft); color:var(--danger); }

  .row-actions{ display:flex; gap:8px; align-items:center; }
  .approve-btn, .reject-btn{
    padding:7px 13px;
    border-radius:8px;
    border:none;
    font-size:12px;
    font-weight:800;
    cursor:pointer;
    transition:background .15s var(--ease), color .15s var(--ease);
  }
  .approve-btn{ background:var(--success); color:#fff; }
  .approve-btn:hover{ background:#188a4a; }
  .reject-btn{ background:var(--danger-soft); color:var(--danger); }
  .reject-btn:hover{ background:var(--danger); color:#fff; }
  .edit-btn, .delete-btn{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 14px 7px 11px;
    border-radius:20px;
    font-size:12px;
    font-weight:800;
    letter-spacing:0.1px;
    cursor:pointer;
    border:1px solid transparent;
    transition:background .18s var(--ease), color .18s var(--ease), box-shadow .18s var(--ease), transform .12s var(--ease), border-color .18s var(--ease);
  }
  .edit-btn svg, .delete-btn svg{ width:13px; height:13px; flex-shrink:0; }
  .edit-btn{
    background:var(--navy-soft);
    color:var(--navy-2);
    border-color:rgba(22,56,95,0.12);
  }
  .edit-btn:hover{
    background:var(--navy);
    color:#fff;
    border-color:var(--navy);
    box-shadow:0 8px 18px -6px rgba(15,42,74,0.45);
    transform:translateY(-1px);
  }
  .edit-btn:active{ transform:translateY(0); box-shadow:none; }
  .delete-btn{
    background:var(--danger-soft);
    color:var(--danger);
    border-color:rgba(192,57,43,0.14);
  }
  .delete-btn:hover{
    background:var(--danger);
    color:#fff;
    border-color:var(--danger);
    box-shadow:0 8px 18px -6px rgba(192,57,43,0.45);
    transform:translateY(-1px);
  }
  .delete-btn:active{ transform:translateY(0); box-shadow:none; }
  .empty-state{
    padding:40px 20px;
    text-align:center;
    color:var(--muted-2);
    font-size:13px;
  }
  .toast{
    position:fixed;
    top:20px; left:50%;
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
    z-index:100;
    box-shadow:0 12px 30px rgba(15,42,74,0.3);
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error-toast{ background:var(--danger); }
  .toast.success-toast{ background:var(--success); }
  .sidebar-backdrop{
    display:none;
    position:fixed; inset:0;
    background:rgba(15,42,74,0.4);
    z-index:45;
  }
  /* ---- Edit Modal ---- */
  .modal-backdrop{
    display:none;
    position:fixed; inset:0;
    background:rgba(15,42,74,0.5);
    z-index:80;
    align-items:center;
    justify-content:center;
    padding:20px;
  }
  .modal-backdrop.show{ display:flex; }
  .modal-box{
    background:#fff;
    border-radius:var(--radius-lg);
    width:100%;
    max-width:420px;
    box-shadow:0 24px 60px -12px rgba(15,42,74,0.35);
    overflow:hidden;
  }
  .modal-head{
    padding:18px 22px;
    border-bottom:1px solid var(--line-soft);
    display:flex;
    align-items:center;
    justify-content:space-between;
  }
  .modal-head h4{ margin:0; font-size:15px; font-weight:800; color:var(--navy); }
  .modal-close{
    width:28px; height:28px;
    border-radius:8px;
    border:none;
    background:var(--navy-soft);
    color:var(--navy-2);
    cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    font-size:16px;
    line-height:1;
  }
  .modal-close:hover{ background:var(--danger-soft); color:var(--danger); }
  .modal-body{ padding:20px 22px; display:flex; flex-direction:column; gap:14px; }
  .modal-field{ display:flex; flex-direction:column; gap:6px; }
  .modal-field label{ font-size:11.5px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:0.4px; }
  .modal-field input{
    padding:10px 12px;
    border-radius:8px;
    border:1px solid var(--line);
    font-size:13.5px;
    font-family:inherit;
    color:var(--text);
    background:#fbfaf9;
  }
  .modal-field input:focus{ outline:none; border-color:var(--navy-2); background:#fff; }
  .modal-foot{
    padding:16px 22px 20px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
  }
  .modal-cancel-btn, .modal-save-btn{
    padding:9px 16px;
    border-radius:8px;
    border:none;
    font-size:13px;
    font-weight:800;
    cursor:pointer;
  }
  .modal-cancel-btn{ background:var(--navy-soft); color:var(--navy-2); }
  .modal-cancel-btn:hover{ background:#e2e8f0; }
  .modal-save-btn{ background:var(--navy); color:#fff; }
  .modal-save-btn:hover{ background:var(--navy-2); }
  @media (max-width:1100px){
    .stats-grid{ grid-template-columns:repeat(2,1fr); }
  }
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
    thead th:nth-child(2), tbody td:nth-child(2){ display:none; }
    .panel-head{ flex-direction:column; align-items:stretch; }
    .edit-btn span, .delete-btn span{ display:none; }
    .edit-btn, .delete-btn{ padding:8px; border-radius:8px; }
  }
  @media (prefers-reduced-motion: reduce){
    *{ animation-duration:0.001ms !important; transition-duration:0.001ms !important; }
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
    <a class="nav-item active" href="admin_availability_requests.php">
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
        <h2>Lecture Time Requests</h2>
        <p id="todayDate">Loading...</p>
      </div>
    </div>
    <div class="topbar-right">
      <button class="icon-btn" title="Notifications">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        <span class="icon-dot"></span>
      </button>
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
      <h1>Lecture Time Approval Requests</h1>
      <p>Approved slots විතරක් මෙතන පෙන්නන්නේ — Lecturer නමක් click කරලා ඒගොල්ලන්ගේ approved slots බලන්න. Subjects වෙන වෙනම group වෙලා පෙනෙනවා.</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          </span>
        </div>
        <div class="stat-value" id="statPending">0</div>
        <div class="stat-label">Pending (all time)</div>
      </div>
      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
          </span>
        </div>
        <div class="stat-value" id="statApproved">0</div>
        <div class="stat-label">Approved Requests</div>
      </div>
      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-icon coral">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </span>
        </div>
        <div class="stat-value" id="statRejected">0</div>
        <div class="stat-label">Rejected (all time)</div>
      </div>
      <div class="stat-card">
        <div class="stat-top">
          <span class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
          </span>
        </div>
        <div class="stat-value" id="statTotal">0</div>
        <div class="stat-label">TotalSlots (all time)</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>Approved Slots</h3>
          <p>Lecturer ලා ගේ approved වුනු lecture time slots — Lecturer → Subject වෙන වෙනම පෙනෙනවා</p>
        </div>
        <span class="pending-tag" style="border-color:var(--success);background:var(--success-soft);color:var(--success);">Showing: Approved only</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Lecturer / Subject</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td colspan="5"><div class="empty-state">Loading...</div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="editModalBackdrop">
  <div class="modal-box">
    <div class="modal-head">
      <h4 id="editModalTitle">Edit Slot</h4>
      <button class="modal-close" id="editModalClose" type="button">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editSlotId">
      <div class="modal-field">
        <label>Subject</label>
        <input type="text" id="editSubject" placeholder="Subject">
      </div>
      <div class="modal-field">
        <label>Date</label>
        <input type="date" id="editDate">
      </div>
      <div class="modal-field">
        <label>Start Time</label>
        <input type="time" id="editStart">
      </div>
      <div class="modal-field">
        <label>End Time</label>
        <input type="time" id="editEnd">
      </div>
    </div>
    <div class="modal-foot">
      <button class="modal-cancel-btn" id="editModalCancel" type="button">Cancel</button>
      <button class="modal-save-btn" id="editModalSave" type="button">Save Changes</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
  const currentFilter = 'approved';
  const openGroups = new Set();
  const BADGE_POLL_INTERVAL_MS = 15000;
  const PHOTO_BASE_PATH = 'uploads/lecturers/';

  function photoUrl(photo){
    if (!photo) return null;
    if (/^https?:\/\//i.test(photo) || photo.indexOf('/') !== -1) return photo;
    return PHOTO_BASE_PATH + photo;
  }

  const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
  if (adminSession && adminSession.username) {
    document.getElementById('adminName').textContent = adminSession.username;
    document.getElementById('adminAvatar').textContent = adminSession.username.charAt(0).toUpperCase();
  }

  document.getElementById('todayDate').textContent = new Date().toLocaleDateString('en-GB', {
    weekday:'long', year:'numeric', month:'long', day:'numeric'
  });

  function showToast(msg, type=''){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (type ? ' ' + type : '');
    setTimeout(() => t.classList.remove('show'), 2500);
  }

  function updateStats(list){
    const pending  = list.filter(r => r.approval_status === 'pending').length;
    const approved = list.filter(r => r.approval_status === 'approved').length;
    const rejected = list.filter(r => r.approval_status === 'rejected').length;
    document.getElementById('statPending').textContent = pending;
    document.getElementById('statApproved').textContent = approved;
    document.getElementById('statRejected').textContent = rejected;
    document.getElementById('statTotal').textContent = list.length;

    const badge = document.getElementById('pendingAvailabilityBadge');
    if (badge) {
      if (pending > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = pending;
      } else {
        badge.style.display = 'none';
      }
    }
  }

  async function updateBookingsBadge() {
    try {
      const res = await fetch('get_bookings.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = data.data.filter(b => (b.status || '').toLowerCase() === 'pending').length;
      const badge = document.getElementById('pendingBookingsBadge');
      if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
      else { badge.style.display = 'none'; }
    } catch (err) { console.error('Bookings badge update failed', err); }
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
    } catch (err) { console.error('Students badge update failed', err); }
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
    } catch (err) { console.error('Activations badge update failed', err); }
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
    } catch (err) { console.error('Teachers badge update failed', err); }
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
    } catch (err) { console.error('Support badge update failed', err); }
  }

  function initials(name){
    if (!name) return '?';
    return name.trim().split(/\s+/).slice(0,2).map(w => w.charAt(0).toUpperCase()).join('');
  }

  function avatarMarkup(name, photo){
    const fallback = escapeHtml(initials(name));
    const url = photoUrl(photo);
    if (url) {
      const src = escapeHtml(url);
      return `<span class="lecturer-avatar"><img src="${src}" alt="${escapeHtml(name)}" onerror="this.parentElement.innerHTML='${fallback}'"></span>`;
    }
    return `<span class="lecturer-avatar">${fallback}</span>`;
  }

  function escapeHtml(str){
    return String(str ?? '').replace(/[&<>"']/g, c => ({
      '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
    }[c]));
  }

  // Group by Lecturer → then by Subject
  function groupByLecturerThenSubject(list){
    const lecturers = new Map();
    list.forEach(r => {
      const lectName = r.lecturer_name || 'Unknown';
      if (!lecturers.has(lectName)) {
        lecturers.set(lectName, { photo: r.photo, subjects: new Map() });
      }
      const lect = lecturers.get(lectName);
      const subj = (r.subject || '—').trim() || '—';
      if (!lect.subjects.has(subj)) lect.subjects.set(subj, []);
      lect.subjects.get(subj).push(r);
    });
    return lecturers;
  }

  function renderTable(list){
    const tbody = document.getElementById('tbody');

    if (list.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">Approved requests නැහැ.</div></td></tr>`;
      return;
    }

    const groups = groupByLecturerThenSubject(list);
    let html = '';

    groups.forEach((lectData, lecturerName) => {
      const groupId = 'grp_' + btoa(unescape(encodeURIComponent(lecturerName))).replace(/[^a-zA-Z0-9]/g, '');
      const totalSlots = Array.from(lectData.subjects.values()).reduce((sum, arr) => sum + arr.length, 0);
      const isOpen = openGroups.has(groupId);

      // Lecturer header row
      html += `
        <tr class="group-row${isOpen ? ' open' : ''}" data-group="${groupId}">
          <td colspan="3">
            <div class="group-cell">
              <span class="group-chevron">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
              </span>
              ${avatarMarkup(lecturerName, lectData.photo)}
              <span class="group-name">${escapeHtml(lecturerName)}</span>
            </div>
          </td>
          <td colspan="2">
            <div class="group-count">
              <span class="count-chip">${totalSlots} approved</span>
            </div>
          </td>
        </tr>
      `;

      // For each subject under this lecturer
      lectData.subjects.forEach((slots, subjectName) => {
        const subjId = groupId + '_subj_' + btoa(unescape(encodeURIComponent(subjectName))).replace(/[^a-zA-Z0-9]/g, '');

        // Subject sub-header
        html += `
          <tr class="subject-header-row${isOpen ? ' show' : ''}" data-group="${groupId}">
            <td colspan="5">
              <div class="subject-header-label">
                <span class="subject-badge-inline">${escapeHtml(subjectName)}</span>
                <span style="font-size:11px;color:var(--muted);font-weight:600;">${slots.length} slot${slots.length !== 1 ? 's' : ''}</span>
              </div>
            </td>
          </tr>
        `;

        // Actual slots under this subject
        slots.forEach(r => {
          html += `
            <tr class="slot-row${isOpen ? ' show' : ''}" data-group="${groupId}">
              <td>
                <div class="slot-indent">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                  Slot
                </div>
              </td>
              <td>${escapeHtml(r.date)}</td>
              <td>${escapeHtml(r.start)} - ${escapeHtml(r.end)}</td>
              <td><span class="status-badge ${r.approval_status}">${r.approval_status}</span></td>
              <td>
                <div class="row-actions">
                  <button class="edit-btn"
                    data-id="${r.id}"
                    data-subject="${escapeHtml(r.subject || '')}"
                    data-date="${escapeHtml(r.date)}"
                    data-start="${escapeHtml(r.start)}"
                    data-end="${escapeHtml(r.end)}"
                    data-lecturer="${escapeHtml(lecturerName)}"
                    onclick="openEditModal(this.dataset)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <span>Edit</span>
                  </button>
                  <button class="delete-btn" onclick="deleteSlot(${r.id})">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z"/><path d="M10 11v6M14 11v6"/></svg>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>
          `;
        });
      });
    });

    tbody.innerHTML = html;

    // Expand / collapse
    tbody.querySelectorAll('.group-row').forEach(row => {
      row.addEventListener('click', () => {
        const groupId = row.dataset.group;
        const isNowOpen = !row.classList.contains('open');
        row.classList.toggle('open', isNowOpen);
        if (isNowOpen) openGroups.add(groupId); else openGroups.delete(groupId);

        tbody.querySelectorAll(`.subject-header-row[data-group="${groupId}"]`).forEach(sr => {
          sr.classList.toggle('show', isNowOpen);
        });
        tbody.querySelectorAll(`.slot-row[data-group="${groupId}"]`).forEach(sr => {
          sr.classList.toggle('show', isNowOpen);
        });
      });
    });
  }

  async function loadRequests(){
    const tbody = document.getElementById('tbody');
    tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">Loading...</div></td></tr>`;
    try {
      const res = await fetch(`admin_get_pending_availability.php?filter=${currentFilter}`);
      const data = await res.json();
      if (!data.success) {
        tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">${escapeHtml(data.message || 'Error')}</div></td></tr>`;
        return;
      }
      try {
        const allRes = await fetch(`admin_get_pending_availability.php?filter=all`);
        const allData = await allRes.json();
        if (allData.success) updateStats(allData.data);
      } catch(e) {}
      renderTable(data.data);
    } catch(e) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">Server error.</div></td></tr>`;
    }
  }

  window.updateApproval = async function(id, action){
    if (!confirm(action === 'approve' ? 'මේ slot එක approve කරන්නද?' : 'මේ slot එක reject කරන්නද?')) return;
    try {
      const res = await fetch('admin_update_availability_approval.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, action })
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success-toast');
        loadRequests();
      } else {
        showToast(data.message || 'Failed', 'error-toast');
      }
    } catch(e) {
      showToast('Server error', 'error-toast');
    }
  };

  /* ===== Edit modal ===== */
  const editModalBackdrop = document.getElementById('editModalBackdrop');
  window.openEditModal = function(data){
    document.getElementById('editSlotId').value = data.id;
    document.getElementById('editSubject').value = data.subject || '';
    document.getElementById('editDate').value = data.date || '';
    document.getElementById('editStart').value = data.start || '';
    document.getElementById('editEnd').value = data.end || '';
    document.getElementById('editModalTitle').textContent = data.lecturer ? `Edit Slot — ${data.lecturer}` : 'Edit Slot';
    editModalBackdrop.classList.add('show');
  };
  function closeEditModal(){
    editModalBackdrop.classList.remove('show');
  }
  document.getElementById('editModalClose').addEventListener('click', closeEditModal);
  document.getElementById('editModalCancel').addEventListener('click', closeEditModal);
  editModalBackdrop.addEventListener('click', (e) => {
    if (e.target === editModalBackdrop) closeEditModal();
  });

  document.getElementById('editModalSave').addEventListener('click', async () => {
    const id = document.getElementById('editSlotId').value;
    const subject = document.getElementById('editSubject').value.trim();
    const date = document.getElementById('editDate').value;
    const start = document.getElementById('editStart').value;
    const end = document.getElementById('editEnd').value;
    if (!date || !start || !end) {
      showToast('Date, Start සහ End time අනිවාර්යයි', 'error-toast');
      return;
    }
    if (start >= end) {
      showToast('End time, Start time එකට වඩා පස්සේ එකක් වෙන්න ඕන', 'error-toast');
      return;
    }
    const saveBtn = document.getElementById('editModalSave');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    try {
      const res = await fetch('admin_edit_availability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, subject, date, start, end })
      });
      const result = await res.json();
      if (result.success) {
        showToast(result.message || 'Slot updated', 'success-toast');
        closeEditModal();
        loadRequests();
      } else {
        showToast(result.message || 'Update failed', 'error-toast');
      }
    } catch (e) {
      showToast('Server error', 'error-toast');
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save Changes';
    }
  });

  window.deleteSlot = async function(id){
    if (!confirm('මේ slot එක permanently delete කරන්නද? මේක undo කරන්න බැහැ.')) return;
    try {
      const res = await fetch('admin_delete_availability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Slot deleted', 'success-toast');
        loadRequests();
      } else {
        showToast(data.message || 'Delete failed', 'error-toast');
      }
    } catch(e) {
      showToast('Server error', 'error-toast');
    }
  };

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

  loadRequests();
  updateBookingsBadge();
  updateStudentsBadge();
  updateActivationsBadge();
  updateTeachersBadge();
  updateSupportBadge();
  setInterval(updateBookingsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateStudentsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateActivationsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateTeachersBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateSupportBadge, BADGE_POLL_INTERVAL_MS);
})();
</script>
</body>
</html>