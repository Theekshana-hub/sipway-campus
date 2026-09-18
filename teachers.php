<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teachers - Sipway Campus Admin</title>
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

  .greeting{
    margin-bottom:22px;
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
  }
  .greeting h1{
    font-size:clamp(20px,2.4vw,26px);
    font-weight:800;
    color:var(--navy);
    margin:0 0 4px;
    letter-spacing:-0.3px;
  }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }

  .add-btn{
    display:flex;
    align-items:center;
    gap:8px;
    padding:11px 20px;
    border-radius:10px;
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff;
    font-weight:700;
    font-size:13.5px;
    border:none;
    cursor:pointer;
    box-shadow:0 8px 20px -6px rgba(214,108,71,0.5);
    transition:transform .15s var(--ease), box-shadow .15s var(--ease);
    white-space:nowrap;
  }
  .add-btn:hover{ transform:translateY(-1px); box-shadow:0 10px 24px -6px rgba(214,108,71,0.6); }
  .add-btn svg{ width:16px; height:16px; }

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

  .head-controls{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }

  .filter-pills{
    display:flex;
    gap:6px;
    background:var(--bg);
    padding:4px;
    border-radius:9px;
    border:1px solid var(--line-soft);
  }
  .filter-pill{
    padding:7px 13px;
    border-radius:7px;
    font-size:12px;
    font-weight:700;
    color:var(--muted);
    cursor:pointer;
    border:none;
    background:none;
    transition:background .15s var(--ease), color .15s var(--ease);
    white-space:nowrap;
  }
  .filter-pill:hover{ color:var(--navy); }
  .filter-pill.active{ background:#fff; color:var(--coral-dark); box-shadow:0 2px 8px rgba(15,42,74,0.08); }

  .search-box{
    padding:9px 14px;
    border:1px solid var(--line);
    border-radius:8px;
    font-size:13px;
    font-family:inherit;
    background:var(--bg);
    min-width:200px;
  }
  .search-box:focus{ outline:none; border-color:var(--coral); }
  .teacher-count{
    font-size:11.5px;
    font-weight:700;
    color:var(--coral-dark);
    background:var(--coral-soft);
    padding:6px 13px;
    border-radius:999px;
    white-space:nowrap;
  }

  .table-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; min-width:1080px; }
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

  .person-cell{ display:flex; align-items:center; gap:10px; }
  .person-avatar{
    width:32px; height:32px;
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:11.5px; font-weight:800;
    color:#fff;
    flex-shrink:0;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    overflow:hidden;
  }
  .person-avatar img{
    width:100%; height:100%;
    object-fit:cover;
    display:block;
  }
  .person-name{ font-weight:700; font-size:13.5px; }
  .person-sub{ font-size:11.5px; color:var(--muted-2); font-weight:500; }

  .subject-badges{
    display:flex;
    flex-wrap:wrap;
    gap:5px;
  }
  .subject-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:5px 11px;
    border-radius:20px;
    font-size:11px;
    font-weight:800;
    background:var(--coral-soft);
    color:var(--coral-dark);
  }

  .lang-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 11px;
    border-radius:20px;
    font-size:11px;
    font-weight:800;
    background:var(--navy-soft);
    color:var(--navy-2);
    white-space:nowrap;
  }
  .lang-badge .lang-flag{
    width:20px; height:14px;
    border-radius:2px;
    box-shadow:0 0 0 1px rgba(0,0,0,0.1);
    display:inline-block;
    vertical-align:middle;
    flex-shrink:0;
  }

  .lang-preview{
    display:flex;
    align-items:center;
    gap:9px;
    margin-top:9px;
    padding:9px 12px;
    background:var(--navy-soft);
    border-radius:9px;
    font-size:12.5px;
    font-weight:700;
    color:var(--navy);
  }
  .lang-preview .lang-flag{
    width:22px; height:16px;
    border-radius:3px;
    box-shadow:0 0 0 1px rgba(15,42,74,0.15);
    flex-shrink:0;
  }

  .status-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:5px 11px;
    border-radius:20px;
    font-size:11px;
    font-weight:800;
    text-transform:capitalize;
  }
  .status-pending{ background:var(--warning-soft); color:var(--warning); }
  .status-approved{ background:var(--success-soft); color:var(--success); }
  .status-rejected{ background:var(--danger-soft); color:var(--danger); }

  .action-cell{ display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
  .icon-btn{
    width:30px; height:30px;
    border-radius:8px;
    border:none;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer;
    transition:background .15s var(--ease), transform .1s var(--ease);
    flex-shrink:0;
  }
  .icon-btn svg{ width:15px; height:15px; }
  .icon-btn:active{ transform:scale(.92); }
  .btn-view{ background:var(--navy-soft); color:var(--navy-2); }
  .btn-view:hover{ background:#dfe7f0; }
  .btn-slot{ background:var(--success-soft); color:var(--success); }
  .btn-slot:hover{ background:#d3f2df; }
  .btn-edit{ background:var(--navy-soft); color:var(--navy); }
  .btn-edit:hover{ background:#dfe7f0; }
  .btn-delete{ background:var(--danger-soft); color:var(--danger); }
  .btn-delete:hover{ background:#fadbd8; }
  .btn-approve{ background:var(--success-soft); color:var(--success); }
  .btn-approve:hover{ background:#d3f2df; }
  .btn-reject{ background:var(--danger-soft); color:var(--danger); }
  .btn-reject:hover{ background:#fadbd8; }

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
    max-width:90vw;
    text-align:center;
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error-toast{ background:var(--danger); }
  .toast.success-toast{ background:var(--success); }
  .toast.warning-toast{ background:var(--warning); }

  .sidebar-backdrop{
    display:none;
    position:fixed; inset:0;
    background:rgba(15,42,74,0.4);
    z-index:45;
  }

  /* ===== Modal ===== */
  .modal-overlay{
    display:none;
    position:fixed; inset:0;
    background:rgba(15,42,74,0.45);
    z-index:200;
    align-items:center;
    justify-content:center;
    padding:20px;
  }
  .modal-overlay.show{ display:flex; }
  .modal-box{
    background:#fff;
    border-radius:var(--radius-lg);
    width:100%;
    max-width:440px;
    box-shadow:0 30px 70px -20px rgba(15,42,74,0.4);
    max-height:90vh;
    overflow-y:auto;
    animation:modalPop .2s var(--ease);
  }
  .modal-box.confirm-box{ max-width:380px; }
  @keyframes modalPop{
    from{ opacity:0; transform:translateY(10px) scale(.98); }
    to{ opacity:1; transform:translateY(0) scale(1); }
  }
  .modal-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:20px 22px;
    border-bottom:1px solid var(--line-soft);
  }
  .modal-head h3{ margin:0; font-size:16px; font-weight:800; color:var(--navy); }
  .modal-close{
    background:var(--navy-soft);
    border:none;
    width:30px; height:30px;
    border-radius:8px;
    cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    color:var(--navy);
  }
  .modal-body{ padding:22px; }
  .form-group{ margin-bottom:16px; }
  .form-group label{
    display:block;
    font-size:12.5px;
    font-weight:700;
    color:var(--navy);
    margin-bottom:6px;
  }
  .form-group input, .form-group textarea, .form-group select{
    width:100%;
    padding:11px 13px;
    border:1px solid var(--line);
    border-radius:9px;
    font-size:13.5px;
    font-family:inherit;
    background:var(--bg);
    transition:border-color .15s var(--ease);
  }
  .form-group textarea{ resize:vertical; min-height:70px; }
  .form-group input:focus, .form-group textarea:focus, .form-group select:focus{ outline:none; border-color:var(--coral); background:#fff; }
  .form-group input:disabled{ opacity:.85; cursor:not-allowed; }
  .form-hint{ font-size:11px; color:var(--muted-2); margin-top:5px; }
  .form-error{
    font-size:11.5px;
    color:var(--danger);
    margin-top:5px;
    font-weight:600;
    display:none;
  }
  .form-error.show{ display:block; }

  /* Multi-select subject styling */
  select[multiple]{
    min-height:110px;
    padding:8px;
  }
  select[multiple] option{
    padding:7px 10px;
    border-radius:6px;
    margin-bottom:2px;
  }
  select[multiple] option:checked{
    background: linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff;
  }

  .photo-upload-row{ display:flex; align-items:center; gap:14px; }
  .photo-current{
    width:60px; height:60px;
    border-radius:12px;
    object-fit:cover;
    background:var(--navy-soft);
    border:1.5px solid var(--line);
    flex-shrink:0;
  }
  .photo-upload-row input[type="file"]{
    font-size:12.5px;
    padding:9px 10px;
  }

  .modal-foot{
    display:flex;
    gap:10px;
    padding:18px 22px 22px;
  }
  .btn-cancel, .btn-save{
    flex:1;
    padding:12px;
    border-radius:9px;
    font-weight:700;
    font-size:13.5px;
    cursor:pointer;
    border:none;
    transition:opacity .15s var(--ease);
  }
  .btn-cancel{ background:var(--navy-soft); color:var(--navy); }
  .btn-save{ background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%); color:#fff; }
  .btn-save.btn-danger{ background:linear-gradient(135deg, #d9534f 0%, var(--danger) 100%); }
  .btn-save:disabled{ opacity:.6; cursor:not-allowed; }
  .btn-cancel:hover, .btn-save:hover:not(:disabled){ opacity:.88; }
  .confirm-text{ font-size:13.5px; color:var(--text); line-height:1.6; margin:0; }
  .confirm-text b{ color:var(--navy); }

  /* ===== View Teacher Modal ===== */
  .view-head{
    display:flex;
    align-items:center;
    gap:16px;
    padding:22px;
    border-bottom:1px solid var(--line-soft);
  }
  .view-photo{
    width:80px; height:80px;
    border-radius:16px;
    object-fit:cover;
    background:var(--navy-soft);
    border:1.5px solid var(--line);
    flex-shrink:0;
  }
  .view-photo-fallback{
    width:80px; height:80px;
    border-radius:16px;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:26px; font-weight:800;
    flex-shrink:0;
  }
  .view-head h3{ margin:0 0 4px; font-size:17px; font-weight:800; color:var(--navy); }
  .view-head p{ margin:0; font-size:12.5px; color:var(--muted); }
  .view-body{ padding:20px 22px; }
  .view-row{ margin-bottom:14px; }
  .view-row:last-child{ margin-bottom:0; }
  .view-row .vlabel{
    font-size:10.5px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase;
    color:var(--muted-2); margin-bottom:5px;
  }
  .view-row .vvalue{ font-size:13.5px; color:var(--text); line-height:1.6; white-space:pre-line; }
  .view-row .vvalue.empty{ color:var(--muted-2); font-style:italic; }

  @media (max-width:880px){
    :root{ --sidebar-w:230px; }
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,0.3); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
    .sidebar-backdrop.show{ display:block; }
  }
  @media (max-width:560px){
    .topbar{ padding:0 16px; }
    .content{ padding:18px 16px 40px; }
    .admin-chip .name, .admin-chip .role{ display:none; }
    .admin-chip{ padding:6px; }
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
    <a class="nav-item active" href="teachers.php">
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
        <h2>Teachers</h2>
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
        <h1>Teachers 👩‍🏫</h1>
        <p>Sipway Campus හි ලියාපදිංචි lecturers okkoma මෙතන. Lecturer ge name ekata issaraha thiyena 📅 icon eken ඒ lecturer ට availability slot ekak directly add karanna puluwan. දැන් subject එකක් තෝරලා ඒ subject එකට වෙනම time add කරන්න පුළුවන්.</p>
      </div>
      <button class="add-btn" id="openAddModalBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        Add Teacher
      </button>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>All Teachers</h3>
          <p>Lecturer accounts registered in the system</p>
        </div>
        <div class="head-controls">
          <div class="filter-pills" id="filterPills">
            <button class="filter-pill active" data-filter="all">All</button>
            <button class="filter-pill" data-filter="pending">Pending</button>
            <button class="filter-pill" data-filter="approved">Approved</button>
            <button class="filter-pill" data-filter="rejected">Rejected</button>
          </div>
          <input type="text" class="search-box" id="searchBox" placeholder="Search name, NIC, phone, username...">
          <span class="teacher-count" id="teacherCount">0 teachers</span>
        </div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Teacher</th>
              <th>Subject</th>
              <th>Language</th>
              <th>Email</th>
              <th>NIC</th>
              <th>Phone</th>
              <th>Username</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="teachersBody">
            <tr><td colspan="10"><div class="empty-state">Loading teachers...</div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ===== Add Teacher Modal ===== -->
<div class="modal-overlay" id="addModalOverlay">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Add New Teacher</h3>
      <button class="modal-close" id="closeAddModalBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="addTeacherForm">
      <div class="modal-body">
        <div class="form-group">
          <label for="tFullName">Full Name</label>
          <input type="text" id="tFullName" name="full_name" placeholder="e.g. Mr. Kasun Perera" required>
          <div class="form-error" id="err_full_name"></div>
        </div>
        <div class="form-group">
          <label for="tGender">Gender</label>
          <select id="tGender" name="gender" required>
            <option value="">-- Select --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
          <div class="form-error" id="err_gender"></div>
        </div>

        <!-- ========== MULTI SUBJECT DROPDOWN ========== -->
        <div class="form-group">
          <label for="tSubject">Subjects</label>
          <select id="tSubject" name="subject" multiple size="5">
            <option value="">-- Select Subject(s) --</option>
          </select>
          <div class="form-hint">Ctrl (Windows) හෝ Cmd (Mac) ඔබා තියාගෙන multiple subjects select කරන්න</div>
          <div class="form-error" id="err_subject"></div>
        </div>

        <div class="form-group">
          <label for="tLanguage">Language</label>
          <select id="tLanguage" name="language">
            <!-- JS එකෙන් fill වෙනවා -->
          </select>
          <div id="tLangPreview" class="lang-preview"></div>
          <div class="form-error" id="err_language"></div>
        </div>
        <div class="form-group">
          <label for="tQualifications">Qualifications</label>
          <textarea id="tQualifications" name="qualifications" placeholder="e.g. BA in English (Hons), TESOL Certified"></textarea>
          <div class="form-error" id="err_qualifications"></div>
        </div>
        <div class="form-group">
          <label for="tEmail">Email</label>
          <input type="email" id="tEmail" name="email" placeholder="teacher@example.com" required>
          <div class="form-error" id="err_email"></div>
        </div>
        <div class="form-group">
          <label for="tNic">NIC Number</label>
          <input type="text" id="tNic" name="nic" placeholder="e.g. 991234567V" required maxlength="12">
          <div class="form-hint">Old format: 991234567V — New format: 199912345678</div>
          <div class="form-error" id="err_nic"></div>
        </div>
        <div class="form-group">
          <label for="tPhone">Phone Number</label>
          <input type="tel" id="tPhone" name="phone" placeholder="e.g. 0771234567" required maxlength="15">
          <div class="form-error" id="err_phone"></div>
        </div>
        <div class="form-group">
          <label for="tUsername">Username</label>
          <input type="text" id="tUsername" name="username" placeholder="e.g. kasun.perera" required>
          <div class="form-error" id="err_username"></div>
        </div>
        <div class="form-group">
          <label for="tPassword">Password</label>
          <input type="password" id="tPassword" name="password" placeholder="Minimum 6 characters" required minlength="6">
          <div class="form-error" id="err_password"></div>
        </div>
        <div class="form-group">
          <label for="tPhoto">Photo (optional)</label>
          <input type="file" id="tPhoto" name="photo" accept="image/jpeg,image/png,image/webp">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" id="cancelAddBtn">Cancel</button>
        <button type="submit" class="btn-save" id="saveTeacherBtn">Save Teacher</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Edit Teacher Modal ===== -->
<div class="modal-overlay" id="editModalOverlay">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Edit Teacher</h3>
      <button class="modal-close" id="closeEditModalBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="editTeacherForm">
      <input type="hidden" id="eTeacherId" name="id">
      <div class="modal-body">
        <div class="form-group">
          <label for="eFullName">Full Name</label>
          <input type="text" id="eFullName" name="full_name" required>
          <div class="form-error" id="edit_err_full_name"></div>
        </div>
        <div class="form-group">
          <label for="eGender">Gender</label>
          <select id="eGender" name="gender" required>
            <option value="">-- Select --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
          <div class="form-error" id="edit_err_gender"></div>
        </div>

        <!-- ========== MULTI SUBJECT DROPDOWN ========== -->
        <div class="form-group">
          <label for="eSubject">Subjects</label>
          <select id="eSubject" name="subject" multiple size="5">
            <option value="">-- Select Subject(s) --</option>
          </select>
          <div class="form-hint">Ctrl (Windows) හෝ Cmd (Mac) ඔබා තියාගෙන multiple subjects select කරන්න</div>
          <div class="form-error" id="edit_err_subject"></div>
        </div>

        <div class="form-group">
          <label for="eLanguage">Language</label>
          <select id="eLanguage" name="language"></select>
          <div id="eLangPreview" class="lang-preview"></div>
          <div class="form-error" id="edit_err_language"></div>
        </div>
        <div class="form-group">
          <label for="eQualifications">Qualifications</label>
          <textarea id="eQualifications" name="qualifications"></textarea>
          <div class="form-error" id="edit_err_qualifications"></div>
        </div>
        <div class="form-group">
          <label for="eEmail">Email</label>
          <input type="email" id="eEmail" name="email" required>
          <div class="form-error" id="edit_err_email"></div>
        </div>
        <div class="form-group">
          <label for="eNic">NIC Number</label>
          <input type="text" id="eNic" name="nic" required maxlength="12">
          <div class="form-error" id="edit_err_nic"></div>
        </div>
        <div class="form-group">
          <label for="ePhone">Phone Number</label>
          <input type="tel" id="ePhone" name="phone" placeholder="e.g. 0771234567" required maxlength="15">
          <div class="form-error" id="edit_err_phone"></div>
        </div>
        <div class="form-group">
          <label for="eUsername">Username</label>
          <input type="text" id="eUsername" name="username" required>
          <div class="form-error" id="edit_err_username"></div>
        </div>
        <div class="form-group">
          <label for="ePassword">New Password (leave blank to keep current)</label>
          <input type="password" id="ePassword" name="password" placeholder="Leave blank to keep current">
          <div class="form-error" id="edit_err_password"></div>
        </div>
        <div class="form-group">
          <label>Photo</label>
          <div class="photo-upload-row">
            <img id="eCurrentPhoto" class="photo-current" style="display:none;" alt="Current">
            <input type="file" id="ePhoto" name="photo" accept="image/jpeg,image/png,image/webp">
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" id="cancelEditBtn">Cancel</button>
        <button type="submit" class="btn-save" id="updateTeacherBtn">Update Teacher</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== View Teacher Modal ===== -->
<div class="modal-overlay" id="viewModalOverlay">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Teacher Details</h3>
      <button class="modal-close" id="closeViewModalBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="view-head">
      <img id="viewPhoto" class="view-photo" style="display:none;" alt="">
      <div id="viewPhotoFallback" class="view-photo-fallback" style="display:none;"></div>
      <div>
        <h3 id="viewName"></h3>
        <p id="viewMeta"></p>
      </div>
    </div>
    <div class="view-body">
      <div class="view-row">
        <div class="vlabel">Subjects</div>
        <div class="vvalue" id="viewSubject"></div>
      </div>
      <div class="view-row">
        <div class="vlabel">Language</div>
        <div class="vvalue" id="viewLanguage"></div>
      </div>
      <div class="view-row">
        <div class="vlabel">Qualifications</div>
        <div class="vvalue" id="viewQualifications"></div>
      </div>
      <div class="view-row">
        <div class="vlabel">Email</div>
        <div class="vvalue" id="viewEmail"></div>
      </div>
      <div class="view-row">
        <div class="vlabel">NIC</div>
        <div class="vvalue" id="viewNic"></div>
      </div>
      <div class="view-row">
        <div class="vlabel">Phone</div>
        <div class="vvalue" id="viewPhone"></div>
      </div>
      <div class="view-row">
        <div class="vlabel">Username</div>
        <div class="vvalue" id="viewUsername"></div>
      </div>
      <div class="view-row">
        <div class="vlabel">Status</div>
        <div class="vvalue" id="viewStatus"></div>
      </div>
      <div class="view-row">
        <div class="vlabel">Joined</div>
        <div class="vvalue" id="viewJoined"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-cancel" id="closeViewBtn2" style="flex:1;">Close</button>
    </div>
  </div>
</div>

<!-- ===== Add Slot Modal (UPDATED - Subject selection added) ===== -->
<div class="modal-overlay" id="addSlotModalOverlay">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Add Availability Slot</h3>
      <button class="modal-close" id="closeAddSlotModalBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="addSlotForm">
      <div class="modal-body">
        <input type="hidden" id="slotLecturerId">
        
        <div class="form-group">
          <label>Lecturer</label>
          <input type="text" id="slotLecturerName" disabled>
        </div>

        <!-- ========== SUBJECT SELECT (NEW) ========== -->
        <div class="form-group">
          <label for="slotSubject">Subject</label>
          <select id="slotSubject" required>
            <option value="">-- Select Subject --</option>
          </select>
          <div class="form-hint">මේ lecturer ට තියෙන subjects වලින් එකක් තෝරන්න. ඒ subject එකට වෙනම time slot එකක් add වෙනවා.</div>
          <div class="form-error" id="slot_err_subject"></div>
        </div>

        <div class="form-group">
          <label for="slotDateInput">Date</label>
          <input type="date" id="slotDateInput" required>
          <div class="form-error" id="slot_err_date"></div>
        </div>
        <div class="form-group">
          <label for="slotStartInput">Start Time</label>
          <input type="time" id="slotStartInput" required>
          <div class="form-error" id="slot_err_start"></div>
        </div>
        <div class="form-group">
          <label for="slotEndInput">End Time</label>
          <input type="time" id="slotEndInput" required>
          <div class="form-error" id="slot_err_end"></div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" id="slotIsFree" style="width:auto;">
            Free Session (no package needed)
          </label>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" id="cancelAddSlotBtn">Cancel</button>
        <button type="submit" class="btn-save" id="saveSlotBtn">Add Slot</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Delete Confirm Modal ===== -->
<div class="modal-overlay" id="deleteModalOverlay">
  <div class="modal-box confirm-box">
    <div class="modal-head">
      <h3>Delete Teacher</h3>
      <button class="modal-close" id="closeDeleteModalBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p class="confirm-text">Are you sure you want to delete <b id="deleteTeacherName"></b>? This action cannot be undone.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
      <button type="button" class="btn-save btn-danger" id="confirmDeleteBtn">Delete</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
  const PHOTO_BASE_PATH = 'uploads/lecturers/';
  const BADGE_POLL_INTERVAL_MS = 30000;
  const NIC_REGEX = /^([0-9]{9}[vVxX]|[0-9]{12})$/;
  const PHONE_REGEX = /^[0-9+\s\-]{9,15}$/;

  let allTeachers = [];
  let currentFilter = 'all';
  let pendingDeleteId = null;

  const avatarColors = ['#0f2a4a','#16385f','#e8825f','#d66c47','#1f9d55','#c0392b'];

  const LANGUAGE_MAP = {
    en: { code: 'gb', label: 'English' },
    de: { code: 'de', label: 'German' },
    zh: { code: 'cn', label: 'Chinese' },
    ja: { code: 'jp', label: 'Japanese' },
    fr: { code: 'fr', label: 'French' },
    hi: { code: 'in', label: 'Hindi' },
    ru: { code: 'ru', label: 'Russian' },
    ar: { code: 'sa', label: 'Arabic' },
    ta: { code: 'in', label: 'Tamil' },
    si: { code: 'lk', label: 'Sinhala' },
    it: { code: 'it', label: 'Italian' },
  };

  function normalizeCode(c){ return String(c||'en').trim().toLowerCase(); }

  function showToast(msg, type){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (type ? ' ' + type : '');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => t.classList.remove('show'), 2800);
  }

  function langBadge(code){
    const info = LANGUAGE_MAP[normalizeCode(code)] || { code:'gb', label: code || 'English' };
    return `<span class="lang-badge"><img class="lang-flag" src="https://flagcdn.com/w40/${info.code}.png" alt="">${info.label}</span>`;
  }

  function statusBadge(s){
    return `<span class="status-badge status-${s}">${s}</span>`;
  }

  function photoUrl(photo) {
    return photo ? PHOTO_BASE_PATH + photo : null;
  }

  // Helper: get selected subjects as comma-separated string
  function getSelectedSubjects(selectEl) {
    return Array.from(selectEl.selectedOptions)
      .map(opt => opt.value.trim())
      .filter(v => v !== '')
      .join(',');
  }

  // Helper: set selected subjects from comma-separated string
  function setSelectedSubjects(selectEl, subjectsStr) {
    const selected = (subjectsStr || '').split(',').map(s => s.trim()).filter(Boolean);
    Array.from(selectEl.options).forEach(opt => {
      opt.selected = selected.includes(opt.value);
    });
  }

  // Helper: render multiple subject badges
  function subjectBadgesHtml(subjectsStr) {
    if (!subjectsStr || !subjectsStr.trim()) {
      return '<span style="color:var(--muted-2);font-size:12px;">—</span>';
    }
    const subjects = subjectsStr.split(',').map(s => s.trim()).filter(Boolean);
    if (subjects.length === 0) {
      return '<span style="color:var(--muted-2);font-size:12px;">—</span>';
    }
    return `<div class="subject-badges">${subjects.map(s => `<span class="subject-badge">${s}</span>`).join('')}</div>`;
  }

  // ========== Load Subjects into both dropdowns ==========
  function loadSubjects() {
    return fetch('subjects_api.php')
      .then(r => r.json())
      .then(data => {
        if (!data.success || !Array.isArray(data.data)) return;
        const options = data.data.map(s => `<option value="${s.name}">${s.name}</option>`).join('');
        const tSelect = document.getElementById('tSubject');
        const eSelect = document.getElementById('eSubject');
        if (tSelect) tSelect.innerHTML = options;
        if (eSelect) eSelect.innerHTML = options;
      })
      .catch(err => console.error('Subjects load failed', err));
  }

  // ========== Language dropdowns ==========
  function populateLanguageSelects() {
    const opts = Object.entries(LANGUAGE_MAP).map(([code, info]) =>
      `<option value="${code}">${info.label}</option>`
    ).join('');
    document.getElementById('tLanguage').innerHTML = opts;
    document.getElementById('eLanguage').innerHTML = opts;
    document.getElementById('tLanguage').value = 'en';
    document.getElementById('eLanguage').value = 'en';
  }

  function updateLangPreview(select, previewEl) {
    const code = normalizeCode(select.value);
    const info = LANGUAGE_MAP[code] || { code:'gb', label: code };
    previewEl.innerHTML = `<img class="lang-flag" src="https://flagcdn.com/w40/${info.code}.png" alt=""> ${info.label}`;
  }

  function updateTeachersBadge() {
    const pending = allTeachers.filter(t => (t.status || 'pending') === 'pending').length;
    const badge = document.getElementById('pendingTeachersBadge');
    if (!badge) return;
    if (pending > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = pending;
    } else {
      badge.style.display = 'none';
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

  async function updateAvailabilityBadge() {
    try {
      const res = await fetch('admin_get_pending_availability.php?filter=all');
      const data = await res.json();
      if (!data.success) return;
      const pending = (data.data || []).filter(r => r.approval_status === 'pending').length;
      const badge = document.getElementById('pendingAvailabilityBadge');
      if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
      else { badge.style.display = 'none'; }
    } catch (err) { console.error('Availability badge update failed', err); }
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

  function applyFilters() {
    const q = document.getElementById('searchBox').value.toLowerCase().trim();
    let list = allTeachers;
    if (currentFilter !== 'all') {
      list = list.filter(t => (t.status || 'pending') === currentFilter);
    }
    if (q !== '') {
      list = list.filter(t =>
        t.full_name.toLowerCase().includes(q) ||
        (t.email || '').toLowerCase().includes(q) ||
        (t.nic || '').toLowerCase().includes(q) ||
        (t.phone || '').toLowerCase().includes(q) ||
        t.username.toLowerCase().includes(q) ||
        (t.subject || '').toLowerCase().includes(q)
      );
    }
    renderTable(list);
  }

  function renderTable(list) {
    const tbody = document.getElementById('teachersBody');
    document.getElementById('teacherCount').textContent = list.length + ' teacher' + (list.length !== 1 ? 's' : '');

    if (list.length === 0) {
      tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state">Teachers හමු නොවුනා.</div></td></tr>`;
      return;
    }

    tbody.innerHTML = list.map((t, i) => {
      const initials = t.full_name.charAt(0).toUpperCase();
      const color = avatarColors[i % avatarColors.length];
      const joined = t.created_at ? new Date(t.created_at).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) : '-';
      const status = t.status || 'pending';
      const pUrl = photoUrl(t.photo);

      const avatarInner = pUrl
        ? `<img src="${pUrl}" alt="${t.full_name}" onerror="var p=this.parentElement; p.textContent='${initials}'; p.style.background='${color}';">`
        : initials;
      const avatarStyle = pUrl ? '' : ` style="background:${color};"`;

      let approveRejectBtns = '';
      if (status === 'pending') {
        approveRejectBtns = `
          <button class="icon-btn btn-approve" title="Approve" data-action="approve" data-id="${t.id}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
          </button>
          <button class="icon-btn btn-reject" title="Reject" data-action="reject" data-id="${t.id}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>`;
      } else if (status === 'approved') {
        approveRejectBtns = `
          <button class="icon-btn btn-reject" title="Revoke / Reject" data-action="reject" data-id="${t.id}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>`;
      } else if (status === 'rejected') {
        approveRejectBtns = `
          <button class="icon-btn btn-approve" title="Approve" data-action="approve" data-id="${t.id}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
          </button>`;
      }

      return `<tr>
        <td>
          <div class="person-cell">
            <span class="person-avatar"${avatarStyle}>${avatarInner}</span>
            <div>
              <div class="person-name">${t.full_name}</div>
              <div class="person-sub">ID: ${t.id}</div>
            </div>
          </div>
        </td>
        <td>${subjectBadgesHtml(t.subject)}</td>
        <td>${langBadge(t.language)}</td>
        <td>${t.email || '-'}</td>
        <td>${t.nic || '-'}</td>
        <td>${t.phone || '-'}</td>
        <td>${t.username}</td>
        <td>${statusBadge(status)}</td>
        <td>${joined}</td>
        <td>
          <div class="action-cell">
            <button class="icon-btn btn-view" title="View" data-action="view" data-id="${t.id}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button class="icon-btn btn-slot" title="Add Availability Slot" data-action="addslot" data-id="${t.id}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M12 14v4M10 16h4"/></svg>
            </button>
            ${approveRejectBtns}
            <button class="icon-btn btn-edit" title="Edit" data-action="edit" data-id="${t.id}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </button>
            <button class="icon-btn btn-delete" title="Delete" data-action="delete" data-id="${t.id}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z"/></svg>
            </button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  async function loadTeachers() {
    const tbody = document.getElementById('teachersBody');
    tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state">Loading...</div></td></tr>`;

    try {
      const res = await fetch('get-teachers.php');
      const data = await res.json();

      if (data.success) {
        allTeachers = data.data;
        applyFilters();
        updateTeachersBadge();
      } else {
        tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state">Error: ${data.message || 'Data load unuwe na'}</div></td></tr>`;
      }
    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state">Server connect unuwe na. get-teachers.php check karanna.</div></td></tr>`;
    }
  }

  document.getElementById('searchBox').addEventListener('input', applyFilters);

  document.getElementById('filterPills').addEventListener('click', (e) => {
    const btn = e.target.closest('.filter-pill');
    if (!btn) return;
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    applyFilters();
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
  function clearErrors(prefix) {
    document.querySelectorAll(`[id^="${prefix}"]`).forEach(el => { el.classList.remove('show'); el.textContent = ''; });
  }

  /* ===== Add Teacher Modal ===== */
  const addModalOverlay = document.getElementById('addModalOverlay');
  const addForm = document.getElementById('addTeacherForm');
  const saveBtn = document.getElementById('saveTeacherBtn');
  const tLanguageSelect = document.getElementById('tLanguage');
  const tLangPreview = document.getElementById('tLangPreview');

  tLanguageSelect.addEventListener('change', () => updateLangPreview(tLanguageSelect, tLangPreview));

  function openAddModal() {
    updateLangPreview(tLanguageSelect, tLangPreview);
    // clear previous selections
    const tSelect = document.getElementById('tSubject');
    Array.from(tSelect.options).forEach(o => o.selected = false);
    addModalOverlay.classList.add('show');
  }
  function closeAddModal() {
    addModalOverlay.classList.remove('show');
    addForm.reset();
    clearErrors('err_');
  }

  document.getElementById('openAddModalBtn').addEventListener('click', openAddModal);
  document.getElementById('closeAddModalBtn').addEventListener('click', closeAddModal);
  document.getElementById('cancelAddBtn').addEventListener('click', closeAddModal);
  addModalOverlay.addEventListener('click', (e) => { if (e.target === addModalOverlay) closeAddModal(); });

  addForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors('err_');

    const genderVal = document.getElementById('tGender').value;
    if (genderVal !== 'male' && genderVal !== 'female') {
      const errEl = document.getElementById('err_gender');
      errEl.textContent = '❌ Gender එක select කරන්න.';
      errEl.classList.add('show');
      return;
    }

    const nicVal = document.getElementById('tNic').value.trim();
    if (!NIC_REGEX.test(nicVal)) {
      const errEl = document.getElementById('err_nic');
      errEl.textContent = '❌ වලංගු NIC අංකයක් ඇතුලත් කරන්න. (උදා: 991234567V හෝ 199912345678)';
      errEl.classList.add('show');
      return;
    }

    const phoneVal = document.getElementById('tPhone').value.trim();
    if (!PHONE_REGEX.test(phoneVal)) {
      const errEl = document.getElementById('err_phone');
      errEl.textContent = '❌ වලංගු දුරකථන අංකයක් ඇතුලත් කරන්න.';
      errEl.classList.add('show');
      return;
    }

    const subjectsVal = getSelectedSubjects(document.getElementById('tSubject'));
    if (!subjectsVal) {
      const errEl = document.getElementById('err_subject');
      errEl.textContent = '❌ අවම වශයෙන් Subject එකක් හෝ select කරන්න.';
      errEl.classList.add('show');
      return;
    }

    const formData = new FormData();
    formData.append('full_name', document.getElementById('tFullName').value.trim());
    formData.append('gender', genderVal);
    formData.append('subject', subjectsVal);
    formData.append('language', document.getElementById('tLanguage').value);
    formData.append('qualifications', document.getElementById('tQualifications').value.trim());
    formData.append('email', document.getElementById('tEmail').value.trim());
    formData.append('nic', nicVal);
    formData.append('phone', phoneVal);
    formData.append('username', document.getElementById('tUsername').value.trim());
    formData.append('password', document.getElementById('tPassword').value);
    const photoFile = document.getElementById('tPhoto').files[0];
    if (photoFile) formData.append('photo', photoFile);

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    try {
      const res = await fetch('add-teacher.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        showToast('Teacher add unuwa! ✅');
        closeAddModal();
        loadTeachers();
      } else {
        if (data.errors) {
          for (const field in data.errors) {
            const errEl = document.getElementById('err_' + field);
            if (errEl) { errEl.textContent = data.errors[field]; errEl.classList.add('show'); }
          }
        } else {
          showToast(data.message || 'Teacher add karanna baa una', 'error-toast');
        }
      }
    } catch (err) {
      console.error(err);
      showToast('Server connect unuwe na', 'error-toast');
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save Teacher';
    }
  });

  /* ===== Edit Teacher Modal ===== */
  const editModalOverlay = document.getElementById('editModalOverlay');
  const editForm = document.getElementById('editTeacherForm');
  const updateBtn = document.getElementById('updateTeacherBtn');
  const eLanguageSelect = document.getElementById('eLanguage');
  const eLangPreview = document.getElementById('eLangPreview');

  eLanguageSelect.addEventListener('change', () => updateLangPreview(eLanguageSelect, eLangPreview));

  function openEditModal(teacher) {
    document.getElementById('eTeacherId').value = teacher.id;
    document.getElementById('eFullName').value = teacher.full_name;
    document.getElementById('eGender').value = teacher.gender || '';
    
    // Multi subjects
    setSelectedSubjects(document.getElementById('eSubject'), teacher.subject || '');

    const langCode = normalizeCode(teacher.language);
    document.getElementById('eLanguage').value = LANGUAGE_MAP[langCode] ? langCode : 'en';
    document.getElementById('eQualifications').value = teacher.qualifications || '';
    document.getElementById('eEmail').value = teacher.email || '';
    document.getElementById('eNic').value = teacher.nic || '';
    document.getElementById('ePhone').value = teacher.phone || '';
    document.getElementById('eUsername').value = teacher.username;
    document.getElementById('ePassword').value = '';
    document.getElementById('ePhoto').value = '';

    updateLangPreview(eLanguageSelect, eLangPreview);

    const curPhoto = document.getElementById('eCurrentPhoto');
    const pUrl = photoUrl(teacher.photo);
    if (pUrl) { curPhoto.src = pUrl; curPhoto.style.display = 'block'; }
    else { curPhoto.style.display = 'none'; }

    clearErrors('edit_err_');
    editModalOverlay.classList.add('show');
  }
  function closeEditModal() {
    editModalOverlay.classList.remove('show');
    editForm.reset();
    document.getElementById('eCurrentPhoto').style.display = 'none';
    clearErrors('edit_err_');
  }

  document.getElementById('closeEditModalBtn').addEventListener('click', closeEditModal);
  document.getElementById('cancelEditBtn').addEventListener('click', closeEditModal);
  editModalOverlay.addEventListener('click', (e) => { if (e.target === editModalOverlay) closeEditModal(); });

  document.getElementById('ePhoto').addEventListener('change', function(){
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      const curPhoto = document.getElementById('eCurrentPhoto');
      curPhoto.src = e.target.result;
      curPhoto.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });

  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors('edit_err_');

    const genderVal = document.getElementById('eGender').value;
    if (genderVal !== 'male' && genderVal !== 'female') {
      const errEl = document.getElementById('edit_err_gender');
      errEl.textContent = '❌ Gender එක select කරන්න.';
      errEl.classList.add('show');
      return;
    }

    const nicVal = document.getElementById('eNic').value.trim();
    if (!NIC_REGEX.test(nicVal)) {
      const errEl = document.getElementById('edit_err_nic');
      errEl.textContent = '❌ වලංගු NIC අංකයක් ඇතුලත් කරන්න. (උදා: 991234567V හෝ 199912345678)';
      errEl.classList.add('show');
      return;
    }

    const phoneVal = document.getElementById('ePhone').value.trim();
    if (!PHONE_REGEX.test(phoneVal)) {
      const errEl = document.getElementById('edit_err_phone');
      errEl.textContent = '❌ වලංගු දුරකථන අංකයක් ඇතුලත් කරන්න.';
      errEl.classList.add('show');
      return;
    }

    const subjectsVal = getSelectedSubjects(document.getElementById('eSubject'));
    if (!subjectsVal) {
      const errEl = document.getElementById('edit_err_subject');
      errEl.textContent = '❌ අවම වශයෙන් Subject එකක් හෝ select කරන්න.';
      errEl.classList.add('show');
      return;
    }

    const formData = new FormData();
    formData.append('id', document.getElementById('eTeacherId').value);
    formData.append('full_name', document.getElementById('eFullName').value.trim());
    formData.append('gender', genderVal);
    formData.append('subject', subjectsVal);
    formData.append('language', document.getElementById('eLanguage').value);
    formData.append('qualifications', document.getElementById('eQualifications').value.trim());
    formData.append('email', document.getElementById('eEmail').value.trim());
    formData.append('nic', nicVal);
    formData.append('phone', phoneVal);
    formData.append('username', document.getElementById('eUsername').value.trim());
    formData.append('password', document.getElementById('ePassword').value);
    const photoFile = document.getElementById('ePhoto').files[0];
    if (photoFile) formData.append('photo', photoFile);

    updateBtn.disabled = true;
    updateBtn.textContent = 'Updating...';

    try {
      const res = await fetch('edit-teacher.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        showToast('Teacher update unuwa! ✅');
        closeEditModal();
        loadTeachers();
      } else {
        if (data.errors) {
          for (const field in data.errors) {
            const errEl = document.getElementById('edit_err_' + field);
            if (errEl) { errEl.textContent = data.errors[field]; errEl.classList.add('show'); }
          }
        } else {
          showToast(data.message || 'Update karanna baa una', 'error-toast');
        }
      }
    } catch (err) {
      console.error(err);
      showToast('Server connect unuwe na', 'error-toast');
    } finally {
      updateBtn.disabled = false;
      updateBtn.textContent = 'Update Teacher';
    }
  });

  /* ===== View Teacher Modal ===== */
  const viewModalOverlay = document.getElementById('viewModalOverlay');

  function openViewModal(teacher) {
    const initials = teacher.full_name.charAt(0).toUpperCase();
    const pUrl = photoUrl(teacher.photo);
    const status = teacher.status || 'pending';
    const joined = teacher.created_at ? new Date(teacher.created_at).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) : '-';

    const imgEl = document.getElementById('viewPhoto');
    const fallbackEl = document.getElementById('viewPhotoFallback');

    if (pUrl) {
      imgEl.src = pUrl;
      imgEl.alt = teacher.full_name;
      imgEl.style.display = 'block';
      fallbackEl.style.display = 'none';
      imgEl.onerror = () => {
        imgEl.style.display = 'none';
        fallbackEl.textContent = initials;
        fallbackEl.style.display = 'flex';
      };
    } else {
      imgEl.removeAttribute('src');
      imgEl.style.display = 'none';
      fallbackEl.textContent = initials;
      fallbackEl.style.display = 'flex';
    }

    document.getElementById('viewName').textContent = teacher.full_name;
    document.getElementById('viewMeta').textContent = `ID: ${teacher.id} · @${teacher.username}`;

    // Subjects (multiple)
    const subjEl = document.getElementById('viewSubject');
    if (teacher.subject && teacher.subject.trim()) {
      subjEl.innerHTML = subjectBadgesHtml(teacher.subject);
      subjEl.classList.remove('empty');
    } else {
      subjEl.textContent = 'Subject specify කරලා නෑ.';
      subjEl.classList.add('empty');
    }

    document.getElementById('viewLanguage').innerHTML = langBadge(teacher.language);

    const qEl = document.getElementById('viewQualifications');
    if (teacher.qualifications && teacher.qualifications.trim() !== '') {
      qEl.textContent = teacher.qualifications;
      qEl.classList.remove('empty');
    } else {
      qEl.textContent = 'Qualifications specify කරලා නෑ.';
      qEl.classList.add('empty');
    }

    document.getElementById('viewEmail').textContent = teacher.email || '-';

    const nicEl = document.getElementById('viewNic');
    if (teacher.nic && teacher.nic.trim() !== '') {
      nicEl.textContent = teacher.nic;
      nicEl.classList.remove('empty');
    } else {
      nicEl.textContent = 'NIC සදහන් කරලා නෑ.';
      nicEl.classList.add('empty');
    }

    const phoneEl = document.getElementById('viewPhone');
    if (teacher.phone && teacher.phone.trim() !== '') {
      phoneEl.textContent = teacher.phone;
      phoneEl.classList.remove('empty');
    } else {
      phoneEl.textContent = 'Phone number සදහන් කරලා නෑ.';
      phoneEl.classList.add('empty');
    }

    document.getElementById('viewUsername').textContent = teacher.username;
    document.getElementById('viewStatus').innerHTML = statusBadge(status);
    document.getElementById('viewJoined').textContent = joined;

    viewModalOverlay.classList.add('show');
  }
  function closeViewModal() { viewModalOverlay.classList.remove('show'); }

  document.getElementById('closeViewModalBtn').addEventListener('click', closeViewModal);
  document.getElementById('closeViewBtn2').addEventListener('click', closeViewModal);
  viewModalOverlay.addEventListener('click', (e) => { if (e.target === viewModalOverlay) closeViewModal(); });

  /* ===== Add Slot Modal (UPDATED) ===== */
  const addSlotModalOverlay = document.getElementById('addSlotModalOverlay');
  const addSlotForm = document.getElementById('addSlotForm');
  const saveSlotBtn = document.getElementById('saveSlotBtn');

  function openAddSlotModal(teacher) {
    document.getElementById('slotLecturerId').value = teacher.id;
    document.getElementById('slotLecturerName').value = teacher.full_name;
    document.getElementById('slotDateInput').value = '';
    document.getElementById('slotStartInput').value = '';
    document.getElementById('slotEndInput').value = '';
    document.getElementById('slotIsFree').checked = false;
    document.getElementById('slotDateInput').min = new Date().toISOString().split('T')[0];

    // ========== Populate Subject dropdown from teacher's subjects ==========
    const slotSubjectSelect = document.getElementById('slotSubject');
    const subjects = (teacher.subject || '').split(',').map(s => s.trim()).filter(Boolean);

    if (subjects.length === 0) {
      slotSubjectSelect.innerHTML = '<option value="">-- No subjects assigned --</option>';
    } else {
      slotSubjectSelect.innerHTML = '<option value="">-- Select Subject --</option>' +
        subjects.map(s => `<option value="${s}">${s}</option>`).join('');
    }

    clearErrors('slot_err_');
    addSlotModalOverlay.classList.add('show');
  }

  function closeAddSlotModal() {
    addSlotModalOverlay.classList.remove('show');
    addSlotForm.reset();
    clearErrors('slot_err_');
  }

  document.getElementById('closeAddSlotModalBtn').addEventListener('click', closeAddSlotModal);
  document.getElementById('cancelAddSlotBtn').addEventListener('click', closeAddSlotModal);
  addSlotModalOverlay.addEventListener('click', (e) => { if (e.target === addSlotModalOverlay) closeAddSlotModal(); });

  addSlotForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors('slot_err_');

    const lecturerId = document.getElementById('slotLecturerId').value;
    const subject    = document.getElementById('slotSubject').value.trim();
    const date       = document.getElementById('slotDateInput').value;
    const start      = document.getElementById('slotStartInput').value;
    const end        = document.getElementById('slotEndInput').value;
    const isFree     = document.getElementById('slotIsFree').checked ? 1 : 0;

    // Validation
    if (!subject) {
      const el = document.getElementById('slot_err_subject');
      el.textContent = 'Subject එකක් තෝරන්න';
      el.classList.add('show');
      return;
    }
    if (!date) {
      const el = document.getElementById('slot_err_date');
      el.textContent = 'Date එක ඕන';
      el.classList.add('show');
      return;
    }
    if (!start) {
      const el = document.getElementById('slot_err_start');
      el.textContent = 'Start time එක ඕන';
      el.classList.add('show');
      return;
    }
    if (!end) {
      const el = document.getElementById('slot_err_end');
      el.textContent = 'End time එක ඕන';
      el.classList.add('show');
      return;
    }
    if (start >= end) {
      const el = document.getElementById('slot_err_end');
      el.textContent = 'End time එක start time එකට පස්සේ වෙන්න ඕන';
      el.classList.add('show');
      return;
    }

    saveSlotBtn.disabled = true;
    saveSlotBtn.textContent = 'Adding...';

    try {
      const res = await fetch('admin_add_availability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          lecturer_id: lecturerId,
          subject: subject,        // ← subject එක යවනවා
          date: date,
          start: start,
          end: end,
          is_free: isFree
        })
      });
      const data = await res.json();

      if (data.success) {
        showToast(data.message || (isFree ? 'Free session slot එක add unuwa! 🎁' : 'Slot එක add unuwa! ✅'), 'success-toast');
        closeAddSlotModal();
        updateAvailabilityBadge();
      } else if (data.errors) {
        for (const field in data.errors) {
          const errEl = document.getElementById('slot_err_' + field);
          if (errEl) { errEl.textContent = data.errors[field]; errEl.classList.add('show'); }
        }
      } else {
        showToast(data.message || 'Slot add karanna baa una', 'error-toast');
      }
    } catch (err) {
      console.error(err);
      showToast('Server connect unuwe na', 'error-toast');
    } finally {
      saveSlotBtn.disabled = false;
      saveSlotBtn.textContent = 'Add Slot';
    }
  });

  /* ===== Delete Modal ===== */
  const deleteModalOverlay = document.getElementById('deleteModalOverlay');
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

  function openDeleteModal(teacher) {
    pendingDeleteId = teacher.id;
    document.getElementById('deleteTeacherName').textContent = teacher.full_name;
    deleteModalOverlay.classList.add('show');
  }
  function closeDeleteModal() {
    deleteModalOverlay.classList.remove('show');
    pendingDeleteId = null;
  }

  document.getElementById('closeDeleteModalBtn').addEventListener('click', closeDeleteModal);
  document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
  deleteModalOverlay.addEventListener('click', (e) => { if (e.target === deleteModalOverlay) closeDeleteModal(); });

  confirmDeleteBtn.addEventListener('click', async () => {
    if (!pendingDeleteId) return;
    confirmDeleteBtn.disabled = true;
    confirmDeleteBtn.textContent = 'Deleting...';

    try {
      const res = await fetch('delete-teacher.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: pendingDeleteId })
      });
      const data = await res.json();

      if (data.success) {
        showToast('Teacher delete unuwa 🗑️');
        closeDeleteModal();
        loadTeachers();
      } else {
        showToast(data.message || 'Delete karanna baa una', 'error-toast');
      }
    } catch (err) {
      console.error(err);
      showToast('Server connect unuwe na', 'error-toast');
    } finally {
      confirmDeleteBtn.disabled = false;
      confirmDeleteBtn.textContent = 'Delete';
    }
  });

  /* ===== Approve / Reject ===== */
  async function updateStatus(id, status) {
    try {
      const res = await fetch('update-teacher-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
      });
      const data = await res.json();

      if (data.success) {
        if (status === 'approved') {
          if (data.mail_sent) {
            showToast('Teacher approve unuwa saha email ekak yawuwa ✅', 'success-toast');
          } else {
            showToast(
              'Teacher approve unuwa, eth EMAIL EKA YAWANNA BAA UNA: ' + (data.mail_error || 'unknown error'),
              'warning-toast'
            );
          }
        } else {
          showToast('Teacher reject unuwa');
        }
        loadTeachers();
      } else {
        showToast(data.message || 'Status update karanna baa una', 'error-toast');
      }
    } catch (err) {
      console.error(err);
      showToast('Server connect unuwe na', 'error-toast');
    }
  }

  /* ===== Table action clicks ===== */
  document.getElementById('teachersBody').addEventListener('click', (e) => {
    const btn = e.target.closest('.icon-btn');
    if (!btn) return;

    const action = btn.dataset.action;
    const id = parseInt(btn.dataset.id, 10);
    const teacher = allTeachers.find(t => t.id === id);
    if (!teacher) return;

    if (action === 'view') openViewModal(teacher);
    else if (action === 'addslot') openAddSlotModal(teacher);
    else if (action === 'edit') openEditModal(teacher);
    else if (action === 'delete') openDeleteModal(teacher);
    else if (action === 'approve') updateStatus(id, 'approved');
    else if (action === 'reject') updateStatus(id, 'rejected');
  });

  // Init
  document.getElementById('todayDate').textContent = new Date().toLocaleDateString('en-GB', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  });

  populateLanguageSelects();
  loadSubjects();
  loadTeachers();
  updateBookingsBadge();
  updateStudentsBadge();
  updateActivationsBadge();
  updateAvailabilityBadge();
  updateSupportBadge();

  setInterval(updateBookingsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateStudentsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateActivationsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateAvailabilityBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateSupportBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(loadTeachers, BADGE_POLL_INTERVAL_MS);
})();
</script>
</body>
</html>