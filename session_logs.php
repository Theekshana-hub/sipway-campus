<?php
session_start();
if (!isset($_SESSION['lecturer_id'])) {
    header("Location: lecturer-login.php");
    exit();
}
require_once 'db.php';
$lecturerId   = (int)$_SESSION['lecturer_id'];
$lecturerName = $_SESSION['lecturer_name'] ?? 'Lecturer';
$lecturerSubject = $_SESSION['lecturer_subject'] ?? '';
$lecturerPhoto = null;

$stmt0 = $conn->prepare("SELECT full_name, subject, photo FROM lecturers WHERE id = ? LIMIT 1");
$stmt0->bind_param('i', $lecturerId);
$stmt0->execute();
$stmt0->bind_result($dbFullName, $dbSubject, $dbPhoto);
if ($stmt0->fetch()) {
    $lecturerName    = $dbFullName;
    $lecturerSubject = $dbSubject;
    $lecturerPhoto   = $dbPhoto;
}
$stmt0->close();
$firstName = htmlspecialchars(explode(' ', trim($lecturerName))[0]);
$photoUrl  = $lecturerPhoto ? 'uploads/lecturers/' . htmlspecialchars($lecturerPhoto) : '';


$logs = [];
$stmt = $conn->prepare("
    SELECT sl.id, sl.student_id, sl.booking_id, sl.activated_package_id,
           sl.session_date, sl.session_time, sl.source, sl.logged_at
    FROM session_logs sl
    WHERE sl.lecturer_id = ?
    ORDER BY sl.session_date DESC, sl.session_time DESC, sl.logged_at DESC
");
$stmt->bind_param('i', $lecturerId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();


function firstExistingKey(array $row, array $keys, $default = '') {
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}
$studentIds = array_values(array_unique(array_filter(array_column($logs, 'student_id'))));
$studentsById = [];
if (!empty($studentIds)) {
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $types = str_repeat('i', count($studentIds));
    $stmt2 = $conn->prepare("SELECT * FROM students WHERE id IN ($placeholders)");
    $stmt2->bind_param($types, ...$studentIds);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($srow = $res2->fetch_assoc()) {
        $studentsById[$srow['id']] = $srow;
    }
    $stmt2->close();
}


$apIds = array_values(array_unique(array_filter(array_column($logs, 'activated_package_id'))));
$packagesByApId = [];
if (!empty($apIds)) {
    $placeholders = implode(',', array_fill(0, count($apIds), '?'));
    $types = str_repeat('i', count($apIds));
    $stmt3 = $conn->prepare("SELECT * FROM activated_packages WHERE id IN ($placeholders)");
    $stmt3->bind_param($types, ...$apIds);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    $needPackageJoin = [];
    while ($aprow = $res3->fetch_assoc()) {
        $pname = firstExistingKey($aprow, ['package_name', 'name'], '');
        if ($pname === '' && isset($aprow['package_id'])) {
            $needPackageJoin[$aprow['id']] = $aprow['package_id'];
        }
        $packagesByApId[$aprow['id']] = $pname;
    }
    $stmt3->close();

    if (!empty($needPackageJoin)) {
        $pkgIds = array_values(array_unique($needPackageJoin));
        $ph = implode(',', array_fill(0, count($pkgIds), '?'));
        $ty = str_repeat('i', count($pkgIds));
        $stmt4 = $conn->prepare("SELECT * FROM packages WHERE id IN ($ph)");
        $stmt4->bind_param($ty, ...$pkgIds);
        $stmt4->execute();
        $res4 = $stmt4->get_result();
        $pkgNameById = [];
        while ($pkrow = $res4->fetch_assoc()) {
            $pkgNameById[$pkrow['id']] = firstExistingKey($pkrow, ['name', 'title', 'package_name'], 'Package');
        }
        $stmt4->close();
        foreach ($needPackageJoin as $apId => $pkgId) {
            $packagesByApId[$apId] = $pkgNameById[$pkgId] ?? 'Package';
        }
    }
}


$sessions = [];
$todayCount = 0;
$today = date('Y-m-d');
foreach ($logs as $log) {
    $srow = $studentsById[$log['student_id']] ?? [];
    $studentName = firstExistingKey($srow, ['full_name', 'name', 'student_name'], 'Unknown Student');
    if ($studentName === 'Unknown Student' && isset($srow['first_name'])) {
        $studentName = trim(($srow['first_name'] ?? '') . ' ' . ($srow['last_name'] ?? ''));
    }
    $studentEmail = firstExistingKey($srow, ['email', 'student_email'], '');
    $packageName  = $packagesByApId[$log['activated_package_id']] ?? '';
    if ($log['session_date'] === $today) {
        $todayCount++;
    }
    $groupKey = 'dt_' . $log['session_date'] . '_' . $log['session_time'];
    if (!isset($sessions[$groupKey])) {
        $sessions[$groupKey] = [
            'date'          => $log['session_date'],
            'time'          => $log['session_time'],
            'students'      => [],
            'last_logged_at'=> $log['logged_at'],
        ];
    }
    if ($log['logged_at'] > $sessions[$groupKey]['last_logged_at']) {
        $sessions[$groupKey]['last_logged_at'] = $log['logged_at'];
    }
    $studentKey = $log['student_id'] . '|' . $studentName;
    if (!isset($sessions[$groupKey]['students'][$studentKey])) {
        $sessions[$groupKey]['students'][$studentKey] = [
            'student_name'  => $studentName ?: 'Unknown Student',
            'student_email' => $studentEmail,
            'package_name'  => $packageName ?: '-',
            'source'        => $log['source'],
            'logged_at'     => $log['logged_at'],
        ];
    }
}
uasort($sessions, function ($a, $b) {
    return strcmp($b['last_logged_at'], $a['last_logged_at']);
});
$totalLogs      = count($logs);
$uniqueStudents = count($studentIds);
$totalSessions  = count($sessions);
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Logs - Sipway Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #f4f0ff;
  --bg-soft: #faf8ff;
  --card: #ffffff;
  --text: #1e1b4b;
  --muted: #6b7280;
  --muted-2: #9ca3af;
  --line: #e9e5f5;
  --line-soft: #f3f0fa;
  --purple: #7c3aed;
  --purple-soft: #f3e8ff;
  --pink: #ec4899;
  --success: #10b981;
  --success-soft: #d1fae5;
  --amber: #f59e0b;
  --amber-soft: #fef3c7;
  --danger: #ef4444;
  --danger-soft: #fef2f2;
  --radius-lg: 20px;
  --radius-md: 14px;
  --radius-sm: 10px;
  --shadow-card: 0 8px 30px -8px rgba(124, 58, 237, 0.08);
  --shadow-hover: 0 16px 40px -12px rgba(124, 58, 237, 0.14);
  --ease: cubic-bezier(.4,0,.2,1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', 'Noto Sans Sinhala', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  -webkit-font-smoothing: antialiased;
  min-height: 100vh;
}

/* ===== Layout ===== */
.app { display: flex; min-height: 100vh; }

.sidebar {
  width: 250px;
  flex-shrink: 0;
  background: linear-gradient(180deg, #0f0c29 0%, #1a1440 50%, #1e1b4b 100%);
  padding: 22px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  z-index: 60;
  transition: transform .3s var(--ease);
}

.logo {
  display: flex;
  align-items: center;
  gap: 11px;
  font-weight: 800;
  color: #fff;
  font-size: 15px;
  padding: 0 8px 22px;
  letter-spacing: -0.3px;
}

.logo-mark {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 800;
  font-size: 13px;
  box-shadow: 0 4px 14px -3px rgba(168, 85, 247, 0.5);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  color: #c4b5fd;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  font-family: inherit;
  text-decoration: none;
  transition: all .2s var(--ease);
}

.nav-item svg { width: 18px; height: 18px; flex-shrink: 0; opacity: 0.9; }

.nav-item:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
}

.nav-item.active {
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  box-shadow: 0 8px 24px -6px rgba(168, 85, 247, 0.5);
}

.nav-item.active svg { opacity: 1; }

.sidebar-bottom { margin-top: auto; padding-top: 14px; }

.logout-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  color: #f9a8d4;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
  transition: all .2s;
}

.logout-link:hover { background: rgba(236, 72, 153, 0.15); color: #fff; }
.logout-link svg { width: 18px; height: 18px; }

.sidebar-overlay { display: none; }

.sidebar-close {
  display: none;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.1);
  color: #fff;
  margin-left: auto;
  cursor: pointer;
  transition: background .15s;
}
.sidebar-close:hover { background: rgba(255,255,255,0.2); }
.sidebar-close svg { width: 16px; height: 16px; }

.sidebar-top-row { display: flex; align-items: center; }

.main {
  flex: 1;
  min-width: 0;
  background: linear-gradient(160deg, #f4f0ff 0%, #faf8ff 40%, #f0eaff 100%);
}

.topbar {
  height: 66px;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 0 26px;
  background: #0b0a1f;
  position: sticky;
  top: 0;
  z-index: 40;
}

.hamburger-btn {
  display: none;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.08);
  color: #e0e7ff;
  cursor: pointer;
  flex-shrink: 0;
  transition: background .15s;
}
.hamburger-btn:hover { background: rgba(255,255,255,0.14); }
.hamburger-btn svg { width: 20px; height: 20px; }

.topbar-title {
  font-size: 16.5px;
  font-weight: 800;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: -0.3px;
}

.topbar-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }

.lecturer-chip {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 14px 6px 6px;
  border-radius: 999px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.1);
}

.lecturer-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 12px;
  overflow: hidden;
  flex-shrink: 0;
}
.lecturer-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

.lecturer-chip .name { font-size: 13px; font-weight: 700; color: #fff; }
.lecturer-chip .subj { font-size: 11px; color: #94a3b8; font-weight: 600; }

.content {
  max-width: 1100px;
  margin: 0 auto;
  padding: 30px 26px 70px;
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  color: var(--text);
  margin: 0 0 5px;
  letter-spacing: -0.4px;
}

.page-sub {
  font-size: 14px;
  color: var(--muted);
  margin: 0 0 26px;
  line-height: 1.55;
}

/* ===== Stat cards ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}
@media (max-width: 820px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr; }
}

.stat-card {
  background: var(--card);
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  padding: 20px;
  transition: transform .2s, box-shadow .2s;
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-hover);
}

.stat-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}
.stat-icon svg { width: 20px; height: 20px; }
.stat-icon.blue { background: var(--purple-soft); color: var(--purple); }
.stat-icon.green { background: var(--success-soft); color: var(--success); }
.stat-icon.orange { background: #fce7f3; color: #be185d; }
.stat-icon.amber { background: var(--amber-soft); color: var(--amber); }

.stat-value {
  font-size: 27px;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.5px;
}
.stat-label {
  font-size: 12.5px;
  color: var(--muted);
  font-weight: 600;
  margin-top: 3px;
}

/* ===== Panel head + search ===== */
.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}
.panel-head h2 {
  font-size: 16px;
  font-weight: 800;
  color: var(--text);
  margin: 0;
}

.search-box {
  position: relative;
  width: 280px;
  max-width: 100%;
}
.search-box input {
  width: 100%;
  padding: 11px 14px 11px 40px;
  border: 1.5px solid var(--line);
  border-radius: 12px;
  font-size: 13.5px;
  font-family: inherit;
  background: var(--card);
  transition: border-color .15s, box-shadow .15s;
}
.search-box input:focus {
  outline: none;
  border-color: #a855f7;
  box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.14);
}
.search-box svg {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: var(--muted-2);
}

/* ===== Session cards ===== */
.sessions-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.session-card {
  background: var(--card);
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  overflow: hidden;
  transition: box-shadow .25s;
}
.session-card:hover {
  box-shadow: var(--shadow-hover);
}

.session-card-head {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 22px;
  background: linear-gradient(135deg, #f3e8ff 0%, #fdf2f8 100%);
  border-bottom: 1px solid var(--line-soft);
  flex-wrap: wrap;
}

.session-date-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 54px;
  height: 54px;
  border-radius: 14px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 4px 14px -3px rgba(168, 85, 247, 0.4);
}
.session-date-badge .d { font-size: 17px; font-weight: 800; line-height: 1; }
.session-date-badge .m { font-size: 10px; font-weight: 700; text-transform: uppercase; margin-top: 2px; letter-spacing: .04em; }

.session-meta { flex: 1; min-width: 160px; }
.session-meta .pkg { font-size: 15px; font-weight: 800; color: var(--text); }
.session-meta .time { font-size: 13px; color: var(--muted); font-weight: 600; margin-top: 3px; }

.session-count-badge {
  font-size: 12px;
  font-weight: 800;
  padding: 7px 14px;
  border-radius: 999px;
  background: #fce7f3;
  color: #be185d;
  white-space: nowrap;
}

.table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

table { width: 100%; border-collapse: collapse; }

thead th {
  text-align: left;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--muted-2);
  font-weight: 800;
  padding: 12px 22px;
  border-bottom: 2px solid var(--line-soft);
  white-space: nowrap;
  background: var(--bg-soft);
}

tbody td {
  padding: 14px 22px;
  border-bottom: 1px solid var(--line-soft);
  font-size: 13.5px;
  vertical-align: middle;
}

tbody tr:hover { background: var(--purple-soft); }
tbody tr:last-child td { border-bottom: none; }

.student-cell { display: flex; align-items: center; gap: 12px; }

.student-avatar {
  width: 36px;
  height: 36px;
  border-radius: 11px;
  background: var(--purple-soft);
  color: var(--purple);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 13px;
  flex-shrink: 0;
}

.student-name { font-weight: 700; color: var(--text); white-space: nowrap; }
.student-email { font-size: 11.5px; color: var(--muted-2); margin-top: 2px; }

.pkg-badge {
  font-size: 11px;
  font-weight: 800;
  padding: 5px 12px;
  border-radius: 999px;
  background: #fce7f3;
  color: #be185d;
  white-space: nowrap;
}

.source-badge {
  font-size: 11px;
  font-weight: 800;
  padding: 5px 12px;
  border-radius: 999px;
  background: var(--purple-soft);
  color: var(--purple);
  text-transform: capitalize;
  white-space: nowrap;
}

.logged-at {
  color: var(--muted);
  font-size: 12.5px;
  white-space: nowrap;
}

.empty-msg {
  text-align: center;
  padding: 56px 16px;
  color: var(--muted-2);
  font-size: 14px;
  background: var(--card);
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
}
.empty-msg svg {
  width: 40px;
  height: 40px;
  margin-bottom: 12px;
  color: #c4b5fd;
}

/* ===== Mobile ===== */
@media (max-width: 860px) {
  .hamburger-btn { display: flex; }
  .sidebar-close { display: flex; }

  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 270px;
    max-width: 82vw;
    transform: translateX(-100%);
    box-shadow: 0 0 0 rgba(0,0,0,0);
  }
  .sidebar.open {
    transform: translateX(0);
    box-shadow: 16px 0 40px -12px rgba(15, 12, 41, 0.4);
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15, 12, 41, 0.55);
    backdrop-filter: blur(3px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .22s;
    z-index: 55;
  }
  .sidebar-overlay.open {
    opacity: 1;
    pointer-events: auto;
  }

  .topbar { padding: 0 14px; gap: 10px; }
  .topbar-title { font-size: 15px; }
  .lecturer-chip .subj { display: none; }

  .content { padding: 20px 14px 52px; }
  .page-title { font-size: 21px; }
  .page-sub { font-size: 13.5px; margin-bottom: 20px; }

  .stat-card { padding: 16px; }
  .stat-value { font-size: 23px; }

  .panel-head { align-items: stretch; }
  .search-box { width: 100%; }

  .session-card-head { padding: 16px; }
  .session-meta { min-width: 120px; }

  thead th, tbody td { padding: 12px 14px; }
}

@media (max-width: 480px) {
  .session-count-badge { order: 1; width: 100%; text-align: center; }
  .session-date-badge { width: 48px; height: 48px; }
}
</style>
</head>
<body>
<div class="app">
  <!-- Sidebar overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-top-row">
      <div class="logo"><span class="logo-mark">SC</span>Sipway English Accademy</div>
      <button class="sidebar-close" id="sidebarCloseBtn" aria-label="Close menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <a href="lecturer-dashboard.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
      Availability
    </a>

    <button class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
      Session Logs
    </button>

    <a href="lecturer_profile.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
      Profile
    </a>

    <div class="sidebar-bottom">
      <a href="lecturer_logout.php" class="logout-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        Logout
      </a>
    </div>
  </div>

  <!-- Main -->
  <div class="main">
    <div class="topbar">
      <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title">Session Logs</div>
      <div class="topbar-right">
        <div class="lecturer-chip">
          <span class="lecturer-avatar" id="topbarAvatar">
            <?php if ($photoUrl): ?>
              <img src="<?php echo $photoUrl; ?>" alt="<?php echo $firstName; ?>" onerror="this.parentElement.textContent='<?php echo strtoupper(substr($firstName,0,1)); ?>';">
            <?php else: ?>
              <?php echo strtoupper(substr($firstName,0,1)); ?>
            <?php endif; ?>
          </span>
          <div>
            <div class="name"><?php echo $firstName; ?></div>
            <?php if ($lecturerSubject): ?>
              <div class="subj"><?php echo htmlspecialchars($lecturerSubject); ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <h1 class="page-title">Session Logs</h1>
      <p class="page-sub">ඔබේ session එකින් එකට join උනු students ලා වෙන වෙනම, session එකට එකයි කියලා මෙතන පේනවා.</p>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
          </div>
          <div class="stat-value"><?php echo $totalSessions; ?></div>
          <div class="stat-label">Total Sessions</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          </div>
          <div class="stat-value"><?php echo $uniqueStudents; ?></div>
          <div class="stat-label">Unique Students</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          </div>
          <div class="stat-value"><?php echo $todayCount; ?></div>
          <div class="stat-label">Today's Joins</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
          </div>
          <div class="stat-value"><?php echo $totalLogs; ?></div>
          <div class="stat-label">Total Joins Logged</div>
        </div>
      </div>

      <div class="panel-head">
        <h2>📅 Sessions</h2>
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          <input type="text" id="searchInput" placeholder="Search student name...">
        </div>
      </div>

      <?php if (empty($sessions)): ?>
        <div class="empty-msg">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
          <div>තවම කිසිම student කෙනෙක් session එකකට log වෙලා නැහැ.</div>
        </div>
      <?php else: ?>
        <div class="sessions-list" id="sessionsList">
          <?php foreach ($sessions as $sessionKey => $session):
              $dateObj = date_create($session['date']);
              $dayNum  = $dateObj ? date_format($dateObj, 'd') : '--';
              $monShort= $dateObj ? strtoupper(date_format($dateObj, 'M')) : '';
              $studentList = array_values($session['students']);
              $distinctPkgs = array_values(array_unique(array_column($studentList, 'package_name')));
              $headerPkgLabel = count($distinctPkgs) === 1 ? $distinctPkgs[0] : 'Multiple packages';
          ?>
            <div class="session-card" data-session-key="<?php echo htmlspecialchars($sessionKey); ?>">
              <div class="session-card-head">
                <div class="session-date-badge">
                  <div class="d"><?php echo htmlspecialchars($dayNum); ?></div>
                  <div class="m"><?php echo htmlspecialchars($monShort); ?></div>
                </div>
                <div class="session-meta">
                  <div class="pkg"><?php echo htmlspecialchars($headerPkgLabel); ?></div>
                  <div class="time"><?php echo htmlspecialchars($session['date']); ?> &middot; <?php echo htmlspecialchars($session['time']); ?></div>
                </div>
                <div class="session-count-badge"><?php echo count($studentList); ?> student<?php echo count($studentList) === 1 ? '' : 's'; ?></div>
              </div>
              <div class="table-wrap">
                <table class="session-table">
                  <thead>
                    <tr>
                      <th>Student</th>
                      <th>Package</th>
                      <th>Source</th>
                      <th>Logged At</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($studentList as $stu): ?>
                      <tr data-search="<?php echo strtolower(htmlspecialchars($stu['student_name'])); ?>">
                        <td>
                          <div class="student-cell">
                            <div class="student-avatar"><?php echo strtoupper(substr($stu['student_name'], 0, 1)); ?></div>
                            <div>
                              <div class="student-name"><?php echo htmlspecialchars($stu['student_name']); ?></div>
                              <?php if ($stu['student_email']): ?>
                                <div class="student-email"><?php echo htmlspecialchars($stu['student_email']); ?></div>
                              <?php endif; ?>
                            </div>
                          </div>
                        </td>
                        <td><span class="pkg-badge"><?php echo htmlspecialchars($stu['package_name']); ?></span></td>
                        <td><span class="source-badge"><?php echo htmlspecialchars($stu['source']); ?></span></td>
                        <td class="logged-at"><?php echo htmlspecialchars($stu['logged_at']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="empty-msg" id="noResultsMsg" style="display:none; margin-top:16px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          <div>Search එකට match වෙන student කෙනෙක් නැහැ.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
/* ===== Mobile sidebar toggle ===== */
const sidebarEl = document.getElementById('sidebar');
const sidebarOverlayEl = document.getElementById('sidebarOverlay');
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

function openSidebar(){
  sidebarEl.classList.add('open');
  sidebarOverlayEl.classList.add('open');
}
function closeSidebar(){
  sidebarEl.classList.remove('open');
  sidebarOverlayEl.classList.remove('open');
}
hamburgerBtn.addEventListener('click', openSidebar);
sidebarCloseBtn.addEventListener('click', closeSidebar);
sidebarOverlayEl.addEventListener('click', closeSidebar);

/* ===== Search ===== */
const searchInput  = document.getElementById('searchInput');
const sessionCards = document.querySelectorAll('.session-card');
const noResultsMsg = document.getElementById('noResultsMsg');

if (searchInput) {
  searchInput.addEventListener('input', () => {
    const q = searchInput.value.trim().toLowerCase();
    let anySessionVisible = false;

    sessionCards.forEach(card => {
      const rows = card.querySelectorAll('tbody tr');
      let matchInThisSession = false;

      rows.forEach(tr => {
        const isMatch = tr.dataset.search.includes(q);
        tr.style.display = isMatch ? '' : 'none';
        if (isMatch) matchInThisSession = true;
      });

      card.style.display = matchInThisSession ? '' : 'none';
      if (matchInThisSession) anySessionVisible = true;
    });

    if (noResultsMsg) {
      noResultsMsg.style.display = anySessionVisible ? 'none' : '';
    }
  });
}
</script>
</body>
</html>