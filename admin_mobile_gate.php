<?php
session_start();


if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
  
}

require_once 'db.php';

if (!isset($conn) || $conn === null) {
    die("Database connection failed.");
}


$logs = [];
$sql = "SELECT id, mobile, ip_address, user_agent, created_at 
        FROM mobile_gate_logs 
        ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

$totalCount = count($logs);
$conn->close();
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mobile Gate Logs - Sipway Admin</title>
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

  /* ===== SIDEBAR (same as dashboard) ===== */
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

  .sidebar-backdrop{
    display:none;
    position:fixed; inset:0;
    background:rgba(15,42,74,0.4);
    z-index:45;
  }
  .sidebar-backdrop.show{ display:block; }

  .menu-toggle{
    display:none;
    background:none;
    border:none;
    cursor:pointer;
    color:var(--navy);
    padding:6px;
  }

  .main{ margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
  .topbar{
    height:68px; display:flex; align-items:center; justify-content:space-between;
    padding:0 28px; background:rgba(255,255,255,0.9);
    backdrop-filter:saturate(180%) blur(10px);
    border-bottom:1px solid var(--line-soft); position:sticky; top:0; z-index:30;
    gap:16px;
  }
  .topbar-title h2{ margin:0; font-size:18px; font-weight:800; color:var(--navy); }
  .topbar-title p{ margin:2px 0 0; font-size:12.5px; color:var(--muted); font-weight:500; }

  .content{ padding:26px 28px 60px; flex:1; }

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

  .search-box{
    padding:9px 14px; border:1px solid var(--line); border-radius:8px;
    font-size:13px; background:var(--bg); min-width:220px; color:var(--text);
    font-family:inherit;
  }
  .search-box:focus{ outline:none; border-color:var(--coral); background:#fff; }

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

  .mobile-badge{
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 12px; border-radius:8px;
    background:var(--navy-soft); color:var(--navy);
    font-weight:700; font-size:14px; letter-spacing:0.5px;
  }
  .date-cell{ font-weight:600; color:var(--text); }
  .time-cell{ font-size:12px; color:var(--muted); margin-top:2px; }
  .ip-cell{ font-family:monospace; font-size:12.5px; color:var(--muted); }
  .ua-cell{
    max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    font-size:12px; color:var(--muted-2);
  }
  .empty-state{ padding:50px 20px; text-align:center; color:var(--muted-2); font-size:14px; }

  .stat-pill{
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 14px; border-radius:999px;
    background:var(--coral-soft); color:var(--coral-dark);
    font-size:13px; font-weight:700;
  }

  @media (max-width:880px){
    :root{ --sidebar-w:230px; }
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,0.3); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
  }
  @media (max-width:560px){
    .topbar{ padding:0 16px; }
    .content{ padding:18px 16px 40px; }
    .panel-head{ flex-direction:column; align-items:stretch; }
    .search-box{ width:100%; }
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
    <a class="nav-item active" href="admin_mobile_gate.php">
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

<div class="main">
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:14px;">
      <button class="menu-toggle" id="menuToggle">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title">
        <h2>Mobile Gate Logs</h2>
        <p>Dashboard එකට ඇතුළු වීමට mobile number දුන් අයගේ list එක</p>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>All Mobile Numbers</h3>
          <p>Latest entries first (date order)</p>
        </div>
        <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
          <span class="stat-pill">📱 Total: <?php echo $totalCount; ?></span>
          <input type="text" class="search-box" id="searchBox" placeholder="Search mobile number...">
        </div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Mobile Number</th>
              <th>Date & Time</th>
              <th>IP Address</th>
              <th>Device / Browser</th>
            </tr>
          </thead>
          <tbody id="logsBody">
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state">තවම mobile numbers කිසිවක් save වී නැත.</div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $index => $log): ?>
                <?php
                  $dt = new DateTime($log['created_at']);
                  $dateStr = $dt->format('d M Y');
                  $timeStr = $dt->format('h:i A');
                  $uaShort = $log['user_agent'] ? substr($log['user_agent'], 0, 80) . (strlen($log['user_agent']) > 80 ? '...' : '') : '—';
                ?>
                <tr data-mobile="<?php echo htmlspecialchars($log['mobile']); ?>">
                  <td><?php echo $index + 1; ?></td>
                  <td>
                    <span class="mobile-badge">
                      📱 <?php echo htmlspecialchars($log['mobile']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="date-cell"><?php echo $dateStr; ?></div>
                    <div class="time-cell"><?php echo $timeStr; ?></div>
                  </td>
                  <td class="ip-cell"><?php echo htmlspecialchars($log['ip_address'] ?: '—'); ?></td>
                  <td class="ua-cell" title="<?php echo htmlspecialchars($log['user_agent'] ?: ''); ?>">
                    <?php echo htmlspecialchars($uaShort); ?>
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

<script>
  document.getElementById('searchBox').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#logsBody tr[data-mobile]').forEach(row => {
      const mobile = row.getAttribute('data-mobile') || '';
      row.style.display = mobile.includes(q) ? '' : 'none';
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
</script>
</body>
</html>