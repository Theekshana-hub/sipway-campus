<?php
session_start();

// Optional: admin session check (adjust to your auth)
// if (!isset($_SESSION['admin_id']) && empty($_SESSION['admin_logged_in'])) {
//     header('Location: admin_login.php');
//     exit;
// }

require_once 'db.php';

if (!isset($conn) || $conn === null) {
    die('Database connection failed.');
}

$uploadDir = __DIR__ . '/uploads/vocabulary/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$toastMsg  = '';
$toastType = '';

// ==================== HANDLE POST ACTIONS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- Upload / Add video ----
    if ($action === 'upload') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $language    = trim($_POST['language'] ?? 'en');
        $duration    = (int)($_POST['duration_seconds'] ?? 0);

        if ($title === '') {
            $toastMsg = 'Title අනිවාර්යයි.';
            $toastType = 'error';
        } elseif (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            $toastMsg = 'Video file එකක් select කරන්න.';
            $toastType = 'error';
        } else {
            $video = $_FILES['video'];
            $ext = strtolower(pathinfo($video['name'], PATHINFO_EXTENSION));
            $allowedVideo = ['mp4', 'webm', 'mov', 'mkv'];
            if (!in_array($ext, $allowedVideo)) {
                $toastMsg = 'Allowed: mp4, webm, mov, mkv';
                $toastType = 'error';
            } else {
                $videoName = 'vid_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $videoPath = $uploadDir . $videoName;
                $relVideo  = 'uploads/vocabulary/' . $videoName;

                if (!move_uploaded_file($video['tmp_name'], $videoPath)) {
                    $toastMsg = 'Video upload fail වුණා.';
                    $toastType = 'error';
                } else {
                    $relThumb = null;
                    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                        $thumb = $_FILES['thumbnail'];
                        $tExt = strtolower(pathinfo($thumb['name'], PATHINFO_EXTENSION));
                        $allowedImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                        if (in_array($tExt, $allowedImg)) {
                            $thumbName = 'thumb_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $tExt;
                            if (move_uploaded_file($thumb['tmp_name'], $uploadDir . $thumbName)) {
                                $relThumb = 'uploads/vocabulary/' . $thumbName;
                            }
                        }
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO vocabulary_videos (title, description, video_path, thumbnail_path, language, duration_seconds, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->bind_param('sssssi', $title, $description, $relVideo, $relThumb, $language, $duration);
                    if ($stmt->execute()) {
                        $toastMsg = 'Video upload වුණා ✓';
                        $toastType = 'success';
                    } else {
                        $toastMsg = 'DB save fail: ' . $stmt->error;
                        $toastType = 'error';
                    }
                    $stmt->close();
                }
            }
        }
    }

    // ---- Toggle active ----
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE vocabulary_videos SET is_active = IF(is_active=1,0,1) WHERE id = $id");
            $toastMsg = 'Status updated.';
            $toastType = 'success';
        }
    }

    // ---- Delete ----
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $res = $conn->query("SELECT video_path, thumbnail_path FROM vocabulary_videos WHERE id = $id LIMIT 1");
            if ($row = $res->fetch_assoc()) {
                if (!empty($row['video_path']) && file_exists(__DIR__ . '/' . $row['video_path'])) {
                    @unlink(__DIR__ . '/' . $row['video_path']);
                }
                if (!empty($row['thumbnail_path']) && file_exists(__DIR__ . '/' . $row['thumbnail_path'])) {
                    @unlink(__DIR__ . '/' . $row['thumbnail_path']);
                }
            }
            $conn->query("DELETE FROM vocabulary_videos WHERE id = $id");
            $toastMsg = 'Video deleted.';
            $toastType = 'success';
        }
    }

    // Redirect to avoid resubmit
    $q = $toastType === 'success' ? 'ok' : 'err';
    header('Location: admin_vocabulary_videos.php?msg=' . urlencode($toastMsg) . '&t=' . $q);
    exit;
}

// Toast from redirect
if (isset($_GET['msg'])) {
    $toastMsg  = $_GET['msg'];
    $toastType = ($_GET['t'] ?? '') === 'ok' ? 'success' : 'error';
}

// Load videos
$videos = [];
$res = $conn->query("SELECT * FROM vocabulary_videos ORDER BY created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $videos[] = $row;
    }
}

// Vocabulary package count (optional info)
$vocabPkgCount = 0;
$r = $conn->query("SELECT COUNT(*) AS c FROM packages WHERE status='active' AND (package_name LIKE '%Vocabulary%' OR package_name LIKE '%Vocab%')");
if ($r && $row = $r->fetch_assoc()) {
    $vocabPkgCount = (int)$row['c'];
}

$conn->close();

$langs = [
    'en' => 'English', 'si' => 'Sinhala', 'ta' => 'Tamil', 'hi' => 'Hindi',
    'de' => 'German', 'zh' => 'Chinese', 'ja' => 'Japanese', 'fr' => 'French',
    'ru' => 'Russian', 'ar' => 'Arabic', 'it' => 'Italian',
];
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vocabulary Videos - Admin | Sipway</title>
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

  .info-banner{
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    padding:14px 18px; border-radius:12px; margin-bottom:22px;
    background:var(--navy-soft); border:1px solid rgba(15,42,74,0.12);
    font-size:13.5px; font-weight:600; color:var(--navy);
  }
  .info-banner a{
    margin-left:auto; padding:8px 14px; border-radius:8px;
    background:var(--navy); color:#fff; font-size:12.5px; font-weight:700;
  }
  .info-banner a:hover{ background:var(--navy-2); }

  .grid-2{ display:grid; grid-template-columns:380px 1fr; gap:22px; align-items:start; }
  .panel{
    background:var(--card); border:1px solid var(--line-soft);
    border-radius:var(--radius-lg); box-shadow:var(--shadow-card); overflow:hidden;
  }
  .panel-head{
    padding:18px 20px; border-bottom:1px solid var(--line-soft);
    display:flex; align-items:center; justify-content:space-between; gap:10px;
  }
  .panel-head h3{ margin:0; font-size:15.5px; font-weight:800; color:var(--navy); }
  .panel-body{ padding:20px; }

  .form-group{ margin-bottom:14px; }
  .form-group label{ display:block; font-size:12.5px; font-weight:700; color:var(--text); margin-bottom:6px; }
  .form-group input, .form-group select, .form-group textarea{
    width:100%; padding:11px 12px; border:1.5px solid var(--line); border-radius:8px;
    font-size:13.5px; font-family:inherit; background:var(--bg); color:var(--text);
  }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus{
    outline:none; border-color:var(--coral); background:#fff;
  }
  .form-group textarea{ min-height:80px; resize:vertical; }
  .btn-upload{
    width:100%; padding:13px; border:none; border-radius:10px;
    background:linear-gradient(135deg, var(--coral), var(--coral-dark));
    color:#fff; font-weight:800; font-size:14px; cursor:pointer;
  }
  .btn-upload:hover{ filter:brightness(1.05); }

  .video-list{ display:flex; flex-direction:column; gap:12px; }
  .video-item{
    display:flex; gap:14px; padding:14px; border:1px solid var(--line-soft);
    border-radius:12px; background:#fbfaf9; align-items:flex-start;
  }
  .video-thumb{
    width:120px; height:68px; border-radius:8px; overflow:hidden; flex-shrink:0;
    background:linear-gradient(135deg, #0f2a4a, #e8825f);
    display:flex; align-items:center; justify-content:center; color:#fff;
  }
  .video-thumb img{ width:100%; height:100%; object-fit:cover; }
  .video-info{ flex:1; min-width:0; }
  .video-info h4{ margin:0 0 4px; font-size:14px; font-weight:800; color:var(--text); }
  .video-info p{ margin:0 0 6px; font-size:12.5px; color:var(--muted); line-height:1.4; }
  .video-meta{ font-size:11.5px; color:var(--muted-2); font-weight:600; display:flex; gap:10px; flex-wrap:wrap; }
  .badge-on{ background:var(--success-soft); color:var(--success); padding:2px 8px; border-radius:999px; font-size:10.5px; font-weight:800; }
  .badge-off{ background:var(--danger-soft); color:var(--danger); padding:2px 8px; border-radius:999px; font-size:10.5px; font-weight:800; }
  .video-actions{ display:flex; flex-direction:column; gap:6px; flex-shrink:0; }
  .video-actions form{ margin:0; }
  .btn-sm{
    padding:7px 12px; border-radius:7px; border:1px solid var(--line);
    background:#fff; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap;
  }
  .btn-sm.toggle{ color:var(--navy); }
  .btn-sm.delete{ color:var(--danger); border-color:transparent; background:var(--danger-soft); }
  .btn-sm.delete:hover{ background:var(--danger); color:#fff; }
  .btn-sm.play{ color:var(--coral-dark); }
  .empty{ text-align:center; padding:40px 16px; color:var(--muted); font-size:14px; font-weight:600; }

  .toast{
    position:fixed; top:20px; left:50%; transform:translateX(-50%) translateY(-16px);
    background:var(--navy); color:#fff; padding:13px 22px; border-radius:10px;
    font-size:13.5px; font-weight:600; opacity:0; pointer-events:none;
    transition:opacity .25s, transform .25s; z-index:100;
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error{ background:var(--danger); }
  .toast.success{ background:var(--success); }

  .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45; }

  @media (max-width:1000px){
    .grid-2{ grid-template-columns:1fr; }
  }
  @media (max-width:880px){
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
    .sidebar-backdrop.show{ display:block; }
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
    <!-- ACTIVE: Vocabulary Videos -->
 
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
       <a class="nav-item active" href="admin_vocabulary_videos.php">
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
    <button class="logout-btn" type="button" id="logoutBtn">
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
        <h2>Vocabulary Videos</h2>
        <p>Upload & manage student Vocabulary Practice videos</p>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="info-banner">
      <span>📦 Active Vocabulary packages: <strong><?php echo $vocabPkgCount; ?></strong> — Package name එකේ “Vocabulary” තියෙන්න ඕන.</span>
      <a href="admin_packages.php">+ Add / Edit Packages</a>
    </div>

    <div class="grid-2">
      <!-- Upload form -->
      <div class="panel">
        <div class="panel-head"><h3>Upload New Video</h3></div>
        <div class="panel-body">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="form-group">
              <label>Title *</label>
              <input type="text" name="title" required placeholder="e.g. Daily Vocabulary – Food">
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" placeholder="Short description..."></textarea>
            </div>
            <div class="form-group">
              <label>Language</label>
              <select name="language">
                <?php foreach ($langs as $code => $label): ?>
                  <option value="<?php echo $code; ?>" <?php echo $code === 'en' ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Duration (seconds)</label>
              <input type="number" name="duration_seconds" min="0" value="0" placeholder="e.g. 180">
            </div>
            <div class="form-group">
              <label>Video file * (mp4, webm, mov)</label>
              <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime,video/*" required>
            </div>
            <div class="form-group">
              <label>Thumbnail (optional)</label>
              <input type="file" name="thumbnail" accept="image/*">
            </div>
            <button type="submit" class="btn-upload">Upload Video</button>
          </form>
        </div>
      </div>

      <!-- List -->
      <div class="panel">
        <div class="panel-head">
          <h3>All Videos (<?php echo count($videos); ?>)</h3>
        </div>
        <div class="panel-body">
          <?php if (count($videos) === 0): ?>
            <div class="empty">තවම videos නැහැ. වම් පැත්තෙන් upload කරන්න.</div>
          <?php else: ?>
            <div class="video-list">
              <?php foreach ($videos as $v):
                $active = (int)$v['is_active'] === 1;
              ?>
              <div class="video-item">
                <div class="video-thumb">
                  <?php if (!empty($v['thumbnail_path'])): ?>
                    <img src="<?php echo htmlspecialchars($v['thumbnail_path']); ?>" alt="">
                  <?php else: ?>
                    ▶
                  <?php endif; ?>
                </div>
                <div class="video-info">
                  <h4><?php echo htmlspecialchars($v['title']); ?></h4>
                  <?php if (!empty($v['description'])): ?>
                    <p><?php echo htmlspecialchars(mb_strimwidth($v['description'], 0, 120, '…')); ?></p>
                  <?php endif; ?>
                  <div class="video-meta">
                    <span><?php echo htmlspecialchars(strtoupper($v['language'] ?? 'en')); ?></span>
                    <?php if ((int)$v['duration_seconds'] > 0): ?>
                      <span><?php echo (int)$v['duration_seconds']; ?>s</span>
                    <?php endif; ?>
                    <span class="<?php echo $active ? 'badge-on' : 'badge-off'; ?>">
                      <?php echo $active ? 'Active' : 'Hidden'; ?>
                    </span>
                    <span><?php echo htmlspecialchars($v['created_at'] ?? ''); ?></span>
                  </div>
                </div>
                <div class="video-actions">
                  <a class="btn-sm play" href="<?php echo htmlspecialchars($v['video_path']); ?>" target="_blank">Play</a>
                  <form method="post">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                    <button type="submit" class="btn-sm toggle"><?php echo $active ? 'Hide' : 'Show'; ?></button>
                  </form>
                  <form method="post" onsubmit="return confirm('මේ video එක delete කරන්නද?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                    <button type="submit" class="btn-sm delete">Delete</button>
                  </form>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast <?php echo $toastType; ?>" id="toast"><?php echo htmlspecialchars($toastMsg); ?></div>

<script>
(function(){
  const BADGE_POLL_INTERVAL_MS = 15000;

  // Toast
  const t = document.getElementById('toast');
  if (t && t.textContent.trim()) {
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
  }

  // Mobile sidebar
  document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarBackdrop').classList.toggle('show');
  });
  document.getElementById('sidebarBackdrop')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('show');
  });

  // Logout — clears the same localStorage key admin-dashboard.html sets on login
  document.getElementById('logoutBtn')?.addEventListener('click', () => {
    localStorage.removeItem('sipwayAdmin');
    window.location.href = 'admin_login.php';
  });

  // ---------- Sidebar badge counts (same endpoints as admin-dashboard.html) ----------
  async function updateBookingsBadge() {
    try {
      const res = await fetch('get_bookings.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = data.data.filter(b => (b.status || '').toLowerCase() === 'pending').length;
      const badge = document.getElementById('pendingBookingsBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) { console.error('Bookings badge update failed', err); }
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
    } catch (err) { console.error('Support badge update failed', err); }
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
    } catch (err) { console.error('Students badge update failed', err); }
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
    } catch (err) { console.error('Activations badge update failed', err); }
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
    } catch (err) { console.error('Teachers badge update failed', err); }
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
    } catch (err) { console.error('Availability badge update failed', err); }
  }

  // NOTE: no get_*.php endpoint for vocabulary activations was in the files you sent,
  // so this badge stays hidden until you point it at the right one. Change the URL
  // below to whatever PHP file returns vocabulary-activation rows (each with a
  // "status" field), e.g. get_vocabulary_activations.php.
  async function updateVocabBadge() {
    try {
      const res = await fetch('get_vocabulary_activations.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = (data.data || []).filter(a => (a.status || 'pending') === 'pending').length;
      const badge = document.getElementById('pendingVocabBadge');
      badge.style.display = pending > 0 ? 'inline-block' : 'none';
      badge.textContent = pending;
    } catch (err) { /* endpoint not confirmed yet — stays silent */ }
  }

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