<?php

session_start();

$needsMobile = !isset($_SESSION['gate_mobile']) || empty($_SESSION['gate_mobile']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gate_mobile'])) {
    $mobile = preg_replace('/\D/', '', trim($_POST['gate_mobile']));
    
    if (preg_match('/^0\d{9}$/', $mobile)) {
        $_SESSION['gate_mobile'] = $mobile;
        $needsMobile = false;

       
        require_once 'db.php';   
        
        if (isset($conn) && $conn) {
            $ip  = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua  = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $conn->prepare("INSERT INTO mobile_gate_logs (mobile, ip_address, user_agent) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $mobile, $ip, $ua);
            $stmt->execute();
            $stmt->close();
        }
       

        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$reminderLockFile = __DIR__ . '/reminder_lock.txt';
$now = time();
$lastRun = file_exists($reminderLockFile) ? (int)file_get_contents($reminderLockFile) : 0;

if ($now - $lastRun >= 300) {  
    file_put_contents($reminderLockFile, $now);
    $phpPath    = 'C:\\wamp64\\bin\\php\\php8.3.14\\php.exe';
    $scriptPath = __DIR__ . '\\send-session-reminders.php';
    pclose(popen("start /B \"\" \"$phpPath\" \"$scriptPath\"", "r"));
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'db.php';

if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}

$isLoggedIn = isset($_SESSION['student_id']);

$studentId        = null;
$studentName      = '';
$studentLanguage  = 'en';
$studentPhoto     = null;
$firstName        = '';
$fullNameSafe     = '';
$mySessions       = [];
$loggedBookingIds = [];
$hasActivePackage = false;
$activePackageSubject = null;
$activePackageType    = null;   

if ($isLoggedIn) {
    $studentId   = $_SESSION['student_id'];
    $studentName = $_SESSION['student_name'] ?? '';

    $stmt = $conn->prepare("SELECT full_name, language, profile_photo FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $studentName     = $row['full_name'];
        $studentLanguage = $row['language'] ?: 'en';
        $studentPhoto    = $row['profile_photo'] ?: null;
    } else {
        session_destroy();
        session_start();
        $isLoggedIn = false;
        $studentId  = null;
    }
    $stmt->close();
}

if ($isLoggedIn) {
    $firstName    = htmlspecialchars(explode(' ', trim($studentName))[0]);
    $fullNameSafe = htmlspecialchars($studentName);
}
$studentNameJs     = json_encode($studentName);
$studentLanguageJs = $isLoggedIn ? json_encode($studentLanguage) : 'null';

$langMeta = [
    'en' => ['flag' => 'https://flagcdn.com/w40/gb.png', 'label' => 'English'],
    'de' => ['flag' => 'https://flagcdn.com/w40/de.png', 'label' => 'German'],
    'zh' => ['flag' => 'https://flagcdn.com/w40/cn.png', 'label' => 'Chinese'],
    'ja' => ['flag' => 'https://flagcdn.com/w40/jp.png', 'label' => 'Japanese'],
    'fr' => ['flag' => 'https://flagcdn.com/w40/fr.png', 'label' => 'French'],
    'hi' => ['flag' => 'https://flagcdn.com/w40/in.png', 'label' => 'Hindi'],
    'ru' => ['flag' => 'https://flagcdn.com/w40/ru.png', 'label' => 'Russian'],
    'ar' => ['flag' => 'https://flagcdn.com/w40/sa.png', 'label' => 'Arabic'],
    'ta' => ['flag' => 'https://flagcdn.com/w40/in.png', 'label' => 'Tamil'],
    'si' => ['flag' => 'https://flagcdn.com/w40/lk.png', 'label' => 'Sinhala'],
    'it' => ['flag' => 'https://flagcdn.com/w40/it.png', 'label' => 'Italian'],
];

$currentLang = strtolower($studentLanguage);
if (!isset($langMeta[$currentLang])) {
    $currentLang = 'en';
}
$langFlag  = $langMeta[$currentLang]['flag'];
$langLabel = $langMeta[$currentLang]['label'];

$photoUrl = null;
if ($isLoggedIn && $studentPhoto) {
    $photoPath = $studentPhoto;
    if (file_exists(__DIR__ . '/' . $photoPath)) {
        $photoUrl = htmlspecialchars($photoPath);
    }
}

if ($isLoggedIn) {
    $stmt2 = $conn->prepare("
        SELECT b.id, b.package_id, b.lecturer_id, l.full_name AS lecturer_name,
               b.session_date, b.session_time, b.meeting_link
        FROM bookings b
        LEFT JOIN lecturers l ON b.lecturer_id = l.id
        WHERE b.student_id = ? AND b.status = 'Accepted'
        ORDER BY b.session_date ASC, b.session_time ASC
    ");
    $stmt2->bind_param("i", $studentId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) {
        $mySessions[] = $r;
    }
    $stmt2->close();

  
    $stmt3 = $conn->prepare("SELECT booking_id FROM session_logs WHERE student_id = ? AND booking_id IS NOT NULL");
    $stmt3->bind_param("i", $studentId);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    while ($r = $res3->fetch_assoc()) {
        $loggedBookingIds[] = (int)$r['booking_id'];
    }
    $stmt3->close();

   
$stmtPkg = $conn->prepare("
    SELECT ap.id, ap.package_name, ap.package_type, p.package_name AS pkg_name
    FROM activated_packages ap
    LEFT JOIN packages p ON p.id = ap.package_id
    WHERE ap.student_id = ? AND ap.status = 'active' AND ap.sessions_remaining > 0
    LIMIT 1
");
$stmtPkg->bind_param("i", $studentId);
$stmtPkg->execute();
$resPkg = $stmtPkg->get_result();
if ($rowPkg = $resPkg->fetch_assoc()) {
    $hasActivePackage = true;
    $activePackageSubject = $rowPkg['package_name'] ?: ($rowPkg['pkg_name'] ?? null);
    $activePackageType = strtolower(trim($rowPkg['package_type'] ?? 'individual'));
    if ($activePackageType !== 'group') {
        $activePackageType = 'individual';
    }
}
$stmtPkg->bind_param("i", $studentId);
    $stmtPkg->execute();
    $resPkg = $stmtPkg->get_result();
    if ($rowPkg = $resPkg->fetch_assoc()) {
        $hasActivePackage = true;
        $activePackageSubject = $rowPkg['package_name'] ?: ($rowPkg['pkg_name'] ?? null);
        $activePackageType = strtolower(trim($rowPkg['package_type'] ?? 'individual'));
        if ($activePackageType !== 'group') {
            $activePackageType = 'individual';
        }
    }
    $stmtPkg->close();
}
$activePackageSubjectJs = json_encode($activePackageSubject !== null && $activePackageSubject !== '' ? strtolower(trim($activePackageSubject)) : null);

$bookedSessionsForJs = array_map(function ($s) {
    return [
        'lecturer_id' => (int)$s['lecturer_id'],
        'date'        => $s['session_date'],
        'time'        => date('H:i', strtotime($s['session_time'])),
    ];
}, $mySessions);
$bookedSessionsJs = json_encode($bookedSessionsForJs);

$sessionsCompleted = 0;
$hoursLearned      = 0;
$achievements      = 0;

if ($isLoggedIn) {

    $stmtCompleted = $conn->prepare("SELECT COUNT(*) AS total FROM session_logs WHERE student_id = ?");
    $stmtCompleted->bind_param("i", $studentId);
    $stmtCompleted->execute();
    $resCompleted = $stmtCompleted->get_result();
    if ($row = $resCompleted->fetch_assoc()) {
        $sessionsCompleted = (int)$row['total'];
    }
    $stmtCompleted->close();

   
    $hoursLearned = $sessionsCompleted;

   
    if ($sessionsCompleted >= 1)  $achievements += 1;   
    if ($sessionsCompleted >= 5)  $achievements += 1;   
    if ($sessionsCompleted >= 10) $achievements += 1;   
    if ($sessionsCompleted >= 20) $achievements += 1;   
    if ($hasActivePackage)       $achievements += 1;    
}

$upcomingSessions = count($mySessions);

$registerVideo = null;

$regRes = $conn->query("
    SELECT id, title, source_type, video_path, video_url
    FROM register_guide_video
    WHERE status = 'active'
    ORDER BY id DESC
    LIMIT 1
");

if ($regRes && $regRow = $regRes->fetch_assoc()) {
    $registerVideo = [
        'title'       => $regRow['title'] ?: 'How to Register',
        'source_type' => $regRow['source_type'],
        'player_type' => 'file',
        'player_src'  => $regRow['video_path'] ?? '',
    ];

    if ($regRow['source_type'] === 'link' && !empty($regRow['video_url'])) {
        $url = trim($regRow['video_url']);

        if (preg_match(
            '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i',
            $url,
            $m
        )) {
            $registerVideo['player_type'] = 'youtube';
            $registerVideo['player_src']  = 'https://www.youtube.com/embed/' . $m[1];
        }
        
        elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            $registerVideo['player_type'] = 'vimeo';
            $registerVideo['player_src']  = 'https://player.vimeo.com/video/' . $m[1];
        }
        else {
            $registerVideo['player_type'] = 'direct';
            $registerVideo['player_src']  = $url;
        }
    }
}

$registerVideoJs = json_encode(
    $registerVideo,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);

$conn->close();
?>
<!DOCTYPE html>
<html lang="si" data-lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --sidebar-bg: #1e1b4b;
  --sidebar-bg-2: #2e2a5e;
  --sidebar-accent: linear-gradient(135deg, #a855f7, #ec4899);
  --sidebar-text: #ffffff;
  --sidebar-text-active: #ffffff;
  --topbar-bg: #0b0a1f;
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
  --coral: #e11d48;
  --success: #10b981;
  --success-soft: #d1fae5;
  --amber: #f59e0b;
  --amber-soft: #fef3c7;
  --blue: #3b82f6;
  --blue-soft: #dbeafe;
  --radius-lg: 20px;
  --radius-md: 14px;
  --radius-sm: 10px;
  --shadow-card: 0 8px 30px -8px rgba(124, 58, 237, 0.08);
  --shadow-hover: 0 16px 40px -12px rgba(124, 58, 237, 0.14);
  --ease: cubic-bezier(.4,0,.2,1);
}

.sidebar {
  width: 260px;
  flex-shrink: 0;
  background: linear-gradient(180deg, #2a2555 0%, #342f6a 50%, #3d3780 100%);
  padding: 22px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  position: sticky;
  top: 64px;
  align-self: flex-start;
  height: calc(100vh - 64px);
  overflow-y: auto;
  transition: transform .3s var(--ease);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  color: #ffffff;                 /* pure white */
  cursor: pointer;
  transition: all .2s var(--ease);
}

.nav-item svg {
  width: 19px;
  height: 19px;
  flex-shrink: 0;
  opacity: 0.95;
  color: #ffffff;
}

.nav-item:hover {
  background: rgba(255,255,255,0.12);
  color: #ffffff;
}

.nav-item.active {
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #ffffff;
  box-shadow: 0 8px 24px -6px rgba(168,85,247,0.5);
}

.nav-item.active svg {
  opacity: 1;
  color: #ffffff;
}

.nav-item .badge-new {
  margin-left: auto;
  font-size: 10px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 999px;
  background: linear-gradient(135deg, #a855f7, #6366f1);
  color: #fff;
  letter-spacing: 0.3px;
}

.nav-item.locked {
  position: relative;
}

.nav-item.locked .lock-badge {
  margin-left: auto;
  width: 15px;
  height: 15px;
  color: #c4b5fd;
  flex-shrink: 0;
}

.side-divider {
  height: 1px;
  background: rgba(255,255,255,0.15);
  margin: 14px 8px;
}

.side-illustration {
  margin-top: auto;
  padding: 16px 8px 8px;
  text-align: center;
}

.side-illustration img {
  width: 100%;
  max-width: 180px;
  height: auto;
  opacity: 0.9;
}

.side-help-card {
  margin-top: 12px;
  padding: 16px;
  border-radius: 16px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  text-align: center;
}

.side-help-card h4 {
  font-size: 13.5px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 4px;
}

.side-help-card p {
  font-size: 11.5px;
  color: #e0e7ff;
  margin-bottom: 12px;
}

.side-help-card a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 999px;
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.18);
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  transition: background .2s;
}

.side-help-card a:hover {
  background: rgba(255,255,255,0.20);
}
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Inter', 'Noto Sans Sinhala', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
  }
  a { color: inherit; text-decoration: none; }

  @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
  @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
  @keyframes scaleIn { from { opacity:0; transform:scale(0.94); } to { opacity:1; transform:scale(1); } }
  @keyframes pulseLive { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:0.4; transform:scale(0.85); } }
  @keyframes spin { to { transform: rotate(360deg); } }

  .animate-up { animation: fadeUp .5s var(--ease) both; }
  .delay-1 { animation-delay: .08s; }
  .delay-2 { animation-delay: .16s; }
  .delay-3 { animation-delay: .24s; }

  /* ========== TOPBAR ========== */
  .topbar {
    height: 64px;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 22px;
    background: var(--topbar-bg);
    position: sticky;
    top: 0;
    z-index: 50;
  }
  .burger {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    border-radius: 10px;
    color: #e0e7ff;
    flex-shrink: 0;
    transition: background .2s;
  }
  .burger:hover { background: rgba(255,255,255,0.08); }
  .burger svg { width: 22px; height: 22px; }

  .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    color: #fff;
    font-size: 15.5px;
    letter-spacing: -0.3px;
    flex-shrink: 0;
    white-space: nowrap;
  }
  .logo-mark {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    box-shadow: 0 4px 12px -3px rgba(239,68,68,0.5);
  }

  .lang-nav-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 14px 5px 8px;
    border-radius: 999px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    margin-left: 6px;
    flex-shrink: 0;
    cursor: pointer;
    transition: background .2s;
  }
  .lang-nav-badge:hover { background: rgba(255,255,255,0.1); }
  .lang-flag-big img {
    width: 26px;
    height: 18px;
    border-radius: 3px;
    object-fit: cover;
    display: block;
  }
  .lang-nav-text { display: flex; flex-direction: column; line-height: 1.15; }
  .lang-nav-label { font-size: 12.5px; font-weight: 800; color: #fff; }
  .lang-nav-sub { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }

  .top-links {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 22px;
  }
  .top-links a {
    font-size: 13px;
    font-weight: 600;
    color: #94a3b8;
    transition: color .2s;
  }
  .top-links a:hover { color: #fff; }

  .notif-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.06);
    color: #e0e7ff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    transition: background .2s;
  }
  .notif-btn:hover { background: rgba(255,255,255,0.12); }
  .notif-btn svg { width: 18px; height: 18px; }
  .notif-dot {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ef4444;
    border: 2px solid var(--topbar-bg);
  }

  .user-menu {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 999px;
    position: relative;
    flex-shrink: 0;
    transition: background .2s;
  }
  .user-menu:hover { background: rgba(255,255,255,0.08); }
  .avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    overflow: hidden;
    font-weight: 700;
    font-size: 13px;
  }
  .avatar img { width: 100%; height: 100%; object-fit: cover; }
  .avatar svg { width: 16px; height: 16px; }
  .user-menu .chev { width: 13px; height: 13px; color: #94a3b8; }
  .user-name { font-size: 13px; font-weight: 700; color: #fff; }

  .dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-hover);
    min-width: 190px;
    padding: 6px;
    display: none;
    z-index: 60;
    animation: scaleIn .2s var(--ease);
  }
  .dropdown.show { display: block; }
  .dropdown a {
    display: block;
    padding: 11px 13px;
    font-size: 13.5px;
    font-weight: 600;
    border-radius: 9px;
    color: var(--text);
    transition: background .15s;
  }
  .dropdown a:hover { background: var(--purple-soft); }
  .dropdown a.danger { color: #b91c1c; }

  .topbar-login-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 999px;
    flex-shrink: 0;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    letter-spacing: 0.3px;
    cursor: pointer;
    box-shadow: 0 8px 18px -5px rgba(168,85,247,0.45);
    transition: filter .2s, transform .15s;
  }
  .topbar-login-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }

  /* ========== LAYOUT ========== */
  .shell { display: flex; min-height: calc(100vh - 64px); }

  .sidebar {
    width: 260px;
    flex-shrink: 0;
    background: linear-gradient(180deg, #0f0c29 0%, #1a1440 50%, #1e1b4b 100%);
    padding: 22px 14px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    position: sticky;
    top: 64px;
    align-self: flex-start;
    height: calc(100vh - 64px);
    overflow-y: auto;
    transition: transform .3s var(--ease);
  }
  .nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    color: var(--sidebar-text);
    cursor: pointer;
    transition: all .2s var(--ease);
  }
  .nav-item svg { width: 19px; height: 19px; flex-shrink: 0; opacity: 0.85; }
  .nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
  .nav-item.active {
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    box-shadow: 0 8px 24px -6px rgba(168,85,247,0.5);
  }
  .nav-item.active svg { opacity: 1; }
  .nav-item .badge-new {
    margin-left: auto;
    font-size: 10px;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 999px;
    background: linear-gradient(135deg, #a855f7, #6366f1);
    color: #fff;
    letter-spacing: 0.3px;
  }
  .nav-item.locked { position: relative; }
  .nav-item.locked .lock-badge {
    margin-left: auto;
    width: 15px;
    height: 15px;
    color: #64748b;
    flex-shrink: 0;
  }

  .side-divider {
    height: 1px;
    background: rgba(255,255,255,0.08);
    margin: 14px 8px;
  }

  .side-illustration {
    margin-top: auto;
    padding: 16px 8px 8px;
    text-align: center;
  }
  .side-illustration img {
    width: 100%;
    max-width: 180px;
    height: auto;
    opacity: 0.9;
  }
  .side-help-card {
    margin-top: 12px;
    padding: 16px;
    border-radius: 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    text-align: center;
  }
  .side-help-card h4 {
    font-size: 13.5px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 4px;
  }
  .side-help-card p {
    font-size: 11.5px;
    color: #94a3b8;
    margin-bottom: 12px;
  }
  .side-help-card a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    color: #e0e7ff;
    font-size: 12px;
    font-weight: 700;
    transition: background .2s;
  }
  .side-help-card a:hover { background: rgba(255,255,255,0.14); }

  .backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,12,41,0.5);
    backdrop-filter: blur(3px);
    z-index: 45;
  }
  .backdrop.show { display: block; animation: fadeIn .25s; }

  .main {
    flex: 1;
    padding: 28px clamp(16px, 3vw, 36px) 50px;
    min-width: 0;
    background: linear-gradient(160deg, #f4f0ff 0%, #faf8ff 40%, #f0eaff 100%);
    position: relative;
    overflow: hidden;
  }
  .main::before {
    content: '';
    position: absolute;
    top: -80px;
    right: -60px;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(168,85,247,0.12) 0%, transparent 70%);
    pointer-events: none;
  }

  .page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 26px;
    position: relative;
    z-index: 1;
  }
  .page-title {
    font-size: 28px;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.5px;
    margin-bottom: 6px;
  }
  .page-subtitle {
    font-size: 14px;
    color: var(--muted);
    font-weight: 500;
  }
  .page-deco {
    position: absolute;
    top: -10px;
    right: 10px;
    width: 160px;
    height: auto;
    opacity: 0.95;
    pointer-events: none;
  }

  .guest-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    padding: 16px 20px;
    border-radius: var(--radius-md);
    margin-bottom: 22px;
    background: var(--purple-soft);
    border: 1px solid rgba(168,85,247,0.25);
  }
  .guest-banner p {
    flex: 1;
    min-width: 200px;
    font-size: 13.5px;
    font-weight: 700;
    color: #6b21a8;
  }
  .guest-banner button {
    padding: 10px 20px;
    border: none;
    border-radius: var(--radius-sm);
    flex-shrink: 0;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 12.5px;
    cursor: pointer;
    box-shadow: 0 8px 18px -5px rgba(168,85,247,0.4);
    transition: filter .2s, transform .15s;
  }
  .guest-banner button:hover { filter: brightness(1.06); transform: translateY(-1px); }

  .grid {
    display: grid;
    grid-template-columns: 1fr 1.05fr;
    gap: 22px;
    align-items: start;
    position: relative;
    z-index: 1;
  }

  /* ========== PANELS ========== */
  .panel {
    background: var(--card);
    border: 1px solid var(--line-soft);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-card);
    padding: 24px;
    transition: box-shadow .3s;
  }
  .panel:hover { box-shadow: var(--shadow-hover); }
  .panel h2 {
    font-size: 16.5px;
    font-weight: 800;
    margin: 0 0 16px 0;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .panel-subtitle {
    font-size: 12.5px;
    color: var(--muted);
    font-weight: 600;
    margin: -10px 0 16px 0;
  }

  /* ========== EMPTY STATE ========== */
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 20px 8px 12px;
  }
  .empty-state svg { width: 160px; height: auto; margin-bottom: 16px; opacity: 0.9; }
  .empty-state .msg {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 16px 0;
  }
  .btn-primary {
    padding: 12px 26px;
    border: none;
    border-radius: var(--radius-sm);
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 13px;
    letter-spacing: 0.3px;
    cursor: pointer;
    box-shadow: 0 10px 24px -6px rgba(168,85,247,0.45);
    transition: filter .2s, transform .15s;
  }
  .btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); }

  /* ========== SESSION CARDS ========== */
  .session-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid rgba(236,72,153,0.2);
    border-radius: var(--radius-md);
    margin-bottom: 12px;
    transition: all .25s var(--ease);
    background: linear-gradient(135deg, #fdf2f8 0%, #fff 100%);
  }
  .session-card:hover {
    box-shadow: 0 10px 28px -10px rgba(236,72,153,0.15);
    transform: translateY(-2px);
  }
  .session-card:last-child { margin-bottom: 0; }
  .session-card.is-today {
    border-color: rgba(236,72,153,0.35);
    background: linear-gradient(135deg, #fce7f3, #fff);
  }
  .session-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #10b981, #34d399);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-weight: 800;
    font-size: 16px;
  }
  .session-icon svg { width: 20px; height: 20px; }
  .session-info { flex: 1; min-width: 0; }
  .session-info .booked-with-label {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: var(--muted-2);
    margin: 0 0 3px 0;
  }
  .session-info .pkg {
    font-size: 14px;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 4px 0;
  }
  .session-info .when {
    font-size: 12.5px;
    color: var(--muted);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
  }
  .session-info .when svg { width: 13px; height: 13px; color: var(--muted-2); }
  .today-chip {
    display: inline-flex;
    align-items: center;
    padding: 2px 9px;
    border-radius: 999px;
    background: #fce7f3;
    color: #be185d;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.3px;
    text-transform: uppercase;
  }

  /* ========== STATS ROW ========== */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-top: 18px;
  }
  .stat-card {
    background: var(--card);
    border: 1px solid var(--line-soft);
    border-radius: 16px;
    padding: 18px 14px;
    text-align: center;
    transition: all .25s;
    box-shadow: var(--shadow-card);
  }
  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
  }
  .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
  }
  .stat-icon svg { width: 20px; height: 20px; }
  .stat-icon.purple { background: var(--purple-soft); color: var(--purple); }
  .stat-icon.green { background: var(--success-soft); color: var(--success); }
  .stat-icon.amber { background: var(--amber-soft); color: var(--amber); }
  .stat-icon.blue { background: var(--blue-soft); color: var(--blue); }
  .stat-value {
    font-size: 22px;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.5px;
  }
  .stat-label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--muted);
    margin-top: 2px;
  }

  /* ========== CALENDAR ========== */
  .cal-block {
    border: 1px solid var(--line-soft);
    border-radius: var(--radius-md);
    padding: 16px;
    margin-bottom: 0;
    background: var(--bg-soft);
  }
  .cal-block-title {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 14px 0;
    display: flex;
    align-items: center;
    gap: 7px;
  }
  .cal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
  }
  .cal-nav-btn {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    border: 1px solid var(--line);
    background: var(--card);
    color: var(--text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .2s;
  }
  .cal-nav-btn:hover { background: var(--purple-soft); transform: scale(1.05); }
  .cal-nav-btn svg { width: 15px; height: 15px; }
  .cal-month-label { font-size: 14.5px; font-weight: 800; color: var(--text); }

  .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
  .cal-dow {
    text-align: center;
    font-size: 10.5px;
    font-weight: 800;
    color: var(--muted-2);
    text-transform: uppercase;
    padding-bottom: 5px;
  }
  .cal-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text);
    border-radius: 10px;
    cursor: pointer;
    position: relative;
    background: var(--card);
    border: 1.5px solid transparent;
    transition: all .2s var(--ease);
  }
  .cal-day:hover { background: var(--purple-soft); transform: scale(1.06); }
  .cal-day.empty { visibility: hidden; cursor: default; }
  .cal-day.past { color: var(--muted-2); cursor: not-allowed; opacity: 0.4; }
  .cal-day.past:hover { background: var(--card); transform: none; }
  .cal-day.today { border-color: #ec4899; }
  .cal-day.has-slots::after {
    content: "";
    position: absolute;
    bottom: 5px;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--success);
  }
  .cal-day.selected {
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    box-shadow: 0 4px 14px -3px rgba(168,85,247,0.45);
  }
  .cal-day.selected::after { background: #fff; }

  .cal-slots {
    margin-top: 16px;
    border-top: 1px solid var(--line-soft);
    padding-top: 14px;
  }
  .cal-slots-title {
    font-size: 12px;
    font-weight: 800;
    color: var(--muted);
    margin: 0 0 12px 0;
  }
  .cal-slot-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 13px;
    border: 1px solid var(--line-soft);
    border-radius: 12px;
    margin-bottom: 9px;
    background: var(--card);
    flex-wrap: wrap;
    transition: all .25s var(--ease);
  }
  .cal-slot-item:hover {
    box-shadow: 0 8px 22px -8px rgba(124,58,237,0.12);
    transform: translateY(-1px);
  }
  .cal-slot-item:last-child { margin-bottom: 0; }
  .cal-slot-avatar {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: var(--purple-soft);
    color: var(--purple);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
    overflow: hidden;
  }
  .cal-slot-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .cal-slot-info { flex: 1; min-width: 0; }
  .cal-slot-name { font-size: 13px; font-weight: 800; color: var(--text); }
  .cal-slot-meta { font-size: 11.5px; color: var(--muted); font-weight: 600; margin-top: 2px; }
  .cal-slots-empty {
    font-size: 13px;
    color: var(--muted-2);
    text-align: center;
    padding: 20px 8px;
  }
  .cal-slot-actions {
    display: flex;
    gap: 7px;
    flex-shrink: 0;
    align-items: center;
    flex-wrap: wrap;
  }

  .view-detail-btn {
    padding: 8px 13px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--card);
    color: var(--text);
    font-weight: 700;
    font-size: 11.5px;
    cursor: pointer;
    white-space: nowrap;
    transition: all .2s;
  }
  .view-detail-btn:hover { background: var(--purple-soft); }

  .book-slot-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 11.5px;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 6px 16px -4px rgba(168,85,247,0.4);
    transition: all .2s;
  }
  .book-slot-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }

  /* Already Booked – colored green button */
  .booked-slot-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 15px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    font-weight: 800;
    font-size: 11.5px;
    cursor: default;
    white-space: nowrap;
    box-shadow: 0 6px 16px -4px rgba(16, 185, 129, 0.4);
  }
  .booked-slot-item {
    border-color: rgba(16, 185, 129, 0.35) !important;
    background: linear-gradient(135deg, #ecfdf5 0%, #fff 100%) !important;
  }
  .cal-day.has-booked::after {
    content: "";
    position: absolute;
    bottom: 5px;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 2px #fff;
  }

  .session-ended-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 800;
    padding: 8px 14px;
    border-radius: 8px;
    background: #f3f4f6;
    color: #6b7280;
    white-space: nowrap;
  }
  .completed-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: var(--radius-sm);
    background: var(--success-soft);
    color: var(--success);
    font-weight: 800;
    font-size: 12px;
    white-space: nowrap;
  }

  .no-package-prompt, .not-booked-prompt, .login-required-prompt {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
    flex-shrink: 0;
  }
  .no-package-prompt .no-package-msg { font-size: 10.5px; font-weight: 800; color: #92400e; white-space: nowrap; }
  .no-package-prompt .activate-pkg-btn {
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    font-weight: 800;
    font-size: 11.5px;
    cursor: pointer;
    transition: all .2s;
  }
  .no-package-prompt .activate-pkg-btn:hover { filter: brightness(1.08); }
  .not-booked-prompt .not-booked-msg { font-size: 10.5px; font-weight: 800; color: #be185d; white-space: nowrap; }
  .not-booked-prompt .book-now-btn {
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 11.5px;
    cursor: pointer;
    box-shadow: 0 6px 14px -4px rgba(168,85,247,0.4);
    transition: all .2s;
  }
  .not-booked-prompt .book-now-btn:hover { filter: brightness(1.08); }
  .login-required-prompt .login-required-msg { font-size: 10.5px; font-weight: 800; color: #6b21a8; white-space: nowrap; }
  .login-required-prompt .login-required-btn {
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 11.5px;
    cursor: pointer;
    box-shadow: 0 6px 14px -4px rgba(168,85,247,0.4);
    transition: all .2s;
  }
  .login-required-prompt .login-required-btn:hover { filter: brightness(1.08); }

  /* ========== LECTURER MODAL ========== */
  .lect-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,12,41,0.65);
    backdrop-filter: blur(8px);
    z-index: 300;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .lect-modal-overlay.show { display: flex; animation: fadeIn .25s; }
  .lect-modal-box {
    background: var(--card);
    border-radius: 24px;
    width: 100%;
    max-width: 440px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 40px 90px -20px rgba(0,0,0,0.5);
    overflow: hidden;
    animation: scaleIn .3s cubic-bezier(.34,1.56,.64,1);
  }
  .lect-modal-header {
    background: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.12), transparent 45%),
                linear-gradient(135deg, #0f0c29 0%, #a855f7 100%);
    padding: 28px 24px 56px;
    position: relative;
    text-align: center;
    overflow: hidden;
    flex-shrink: 0;
  }
  .lect-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 11px;
    border: none;
    background: rgba(255,255,255,0.18);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    transition: all .25s;
  }
  .lect-modal-close:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
  .lect-modal-photo, .lect-modal-photo-fallback {
    width: 112px;
    height: 112px;
    border-radius: 50%;
    position: absolute;
    left: 50%;
    bottom: -56px;
    transform: translateX(-50%);
    z-index: 2;
  }
  .lect-modal-photo {
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 10px 26px rgba(0,0,0,0.3);
    background: #fff;
  }
  .lect-modal-photo-fallback {
    border: 4px solid #fff;
    box-shadow: 0 10px 26px rgba(0,0,0,0.3);
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
    font-weight: 800;
  }
  .lect-modal-body {
    padding: 68px 22px 24px;
    text-align: center;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
  }
  .lect-modal-name { font-size: 20px; font-weight: 800; color: var(--text); margin: 0 0 8px 0; }
  .lect-modal-subject {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 800;
    background: var(--purple-soft);
    color: #6b21a8;
    margin-bottom: 18px;
  }
  .lect-modal-info-grid { display: flex; flex-direction: column; gap: 10px; text-align: left; }
  .lect-modal-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    border: 1px solid var(--line-soft);
    border-radius: 14px;
    background: var(--bg-soft);
  }
  .lect-modal-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    background: var(--purple-soft);
    color: var(--purple);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .lect-modal-card-icon svg { width: 16px; height: 16px; }
  .lect-modal-label {
    font-size: 10px;
    font-weight: 800;
    color: var(--muted-2);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin: 0 0 4px 0;
  }
  .lect-modal-value { font-size: 13.5px; color: var(--text); line-height: 1.55; margin: 0; font-weight: 600; }
  .lect-modal-value.muted { color: var(--muted-2); font-style: italic; font-weight: 500; }
  .lect-modal-qual-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
  .lect-modal-qual-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid var(--line-soft);
    border-radius: 10px;
    background: var(--card);
    font-size: 13px;
    line-height: 1.5;
    font-weight: 500;
    color: var(--text);
  }
  .lect-modal-qual-item .qual-bullet {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #a855f7;
    flex-shrink: 0;
    margin-top: 6px;
  }
  .lect-modal-qual-empty { font-size: 13px; color: var(--muted-2); font-style: italic; margin: 0; }
  .lect-modal-loading {
    padding: 60px 20px;
    text-align: center;
    color: var(--muted-2);
    font-size: 13px;
  }
  .lect-modal-loading::before {
    content: "";
    display: block;
    width: 28px;
    height: 28px;
    margin: 0 auto 14px;
    border-radius: 50%;
    border: 3px solid var(--line);
    border-top-color: #a855f7;
    animation: spin .7s linear infinite;
  }
  .lect-modal-book-btn {
    width: 100%;
    margin-top: 20px;
    padding: 15px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 10px 26px -6px rgba(168,85,247,0.45);
    transition: all .2s;
  }
  .lect-modal-book-btn:hover { filter: brightness(1.06); transform: translateY(-1px); }

  /* ========== LIVE NOW ========== */
  #liveNowPanel {
    border: 1px solid rgba(236,72,153,0.2);
    background: linear-gradient(180deg, #fdf2f8 0%, var(--card) 70%);
    margin-bottom: 22px;
  }
  .live-now-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 15px 16px;
    border: 1px solid rgba(236,72,153,0.15);
    background: var(--card);
    border-radius: var(--radius-md);
    margin-bottom: 10px;
    flex-wrap: wrap;
    box-shadow: 0 4px 16px -6px rgba(236,72,153,0.12);
    transition: all .25s;
  }
  .live-now-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px -8px rgba(236,72,153,0.18);
  }
  .live-now-item:last-child { margin-bottom: 0; }
  .live-now-avatar {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    background: var(--purple-soft);
    color: var(--purple);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;
    overflow: hidden;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px rgba(236,72,153,0.35);
  }
  .live-now-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .live-now-info { flex: 1; min-width: 140px; }
  .live-now-name { font-size: 14px; font-weight: 800; color: var(--text); }
  .live-now-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    font-size: 12px;
    color: var(--muted);
    font-weight: 600;
    margin-top: 4px;
  }
  .live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ec4899;
    animation: pulseLive 1.3s infinite;
    flex-shrink: 0;
  }
  .live-now-elapsed {
    font-size: 10.5px;
    font-weight: 800;
    color: #be185d;
    background: #fce7f3;
    padding: 2px 9px;
    border-radius: 999px;
  }
  .join-live-btn {
    padding: 11px 22px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 12.5px;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    box-shadow: 0 6px 18px -4px rgba(168,85,247,0.45);
    transition: all .2s;
  }
  .join-live-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }

  /* ========== MEETING MODAL ========== */
  .meeting-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(11,18,32,0.85);
    z-index: 500;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .meeting-modal-overlay.show { display: flex; animation: fadeIn .25s; }
  .meeting-modal-box {
    background: #0f0c29;
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 1000px;
    overflow: hidden;
    box-shadow: 0 30px 80px -20px rgba(0,0,0,0.6);
    animation: scaleIn .3s var(--ease);
  }
  .meeting-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 15px 22px;
    background: #fff;
  }
  .meeting-modal-title { font-size: 14.5px; font-weight: 800; color: var(--text); }
  .meeting-modal-title span { color: #a855f7; }
  .meeting-modal-leave {
    padding: 10px 18px;
    border-radius: 9px;
    border: 1px solid #fce7f3;
    background: #fce7f3;
    color: #be185d;
    font-weight: 800;
    font-size: 13px;
    cursor: pointer;
    transition: all .2s;
  }
  .meeting-modal-leave:hover { background: #be185d; color: #fff; }
  .meeting-modal-frame {
    width: 100%;
    min-height: 320px;
    background: #0f0c29;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    text-align: center;
    padding: 40px 24px;
    color: #e9eef4;
  }
  .meeting-modal-frame p {
    font-size: 13.5px;
    color: #c4b5fd;
    max-width: 400px;
    margin: 0;
    line-height: 1.5;
  }

  /* ========== AUTH MODAL ========== */
  .auth-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,12,41,0.65);
    backdrop-filter: blur(8px);
    z-index: 400;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
  }
  .auth-modal-overlay.show { display: flex; animation: fadeIn .25s; }
  .auth-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 11px;
    border: none;
    background: var(--purple-soft);
    color: var(--purple);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    transition: all .25s;
  }
  .auth-modal-close:hover { background: #fce7f3; color: #be185d; transform: rotate(90deg); }
  .gp-card {
    background: var(--card);
    border-radius: var(--radius-lg);
    padding: clamp(28px, 4vw, 40px) clamp(22px, 4vw, 34px);
    box-shadow: 0 40px 90px -20px rgba(0,0,0,0.4);
    border: 1px solid var(--line-soft);
    width: 440px;
    max-width: 100%;
    position: relative;
    margin: auto;
    animation: scaleIn .3s var(--ease);
  }
  .gp-hidden { display: none !important; }
  .gp-head { text-align: center; margin-bottom: 24px; }
  .gp-head .gp-eyebrow {
    font-size: 12.5px;
    font-weight: 700;
    color: #a855f7;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin: 0 0 8px 0;
  }
  .gp-head h1 {
    font-size: clamp(20px, 3vw, 25px);
    color: var(--text);
    margin: 0 0 6px 0;
    font-weight: 800;
    letter-spacing: -0.4px;
  }
  .gp-head p { color: var(--muted); font-size: 13px; line-height: 1.6; margin: 0; }
  .gp-field { margin-bottom: 15px; position: relative; }
  .gp-field label { display: block; font-size: 12.5px; color: var(--text); margin-bottom: 7px; font-weight: 600; }
  .gp-input-shell { position: relative; display: flex; align-items: center; }
  .gp-input-icon {
    position: absolute;
    left: 14px;
    width: 18px;
    height: 18px;
    color: var(--muted-2);
    pointer-events: none;
    display: flex;
    flex-shrink: 0;
  }
  .gp-field input, .gp-field select {
    width: 100%;
    padding: 12.5px 14px 12.5px 40px;
    border-radius: var(--radius-sm);
    border: 1.5px solid var(--line);
    font-size: 14px;
    font-family: inherit;
    outline: none;
    background: var(--bg-soft);
    color: var(--text);
    transition: border-color .15s, box-shadow .15s, background .15s;
  }
  .gp-field select { padding-left: 14px; cursor: pointer; }
  .gp-field input::placeholder { color: var(--muted-2); }
  .gp-field input:focus, .gp-field select:focus {
    border-color: #a855f7;
    background: var(--card);
    box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
  }
  .gp-field input.gp-invalid { border-color: #ef4444; background: #fef2f2; }
  .gp-field input.gp-valid { border-color: var(--success); }
  .gp-toggle-pass {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--muted-2);
    padding: 6px;
    display: flex;
    align-items: center;
    border-radius: 6px;
  }
  .gp-toggle-pass:hover { color: var(--purple); background: var(--purple-soft); }
  .gp-toggle-pass svg { width: 18px; height: 18px; }
  .gp-error {
    font-size: 12px;
    color: #ef4444;
    margin-top: 6px;
    display: none;
    align-items: center;
    gap: 5px;
    font-weight: 500;
  }
  .gp-error.show { display: flex; }
  .gp-strength-meter { display: flex; gap: 4px; margin-top: 8px; height: 4px; }
  .gp-strength-meter span { flex: 1; border-radius: 2px; background: var(--line); transition: background .2s; }
  .gp-strength-label { font-size: 11px; color: var(--muted-2); margin-top: 5px; font-weight: 600; }
  .gp-row-inline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 2px 0 18px 0;
    flex-wrap: wrap;
    gap: 8px;
  }
  .gp-checkbox-label {
    font-size: 13px;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 7px;
    font-weight: 500;
    cursor: pointer;
    user-select: none;
  }
  .gp-checkbox-label input { width: 16px; height: 16px; accent-color: #a855f7; cursor: pointer; }
  .gp-row-inline a { font-size: 13px; color: var(--purple); font-weight: 700; }
  .gp-row-inline a:hover { color: #7c3aed; }
  .gp-btn-primary {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: var(--radius-sm);
    background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
    color: #fff;
    font-weight: 800;
    font-size: 14px;
    letter-spacing: 0.4px;
    cursor: pointer;
    transition: transform .12s, box-shadow .2s, filter .15s;
    box-shadow: 0 10px 24px -6px rgba(168,85,247,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  .gp-btn-primary:hover { filter: brightness(1.04); }
  .gp-btn-primary:disabled { opacity: .7; cursor: not-allowed; }
  .gp-btn-primary .gp-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: none;
  }
  .gp-btn-primary.loading .gp-spinner { display: inline-block; }
  .gp-btn-primary.loading .gp-btn-text { opacity: 0.85; }
  .gp-switch-row { text-align: center; margin-top: 22px; font-size: 13.5px; color: var(--muted); }
  .gp-switch-row a { color: #a855f7; font-weight: 800; cursor: pointer; }
  .gp-switch-row a:hover { text-decoration: underline; }
  .gp-lang-select { position: relative; }
  .gp-lang-trigger {
    width: 100%;
    padding: 12.5px 14px 12.5px 40px;
    border-radius: var(--radius-sm);
    border: 1.5px solid var(--line);
    font-size: 14px;
    font-family: inherit;
    outline: none;
    background: var(--bg-soft);
    color: var(--text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    transition: border-color .15s, box-shadow .15s;
    user-select: none;
  }
  .gp-lang-trigger.open {
    border-color: #a855f7;
    background: var(--card);
    box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
  }
  .gp-lang-trigger .gp-lang-current {
    display: flex;
    align-items: center;
    gap: 9px;
    overflow: hidden;
    white-space: nowrap;
  }
  .gp-lang-flag {
    width: 22px;
    height: 16px;
    flex-shrink: 0;
    border-radius: 3px;
    box-shadow: 0 0 0 1px rgba(0,0,0,0.12);
    overflow: hidden;
  }
  .gp-lang-caret { width: 16px; height: 16px; color: var(--muted-2); flex-shrink: 0; transition: transform .18s; }
  .gp-lang-trigger.open .gp-lang-caret { transform: rotate(180deg); }
  .gp-lang-options {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: var(--card);
    border: 1.5px solid var(--line);
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow-hover);
    z-index: 20;
    max-height: 220px;
    overflow-y: auto;
    padding: 6px;
    opacity: 0;
    transform: translateY(-6px);
    pointer-events: none;
    transition: opacity .15s, transform .15s;
  }
  .gp-lang-options.open { opacity: 1; transform: translateY(0); pointer-events: auto; }
  .gp-lang-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: var(--text);
  }
  .gp-lang-option:hover { background: var(--purple-soft); }
  .gp-lang-option-left { display: flex; align-items: center; gap: 10px; }
  .gp-lang-tick { width: 16px; height: 16px; color: #a855f7; flex-shrink: 0; opacity: 0; transition: opacity .12s; }
  .gp-lang-option.selected { background: var(--purple-soft); font-weight: 700; color: #6b21a8; }
  .gp-lang-option.selected .gp-lang-tick { opacity: 1; }
  .gp-lang-option-loading { padding: 12px; font-size: 13px; color: var(--muted-2); text-align: center; }
  .gp-toast {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(-16px);
    background: #0f0c29;
    color: #fff;
    padding: 13px 22px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s, transform .25s;
    z-index: 600;
    box-shadow: 0 12px 30px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 90vw;
  }
  .gp-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
  .gp-toast.gp-error-toast { background: #ef4444; }

  /* ========== HOW TO REGISTER VIDEO MODAL ========== */
  .howto-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,12,41,0.75);
    backdrop-filter: blur(8px);
    z-index: 450;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .howto-modal-overlay.show { display: flex; animation: fadeIn .25s; }

  .howto-modal-box {
    background: var(--card);
    border-radius: 20px;
    width: 100%;
    max-width: 720px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 40px 90px -20px rgba(0,0,0,0.5);
    animation: scaleIn .3s cubic-bezier(.34,1.56,.64,1);
    display: flex;
    flex-direction: column;
  }

  .howto-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: linear-gradient(135deg, #0f0c29 0%, #a855f7 100%);
    color: #fff;
    flex-shrink: 0;
  }

  .howto-modal-header h3 {
    font-size: 16px;
    font-weight: 800;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
  }

  .howto-modal-header h3 span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .howto-modal-close {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: none;
    background: rgba(255,255,255,0.18);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .25s;
    flex-shrink: 0;
  }

  .howto-modal-close:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
  }

  .howto-modal-body {
    padding: 0;
    background: #000;
    overflow: auto;
  }

  .howto-modal-body video {
    width: 100%;
    max-height: 70vh;
    display: block;
    background: #000;
  }

  .howto-modal-body iframe {
    width: 100%;
    aspect-ratio: 16 / 9;
    min-height: 320px;
    border: none;
    background: #000;
    display: block;
  }

  .howto-modal-footer {
    padding: 14px 20px;
    text-align: center;
    background: var(--bg-soft);
    border-top: 1px solid var(--line-soft);
    flex-shrink: 0;
  }

  .howto-modal-footer p {
    font-size: 13px;
    color: var(--muted);
    margin: 0 0 10px 0;
    font-weight: 600;
  }

  .howto-modal-footer .btn-primary {
    padding: 10px 22px;
    font-size: 13px;
  }

  @media (max-width: 560px) {
    .howto-modal-overlay { padding: 10px; }
    .howto-modal-box { border-radius: 16px; max-height: 94vh; }
    .howto-modal-header { padding: 13px 14px; }
    .howto-modal-header h3 { font-size: 14px; }
    .howto-modal-body iframe { min-height: 220px; }
    .howto-modal-footer { padding: 12px 14px; }
  }

  /* ========== CHATBOT STYLES ========== */
  .chatbot-toggle {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 28px -4px rgba(168,85,247,0.55);
    z-index: 900;
    transition: transform .2s, box-shadow .2s;
  }
  .chatbot-toggle:hover {
    transform: scale(1.08);
    box-shadow: 0 12px 32px -4px rgba(168,85,247,0.65);
  }
  .chatbot-toggle svg { width: 28px; height: 28px; }
  .chatbot-toggle .chat-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 18px;
    height: 18px;
    background: #ef4444;
    border-radius: 50%;
    font-size: 10px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
  }

  .chatbot-window {
    position: fixed;
    bottom: 100px;
    right: 24px;
    width: 370px;
    max-width: calc(100vw - 32px);
    height: 520px;
    max-height: calc(100vh - 140px);
    background: var(--card);
    border-radius: 20px;
    box-shadow: 0 20px 60px -12px rgba(0,0,0,0.35);
    display: none;
    flex-direction: column;
    z-index: 910;
    overflow: hidden;
    border: 1px solid var(--line-soft);
    animation: scaleIn .25s var(--ease);
  }
  .chatbot-window.open { display: flex; }

  .chatbot-header {
    background: linear-gradient(135deg, #0f0c29 0%, #a855f7 100%);
    color: #fff;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
  }
  .chatbot-header-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
  }
  .chatbot-header-info h3 {
    font-size: 15px;
    font-weight: 800;
    margin: 0 0 2px 0;
  }
  .chatbot-header-info p {
    font-size: 11.5px;
    opacity: 0.85;
    margin: 0;
  }
  .chatbot-close {
    margin-left: auto;
    background: rgba(255,255,255,0.15);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 10px;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s;
  }
  .chatbot-close:hover { background: rgba(255,255,255,0.28); }

  .chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 18px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #faf8ff;
  }
  .chat-msg {
    max-width: 85%;
    padding: 11px 14px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.5;
    word-wrap: break-word;
  }
  .chat-msg.bot {
    background: #fff;
    border: 1px solid var(--line-soft);
    color: var(--text);
    border-bottom-left-radius: 4px;
    align-self: flex-start;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .chat-msg.user {
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    border-bottom-right-radius: 4px;
    align-self: flex-end;
  }
  .chat-msg .quick-replies {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
  }
  .quick-reply-btn {
    padding: 6px 12px;
    border-radius: 999px;
    border: 1px solid #c4b5fd;
    background: #f3e8ff;
    color: #6b21a8;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
  }
  .quick-reply-btn:hover {
    background: #a855f7;
    color: #fff;
    border-color: #a855f7;
  }

  .chatbot-input-area {
    padding: 12px 14px;
    border-top: 1px solid var(--line-soft);
    display: flex;
    gap: 8px;
    background: #fff;
    flex-shrink: 0;
  }
  .chatbot-input {
    flex: 1;
    padding: 12px 14px;
    border: 1.5px solid var(--line);
    border-radius: 12px;
    font-size: 13.5px;
    font-family: inherit;
    outline: none;
    background: var(--bg-soft);
    transition: border-color .15s;
  }
  .chatbot-input:focus {
    border-color: #a855f7;
    background: #fff;
  }
  .chatbot-send {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: filter .15s;
  }
  .chatbot-send:hover { filter: brightness(1.08); }
  .chatbot-send:disabled { opacity: 0.6; cursor: not-allowed; }

  .chat-typing {
    display: flex;
    gap: 4px;
    padding: 10px 14px;
    align-self: flex-start;
  }
  .chat-typing span {
    width: 7px;
    height: 7px;
    background: #a855f7;
    border-radius: 50%;
    animation: typingBounce 1.2s infinite ease-in-out;
  }
  .chat-typing span:nth-child(2) { animation-delay: 0.15s; }
  .chat-typing span:nth-child(3) { animation-delay: 0.3s; }
  @keyframes typingBounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
    30% { transform: translateY(-6px); opacity: 1; }
  }

  /* ========== MOBILE GATE POPUP ========== */
  .mobile-gate-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 12, 41, 0.92);
    backdrop-filter: blur(12px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .mobile-gate-box {
    background: #fff;
    border-radius: 24px;
    width: 100%;
    max-width: 420px;
    padding: 36px 28px;
    box-shadow: 0 40px 90px -20px rgba(0,0,0,0.5);
    text-align: center;
    animation: scaleIn .35s cubic-bezier(.34,1.56,.64,1);
  }
  .mobile-gate-box h2 {
    font-size: 22px;
    font-weight: 800;
    color: #1e1b4b;
    margin: 0 0 8px 0;
  }
  .mobile-gate-box p {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 24px 0;
    line-height: 1.5;
  }
  .mobile-gate-input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e9e5f5;
    border-radius: 12px;
    font-size: 16px;
    font-family: inherit;
    text-align: center;
    letter-spacing: 1px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    margin-bottom: 8px;
  }
  .mobile-gate-input:focus {
    border-color: #a855f7;
    box-shadow: 0 0 0 4px rgba(168,85,247,0.15);
  }
  .mobile-gate-error {
    font-size: 13px;
    color: #ef4444;
    font-weight: 600;
    margin-bottom: 14px;
    min-height: 20px;
  }
  .mobile-gate-btn {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    color: #fff;
    font-weight: 800;
    font-size: 15px;
    cursor: pointer;
    box-shadow: 0 10px 24px -6px rgba(168,85,247,0.45);
    transition: filter .2s, transform .15s;
  }
  .mobile-gate-btn:hover {
    filter: brightness(1.06);
    transform: translateY(-1px);
  }
  .mobile-gate-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  /* ========== RESPONSIVE ========== */
  @media (max-width: 1100px) {
    .grid { grid-template-columns: 1fr 1fr; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 900px) {
    .top-links { display: none; }
  }
  @media (max-width: 820px) {
    .sidebar {
      position: fixed;
      left: 0;
      top: 64px;
      transform: translateX(-100%);
      width: 280px;
      height: calc(100vh - 64px);
      z-index: 46;
      box-shadow: 0 0 40px rgba(0,0,0,0.3);
    }
    .sidebar.open { transform: translateX(0); }
    .grid { grid-template-columns: 1fr; }
    .lang-nav-badge { padding: 4px 10px 4px 6px; }
    .lang-flag-big img { width: 24px; height: 17px; }
    .lang-nav-label { font-size: 11.5px; }
    .page-deco { display: none; }
  }
  @media (max-width: 560px) {
    .topbar { padding: 0 10px; gap: 8px; }
    .logo span:not(.logo-mark) { display: none; }
    .lang-nav-text { display: none; }
    .user-name { display: none; }
    .main { padding: 18px 12px 36px; }
    .page-title { font-size: 22px; }
    .panel { padding: 18px; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .session-card { flex-wrap: wrap; }
    .cal-slot-item { flex-wrap: wrap; }
    .cal-slot-actions { width: 100%; }
    .cal-slot-actions button { flex: 1; }
    .live-now-item { flex-direction: column; align-items: flex-start; }
    .join-live-btn, .book-now-btn, .activate-pkg-btn, .login-required-btn { width: 100%; text-align: center; }
    .guest-banner { flex-direction: column; align-items: flex-start; }
    .chatbot-window {
      right: 12px;
      left: 12px;
      width: auto;
      bottom: 90px;
    }
    .chatbot-toggle { bottom: 18px; right: 18px; }
  }
  .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.logo-image {
    width: 100px;
    height: 100px;
    object-fit: contain;
    border-radius: 10px;
}
/* ===== Language dropdown – admin enable/disable ===== */
.gp-lang-option.disabled {
  opacity: 0.45;
  cursor: not-allowed;
  pointer-events: none;
  background: #f3f4f6 !important;
  color: #9ca3af !important;
}
.gp-lang-option.disabled .gp-lang-tick {
  display: none;
}
.gp-lang-option.enabled:hover {
  background: var(--purple-soft);
}
</style>
</head>
<body>

<?php if ($needsMobile): ?>
<div class="mobile-gate-overlay" id="mobileGateOverlay">
  <div class="mobile-gate-box">
    <h2>📱 Mobile Number</h2>
    <p>Dashboard එකට යන්න කලින් ඔබේ mobile number එක ඇතුළත් කරන්න.<br>
    (10 digit number – 07XXXXXXXX)</p>
    <form method="POST" action="" id="mobileGateForm">
      <input type="tel" name="gate_mobile" id="gateMobileInput" class="mobile-gate-input"
             placeholder="07XXXXXXXX" maxlength="10" inputmode="numeric" autocomplete="tel" required>
      <div class="mobile-gate-error" id="gateMobileError"></div>
      <button type="submit" class="mobile-gate-btn" id="gateMobileBtn">Continue to Dashboard →</button>
    </form>
  </div>
</div>
<script>
  document.getElementById('mobileGateForm').addEventListener('submit', function(e) {
    const input = document.getElementById('gateMobileInput');
    const err = document.getElementById('gateMobileError');
    const val = input.value.replace(/\D/g, '');
    if (!/^0\d{9}$/.test(val)) {
      e.preventDefault();
      err.textContent = '⚠ 10 digit mobile number එකක් දෙන්න (0XXXXXXXXX)';
      input.focus();
      return false;
    }
    err.textContent = '';
    document.getElementById('gateMobileBtn').disabled = true;
    document.getElementById('gateMobileBtn').textContent = 'Please wait...';
  });
  document.getElementById('gateMobileInput').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
  });
</script>
<?php else: ?>

<header class="topbar">
  <button class="burger" id="burgerBtn" aria-label="Menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
<a href="index.php" class="logo">
    <img src="images/logo.png" alt="Lingora Logo" class="logo-image">
    <span></span>
</a>
  <?php if ($isLoggedIn): ?>
  <div class="lang-nav-badge">
    <div class="lang-flag-big"><img src="<?php echo htmlspecialchars($langFlag); ?>" alt="<?php echo htmlspecialchars($langLabel); ?>"></div>
    <div class="lang-nav-text">
      <span class="lang-nav-label"><?php echo htmlspecialchars($langLabel); ?></span>
      <span class="lang-nav-sub">Your Language</span>
    </div>
  </div>
  <?php endif; ?>

  <nav class="top-links">
    <a href="about_sipway_campus.php">About Sipway Campus</a>
    <a href="terms_of_use.php">Terms of Use</a>
    <a href="privacy_policy.php">Privacy Policy</a>
  </nav>
 
  <?php if ($isLoggedIn): ?>
  <div class="user-menu" id="userMenu">
    <div class="avatar">
      <?php if ($photoUrl): ?>
        <img src="<?php echo $photoUrl; ?>" alt="">
      <?php else: ?>
        <?php echo strtoupper(substr($firstName, 0, 1)); ?>
      <?php endif; ?>
    </div>
    <span class="user-name">Hi, <?php echo $firstName; ?></span>
    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
     <div class="dropdown" id="userDropdown">
      <a href="edit_profile.php">✏️ Edit Profile</a>
      <a href="student_logout.php" class="danger">Log out</a>
    </div>
  </div>
  <?php else: ?>
  <button class="topbar-login-btn" onclick="openAuthModal(false)">Login / Register</button>
  <?php endif; ?>
</header>

<div class="backdrop" id="backdrop"></div>

<div class="shell">
<aside class="sidebar" id="sidebar">
  <a href="index.php" class="nav-item active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    Dashboard
  </a>
  

  <a href="packages.php" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Packages
  </a>

  <a href="session_progress.php" class="nav-item" >
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
    My Progress
  </a>

  <a href="practice-ai-video.php" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
    Practice with AI Video
    <span class="badge-new">New</span>
  </a>
 
<a href="student_chat.php" class="nav-item">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
  Chat with Admin
</a>
  <div class="side-divider"></div>

  <a href="faq-support.php" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    FAQs & Support
  </a>
  <a href="lecturer-details.php" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Lecturer Details
  </a>


  <a href="javascript:void(0)" class="nav-item" id="howToRegisterBtn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
      <polyline points="14 2 14 8 20 8"/>
      <line x1="16" y1="13" x2="8" y2="13"/>
      <line x1="16" y1="17" x2="8" y2="17"/>
      <polyline points="10 9 9 9 8 9"/>
    </svg>
    How to Register
  </a>

  


    <div class="side-illustration">
      <svg viewBox="0 0 200 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="max-width:170px;margin:0 auto;display:block;">
        <ellipse cx="100" cy="160" rx="70" ry="12" fill="rgba(168,85,247,0.15)"/>
        <rect x="40" y="100" width="50" height="45" rx="4" fill="#7c3aed" opacity="0.7"/>
        <rect x="50" y="90" width="50" height="45" rx="4" fill="#a855f7" opacity="0.8"/>
        <rect x="60" y="80" width="50" height="45" rx="4" fill="#c084fc"/>
        <circle cx="140" cy="70" r="35" fill="url(#g1)" opacity="0.9"/>
        <path d="M110 70 Q140 40 170 70 Q140 100 110 70" fill="none" stroke="#e0e7ff" stroke-width="1.5" opacity="0.5"/>
        <path d="M140 35 L140 105 M105 70 L175 70" stroke="#e0e7ff" stroke-width="1" opacity="0.4"/>
        <path d="M70 70 L100 55 L130 70 L100 85 Z" fill="#fbbf24"/>
        <rect x="95" y="70" width="10" height="25" fill="#f59e0b"/>
        <defs>
          <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#6366f1"/>
            <stop offset="100%" stop-color="#a855f7"/>
          </linearGradient>
        </defs>
      </svg>
    </div>

    
  </aside>

  <main class="main">
    <div class="page-header animate-up">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back! Keep learning and improving every day. 👏</p>
      </div>
      <!-- Decorative graduation + books illustration (top-right) -->
      <svg class="page-deco" viewBox="0 0 180 140" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="90" y="70" width="55" height="40" rx="4" fill="#a78bfa"/>
        <rect x="100" y="60" width="55" height="40" rx="4" fill="#8b5cf6"/>
        <rect x="110" y="50" width="55" height="40" rx="4" fill="#7c3aed"/>
        <path d="M40 55 L75 40 L110 55 L75 70 Z" fill="#c4b5fd"/>
        <rect x="70" y="55" width="10" height="30" fill="#a78bfa"/>
        <ellipse cx="55" cy="95" rx="8" ry="4" fill="#34d399" opacity="0.6"/>
        <circle cx="150" cy="30" r="18" fill="#fbbf24" opacity="0.3"/>
        <path d="M140 30 Q150 18 160 30 Q150 42 140 30" fill="none" stroke="#fbbf24" stroke-width="1.5" opacity="0.5"/>
      </svg>
    </div>

    <?php if (!$isLoggedIn): ?>
    <div class="guest-banner animate-up delay-1">
      <p>🔐 Login / Register කරලා packages activate කරලා sessions book කරන්න!</p>
      <button onclick="openAuthModal(false)">Login / Register</button>
    </div>
    <?php endif; ?>

    <!-- Live Now Panel -->
    <div class="panel animate-up delay-1" id="liveNowPanel" style="display:none;">
      <h2>
        <span class="live-dot" style="width:10px;height:10px;"></span>
        Live Now
      </h2>
      <div id="liveNowList"></div>
    </div>

    <div class="grid">
      <!-- LEFT COLUMN -->
      <div>
        <!-- Let's Have a Talk / My Booked Sessions -->
        <div class="panel animate-up delay-1" style="margin-bottom:22px;">
          <h2>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Let's Have a Talk!
          </h2>
          <?php if ($isLoggedIn && count($mySessions) > 0): ?>
            <p class="panel-subtitle"><?php echo count($mySessions); ?> teacher(s) booked</p>
            <?php
            $todayStr = date('Y-m-d');
            foreach ($mySessions as $s):
              $isToday = ($s['session_date'] === $todayStr);
              $timeFmt = date('h:i A', strtotime($s['session_time']));
              $dateFmt = date('d M Y', strtotime($s['session_date']));
              $initial = strtoupper(substr($s['lecturer_name'] ?? 'T', 0, 1));
            ?>
            <div class="session-card <?php echo $isToday ? 'is-today' : ''; ?>">
              <div class="session-icon"><?php echo $initial; ?></div>
              <div class="session-info">
                <p class="booked-with-label">Booked With</p>
                <p class="pkg"><?php echo htmlspecialchars($s['lecturer_name'] ?? 'Teacher'); ?></p>
                <div class="when">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  <?php echo $dateFmt; ?> · <?php echo $timeFmt; ?>
                  <?php if ($isToday): ?><span class="today-chip">Today</span><?php endif; ?>
                </div>
              </div>
              <?php if (!empty($s['meeting_link'])): ?>
              <button class="join-live-btn" style="padding:9px 16px;font-size:12px;" onclick="joinBookedSession(<?php echo json_encode($s['meeting_link']); ?>, <?php echo json_encode($s['lecturer_name']); ?>)">Join</button>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-state">
              <svg viewBox="0 0 200 140" fill="none">
                <rect x="40" y="30" width="120" height="80" rx="12" fill="#f3e8ff"/>
                <circle cx="100" cy="60" r="20" fill="#c4b5fd"/>
                <path d="M70 100 Q100 80 130 100" stroke="#a78bfa" stroke-width="3" fill="none" stroke-linecap="round"/>
                <circle cx="85" cy="55" r="3" fill="#7c3aed"/>
                <circle cx="115" cy="55" r="3" fill="#7c3aed"/>
              </svg>
              <p class="msg"><?php echo $isLoggedIn ? 'තවම sessions book කරලා නැහැ. Calendar එකෙන් book කරන්න!' : 'Login කරලා sessions book කරන්න.'; ?></p>
              <?php if (!$isLoggedIn): ?>
              <button class="btn-primary" id="emptyStateLoginBtn">Login / Register</button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="stats-row animate-up delay-2">
          <div class="stat-card">
            <div class="stat-icon purple">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-value"><?php echo $sessionsCompleted; ?></div>
            <div class="stat-label">Sessions Completed</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div class="stat-value"><?php echo $upcomingSessions; ?></div>
            <div class="stat-label">Upcoming Sessions</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon amber">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div class="stat-value"><?php echo $achievements; ?></div>
            <div class="stat-label">Achievements</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-value"><?php echo $hoursLearned; ?></div>
            <div class="stat-label">Hours Learned</div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Calendar -->
      <div class="panel animate-up delay-2">
        <h2>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          My Sessions
        </h2>
        <div class="cal-block">
          <div class="cal-block-title">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            Teacher Availability Calendar
          </div>
          <div class="cal-header">
            <button class="cal-nav-btn" id="calPrevBtn" aria-label="Previous month">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <span class="cal-month-label" id="calMonthLabel">August 2026</span>
            <button class="cal-nav-btn" id="calNextBtn" aria-label="Next month">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
            </button>
          </div>
          <div class="cal-grid">
            <div class="cal-dow">Su</div>
            <div class="cal-dow">Mo</div>
            <div class="cal-dow">Tu</div>
            <div class="cal-dow">We</div>
            <div class="cal-dow">Th</div>
            <div class="cal-dow">Fr</div>
            <div class="cal-dow">Sa</div>
          </div>
          <div class="cal-grid" id="calDaysGrid"></div>
          <div class="cal-slots">
            <p class="cal-slots-title" id="calSlotsTitle">Select a date</p>
            <div id="calSlotsList">
              <div class="cal-slots-empty">දවසක් select කරලා available teachers බලන්න.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- ==================== HOW TO REGISTER VIDEO MODAL ==================== -->
<div class="howto-modal-overlay" id="howtoModalOverlay" aria-hidden="true">
  <div class="howto-modal-box" role="dialog" aria-modal="true" aria-labelledby="howtoModalTitle">
    <div class="howto-modal-header">
      <h3>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polygon points="5 3 19 12 5 21 5 3"/>
        </svg>
        <span id="howtoModalTitle">How to Register – Video Guide</span>
      </h3>

      <button class="howto-modal-close" id="howtoModalCloseBtn" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="howto-modal-body" id="howtoModalBody">
      <div style="padding:40px;text-align:center;color:#94a3b8;background:#0f0c29;">
        Video එක load වෙමින්...
      </div>
    </div>

    <div class="howto-modal-footer">
      <p>Video එක බලලා Register කරන්න. ගැටලුවක් තියෙනවා නම් FAQs &amp; Support බලන්න.</p>
      <button class="btn-primary" onclick="closeHowtoModal(); openAuthModal(true);">
        Register Now
      </button>
    </div>
  </div>
</div>

<!-- ==================== LECTURER DETAIL MODAL ==================== -->
<div class="lect-modal-overlay" id="lectModalOverlay">
  <div class="lect-modal-box">
    <div class="lect-modal-header">
      <button class="lect-modal-close" id="lectModalCloseBtn" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
      <div id="lectModalPhotoWrap"></div>
    </div>
    <div class="lect-modal-body" id="lectModalBody"></div>
  </div>
</div>

<!-- ==================== MEETING MODAL ==================== -->
<div class="meeting-modal-overlay" id="meetingModalOverlay">
  <div class="meeting-modal-box">
    <div class="meeting-modal-header">
      <div class="meeting-modal-title">Live Session with <span id="meetingLecturerName">Teacher</span></div>
      <div style="display:flex;gap:8px;">
        <button class="meeting-modal-leave" id="reopenStudentMeetingBtn" style="background:#f3e8ff;color:#6b21a8;border-color:#e9d5ff;">Re-open</button>
        <button class="meeting-modal-leave" id="leaveMeetingBtn">Leave</button>
      </div>
    </div>
    <div class="meeting-modal-frame">
      <p>Meeting window opened in a new tab. If it was blocked, click Re-open.</p>
    </div>
  </div>
</div>

<!-- ==================== AUTH MODAL ==================== -->
<div class="auth-modal-overlay" id="authModalOverlay">
  <div class="gp-card" id="gpLoginCard">
    <button class="auth-modal-close" onclick="closeAuthModal()" aria-label="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="gp-head">
      <p class="gp-eyebrow">Sipway Campus</p>
      <h1>Welcome Back</h1>
      <p>Login to continue your learning journey</p>
    </div>
    <form id="gpLoginForm" novalidate>
      <div class="gp-field">
        <label for="gpLoginEmail">Email</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 6l8 6 8-6"/></svg></span>
          <input type="email" id="gpLoginEmail" placeholder="you@example.com" autocomplete="email">
        </div>
        <div class="gp-error" id="gpLoginEmailErr">⚠ Valid email එකක් දෙන්න</div>
      </div>
      <div class="gp-field">
        <label for="gpLoginPass">Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpLoginPass" placeholder="••••••••" autocomplete="current-password">
          <button type="button" class="gp-toggle-pass" data-target="gpLoginPass" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-error" id="gpLoginPassErr">⚠ Password එක ඇතුළත් කරන්න</div>
      </div>
      <div class="gp-row-inline">
        <label class="gp-checkbox-label"><input type="checkbox"> Remember me</label>
       
      </div>
      <button type="submit" class="gp-btn-primary" id="gpLoginBtn">
        <span class="gp-spinner"></span>
        <span class="gp-btn-text">Login</span>
      </button>
    </form>
    <div class="gp-switch-row">Don't have an account? <a id="gpGoRegister">Register</a></div>
  </div>

  <div class="gp-card gp-hidden" id="gpRegisterCard">
    <button class="auth-modal-close" onclick="closeAuthModal()" aria-label="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="gp-head">
      <p class="gp-eyebrow">Sipway Campus</p>
      <h1>Create Account</h1>
      <p>Join and start learning with expert teachers</p>
    </div>
    <form id="gpRegisterForm" novalidate enctype="multipart/form-data">
      <div class="gp-field">
        <label for="gpRegName">Full Name</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <input type="text" id="gpRegName" placeholder="Your full name">
        </div>
        <div class="gp-error" id="gpRegNameErr">⚠ Name එක ඇතුළත් කරන්න</div>
      </div>
      <div class="gp-field">
        <label for="gpRegEmail">Email</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 6l8 6 8-6"/></svg></span>
          <input type="email" id="gpRegEmail" placeholder="you@example.com">
        </div>
        <div class="gp-error" id="gpRegEmailErr">⚠ Valid email එකක් දෙන්න</div>
      </div>
      <div class="gp-field">
        <label for="gpRegMobile">Mobile</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
          <input type="tel" id="gpRegMobile" placeholder="07XXXXXXXX" maxlength="10">
        </div>
        <div class="gp-error" id="gpRegMobileErr">⚠ 10 digit mobile number එකක් දෙන්න (0XXXXXXXXX)</div>
      </div>
      <div class="gp-field">
        <label>What language do you want to practise?</label>
        <div class="gp-lang-select" id="gpLangSelect">
          <div class="gp-lang-trigger" id="gpLangTrigger" tabindex="0" role="combobox" aria-expanded="false">
            <span class="gp-input-icon" style="left:14px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
            <span class="gp-lang-current" id="gpLangCurrent">Loading...</span>
            <svg class="gp-lang-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
          </div>
          <ul class="gp-lang-options" id="gpLangOptions" role="listbox"></ul>
          <input type="hidden" id="gpRegLanguage" value="en">
        </div>
      </div>
      <div class="gp-field">
        <label for="gpRegPhoto">Profile Photo (optional)</label>
        <input type="file" id="gpRegPhoto" accept="image/*" style="padding-left:14px;">
      </div>
      <div class="gp-field">
        <label for="gpRegPass">Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpRegPass" placeholder="Min 6 characters">
          <button type="button" class="gp-toggle-pass" data-target="gpRegPass" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-strength-meter" id="gpStrengthMeter"><span></span><span></span><span></span><span></span></div>
        <div class="gp-strength-label" id="gpStrengthLabel">Minimum 6 characters</div>
        <div class="gp-error" id="gpRegPassErr">⚠ Password අවම වශයෙන් අක්ෂර 6ක් විය යුතුයි</div>
      </div>
      <div class="gp-field">
        <label for="gpRegPass2">Confirm Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpRegPass2" placeholder="Repeat password">
          <button type="button" class="gp-toggle-pass" data-target="gpRegPass2" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-error" id="gpRegPass2Err">⚠ Passwords ගැලපෙන්නේ නැහැ</div>
      </div>
      <button type="submit" class="gp-btn-primary" id="gpRegisterBtn">
        <span class="gp-spinner"></span>
        <span class="gp-btn-text">Create Account</span>
      </button>
    </form>
    <div class="gp-switch-row">Already have an account? <a id="gpGoLogin">Login</a></div>
  </div>
</div>

<div class="gp-toast" id="gpToast"></div>

<button class="chatbot-toggle" id="chatbotToggle" aria-label="Open chat">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
  <span class="chat-badge" id="chatBadge">1</span>
</button>

<div class="chatbot-window" id="chatbotWindow">
  <div class="chatbot-header">
    <div class="chatbot-header-avatar">🤖</div>
    <div class="chatbot-header-info">
      <h3>Sipway Assistant</h3>
      <p>Always here to help • Free</p>
    </div>
    <button class="chatbot-close" id="chatbotClose" aria-label="Close chat">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>
  <div class="chatbot-messages" id="chatbotMessages"></div>
  <div class="chatbot-input-area">
    <input type="text" class="chatbot-input" id="chatbotInput" placeholder="Type your question..." autocomplete="off">
    <button class="chatbot-send" id="chatbotSend" aria-label="Send">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
    </button>
  </div>
</div>

<script>
  const IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  const STUDENT_NAME = <?php echo $studentNameJs; ?>;
  const STUDENT_LANGUAGE = <?php echo $studentLanguageJs; ?>;
  const HAS_ACTIVE_PACKAGE = <?php echo json_encode($hasActivePackage); ?>;
  const ACTIVE_PACKAGE_SUBJECT = <?php echo $activePackageSubjectJs; ?>;
  const ACTIVE_PACKAGE_TYPE = <?php echo isset($activePackageTypeJs) ? $activePackageTypeJs : 'null'; ?>; // 'individual' | 'group' | null
  const BOOKED_SESSIONS = <?php echo $bookedSessionsJs; ?>;
  const _nowLocal = new Date();
  const TODAY_STR = _nowLocal.getFullYear() + '-' +
    String(_nowLocal.getMonth() + 1).padStart(2, '0') + '-' +
    String(_nowLocal.getDate()).padStart(2, '0');

  const authOverlay = document.getElementById('authModalOverlay');
  const loginCard = document.getElementById('gpLoginCard');
  const registerCard = document.getElementById('gpRegisterCard');
  const gpToast = document.getElementById('gpToast');

  function openAuthModal(isRegister) {
    authOverlay.classList.add('show');
    if (isRegister) {
      loginCard.classList.add('gp-hidden');
      registerCard.classList.remove('gp-hidden');
    } else {
      registerCard.classList.add('gp-hidden');
      loginCard.classList.remove('gp-hidden');
    }
  }
  function closeAuthModal() { authOverlay.classList.remove('show'); }
  function requireAuth() {
    if (!IS_LOGGED_IN) { openAuthModal(false); return false; }
    return true;
  }
  window.requireAuth = requireAuth;
  window.openAuthModal = openAuthModal;
  window.closeAuthModal = closeAuthModal;

  authOverlay.addEventListener('click', (e) => { if (e.target === authOverlay) closeAuthModal(); });

  const emptyStateLoginBtn = document.getElementById('emptyStateLoginBtn');
  if (emptyStateLoginBtn) emptyStateLoginBtn.addEventListener('click', () => openAuthModal(false));

  document.querySelectorAll('[data-requires-auth="1"]').forEach(link => {
    link.addEventListener('click', function(e){
      if (!IS_LOGGED_IN) { e.preventDefault(); openAuthModal(false); }
    });
  });

  const REGISTER_VIDEO = <?php echo $registerVideoJs ?: 'null'; ?>;

  const howtoOverlay = document.getElementById('howtoModalOverlay');
  const howtoBody    = document.getElementById('howtoModalBody');
  const howtoTitle   = document.getElementById('howtoModalTitle');
  const howToRegisterBtn = document.getElementById('howToRegisterBtn');
  const howtoCloseBtn = document.getElementById('howtoModalCloseBtn');

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function openHowtoModal() {
    if (!REGISTER_VIDEO || !REGISTER_VIDEO.player_src) {
      alert('Register guide video තවම upload කරලා නැහැ. Admin ට කියන්න.');
      return;
    }
    howtoTitle.textContent = REGISTER_VIDEO.title || 'How to Register – Video Guide';
    const type = REGISTER_VIDEO.player_type;
    const src  = REGISTER_VIDEO.player_src;

    if (type === 'youtube' || type === 'vimeo') {
      const autoplaySrc = src + (src.includes('?') ? '&' : '?') + 'autoplay=1';
      howtoBody.innerHTML =
        `<iframe src="${escapeHtml(autoplaySrc)}"
                 allow="autoplay; fullscreen; picture-in-picture"
                 allowfullscreen
                 title="${escapeHtml(REGISTER_VIDEO.title || 'How to Register Video')}"></iframe>`;
    } else {
      const safeSrc = escapeHtml(src);
      howtoBody.innerHTML =
        `<video id="howtoVideoPlayer" controls playsinline autoplay preload="metadata"
                style="width:100%;max-height:70vh;display:block;background:#000;">
           <source src="${safeSrc}">
           Your browser does not support the video tag.
         </video>`;
      const player = document.getElementById('howtoVideoPlayer');
      if (player) player.play().catch(() => {});
    }
    howtoOverlay.classList.add('show');
    howtoOverlay.setAttribute('aria-hidden', 'false');
  }

  function closeHowtoModal() {
    howtoOverlay.classList.remove('show');
    howtoOverlay.setAttribute('aria-hidden', 'true');
    const v = document.getElementById('howtoVideoPlayer');
    if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
    howtoBody.innerHTML = '';
  }

  window.openHowtoModal = openHowtoModal;
  window.closeHowtoModal = closeHowtoModal;

  if (howToRegisterBtn) {
    howToRegisterBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openHowtoModal();
    });
  }
  if (howtoCloseBtn) howtoCloseBtn.addEventListener('click', closeHowtoModal);
  if (howtoOverlay) {
    howtoOverlay.addEventListener('click', function(e) {
      if (e.target === howtoOverlay) closeHowtoModal();
    });
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && howtoOverlay && howtoOverlay.classList.contains('show')) {
      closeHowtoModal();
    }
  });

  (function(){
    document.getElementById('gpGoRegister').addEventListener('click', () => openAuthModal(true));
    document.getElementById('gpGoLogin').addEventListener('click', () => openAuthModal(false));

    function showToast(msg, isError){
      gpToast.textContent = (isError ? '⚠ ' : '✓ ') + msg;
      gpToast.classList.toggle('gp-error-toast', !!isError);
      gpToast.classList.add('show');
      clearTimeout(showToast._t);
      showToast._t = setTimeout(()=> gpToast.classList.remove('show'), 2600);
    }
    function isValidEmail(v){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); }
    function isValidMobile(v){ return /^0\d{9}$/.test(v.trim()); }
    function setFieldState(input, errEl, valid){
      input.classList.toggle('gp-invalid', !valid);
      input.classList.toggle('gp-valid', valid);
      errEl.classList.toggle('show', !valid);
    }

    document.querySelectorAll('.gp-toggle-pass').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        const isPass = target.type === 'password';
        target.type = isPass ? 'text' : 'password';
        btn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
      });
    });

    const langSelect = document.getElementById('gpLangSelect');
    const langTrigger = document.getElementById('gpLangTrigger');
    const langOptions = document.getElementById('gpLangOptions');
    const langCurrent = document.getElementById('gpLangCurrent');
    const regLanguage = document.getElementById('gpRegLanguage');

    function normalizeCode(code) { return (code || '').toString().trim().toLowerCase(); }

    const FLAG_SVGS = {
      GB: '<rect width="60" height="40" fill="#012169"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#fff" stroke-width="10"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#C8102E" stroke-width="6"/><path d="M30,0 V40 M0,20 H60" stroke="#fff" stroke-width="16"/><path d="M30,0 V40 M0,20 H60" stroke="#C8102E" stroke-width="10"/>',
      DE: '<rect width="60" height="13.34" y="0" fill="#000"/><rect width="60" height="13.33" y="13.33" fill="#DD0000"/><rect width="60" height="13.33" y="26.67" fill="#FFCE00"/>',
      FR: '<rect width="20" height="40" x="0" fill="#002395"/><rect width="20" height="40" x="20" fill="#FFF"/><rect width="20" height="40" x="40" fill="#ED2939"/>',
      CN: '<rect width="60" height="40" fill="#DE2910"/><polygon points="10,6 11.8,11.5 17.5,11.5 12.8,14.8 14.5,20.2 10,17 5.5,20.2 7.2,14.8 2.5,11.5 8.2,11.5" fill="#FFDE00"/>',
      JP: '<rect width="60" height="40" fill="#FFF"/><circle cx="30" cy="20" r="12" fill="#BC002D"/>',
      IN: '<rect width="60" height="13.34" y="0" fill="#FF9933"/><rect width="60" height="13.33" y="13.33" fill="#FFF"/><rect width="60" height="13.33" y="26.67" fill="#138808"/><circle cx="30" cy="20" r="4.5" fill="none" stroke="#000080" stroke-width="1"/><circle cx="30" cy="20" r="1" fill="#000080"/>',
      RU: '<rect width="60" height="13.34" y="0" fill="#FFF"/><rect width="60" height="13.33" y="13.33" fill="#0039A6"/><rect width="60" height="13.33" y="26.67" fill="#D52B1E"/>',
      SA: '<rect width="60" height="40" fill="#006C35"/>',
      LK: '<rect width="60" height="40" fill="#FFB714"/><rect x="0" y="0" width="10" height="40" fill="#8D153A"/><rect x="10" y="0" width="8" height="40" fill="#00534E"/><rect x="20" y="4" width="36" height="32" fill="#8D153A"/>',
      IT: '<rect width="20" height="40" x="0" fill="#009246"/><rect width="20" height="40" x="20" fill="#FFF"/><rect width="20" height="40" x="40" fill="#CE2B37"/>',
      DEFAULT: '<rect width="60" height="40" fill="#e6e2da"/><circle cx="30" cy="20" r="12" fill="none" stroke="#8a93a3" stroke-width="2"/>'
    };
    const LANG_TO_FLAG = {
      en:'GB', zh:'CN', ja:'JP', fr:'FR', hi:'IN', ru:'RU', ar:'SA', ta:'IN', si:'LK', de:'DE', it:'IT'
    };

function resolveFlagCode(lang) {
  let code = (lang.flag || lang.flag_code || '').toString().trim().toUpperCase();
  if (code && FLAG_SVGS[code]) return code;
  const langCode = normalizeCode(lang.code);
  if (LANG_TO_FLAG[langCode] && FLAG_SVGS[LANG_TO_FLAG[langCode]]) return LANG_TO_FLAG[langCode];
  return 'DEFAULT';
}

function buildLangOption(lang, selectDefault) {
  const flagCode  = resolveFlagCode(lang);
  const codeNorm  = normalizeCode(lang.code);
  const isEnabled = lang.is_enabled === true || lang.is_enabled === 1;

  const li = document.createElement('li');
  li.className = 'gp-lang-option' + (selectDefault ? ' selected' : '') + (isEnabled ? ' enabled' : ' disabled');
  li.setAttribute('role', 'option');
  li.setAttribute('aria-selected', selectDefault ? 'true' : 'false');
  li.setAttribute('aria-disabled', isEnabled ? 'false' : 'true');
  li.dataset.value   = codeNorm;
  li.dataset.flag    = flagCode;
  li.dataset.label   = lang.label;
  li.dataset.enabled = isEnabled ? '1' : '0';

  li.innerHTML =
    '<span class="gp-lang-option-left">' +
      flagImgHtml(flagCode) +
      '<span>' + lang.label + (isEnabled ? '' : ' (disabled)') + '</span>' +
    '</span>' +
    '<svg class="gp-lang-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';

  if (isEnabled) {
    li.addEventListener('click', () => {
      selectLangOption(li);
      closeLangDropdown();
    });
  }

  return li;
}

function loadLanguages() {
  fetch('get_languages.php')
    .then(res => res.json())
    .then(data => {
      langOptions.innerHTML = '';

      if (!data.success || !data.languages || data.languages.length === 0) {
        langOptions.innerHTML = '<li class="gp-lang-option-loading">No languages found.</li>';
        langCurrent.innerHTML = flagImgHtml('GB') + '<span>English</span>';
        regLanguage.value = 'en';
        return;
      }

      const enabledList = data.languages.filter(l => l.is_enabled === true || l.is_enabled === 1);
      const defaultLang = enabledList.find(l => normalizeCode(l.code) === 'en')
                       || enabledList[0]
                       || data.languages[0];

      data.languages.forEach(lang => {
        const isDefault = normalizeCode(lang.code) === normalizeCode(defaultLang.code);
        langOptions.appendChild(buildLangOption(lang, isDefault));
      });

      langCurrent.innerHTML = flagImgHtml(resolveFlagCode(defaultLang)) + '<span>' + defaultLang.label + '</span>';
      regLanguage.value = normalizeCode(defaultLang.code);
    })
    .catch(() => {
      langOptions.innerHTML = '<li class="gp-lang-option-loading">Could not load languages.</li>';
      langCurrent.innerHTML = flagImgHtml('GB') + '<span>English</span>';
      regLanguage.value = 'en';
    });
}
    function flagImgHtml(code) {
      const key = (code || 'DEFAULT').toUpperCase().trim();
      const inner = FLAG_SVGS[key] || FLAG_SVGS.DEFAULT;
      return '<svg class="gp-lang-flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" width="22" height="16">' + inner + '</svg>';
    }
    function openLangDropdown(){ langOptions.classList.add('open'); langTrigger.classList.add('open'); langTrigger.setAttribute('aria-expanded','true'); }
    function closeLangDropdown(){ langOptions.classList.remove('open'); langTrigger.classList.remove('open'); langTrigger.setAttribute('aria-expanded','false'); }
    langTrigger.addEventListener('click', (e) => { e.stopPropagation(); langOptions.classList.contains('open') ? closeLangDropdown() : openLangDropdown(); });
    document.addEventListener('click', (e) => { if (!langSelect.contains(e.target)) closeLangDropdown(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLangDropdown(); });

      function selectLangOption(li){
      langOptions.querySelectorAll('.gp-lang-option').forEach(o => {
        o.classList.remove('selected');
        o.setAttribute('aria-selected', 'false');
      });
      li.classList.add('selected');
      li.setAttribute('aria-selected', 'true');
      langCurrent.innerHTML = flagImgHtml(li.dataset.flag) + '<span>' + li.dataset.label + '</span>';
      regLanguage.value = normalizeCode(li.dataset.value);
    }

    function buildLangOption(lang, selectDefault){
      const flagCode = resolveFlagCode(lang);
      const codeNorm = normalizeCode(lang.code);

      const li = document.createElement('li');
      li.className = 'gp-lang-option' + (selectDefault ? ' selected' : '');
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', selectDefault ? 'true' : 'false');
      li.dataset.value = codeNorm;
      li.dataset.flag  = flagCode;
      li.dataset.label = lang.label;

      li.innerHTML =
        '<span class="gp-lang-option-left">' +
          flagImgHtml(flagCode) +
          '<span>' + lang.label + '</span>' +
        '</span>' +
        '<svg class="gp-lang-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';

      li.addEventListener('click', () => {
        selectLangOption(li);
        closeLangDropdown();
      });

      return li;
    }

    function loadLanguages(){
      fetch('get_languages.php')
        .then(res => res.json())
        .then(data => {
          langOptions.innerHTML = '';

          if (!data.success || !data.languages || data.languages.length === 0) {
            langOptions.innerHTML = '<li class="gp-lang-option-loading">No languages found.</li>';
            langCurrent.innerHTML = flagImgHtml('GB') + '<span>English</span>';
            regLanguage.value = 'en';
            return;
          }

          const defaultLang =
            data.languages.find(l => normalizeCode(l.code) === 'en') ||
            data.languages[0];

          data.languages.forEach(lang => {
            const isDefault = normalizeCode(lang.code) === normalizeCode(defaultLang.code);
            langOptions.appendChild(buildLangOption(lang, isDefault));
          });

          langCurrent.innerHTML = flagImgHtml(resolveFlagCode(defaultLang)) +
                                  '<span>' + defaultLang.label + '</span>';
          regLanguage.value = normalizeCode(defaultLang.code);
        })
        .catch(() => {
          langOptions.innerHTML = '<li class="gp-lang-option-loading">Could not load languages.</li>';
          langCurrent.innerHTML = flagImgHtml('GB') + '<span>English</span>';
          regLanguage.value = 'en';
        });
    }
    loadLanguages();

    const loginEmail = document.getElementById('gpLoginEmail');
    const loginPass = document.getElementById('gpLoginPass');
    loginEmail.addEventListener('input', () => { if (loginEmail.value.length) setFieldState(loginEmail, document.getElementById('gpLoginEmailErr'), isValidEmail(loginEmail.value)); });
    loginPass.addEventListener('input', () => { if (loginPass.value.length) setFieldState(loginPass, document.getElementById('gpLoginPassErr'), loginPass.value.length > 0); });

    const loginForm = document.getElementById('gpLoginForm');
    const loginBtn = document.getElementById('gpLoginBtn');
    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      let ok = true;
      const emailOk = isValidEmail(loginEmail.value);
      setFieldState(loginEmail, document.getElementById('gpLoginEmailErr'), emailOk); if (!emailOk) ok = false;
      const passOk = loginPass.value.length > 0;
      setFieldState(loginPass, document.getElementById('gpLoginPassErr'), passOk); if (!passOk) ok = false;
      if (!ok) return;
      loginBtn.classList.add('loading'); loginBtn.disabled = true;
      try {
        const res = await fetch('login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: loginEmail.value.trim(), password: loginPass.value })
        });
        const result = await res.json();
        if (result.success) {
          showToast(result.message || 'Login successful! Welcome back.');
          setTimeout(() => { window.location.href = 'index.php'; }, 600);
        } else {
          showToast(result.message || 'Invalid email or password.', true);
        }
      } catch (err) {
        showToast('Could not connect to the server. Please try again.', true);
      } finally {
        loginBtn.classList.remove('loading'); loginBtn.disabled = false;
      }
    });

    const regName = document.getElementById('gpRegName');
    const regEmail = document.getElementById('gpRegEmail');
    const regMobile = document.getElementById('gpRegMobile');
    const regPass = document.getElementById('gpRegPass');
    const regPass2 = document.getElementById('gpRegPass2');
    regMobile.addEventListener('input', () => {
      regMobile.value = regMobile.value.replace(/\D/g, '').slice(0,10);
      if (regMobile.value.length) setFieldState(regMobile, document.getElementById('gpRegMobileErr'), isValidMobile(regMobile.value));
    });
    regName.addEventListener('input', () => { if (regName.value.length) setFieldState(regName, document.getElementById('gpRegNameErr'), regName.value.trim().length > 0); });
    regEmail.addEventListener('input', () => { if (regEmail.value.length) setFieldState(regEmail, document.getElementById('gpRegEmailErr'), isValidEmail(regEmail.value)); });

    const strengthBars = document.querySelectorAll('#gpStrengthMeter span');
    const strengthLabel = document.getElementById('gpStrengthLabel');
    const strengthColors = ['#ef4444', '#f59e0b', '#eab308', '#10b981'];
    const strengthText = ['Weak', 'Fair', 'Good', 'Strong'];
    function scorePassword(v){
      let score = 0;
      if (v.length >= 6) score++;
      if (v.length >= 10) score++;
      if (/[A-Z]/.test(v) && /[0-9]/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      return Math.min(score, 4);
    }
    regPass.addEventListener('input', () => {
      const score = regPass.value.length ? Math.max(1, scorePassword(regPass.value)) : 0;
      strengthBars.forEach((bar, i) => { bar.style.background = i < score ? strengthColors[score-1] : 'var(--line)'; });
      strengthLabel.textContent = regPass.value.length === 0 ? 'Minimum 6 characters' : strengthText[Math.max(0, score-1)];
      if (regPass.value.length) setFieldState(regPass, document.getElementById('gpRegPassErr'), regPass.value.length >= 6);
      if (regPass2.value.length) setFieldState(regPass2, document.getElementById('gpRegPass2Err'), regPass2.value === regPass.value);
    });
    regPass2.addEventListener('input', () => {
      if (regPass2.value.length) setFieldState(regPass2, document.getElementById('gpRegPass2Err'), regPass2.value === regPass.value && regPass2.value.length > 0);
    });

    const registerForm = document.getElementById('gpRegisterForm');
    const registerBtn = document.getElementById('gpRegisterBtn');
    registerForm.addEventListener('submit', async function(e){
      e.preventDefault();
      let ok = true;
      const nameOk = regName.value.trim().length > 0;
      setFieldState(regName, document.getElementById('gpRegNameErr'), nameOk); if (!nameOk) ok = false;
      const emailOk = isValidEmail(regEmail.value);
      setFieldState(regEmail, document.getElementById('gpRegEmailErr'), emailOk); if (!emailOk) ok = false;
      const mobileOk = isValidMobile(regMobile.value);
      setFieldState(regMobile, document.getElementById('gpRegMobileErr'), mobileOk); if (!mobileOk) ok = false;
      const passOk = regPass.value.length >= 6;
      setFieldState(regPass, document.getElementById('gpRegPassErr'), passOk); if (!passOk) ok = false;
      const pass2Ok = regPass2.value === regPass.value && regPass2.value.length > 0;
      setFieldState(regPass2, document.getElementById('gpRegPass2Err'), pass2Ok); if (!pass2Ok) ok = false;
      if (!ok) { showToast('Please fix the highlighted fields.', true); return; }

      registerBtn.classList.add('loading'); registerBtn.disabled = true;
      const fd = new FormData();
      fd.append('fullName', regName.value.trim());
      fd.append('email', regEmail.value.trim());
      fd.append('mobile', regMobile.value.trim());
      fd.append('language', normalizeCode(regLanguage.value));
      fd.append('password', regPass.value);
      const photoFile = document.getElementById('gpRegPhoto').files[0];
      if (photoFile) fd.append('profilePhoto', photoFile);
      try {
        const res = await fetch('register.php', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.success) {
          showToast(result.message || 'Account created! You can now log in.');
          registerForm.reset();
          [regName, regEmail, regMobile, regPass, regPass2].forEach(i => i.classList.remove('gp-valid','gp-invalid'));
          strengthBars.forEach(bar => bar.style.background = 'var(--line)');
          strengthLabel.textContent = 'Minimum 6 characters';
          loadLanguages();
          setTimeout(() => openAuthModal(false), 900);
        } else if (result.errors) {
          const map = { fullName: [regName,'gpRegNameErr'], email:[regEmail,'gpRegEmailErr'], mobile:[regMobile,'gpRegMobileErr'], password:[regPass,'gpRegPassErr'] };
          Object.keys(result.errors).forEach(key => {
            if (map[key]) {
              const [input, errId] = map[key];
              document.getElementById(errId).textContent = '⚠ ' + result.errors[key];
              setFieldState(input, document.getElementById(errId), false);
            }
          });
          showToast('Please fix the highlighted fields.', true);
        } else {
          showToast(result.message || 'Could not create account.', true);
        }
      } catch (err) {
        showToast('Could not connect to the server. Please try again.', true);
      } finally {
        registerBtn.classList.remove('loading'); registerBtn.disabled = false;
      }
    });
  })();

  const LANG_MAP = {
    en:{flag:'https://flagcdn.com/w20/gb.png', label:'English'},
    de:{flag:'https://flagcdn.com/w20/de.png', label:'German'},
    zh:{flag:'https://flagcdn.com/w20/cn.png', label:'Chinese'},
    ja:{flag:'https://flagcdn.com/w20/jp.png', label:'Japanese'},
    fr:{flag:'https://flagcdn.com/w20/fr.png', label:'French'},
    hi:{flag:'https://flagcdn.com/w20/in.png', label:'Hindi'},
    ru:{flag:'https://flagcdn.com/w20/ru.png', label:'Russian'},
    ar:{flag:'https://flagcdn.com/w20/sa.png', label:'Arabic'},
    ta:{flag:'https://flagcdn.com/w20/in.png', label:'Tamil'},
    si:{flag:'https://flagcdn.com/w20/lk.png', label:'Sinhala'},
    it:{flag:'https://flagcdn.com/w20/it.png', label:'Italian'},
  };
  function langBadgeSmall(code){
    const info = LANG_MAP[code] || {flag:'https://flagcdn.com/w20/un.png', label: code || 'English'};
    return `<span style="display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:999px;background:var(--purple-soft);color:#6b21a8;margin-left:4px;"><img src="${info.flag}" alt="${info.label}" width="16" height="12" style="border-radius:2px;object-fit:cover;"> ${info.label}</span>`;
  }
  function matchesStudentLanguage(item){
    if (STUDENT_LANGUAGE === null || STUDENT_LANGUAGE === undefined || STUDENT_LANGUAGE === '') return true;
    const itemLang = (item.language || 'en').toString().trim().toLowerCase();
    const studentLang = String(STUDENT_LANGUAGE).trim().toLowerCase();
    if (itemLang === studentLang) return true;
    if (!item.language || String(item.language).trim() === '') return true;
    return false;
  }

  function normalizeSubject(s){
    return String(s || '').trim().toLowerCase().replace(/\s+/g, ' ');
  }
  function matchesActivePackageSubject(item){
    if (!HAS_ACTIVE_PACKAGE || !ACTIVE_PACKAGE_SUBJECT) return true;
    const itemSubject = normalizeSubject(item.subject);
    const pkgSubject  = normalizeSubject(ACTIVE_PACKAGE_SUBJECT);
    if (!itemSubject) return false;
    if (itemSubject === pkgSubject) return true;
    if (itemSubject.includes(pkgSubject) || pkgSubject.includes(itemSubject)) return true;
    return false;
  }

  function normalizeTimeHHMM(t){
    if (!t) return '';
    t = String(t).trim();
    const m = t.match(/^(\d{1,2}):(\d{2})/);
    if (m) return String(m[1]).padStart(2, '0') + ':' + m[2];
    return t;
  }
  function isSlotBooked(lecturerId, dateStr, startTime){
    if (!IS_LOGGED_IN) return false;
    const lecId = Number(lecturerId);
    const startNorm = normalizeTimeHHMM(startTime);
    return BOOKED_SESSIONS.some(b => b.lecturer_id === lecId && b.date === dateStr && b.time === startNorm);
  }
  window.isSlotBooked = isSlotBooked;

  function canJoinLive(lecturerId, dateStr, startTime, isFree = 0){
    if (Number(isFree) === 1) return true;
    return isSlotBooked(lecturerId, dateStr, startTime);
  }
  window.canJoinLive = canJoinLive;

  // ★ Capacity badge — Open until first booking, then Group / Individual
  function buildCapBadge(s, small) {
    const bookedCount = Number(s.booked_count) || 0;
    const rawType     = String(s.session_type || 'open').toLowerCase();
    const sessionType = (rawType === 'group' || rawType === 'individual') ? rawType : 'open';
    const maxCapacity = Number(s.max_capacity) || (sessionType === 'group' ? 10 : (sessionType === 'individual' ? 1 : 10));

    const pad = small
      ? 'padding:2px 9px;font-size:10.5px;margin-left:6px;'
      : 'padding:4px 10px;font-size:11px;margin-right:6px;';

    if (sessionType === 'open' || bookedCount === 0) {
      return `<span style="display:inline-flex;align-items:center;gap:4px;font-weight:800;border-radius:999px;background:#f3f4f6;color:#4b5563;${pad}">🟢 Slot Available</span>`;
    }
    const capLabel = sessionType === 'group' ? 'Group' : 'Individual';
    return `<span style="display:inline-flex;align-items:center;gap:4px;font-weight:800;border-radius:999px;background:#ede9fe;color:#5b21b6;${pad}">👥 ${capLabel} ${bookedCount}/${maxCapacity}</span>`;
  }

  // Sidebar mobile
  const sidebar = document.getElementById('sidebar');
  const burgerBtn = document.getElementById('burgerBtn');
  const backdrop = document.getElementById('backdrop');
  function openSidebar(){ sidebar.classList.add('open'); backdrop.classList.add('show'); }
  function closeSidebar(){ sidebar.classList.remove('open'); backdrop.classList.remove('show'); }
  burgerBtn.addEventListener('click', () => { sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
  backdrop.addEventListener('click', closeSidebar);

  // User dropdown
  const userMenu = document.getElementById('userMenu');
  if (userMenu) {
    const userDropdown = document.getElementById('userDropdown');
    userMenu.addEventListener('click', (e) => { userDropdown.classList.toggle('show'); e.stopPropagation(); });
    document.addEventListener('click', () => userDropdown.classList.remove('show'));
  }

  function bookSlot(lecturerId, lecturerName, date, start, end, isFree = 0, slotId = 0){
    if (!requireAuth()) return;
    if (!HAS_ACTIVE_PACKAGE && Number(isFree) !== 1) {
      alert('Package එකක් active නැති නිසා session එකක් book කරන්න බැහැ. කරුණාකර මුලින් package එකක් activate කරන්න.');
      window.location.href = 'index.php';
      return;
    }
    const params = new URLSearchParams({
      lecturer_id: lecturerId,
      lecturer_name: lecturerName,
      date: date,
      start: start,
      end: end,
      is_free: isFree
    });
    if (slotId && Number(slotId) > 0) {
      params.set('availability_id', slotId);
    }
    window.location.href = `book-session.php?${params.toString()}`;
  }
  window.bookSlot = bookSlot;

  // Lecturer Modal
  const lectModalOverlay = document.getElementById('lectModalOverlay');
  const lectModalPhotoWrap = document.getElementById('lectModalPhotoWrap');
  const lectModalBody = document.getElementById('lectModalBody');
  const lectModalCloseBtn = document.getElementById('lectModalCloseBtn');
  let currentModalSlotContext = null;

  function closeLectModal(){ lectModalOverlay.classList.remove('show'); }
  lectModalCloseBtn.addEventListener('click', closeLectModal);
  lectModalOverlay.addEventListener('click', (e) => { if (e.target === lectModalOverlay) closeLectModal(); });

  function formatQualificationsHtml(raw){
    if (!raw || !String(raw).trim()) return '<p class="lect-modal-qual-empty">Not provided yet</p>';
    const original = String(raw).replace(/\r\n/g, '\n').trim();
    let lines = original.split('\n').map(l => l.trim()).filter(Boolean);
    if (lines.length <= 1) lines = original.split(/\s\*\s+/).map(l => l.trim()).filter(Boolean);
    const parsed = lines.map(line => {
      const isBullet = /^\*\s+/.test(line) || /^[-•]\s+/.test(line);
      let clean = line.replace(/^\*\s+/, '').replace(/^[-•]\s+/, '');
      clean = escapeHtml(clean);
      clean = clean.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
      clean = clean.replace(/\*(.+?)\*/g, '<strong>$1</strong>');
      return { isBullet, html: clean };
    });
    const allBullets = parsed.every(p => p.isBullet) || parsed.filter(p => p.isBullet).length >= 2;
    if (allBullets || parsed.some(p => p.isBullet)) {
      let html = '<ul class="lect-modal-qual-list">';
      parsed.forEach(({ html: h }) => { html += `<li class="lect-modal-qual-item"><span class="qual-bullet"></span><span>${h}</span></li>`; });
      html += '</ul>';
      return html;
    }
    return parsed.map(p => `<p class="lect-modal-qual-para">${p.html}</p>`).join('');
  }

  async function openLecturerDetail(lecturerId, slotContext){
    currentModalSlotContext = slotContext || null;
    lectModalOverlay.classList.add('show');
    lectModalPhotoWrap.innerHTML = '';
    lectModalBody.innerHTML = `<div class="lect-modal-loading">Loading...</div>`;
    try {
      const res = await fetch(`get_lecturer_details.php?id=${lecturerId}`);
      const data = await res.json();
      if (!data.success) {
        lectModalBody.innerHTML = `<div class="lect-modal-loading">${data.message || 'Details load වුණේ නෑ'}</div>`;
        return;
      }
      const l = data.data;
      const initials = (l.full_name || '?').charAt(0).toUpperCase();
      if (l.photo) {
        lectModalPhotoWrap.innerHTML = `<img class="lect-modal-photo" src="${l.photo}" alt="${escapeHtml(l.full_name)}" onerror="this.outerHTML='<div class=&quot;lect-modal-photo-fallback&quot;>${initials}</div>'">`;
      } else {
        lectModalPhotoWrap.innerHTML = `<div class="lect-modal-photo-fallback">${initials}</div>`;
      }
      let bookBtnHtml = '';
      if (currentModalSlotContext) {
        const isFreeSlot = Number(currentModalSlotContext.is_free) === 1;
        const slotId     = currentModalSlotContext.slot_id || 0;
        if (!IS_LOGGED_IN) {
          bookBtnHtml = `<button class="lect-modal-book-btn" onclick="requireAuth()">🔐 Login to Book This Slot</button>`;
        } else if (HAS_ACTIVE_PACKAGE || isFreeSlot) {
          bookBtnHtml = `<button class="lect-modal-book-btn" onclick="bookSlot(${lecturerId}, '${String(l.full_name || '').replace(/'/g, "\\'")}', '${currentModalSlotContext.date}', '${currentModalSlotContext.start}', '${currentModalSlotContext.end}', ${isFreeSlot ? 1 : 0}, ${slotId})"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> Book This Slot</button>`;
        } else {
          bookBtnHtml = `<button class="lect-modal-book-btn" onclick="window.location.href='packages.php'">📦 Activate Package to Book</button>`;
        }
      }
      const qualHtml = formatQualificationsHtml(l.qualifications);
      lectModalBody.innerHTML = `
        <p class="lect-modal-name">${escapeHtml(l.full_name || '')}</p>
        <span class="lect-modal-subject">🎓 ${escapeHtml(l.subject || 'General')}</span>
        <div class="lect-modal-info-grid">
          <div class="lect-modal-card">
            <div class="lect-modal-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 6l8 6 8-6"/></svg></div>
            <div>
              <p class="lect-modal-label">Email</p>
              <p class="lect-modal-value ${l.email ? '' : 'muted'}">${l.email ? escapeHtml(l.email) : 'Not provided'}</p>
            </div>
          </div>
          <div class="lect-modal-card">
            <div class="lect-modal-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/></svg></div>
            <div style="flex:1; min-width:0;">
              <p class="lect-modal-label">Qualifications</p>
              <div class="lect-modal-qual-wrap">${qualHtml}</div>
            </div>
          </div>
        </div>
        ${bookBtnHtml}
      `;
    } catch (err) {
      lectModalBody.innerHTML = `<div class="lect-modal-loading">Server connect වුණේ නෑ.</div>`;
    }
  }
  window.openLecturerDetail = openLecturerDetail;

  // Live Now
  let knownLiveSlotIds = new Set();
  function formatElapsed(startTimeStr){
    const now = new Date();
    const [h, m] = startTimeStr.split(':').map(Number);
    const startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), h, m, 0);
    let diffMin = Math.floor((now - startDate) / 60000);
    if (diffMin < 0) diffMin = 0;
    if (diffMin < 1) return 'just started';
    if (diffMin === 1) return '1 min ago';
    if (diffMin < 60) return `${diffMin} min ago`;
    const hrs = Math.floor(diffMin / 60);
    const mins = diffMin % 60;
    return `${hrs}h ${mins}m ago`;
  }
  function renderNoPackagePromptHtml(){
    return `<div class="no-package-prompt"><span class="no-package-msg">Package එකක් active නැති නිසා join වෙන්න බැහැ</span><button class="activate-pkg-btn" onclick="location.href='packages.php'">📦 Activate Package</button></div>`;
  }
  function renderNotBookedPromptHtml(lecturerId, lecturerName, dateStr, start, end, isFree = 0, slotId = 0){
    const nameEscaped = String(lecturerName).replace(/'/g, "\\'");
    return `<div class="not-booked-prompt"><span class="not-booked-msg">ඔයාට මේ session එක book කරලා නෑ</span><button class="book-now-btn" onclick="bookSlot(${lecturerId}, '${nameEscaped}', '${dateStr}', '${start}', '${end}', ${Number(isFree)}, ${Number(slotId) || 0})">📅 Book Now</button></div>`;
  }
  function renderLoginRequiredPromptHtml(){
    return `<div class="login-required-prompt"><span class="login-required-msg">Join කරන්න login වෙන්න ඕනේ</span><button class="login-required-btn" onclick="requireAuth()">🔐 Login / Register</button></div>`;
  }

  function renderLiveNowList(items){
    const list = document.getElementById('liveNowList');
    const currentIds = new Set(items.map(s => s.slot_id));
    list.innerHTML = items.map(s => {
      const avatarInner = s.photo
        ? `<img src="${s.photo}" alt="${s.full_name}" onerror="this.parentElement.textContent='${s.full_name.charAt(0).toUpperCase()}';">`
        : s.full_name.charAt(0).toUpperCase();
      const isNew = !knownLiveSlotIds.has(s.slot_id);
      const isFree = Number(s.is_free) === 1;
      const dateForCheck = s.date || TODAY_STR;
      const slotId = s.slot_id || s.id || 0;
      const isFull = s.is_full === true || s.is_full === 1;

      const capBadge = buildCapBadge(s, true);

      const freeBadge = isFree
        ? `<span style="display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:800;padding:2px 9px;border-radius:999px;background:#d1fae5;color:#065f46;margin-left:6px;">🎁 Free</span>`
        : '';

      let actionHtml;
      if (!IS_LOGGED_IN) {
        actionHtml = renderLoginRequiredPromptHtml();
      } else if (isFull && !isSlotBooked(s.lecturer_id, dateForCheck, s.start)) {
        actionHtml = `<span class="session-ended-badge" style="background:#fee2e2;color:#b91c1c;">Already Booked</span>`;
      } else if (isFree || isSlotBooked(s.lecturer_id, dateForCheck, s.start)) {
        actionHtml = `<button class="join-live-btn" onclick='joinLiveOnly(${JSON.stringify(s.room_name)}, ${JSON.stringify(s.full_name)}, ${Number(s.lecturer_id)}, ${JSON.stringify(dateForCheck)}, ${JSON.stringify(s.start)}, ${isFree ? 1 : 0})'>🔴 Join Live</button>`;
      } else if (!HAS_ACTIVE_PACKAGE) {
        actionHtml = renderNoPackagePromptHtml();
      } else {
        actionHtml = renderNotBookedPromptHtml(s.lecturer_id, s.full_name, dateForCheck, s.start, s.end, 0, slotId);
      }
      return `
        <div class="live-now-item ${isNew ? 'just-appeared' : ''}" data-slot-id="${s.slot_id}" data-start="${s.start}">
          <div class="live-now-avatar">${avatarInner}</div>
          <div class="live-now-info">
            <div class="live-now-name">${s.full_name}${freeBadge}${capBadge}</div>
            <div class="live-now-meta">
              <span class="live-dot"></span> ${s.subject || 'General'}
              <span>·</span> ${s.start} - ${s.end}
              <span class="live-now-elapsed" data-elapsed>${formatElapsed(s.start)}</span>
              ${langBadgeSmall(s.language)}
            </div>
          </div>
          ${actionHtml}
        </div>`;
    }).join('');
    knownLiveSlotIds = currentIds;
  }
  function tickElapsedLabels(){
    document.querySelectorAll('#liveNowList .live-now-item').forEach(item => {
      const start = item.getAttribute('data-start');
      const label = item.querySelector('[data-elapsed]');
      if (start && label) label.textContent = formatElapsed(start);
    });
  }
  async function loadLiveSessions(){
    const panel = document.getElementById('liveNowPanel');
    try {
      const res = await fetch('get_live_session.php');
      const data = await res.json();
      if (!data.success || !data.data || data.data.length === 0) {
        panel.style.display = 'none';
        knownLiveSlotIds = new Set();
        return;
      }
      const filtered = data.data.filter(item => matchesStudentLanguage(item) && matchesActivePackageSubject(item));
      if (filtered.length === 0) {
        panel.style.display = 'none';
        knownLiveSlotIds = new Set();
        return;
      }
      panel.style.display = 'block';
      renderLiveNowList(filtered);
    } catch(e) {
      panel.style.display = 'none';
    }
  }

  let meetingWindow = null;
  let activeStudentMeetingRoom = null;
  let currentStudentMeetingUrl = null;

  window.joinLiveSession = function(roomName, lecturerName){
    document.getElementById('meetingLecturerName').textContent = lecturerName;
    document.getElementById('meetingModalOverlay').classList.add('show');
    const url = 'https://meet.jit.si/' + encodeURIComponent(roomName)
      + '#config.prejoinPageEnabled=false'
      + '&config.startWithVideoMuted=true'
      + '&config.toolbarButtons=["microphone","camera","chat","tileview","hangup","fullscreen"]'
      + '&config.disableReactions=true'
      + '&config.disableModeratorIndicator=true'
      + '&config.disableRemoteMute=true'
      + '&config.remoteVideoMenu.disableKick=true'
      + '&config.remoteVideoMenu.disableGrantModerator=true'
      + '&config.participantsPane.hideModeratorSettingsTab=true'
      + '&config.participantsPane.hideMoreActionsButton=true'
      + '&config.visitorsSupported=false'
      + '&userInfo.displayName=' + encodeURIComponent(STUDENT_NAME || 'Student');
    currentStudentMeetingUrl = url;
    meetingWindow = window.open(url, '_blank', 'noopener,noreferrer');
    activeStudentMeetingRoom = roomName;
  };
  window.joinLiveOnly = function(roomName, lecturerName, lecturerId, dateStr, startTime, isFree = 0){
    if (!requireAuth()) return;
    if (!canJoinLive(lecturerId, dateStr, startTime, isFree)) {
      alert('ඔයාට මේ session එක book කරලා නැති නිසා join වෙන්න බැහැ. කරුණාකර මුලින් session එක book කරන්න.');
      return;
    }
    joinLiveSession(roomName, lecturerName);
  };
  window.joinBookedSession = function(roomName, lecturerName){
    if (!requireAuth()) return;
    joinLiveSession(roomName, lecturerName);
  };
  document.getElementById('reopenStudentMeetingBtn').addEventListener('click', () => {
    if (!currentStudentMeetingUrl) return;
    meetingWindow = window.open(currentStudentMeetingUrl, '_blank', 'noopener,noreferrer');
  });
  function leaveStudentMeeting(){
    if (meetingWindow && !meetingWindow.closed) meetingWindow.close();
    meetingWindow = null;
    document.getElementById('meetingModalOverlay').classList.remove('show');
    activeStudentMeetingRoom = null;
  }
  document.getElementById('leaveMeetingBtn').addEventListener('click', leaveStudentMeeting);

  loadLiveSessions();
  setInterval(loadLiveSessions, 20000);
  setInterval(tickElapsedLabels, 30000);

  // Calendar
  (function(){
    const monthLabelEl = document.getElementById('calMonthLabel');
    const daysGridEl   = document.getElementById('calDaysGrid');
    const prevBtn      = document.getElementById('calPrevBtn');
    const nextBtn      = document.getElementById('calNextBtn');
    const slotsListEl  = document.getElementById('calSlotsList');
    const slotsTitleEl = document.getElementById('calSlotsTitle');
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const todayObj = new Date();
    const todayStr = todayObj.getFullYear() + '-' +
      String(todayObj.getMonth() + 1).padStart(2, '0') + '-' +
      String(todayObj.getDate()).padStart(2, '0');
    let viewYear  = todayObj.getFullYear();
    let viewMonth = todayObj.getMonth() + 1;
    let selectedDate = null;
    let datesWithSlots = new Set();
    const bookedDates = new Set((typeof BOOKED_SESSIONS !== 'undefined' ? BOOKED_SESSIONS : []).map(b => b.date));

    function pad(n){ return n < 10 ? '0' + n : '' + n; }

    async function loadMonthAvailability(){
      datesWithSlots = new Set();
      try {
        const params = new URLSearchParams({ year: viewYear, month: viewMonth });
        if (STUDENT_LANGUAGE) params.set('lang', String(STUDENT_LANGUAGE).trim().toLowerCase());
        if (HAS_ACTIVE_PACKAGE && ACTIVE_PACKAGE_SUBJECT) {
          params.set('subject', String(ACTIVE_PACKAGE_SUBJECT).trim().toLowerCase());
        }
        const res = await fetch(`get_availability_month.php?${params.toString()}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.dates)) {
          data.dates.forEach(d => datesWithSlots.add(d));
        }
      } catch(e) {}
      renderCalendar();
    }

    function renderCalendar(){
      monthLabelEl.textContent = `${monthNames[viewMonth-1]} ${viewYear}`;
      daysGridEl.innerHTML = '';
      const firstDay = new Date(viewYear, viewMonth-1, 1);
      const startWeekday = firstDay.getDay();
      const daysInMonth = new Date(viewYear, viewMonth, 0).getDate();
      for (let i=0; i<startWeekday; i++){
        const empty = document.createElement('div');
        empty.className = 'cal-day empty';
        daysGridEl.appendChild(empty);
      }
      for (let day=1; day<=daysInMonth; day++){
        const dateStr = `${viewYear}-${pad(viewMonth)}-${pad(day)}`;
        const cell = document.createElement('div');
        cell.className = 'cal-day';
        cell.textContent = day;
        const isPast = dateStr < todayStr;
        if (isPast) cell.classList.add('past');
        if (dateStr === todayStr) cell.classList.add('today');
        if (datesWithSlots.has(dateStr)) cell.classList.add('has-slots');
        if (bookedDates.has(dateStr)) cell.classList.add('has-booked');
        if (dateStr === selectedDate) cell.classList.add('selected');
        if (!isPast) cell.addEventListener('click', () => selectDate(dateStr));
        daysGridEl.appendChild(cell);
      }
    }

    function renderSlotItem(s, dateStr){
      const avatarInner = s.photo
        ? `<img src="${s.photo}" alt="${s.full_name}" onerror="this.parentElement.textContent='${s.full_name.charAt(0).toUpperCase()}';">`
        : s.full_name.charAt(0).toUpperCase();

      const isLive        = s.status === 'live';
      const isEnded       = s.status === 'ended';
      const isFree        = Number(s.is_free) === 1;
      const alreadyBooked = isSlotBooked(s.lecturer_id, dateStr, s.start) || s.is_booked_by_me === true;
      const isFull        = s.is_full === true || s.is_full === 1;
      const slotId        = s.slot_id || s.id || 0;

      const capBadge = buildCapBadge(s, false);

      const freeBadge = isFree
        ? `<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;background:#d1fae5;color:#065f46;margin-right:6px;">🎁 Free Session</span>`
        : '';

      const liveBadgeHtml = `<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 11px;border-radius:999px;background:#fce7f3;color:#be185d;margin-right:6px;"><span class="live-dot"></span>LIVE</span>`;

      let actionsHtml;

      if (!IS_LOGGED_IN && (isLive || !isEnded)) {
        actionsHtml = (isLive ? liveBadgeHtml : '') + capBadge + freeBadge + renderLoginRequiredPromptHtml();
      }
      else if (isFull && !alreadyBooked) {
        actionsHtml = capBadge + freeBadge +
          `<span class="session-ended-badge" style="background:#fee2e2;color:#b91c1c;">Already Booked</span>`;
      }
      else if (isLive && (isFree || alreadyBooked)) {
        actionsHtml = liveBadgeHtml + capBadge + freeBadge +
          `<button class="join-live-btn" onclick='joinLiveOnly(${JSON.stringify(s.room_name)}, ${JSON.stringify(s.full_name)}, ${Number(s.lecturer_id)}, ${JSON.stringify(dateStr)}, ${JSON.stringify(s.start)}, ${isFree ? 1 : 0})'>🔴 Join Live</button>`;
      }
      else if (isLive && !HAS_ACTIVE_PACKAGE) {
        actionsHtml = liveBadgeHtml + capBadge + freeBadge + renderNoPackagePromptHtml();
      }
      else if (isLive) {
        actionsHtml = liveBadgeHtml + capBadge + freeBadge +
          renderNotBookedPromptHtml(s.lecturer_id, s.full_name, dateStr, s.start, s.end, 0, slotId);
      }
      else if (isEnded) {
        actionsHtml = capBadge + freeBadge + `<span class="session-ended-badge">✔ Session Ended</span>`;
      }
      else if (alreadyBooked) {
        actionsHtml = capBadge + freeBadge + `<span class="booked-slot-btn">✔ Already Booked</span>`;
      }
      else if (isFree) {
        actionsHtml = capBadge + freeBadge +
          `<button class="view-detail-btn" onclick="openLecturerDetail(${s.lecturer_id}, {date:'${dateStr}', start:'${s.start}', end:'${s.end}', is_free:1, slot_id:${slotId}})">View Detail</button>
           <span class="completed-badge" style="background:#d1fae5;color:#065f46;">🎁 Free – Join when Live</span>`;
      }
      else if (!HAS_ACTIVE_PACKAGE) {
        actionsHtml = capBadge + freeBadge + renderNoPackagePromptHtml();
      }
      else {
        const nameEscaped = s.full_name.replace(/'/g, "\\'");
        actionsHtml = capBadge + freeBadge +
          `<button class="view-detail-btn" onclick="openLecturerDetail(${s.lecturer_id}, {date:'${dateStr}', start:'${s.start}', end:'${s.end}', is_free:0, slot_id:${slotId}})">View Detail</button>
           <button class="book-slot-btn" onclick="bookSlot(${s.lecturer_id}, '${nameEscaped}', '${dateStr}', '${s.start}', '${s.end}', 0, ${slotId})">Book Now</button>`;
      }

      const itemClass = alreadyBooked ? 'cal-slot-item booked-slot-item' : 'cal-slot-item';
      return `
      <div class="${itemClass}" data-slot-id="${slotId}" data-room="${s.room_name ?? ''}">
        <div class="cal-slot-avatar">${avatarInner}</div>
        <div class="cal-slot-info">
          <div class="cal-slot-name">${s.full_name} ${langBadgeSmall(s.language)}</div>
          <div class="cal-slot-meta">${s.subject || 'General'} · ${s.start} - ${s.end}</div>
        </div>
        <div class="cal-slot-actions">${actionsHtml}</div>
      </div>`;
    }

    async function selectDate(dateStr){
      selectedDate = dateStr;
      renderCalendar();
      const d = new Date(dateStr + 'T00:00:00');
      const label = d.toLocaleDateString('en-GB', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
      slotsTitleEl.textContent = label;
      slotsListEl.innerHTML = `<div class="cal-slots-empty">Loading...</div>`;

      try {
        const params = new URLSearchParams({ date: dateStr });
        if (STUDENT_LANGUAGE) params.set('lang', String(STUDENT_LANGUAGE).trim().toLowerCase());
        if (HAS_ACTIVE_PACKAGE && ACTIVE_PACKAGE_SUBJECT) {
          params.set('subject', String(ACTIVE_PACKAGE_SUBJECT).trim().toLowerCase());
        }
        const res = await fetch(`get_availability_by_date.php?${params.toString()}`);
        const data = await res.json();

        if (!data.success) {
          slotsListEl.innerHTML = `<div class="cal-slots-empty">${data.message || 'Error loading slots'}</div>`;
          return;
        }

        const filteredSlots = data.data.filter(item =>
          matchesStudentLanguage(item) && matchesActivePackageSubject(item)
        );

        if (data.data.length === 0) {
          datesWithSlots.delete(dateStr);
          renderCalendar();
          slotsListEl.innerHTML = `<div class="cal-slots-empty">මේ දවසේ available teachers නැහැ.</div>`;
          return;
        }

        if (filteredSlots.length === 0) {
          datesWithSlots.delete(dateStr);
          renderCalendar();
          let reason = '';
          if (HAS_ACTIVE_PACKAGE && ACTIVE_PACKAGE_SUBJECT) {
            reason = `ඔබේ active package subject එකට (<b>${ACTIVE_PACKAGE_SUBJECT}</b>) අදාළ teachers මේ දවසේ නැහැ.`;
          } else {
            reason = 'ඔබේ language එකට match වෙන teachers මේ දවසේ නැහැ.';
          }
          slotsListEl.innerHTML = `<div class="cal-slots-empty" style="padding:24px;text-align:center;line-height:1.6;">${reason}</div>`;
          return;
        }

        datesWithSlots.add(dateStr);
        if (activeStudentMeetingRoom) {
          const stillLive = filteredSlots.some(s => s.room_name === activeStudentMeetingRoom && s.status === 'live');
          if (!stillLive) leaveStudentMeeting();
        }
        slotsListEl.innerHTML = filteredSlots.map(s => renderSlotItem(s, dateStr)).join('');
      } catch(e) {
        slotsListEl.innerHTML = `<div class="cal-slots-empty">Server error.</div>`;
      }
    }

    async function refreshSelectedDateSlotsSilently(){
      if (!selectedDate) return;
      try {
        const params = new URLSearchParams({ date: selectedDate });
        if (STUDENT_LANGUAGE) params.set('lang', String(STUDENT_LANGUAGE).trim().toLowerCase());
        if (HAS_ACTIVE_PACKAGE && ACTIVE_PACKAGE_SUBJECT) {
          params.set('subject', String(ACTIVE_PACKAGE_SUBJECT).trim().toLowerCase());
        }
        const res = await fetch(`get_availability_by_date.php?${params.toString()}`);
        const data = await res.json();
        if (!data.success) return;
        const filteredSlots = data.data.filter(item => matchesStudentLanguage(item) && matchesActivePackageSubject(item));
        if (activeStudentMeetingRoom) {
          const stillLive = filteredSlots.some(s => s.room_name === activeStudentMeetingRoom && s.status === 'live');
          if (!stillLive) leaveStudentMeeting();
        }
        if (filteredSlots.length === 0) {
          datesWithSlots.delete(selectedDate);
          renderCalendar();
          if (data.data && data.data.length > 0) {
            let reason = '';
            if (HAS_ACTIVE_PACKAGE && ACTIVE_PACKAGE_SUBJECT) {
              reason = `ඔබේ active package subject එකට (<b>${ACTIVE_PACKAGE_SUBJECT}</b>) අදාළ teachers මේ දවසේ නැහැ.`;
            } else {
              reason = 'ඔබේ language එකට match වෙන teachers මේ දවසේ නැහැ.';
            }
            slotsListEl.innerHTML = `<div class="cal-slots-empty" style="padding:24px;text-align:center;line-height:1.6;">${reason}</div>`;
          } else {
            slotsListEl.innerHTML = `<div class="cal-slots-empty">මේ දවසේ available teachers නැහැ.</div>`;
          }
          return;
        }
        datesWithSlots.add(selectedDate);
        slotsListEl.innerHTML = filteredSlots.map(s => renderSlotItem(s, selectedDate)).join('');
      } catch(e) {}
    }

    prevBtn.addEventListener('click', () => {
      viewMonth--;
      if (viewMonth < 1) { viewMonth = 12; viewYear--; }
      loadMonthAvailability();
    });
    nextBtn.addEventListener('click', () => {
      viewMonth++;
      if (viewMonth > 12) { viewMonth = 1; viewYear++; }
      loadMonthAvailability();
    });

    loadMonthAvailability();
    setInterval(refreshSelectedDateSlotsSilently, 15000);
  })();

  (function(){
    const toggleBtn = document.getElementById('chatbotToggle');
    const chatWindow = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('chatbotClose');
    const messagesEl = document.getElementById('chatbotMessages');
    const inputEl = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSend');
    const badge = document.getElementById('chatBadge');
    let isOpen = false;
    let hasGreeted = false;

    const knowledge = [
      { keys: ['hi', 'hello', 'ayubowan', 'හායි', 'හෙලෝ', 'ආයුබෝවන්', 'hey', 'good morning', 'good evening'], reply: '👋 ආයුබෝවන්! මම Sipway Assistant. මට ඔයාට උදව් කරන්න පුළුවන්.\n\nHello! I am Sipway Assistant. How can I help you today?', quick: ['How to book', 'Packages', 'Login help', 'Live sessions'] },
      { keys: ['book', 'booking', 'session book', 'කොහොමද book', 'session එකක් book', 'book කරන්නේ', 'calendar'], reply: '📅 Session එකක් book කරන්න:\n\n1. Dashboard එකේ දකුණු පැත්තේ Calendar එක බලන්න\n2. දවසක් select කරන්න\n3. Available teacher එකක් තෝරලා "Book Now" ඔබන්න\n4. Package එකක් active වෙලා තියෙන්න ඕනේ (Free sessions හැර)\n\nTo book: Select a date → choose teacher → Book Now. You need an active package (except free sessions).', quick: ['Packages', 'Free sessions', 'Live now'] },
      { keys: ['package', 'packages', 'activate', 'පැකේජ්', 'package එක', 'activate කරන්න'], reply: '📦 Packages:\n\n• Packages page එකට යන්න (sidebar එකෙන්)\n• ඔයාට ඕන package එකක් choose කරලා activate කරන්න\n• Active package එකක් තියෙනකොට sessions book කරන්න පුළුවන්\n\nGo to Packages from the sidebar, choose a plan and activate it. Then you can book sessions.', quick: ['How to book', 'Free sessions'] },
      { keys: ['login', 'register', 'sign in', 'ලොග්', 'ලොගින්', 'register කරන්න', 'account'], reply: '🔐 Login / Register:\n\n• Top right එකේ "Login / Register" button එක ඔබන්න\n• නැත්නම් මේ chatbot එකෙන් පුළුවන්\n• Register කරද්දී email, mobile, password අවශ්‍යයි\n\nClick the Login / Register button on the top right to create an account or sign in.', quick: ['How to book', 'Packages'] },
      { keys: ['live', 'live now', 'join', 'join live', 'live session', 'ලයිව්', 'join වෙන්න'], reply: '🔴 Live Sessions:\n\n• "Live Now" panel එක පේනවා නම් teacher කෙනෙක් online\n• Book කරලා තියෙන session එකක් නම් හෝ Free session නම් "Join Live" ඔබන්න\n• Meeting එක new tab එකක open වෙනවා (Jitsi)\n\nIf you see Live Now, click Join Live (must be booked or free session).', quick: ['How to book', 'Free sessions'] },
      { keys: ['free', 'free session', 'නොමිලේ', 'free එක', 'නොමිලේ session'], reply: '🎁 Free Sessions:\n\n• Free sessions book කරන්න package එකක් අවශ්‍ය නැහැ\n• Live වෙනකොට "Join Live" ඔබලා එන්න පුළුවන්\n• Calendar එකේ හරිත "Free Session" badge එකක් පේනවා\n\nFree sessions do not require a package. Just join when they go live.', quick: ['How to book', 'Live now'] },
      { keys: ['progress', 'my progress', 'stats', 'ප්‍රගතිය', 'achievements'], reply: '📊 My Progress:\n\n• Sidebar එකෙන් "My Progress" යන්න\n• Completed sessions, hours learned, achievements බලන්න පුළුවන්\n• Dashboard එකේත් stats cards 4ක් තියෙනවා\n\nCheck My Progress in the sidebar for detailed stats.', quick: ['How to book'] },
      { keys: ['ai', 'practice', 'video', 'vocabulary', 'ai video', 'practice with ai'], reply: '🎥 Practice tools:\n\n• Practice with AI Video – sidebar එකෙන්\n• Vocabulary Practice – new feature\n• Login වෙලා තියෙන්න ඕනේ\n\nUse the sidebar links for AI Video practice and Vocabulary practice.', quick: ['How to book', 'Packages'] },
      { keys: ['faq', 'support', 'help', 'contact', 'උදව්', 'සහාය', 'problem'], reply: '❓ More help:\n\n• FAQs & Support page එකට යන්න (sidebar)\n• නැත්නම් මේ chatbot එකෙන් තවත් අහන්න\n• Lecturer Details page එකෙන් teachers ගැන බලන්න පුළුවන්\n\nVisit FAQs & Support in the sidebar for more detailed help.', quick: ['How to book', 'Packages', 'Login help'] },
      { keys: ['thank', 'thanks', 'ස්තූතියි', 'ස්තුතියි', 'thank you'], reply: '😊 කමක් නැහැ! තවත් උදව් ඕන නම් අහන්න.\n\nYou\'re welcome! Ask me anything else anytime.', quick: ['How to book', 'Packages'] }
    ];

    const fallback = 'මට හොඳට තේරුණේ නැහැ 😊 මේවාත් try කරන්න:\n\n• How to book a session\n• Packages\n• Login help\n• Live sessions\n• Free sessions\n\nOr visit FAQs & Support page.';

    function addMessage(text, isUser = false, quickReplies = null) {
      const div = document.createElement('div');
      div.className = 'chat-msg ' + (isUser ? 'user' : 'bot');
      div.innerHTML = text.replace(/\n/g, '<br>');
      if (quickReplies && quickReplies.length) {
        const qr = document.createElement('div');
        qr.className = 'quick-replies';
        quickReplies.forEach(q => {
          const btn = document.createElement('button');
          btn.className = 'quick-reply-btn';
          btn.textContent = q;
          btn.onclick = () => { inputEl.value = q; sendMessage(); };
          qr.appendChild(btn);
        });
        div.appendChild(qr);
      }
      messagesEl.appendChild(div);
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function showTyping() {
      const div = document.createElement('div');
      div.className = 'chat-typing';
      div.id = 'typingIndicator';
      div.innerHTML = '<span></span><span></span><span></span>';
      messagesEl.appendChild(div);
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    function hideTyping() {
      const t = document.getElementById('typingIndicator');
      if (t) t.remove();
    }

    function getReply(userText) {
      const lower = userText.toLowerCase().trim();
      for (const item of knowledge) {
        if (item.keys.some(k => lower.includes(k))) {
          return { text: item.reply, quick: item.quick || null };
        }
      }
      return { text: fallback, quick: ['How to book', 'Packages', 'Login help', 'Live sessions'] };
    }

    function sendMessage() {
      const text = inputEl.value.trim();
      if (!text) return;
      addMessage(text, true);
      inputEl.value = '';
      sendBtn.disabled = true;
      showTyping();
      setTimeout(() => {
        hideTyping();
        const res = getReply(text);
        addMessage(res.text, false, res.quick);
        sendBtn.disabled = false;
        inputEl.focus();
      }, 600 + Math.random() * 400);
    }

    function openChat() {
      isOpen = true;
      chatWindow.classList.add('open');
      badge.style.display = 'none';
      if (!hasGreeted) {
        hasGreeted = true;
        setTimeout(() => {
          addMessage(
            '👋 ආයුබෝවන්! මම Sipway Assistant.\n\nHello! I can help you with booking sessions, packages, login, live classes and more. What do you need?',
            false,
            ['How to book', 'Packages', 'Login help', 'Live sessions', 'Free sessions']
          );
        }, 300);
      }
      inputEl.focus();
    }
    function closeChat() {
      isOpen = false;
      chatWindow.classList.remove('open');
    }

    toggleBtn.addEventListener('click', () => { isOpen ? closeChat() : openChat(); });
    closeBtn.addEventListener('click', closeChat);
    sendBtn.addEventListener('click', sendMessage);
    inputEl.addEventListener('keydown', (e) => { if (e.key === 'Enter') sendMessage(); });
  })();
</script>

</body>
</html>
<?php endif; ?>