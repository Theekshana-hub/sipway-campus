<?php
session_start();

require_once 'db.php';
if (!isset($conn) || $conn === null) {
    die('Database connection failed.');
}


$conn->query("
CREATE TABLE IF NOT EXISTS register_guide_video (
  id INT AUTO_INCREMENT PRIMARY KEY,
  source_type ENUM('upload','link') DEFAULT 'upload',
  video_path VARCHAR(500) DEFAULT NULL,
  video_url VARCHAR(500) DEFAULT NULL,
  thumbnail_path VARCHAR(500) DEFAULT NULL,
  title VARCHAR(200) DEFAULT 'How to Register',
  status ENUM('active','hidden') DEFAULT 'active',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$uploadDir = __DIR__ . '/uploads/register_guide/';
$thumbDir  = __DIR__ . '/uploads/register_guide_thumbs/';

if (!is_dir($uploadDir)) {
    if (!is_dir(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0755, true);
    @mkdir($uploadDir, 0755, true);
}
if (!is_dir($thumbDir)) {
    @mkdir($thumbDir, 0755, true);
}

$message     = '';
$messageType = '';

function uploadErrorMessage($code) {
    $map = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (php.ini upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'File too large (form MAX_FILE_SIZE).',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file selected.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
    ];
    return $map[$code] ?? ('Unknown upload error: ' . $code);
}

function parseVideoUrl($url) {
    $url = trim($url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return null;

    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $url, $m)) {
        $id = $m[1];
        return [
            'type'      => 'youtube',
            'embed_url' => "https://www.youtube.com/embed/{$id}",
            'thumb_url' => "https://img.youtube.com/vi/{$id}/hqdefault.jpg",
        ];
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
        $id = $m[1];
        return [
            'type'      => 'vimeo',
            'embed_url' => "https://player.vimeo.com/video/{$id}",
            'thumb_url' => null,
        ];
    }
    return [
        'type'      => 'direct',
        'embed_url' => $url,
        'thumb_url' => null,
    ];
}

// ===== Handle Upload / Link =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $title  = trim($_POST['title'] ?? 'How to Register');
    $source = ($_POST['source'] ?? 'upload') === 'link' ? 'link' : 'upload';

    // Delete old files first (we keep only ONE active register video)
    $old = $conn->query("SELECT video_path, thumbnail_path FROM register_guide_video ORDER BY id DESC LIMIT 1");
    if ($old && $row = $old->fetch_assoc()) {
        if (!empty($row['video_path']) && file_exists(__DIR__ . '/' . $row['video_path'])) {
            @unlink(__DIR__ . '/' . $row['video_path']);
        }
        if (!empty($row['thumbnail_path']) && strpos($row['thumbnail_path'], 'uploads/') === 0 && file_exists(__DIR__ . '/' . $row['thumbnail_path'])) {
            @unlink(__DIR__ . '/' . $row['thumbnail_path']);
        }
    }
    // Clear table (only one row needed)
    $conn->query("DELETE FROM register_guide_video");

    if ($source === 'link') {
        $rawUrl = trim($_POST['video_url'] ?? '');
        $parsed = parseVideoUrl($rawUrl);

        if ($parsed === null) {
            $message = 'Please paste a valid video link (YouTube, Vimeo, or direct .mp4).';
            $messageType = 'error';
        } else {
            $videoPath = null;
            $videoUrl  = $rawUrl;
            $thumbPath = $parsed['thumb_url'];

            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $tExt = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
                if (in_array($tExt, ['jpg','jpeg','png','webp'], true) && is_writable($thumbDir)) {
                    $tName = 'reg_thumb_' . time() . '.' . $tExt;
                    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbDir . $tName)) {
                        $thumbPath = 'uploads/register_guide_thumbs/' . $tName;
                    }
                }
            }

            $stmt = $conn->prepare("
                INSERT INTO register_guide_video
                (source_type, video_path, video_url, thumbnail_path, title, status)
                VALUES ('link', NULL, ?, ?, ?, 'active')
            ");
            $stmt->bind_param('sss', $videoUrl, $thumbPath, $title);
            if ($stmt->execute()) {
                $message = 'Register guide video (link) saved successfully!';
                $messageType = 'success';
            } else {
                $message = 'DB error: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    } else {
        // File upload
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            $message = isset($_FILES['video']) ? uploadErrorMessage((int)$_FILES['video']['error']) : 'No video selected.';
            $messageType = 'error';
        } else {
            $file    = $_FILES['video'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['mp4', 'webm', 'mov', 'mkv'];

            if (!in_array($ext, $allowed, true)) {
                $message = 'Only MP4, WebM, MOV, MKV allowed.';
                $messageType = 'error';
            } elseif (!is_writable($uploadDir)) {
                $message = 'Upload folder not writable: uploads/register_guide/';
                $messageType = 'error';
            } else {
                $safeName = 'reg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest     = $uploadDir . $safeName;

                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $message = 'Failed to move uploaded file.';
                    $messageType = 'error';
                } else {
                    $videoPath = 'uploads/register_guide/' . $safeName;
                    $thumbPath = null;

                    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                        $tExt = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
                        if (in_array($tExt, ['jpg','jpeg','png','webp'], true) && is_writable($thumbDir)) {
                            $tName = 'reg_thumb_' . time() . '.' . $tExt;
                            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbDir . $tName)) {
                                $thumbPath = 'uploads/register_guide_thumbs/' . $tName;
                            }
                        }
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO register_guide_video
                        (source_type, video_path, video_url, thumbnail_path, title, status)
                        VALUES ('upload', ?, NULL, ?, ?, 'active')
                    ");
                    $stmt->bind_param('sss', $videoPath, $thumbPath, $title);
                    if ($stmt->execute()) {
                        $message = 'Register guide video uploaded successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'DB error: ' . $stmt->error;
                        $messageType = 'error';
                        @unlink($dest);
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// ===== Delete current video =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $r = $conn->query("SELECT video_path, thumbnail_path FROM register_guide_video LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        if (!empty($row['video_path']) && file_exists(__DIR__ . '/' . $row['video_path'])) {
            @unlink(__DIR__ . '/' . $row['video_path']);
        }
        if (!empty($row['thumbnail_path']) && strpos($row['thumbnail_path'], 'uploads/') === 0 && file_exists(__DIR__ . '/' . $row['thumbnail_path'])) {
            @unlink(__DIR__ . '/' . $row['thumbnail_path']);
        }
    }
    $conn->query("DELETE FROM register_guide_video");
    $message = 'Register guide video deleted.';
    $messageType = 'success';
}

// Fetch current video
$current = null;
$res = $conn->query("SELECT * FROM register_guide_video WHERE status = 'active' ORDER BY id DESC LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $current = $row;
}

$phpUploadMax = ini_get('upload_max_filesize');
$dirWritable  = is_dir($uploadDir) && is_writable($uploadDir);
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Guide Video — Admin | Sipway Campus</title>
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
    z-index:50; transition:transform .25s var(--ease);
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
    color:rgba(255,255,255,0.35); text-transform:uppercase;
    padding:8px 12px 6px;
  }
  .nav-item{
    display:flex; align-items:center; gap:12px;
    padding:11px 14px; border-radius:10px;
    font-size:13.5px; font-weight:600;
    color:rgba(255,255,255,0.75); cursor:pointer;
    margin-bottom:3px; transition:all .15s var(--ease);
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
    padding:0 28px; background:rgba(255,255,255,0.9); backdrop-filter:saturate(180%) blur(10px);
    border-bottom:1px solid var(--line-soft); position:sticky; top:0; z-index:30; gap:16px;
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
    color:#fff; font-weight:800; font-size:12.5px;
  }
  .admin-chip .name{ font-size:13px; font-weight:700; color:var(--navy); }
  .admin-chip .role{ font-size:10.5px; color:var(--muted-2); font-weight:600; }
  .content{ padding:26px 28px 60px; flex:1; }
  .greeting{ margin-bottom:22px; }
  .greeting h1{
    font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--navy);
    margin:0 0 4px; letter-spacing:-0.3px;
  }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }
  .alert{ padding:13px 16px; border-radius:10px; margin-bottom:18px; font-weight:600; font-size:13.5px; }
  .alert.success{ background:var(--success-soft); color:var(--success); }
  .alert.error{ background:var(--danger-soft); color:var(--danger); }
  .panel{
    background:var(--card); border:1px solid var(--line-soft);
    border-radius:var(--radius-lg); padding:22px; box-shadow:var(--shadow-card);
    max-width:640px;
  }
  .panel h3{ margin:0 0 16px; font-size:15.5px; font-weight:800; color:var(--navy); }
  label{ display:block; font-size:12px; font-weight:700; color:var(--muted); margin-bottom:5px; }
  input, textarea{
    width:100%; padding:10px 12px; border:1px solid var(--line);
    border-radius:8px; font-size:13.5px; font-family:inherit; margin-bottom:14px;
  }
  input:focus, textarea:focus{ outline:none; border-color:var(--coral); }
  .source-toggle{
    display:flex; gap:8px; margin-bottom:16px;
    background:var(--navy-soft); padding:5px; border-radius:10px;
  }
  .source-toggle-btn{
    flex:1; text-align:center; padding:9px; border-radius:7px;
    font-size:12.5px; font-weight:800; cursor:pointer; color:var(--muted-2);
  }
  .source-toggle-btn.active{
    background:#fff; color:var(--navy); box-shadow:0 2px 6px rgba(15,42,74,0.12);
  }
  .source-panel{ display:none; }
  .source-panel.active{ display:block; }
  .btn{
    display:inline-flex; align-items:center; gap:8px;
    padding:11px 20px; border:none; border-radius:8px;
    font-weight:800; font-size:13px; cursor:pointer; font-family:inherit;
  }
  .btn-primary{ background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff; }
  .btn-danger{ background:var(--danger-soft); color:var(--danger); }
  .current-box{
    background:var(--navy-soft); border-radius:12px; padding:16px; margin-bottom:20px;
  }
  .current-box h4{ margin:0 0 8px; font-size:14px; color:var(--navy); }
  .current-box p{ margin:0; font-size:13px; color:var(--muted); }
  .current-box video, .current-box iframe{
    width:100%; max-height:280px; border-radius:10px; margin-top:12px; background:#000;
  }
  .debug-box{
    font-size:12px; color:var(--muted); background:#fff;
    border:1px dashed var(--line); border-radius:10px; padding:10px 14px; margin-bottom:18px;
  }
  .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45; }
  .toast{
    position:fixed; top:20px; left:50%; transform:translateX(-50%) translateY(-16px);
    background:var(--navy); color:#fff; padding:13px 22px; border-radius:10px;
    font-size:13.5px; font-weight:600; opacity:0; pointer-events:none;
    transition:opacity .25s var(--ease), transform .25s var(--ease); z-index:100;
    box-shadow:0 12px 30px rgba(15,42,74,0.3);
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error-toast{ background:var(--danger); }

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
<a class="nav-item active" href="admin_register_video.php">
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
        <h2>Register Guide Video</h2>
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
      <h1>How to Register – Video 🎬</h1>
      <p>Student dashboard එකේ “How to Register” button එකෙන් පේන video එක මෙතනින් upload / update කරන්න. එක විතරක් තියෙනවා (new one upload කළාම old එක replace වෙනවා).</p>
    </div>

    <?php if ($message): ?>
      <div class="alert <?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <div class="debug-box">
      upload_max_filesize = <?php echo htmlspecialchars($phpUploadMax); ?> |
      folder writable = <?php echo $dirWritable ? 'YES ✅' : 'NO ❌'; ?>
    </div>

    <?php if ($current): ?>
      <div class="current-box">
        <h4>Current Active Video</h4>
        <p>
          <strong><?php echo htmlspecialchars($current['title']); ?></strong><br>
          Type: <?php echo $current['source_type'] === 'link' ? 'Link' : 'Uploaded file'; ?> ·
          Updated: <?php echo htmlspecialchars($current['updated_at']); ?>
        </p>
        <?php
          $playerType = 'file';
          $playerSrc  = $current['video_path'] ?? '';
          if ($current['source_type'] === 'link' && !empty($current['video_url'])) {
              $parsed = parseVideoUrl($current['video_url']);
              if ($parsed) {
                  $playerType = $parsed['type'];
                  $playerSrc  = $parsed['embed_url'];
              }
          }
        ?>
        <?php if ($playerType === 'youtube' || $playerType === 'vimeo'): ?>
          <iframe src="<?php echo htmlspecialchars($playerSrc); ?>" allowfullscreen style="width:100%;aspect-ratio:16/9;border:none;border-radius:10px;margin-top:12px;"></iframe>
        <?php elseif ($playerSrc): ?>
          <video controls playsinline src="<?php echo htmlspecialchars($playerSrc); ?>" style="width:100%;max-height:280px;border-radius:10px;margin-top:12px;background:#000;"></video>
        <?php endif; ?>

        <form method="POST" style="margin-top:14px;" onsubmit="return confirm('Delete this register guide video?');">
          <input type="hidden" name="action" value="delete">
          <button type="submit" class="btn btn-danger">🗑 Delete Current Video</button>
        </form>
      </div>
    <?php else: ?>
      <div class="current-box">
        <h4>No register guide video yet</h4>
        <p>Upload or paste a link below. It will appear on the student dashboard “How to Register” button.</p>
      </div>
    <?php endif; ?>

    <div class="panel">
      <h3><?php echo $current ? 'Replace Video' : 'Add Register Guide Video'; ?></h3>

      <div class="source-toggle" id="sourceToggle">
        <div class="source-toggle-btn active" data-source="link">🔗 Paste Link</div>
        <div class="source-toggle-btn" data-source="upload">⬆ Upload File</div>
      </div>

      <form method="POST" enctype="multipart/form-data" id="videoForm">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="source" id="sourceInput" value="link">

        <label>Title</label>
        <input type="text" name="title" value="How to Register" placeholder="How to Register">

        <div class="source-panel active" id="panelLink">
          <label>Video Link (YouTube / Vimeo / direct .mp4) *</label>
          <input type="url" name="video_url" id="videoUrlInput" placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX">
        </div>

        <div class="source-panel" id="panelUpload">
          <label>Video file (MP4 / WebM) *</label>
          <input type="file" name="video" id="videoFileInput" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov">
        </div>

        <label>Thumbnail (optional)</label>
        <input type="file" name="thumbnail" accept="image/*">

        <button type="submit" class="btn btn-primary">Save Register Guide Video</button>
      </form>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
  const BADGE_POLL_INTERVAL_MS = 15000;

  const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
  if (!adminSession || !adminSession.username) {
    window.location.href = 'admin-login.html';
    return;
  }
  document.getElementById('adminName').textContent = adminSession.username;
  document.getElementById('adminAvatar').textContent = adminSession.username.charAt(0).toUpperCase();
  document.getElementById('todayDate').textContent = new Date().toLocaleDateString('en-GB', {
    weekday:'long', year:'numeric', month:'long', day:'numeric'
  });

  function showToast(msg, type = '') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = 'toast show' + (type ? ' ' + type : '');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }

  /* ================= Sidebar pending badges ================= */
  async function updateBookingsBadge() {
    try {
      const res = await fetch('get_bookings.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = data.data.filter(b => (b.status || '').toLowerCase() === 'pending').length;
      const badge = document.getElementById('pendingBookingsBadge');
      if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
      else { badge.style.display = 'none'; }
    } catch (err) { console.error(err); }
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
    } catch (err) { console.error(err); }
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
    } catch (err) { console.error(err); }
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
    } catch (err) { console.error(err); }
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
    } catch (err) { console.error(err); }
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
    } catch (err) { console.error(err); }
  }
  updateBookingsBadge(); updateStudentsBadge(); updateActivationsBadge();
  updateTeachersBadge(); updateAvailabilityBadge(); updateSupportBadge();
  setInterval(updateBookingsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateStudentsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateActivationsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateTeachersBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateAvailabilityBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateSupportBadge, BADGE_POLL_INTERVAL_MS);

  const sourceToggle = document.getElementById('sourceToggle');
  const sourceInput  = document.getElementById('sourceInput');
  const panelLink    = document.getElementById('panelLink');
  const panelUpload  = document.getElementById('panelUpload');
  const videoUrlInput  = document.getElementById('videoUrlInput');
  const videoFileInput = document.getElementById('videoFileInput');

  sourceToggle.querySelectorAll('.source-toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      sourceToggle.querySelectorAll('.source-toggle-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const src = btn.dataset.source;
      sourceInput.value = src;
      if (src === 'link') {
        panelLink.classList.add('active');
        panelUpload.classList.remove('active');
        videoUrlInput.setAttribute('required', 'required');
        videoFileInput.removeAttribute('required');
      } else {
        panelUpload.classList.add('active');
        panelLink.classList.remove('active');
        videoFileInput.setAttribute('required', 'required');
        videoUrlInput.removeAttribute('required');
      }
    });
  });
  videoUrlInput.setAttribute('required', 'required');

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
})();
</script>
</body>
</html>