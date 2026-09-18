<?php
session_start();

require_once 'db.php';
if (!isset($conn) || $conn === null) {
    die('Database connection failed.');
}


$conn->query("
CREATE TABLE IF NOT EXISTS practice_videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  language VARCHAR(10) DEFAULT 'en',
  level VARCHAR(30) DEFAULT 'Beginner',
  video_path VARCHAR(500) NOT NULL,
  thumbnail_path VARCHAR(500) DEFAULT NULL,
  duration_label VARCHAR(20) DEFAULT NULL,
  status ENUM('active','hidden') DEFAULT 'active',
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$uploadDir = __DIR__ . '/uploads/practice_videos/';
$thumbDir  = __DIR__ . '/uploads/practice_thumbs/';

if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        if (!is_dir(__DIR__ . '/uploads')) {
            @mkdir(__DIR__ . '/uploads', 0755, true);
        }
        @mkdir($uploadDir, 0755, true);
    }
}
if (!is_dir($thumbDir)) {
    @mkdir($thumbDir, 0755, true);
}

$langOptions = [
    'en' => ['flag' => '🇬🇧', 'label' => 'English'],
    'de' => ['flag' => '🇩🇪', 'label' => 'German'],
    'zh' => ['flag' => '🇨🇳', 'label' => 'Chinese'],
    'ja' => ['flag' => '🇯🇵', 'label' => 'Japanese'],
    'fr' => ['flag' => '🇫🇷', 'label' => 'French'],
    'hi' => ['flag' => '🇮🇳', 'label' => 'Hindi'],
    'ru' => ['flag' => '🇷🇺', 'label' => 'Russian'],
    'ar' => ['flag' => '🇸🇦', 'label' => 'Arabic'],
    'ta' => ['flag' => '🇮🇳', 'label' => 'Tamil'],
    'it' => ['flag' => '🇮🇹', 'label' => 'Italian'],
];

$message     = '';
$messageType = '';

function uploadErrorMessage($code) {
    $map = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (php.ini upload_max_filesize). Increase limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File too large (form MAX_FILE_SIZE).',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded. Try again.',
        UPLOAD_ERR_NO_FILE    => 'No file selected.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
    ];
    return $map[$code] ?? ('Unknown upload error code: ' . $code);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $language    = strtolower(trim($_POST['language'] ?? 'en'));
    $level       = trim($_POST['level'] ?? 'Beginner');
    $duration    = trim($_POST['duration_label'] ?? '');
    $sortOrder   = (int)($_POST['sort_order'] ?? 0);

    if (!isset($langOptions[$language])) {
        $language = 'en';
    }

    if ($title === '') {
        $message = 'Title is required.';
        $messageType = 'error';
    } elseif (!isset($_FILES['video'])) {
        $message = 'No video field received. Check form enctype="multipart/form-data".';
        $messageType = 'error';
    } elseif ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $message = uploadErrorMessage((int)$_FILES['video']['error']);
        $messageType = 'error';
    } else {
        $file    = $_FILES['video'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['mp4', 'webm', 'mov', 'mkv'];

        if (!in_array($ext, $allowed, true)) {
            $message = 'Only MP4, WebM, MOV, MKV allowed. Got: .' . $ext;
            $messageType = 'error';
        } elseif (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            $message = 'Upload folder not writable: uploads/practice_videos/';
            $messageType = 'error';
        } else {
            $safeName = 'vid_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest     = $uploadDir . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $message = 'Failed to move uploaded file. Check folder permissions.';
                $messageType = 'error';
            } else {
                $videoPath = 'uploads/practice_videos/' . $safeName;
                $thumbPath = '';

                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $tExt = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
                    if (in_array($tExt, ['jpg','jpeg','png','webp'], true) && is_dir($thumbDir) && is_writable($thumbDir)) {
                        $tName = 'thumb_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $tExt;
                        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbDir . $tName)) {
                            $thumbPath = 'uploads/practice_thumbs/' . $tName;
                        }
                    }
                }

                $stmt = $conn->prepare("
                    INSERT INTO practice_videos
                    (title, description, language, level, video_path, thumbnail_path, duration_label, sort_order, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                if (!$stmt) {
                    $message = 'Prepare failed: ' . $conn->error;
                    $messageType = 'error';
                    @unlink($dest);
                } else {
                    $stmt->bind_param(
                        'sssssssi',
                        $title, $description, $language, $level,
                        $videoPath, $thumbPath, $duration, $sortOrder
                    );
                    if ($stmt->execute()) {
                        $flag  = $langOptions[$language]['flag'] ?? '';
                        $label = $langOptions[$language]['label'] ?? $language;
                        $message = "Video uploaded successfully for {$flag} {$label} students. ID: " . $stmt->insert_id;
                        $messageType = 'success';
                    } else {
                        $message = 'DB insert failed: ' . $stmt->error;
                        $messageType = 'error';
                        @unlink($dest);
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// ===== Toggle / Single Delete / Bulk Delete =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Single Toggle
    if ($_POST['action'] === 'toggle' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE practice_videos SET status = IF(status='active','hidden','active') WHERE id = $id");
        $message = 'Status updated.';
        $messageType = 'success';
    }

    // Single Delete
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $r = $conn->query("SELECT video_path, thumbnail_path FROM practice_videos WHERE id = $id");
        if ($r && $row = $r->fetch_assoc()) {
            if (!empty($row['video_path']) && file_exists(__DIR__ . '/' . $row['video_path'])) {
                @unlink(__DIR__ . '/' . $row['video_path']);
            }
            if (!empty($row['thumbnail_path']) && file_exists(__DIR__ . '/' . $row['thumbnail_path'])) {
                @unlink(__DIR__ . '/' . $row['thumbnail_path']);
            }
        }
        $conn->query("DELETE FROM practice_videos WHERE id = $id");
        $message = 'Video deleted.';
        $messageType = 'success';
    }

    // ===== BULK DELETE =====
    if ($_POST['action'] === 'bulk_delete') {
        if (empty($_POST['selected_ids']) || !is_array($_POST['selected_ids'])) {
            $message = 'No videos selected.';
            $messageType = 'error';
        } else {
            $ids = array_map('intval', $_POST['selected_ids']);
            $ids = array_filter($ids);

            if (empty($ids)) {
                $message = 'No valid videos selected.';
                $messageType = 'error';
            } else {
                $idList = implode(',', $ids);

                // Delete files
                $r = $conn->query("SELECT video_path, thumbnail_path FROM practice_videos WHERE id IN ($idList)");
                if ($r) {
                    while ($row = $r->fetch_assoc()) {
                        if (!empty($row['video_path']) && file_exists(__DIR__ . '/' . $row['video_path'])) {
                            @unlink(__DIR__ . '/' . $row['video_path']);
                        }
                        if (!empty($row['thumbnail_path']) && file_exists(__DIR__ . '/' . $row['thumbnail_path'])) {
                            @unlink(__DIR__ . '/' . $row['thumbnail_path']);
                        }
                    }
                }

                // Delete from DB
                $conn->query("DELETE FROM practice_videos WHERE id IN ($idList)");
                $deletedCount = $conn->affected_rows;

                $message = $deletedCount . ' video(s) deleted successfully.';
                $messageType = 'success';
            }
        }
    }
}

// Fetch all videos
$videos = [];
$res = $conn->query("SELECT * FROM practice_videos ORDER BY language ASC, sort_order ASC, id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) $videos[] = $row;
} else {
    if ($message === '') {
        $message = 'Could not read videos: ' . $conn->error;
        $messageType = 'error';
    }
}

$phpUploadMax = ini_get('upload_max_filesize');
$phpPostMax   = ini_get('post_max_size');
$dirWritable  = is_dir($uploadDir) && is_writable($uploadDir);
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practice Videos — Admin | Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy:#0f2a4a; --navy-2:#16385f; --navy-soft:#eef2f7;
    --coral:#e8825f; --coral-dark:#d66c47; --coral-soft:#fdece5;
    --bg:#f6f5f3; --card:#ffffff; --line:#e6e2da; --line-soft:#f0ede7;
    --muted:#6b7280; --muted-2:#8a93a3; --text:#1b2430;
    --danger:#c0392b; --danger-soft:#fdecea; --success:#1f9d55; --success-soft:#e8f8ee;
    --warning:#f2994a; --warning-soft:#fdf1e4;
    --radius-lg:16px; --radius-md:10px; --radius-sm:8px;
    --shadow-card:0 10px 34px -12px rgba(15,42,74,0.14), 0 2px 8px rgba(15,42,74,0.05);
    --ease:cubic-bezier(.4,0,.2,1); --sidebar-w:250px;
  }
  *{ box-sizing:border-box; }
  body{ margin:0; font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased; }
  a{ text-decoration:none; color:inherit; }
  ::selection{ background:var(--coral-soft); color:var(--coral-dark); }
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
  .topbar-right{ display:flex; align-items:center; gap:16px; }
  .icon-btn{ width:38px; height:38px; border-radius:10px; border:1px solid var(--line); background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--muted); position:relative; flex-shrink:0; }
  .icon-btn:hover{ border-color:#d7d2c8; color:var(--navy-2); }
  .icon-btn svg{ width:18px; height:18px; }
  .icon-dot{ position:absolute; top:8px; right:8px; width:7px; height:7px; border-radius:50%; background:var(--coral); border:1.5px solid #fff; }
  .admin-chip{ display:flex; align-items:center; gap:10px; padding:6px 14px 6px 6px; border-radius:999px; background:var(--navy-soft); cursor:default; }
  .admin-avatar{ width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:12.5px; flex-shrink:0; }
  .admin-chip .name{ font-size:13px; font-weight:700; color:var(--navy); line-height:1.2; }
  .admin-chip .role{ font-size:10.5px; color:var(--muted-2); font-weight:600; }
  .content{ padding:26px 28px 60px; flex:1; }
  .greeting{ margin-bottom:22px; }
  .greeting h1{ font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--navy); margin:0 0 4px; letter-spacing:-0.3px; }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }
  .alert{ padding:13px 16px; border-radius:10px; margin-bottom:18px; font-weight:600; font-size:13.5px; }
  .alert.success{ background:var(--success-soft); color:var(--success); }
  .alert.error{ background:var(--danger-soft); color:var(--danger); }
  .debug-box{ font-size:12px; color:var(--muted); background:#fff; border:1px dashed var(--line); border-radius:10px; padding:10px 14px; margin-bottom:18px; }
  .grid{ display:grid; grid-template-columns:1fr 1.25fr; gap:22px; align-items:start; }
  .panel{ background:var(--card); border:1px solid var(--line-soft); border-radius:var(--radius-lg); padding:22px; box-shadow:var(--shadow-card); }
  .panel h3{ margin:0 0 16px; font-size:15.5px; font-weight:800; color:var(--navy); }
  label{ display:block; font-size:12px; font-weight:700; color:var(--muted); margin-bottom:5px; }
  input, select, textarea{ width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:8px; font-size:13.5px; font-family:inherit; margin-bottom:14px; background:#fff; color:var(--text); }
  input:focus, select:focus, textarea:focus{ outline:none; border-color:var(--coral); }
  textarea{ min-height:80px; resize:vertical; }
  .lang-picker{ display:grid; grid-template-columns:repeat(auto-fill, minmax(110px, 1fr)); gap:8px; margin-bottom:14px; }
  .lang-option{ display:flex; align-items:center; gap:8px; padding:10px 12px; border-radius:10px; border:1.5px solid var(--line); background:#fff; cursor:pointer; font-size:12.5px; font-weight:700; user-select:none; transition:all .15s var(--ease); }
  .lang-option:hover{ border-color:var(--coral); background:var(--coral-soft); }
  .lang-option.selected{ border-color:var(--coral); background:var(--coral-soft); box-shadow:0 0 0 1px var(--coral); }
  .lang-option .flag{ font-size:22px; line-height:1; }
  .lang-option input{ display:none; }
  .lang-hint{ font-size:12px; color:var(--muted); margin:-6px 0 14px; padding:8px 12px; background:var(--navy-soft); border-radius:8px; }
  .btn{ display:inline-flex; align-items:center; gap:8px; padding:11px 20px; border:none; border-radius:8px; font-weight:800; font-size:13px; cursor:pointer; font-family:inherit; transition:opacity .15s var(--ease); }
  .btn:hover{ opacity:0.92; }
  .btn:disabled{ opacity:0.55; cursor:not-allowed; }
  .btn-primary{ background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff; }
  .btn-sm{ padding:6px 12px; font-size:11.5px; border-radius:7px; width:100%; }
  .btn-ghost{ background:var(--navy-soft); color:var(--navy); }
  .btn-danger{ background:var(--danger-soft); color:var(--danger); }
  .btn-view{ background:var(--coral-soft); color:var(--coral-dark); }
  .vid-list{ display:flex; flex-direction:column; gap:12px; }
  .vid-item{ display:flex; gap:14px; align-items:center; padding:12px; border:1px solid var(--line-soft); border-radius:12px; background:#fbfaf9; }
  .vid-thumb{ width:100px; height:60px; border-radius:8px; object-fit:cover; background:#ddd; flex-shrink:0; cursor:pointer; }
  .vid-info{ flex:1; min-width:0; }
  .vid-info h4{ margin:0 0 3px; font-size:13.5px; font-weight:800; }
  .vid-info p{ margin:0; font-size:12px; color:var(--muted); }
  .vid-meta{ font-size:11px; color:var(--muted); margin-top:4px; display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
  .badge{ display:inline-block; padding:2px 8px; border-radius:999px; font-size:10px; font-weight:800; }
  .badge.active{ background:var(--success-soft); color:var(--success); }
  .badge.hidden{ background:var(--danger-soft); color:var(--danger); }
  .lang-chip{ display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:800; background:var(--navy-soft); color:var(--navy); }
  .list-filters{ display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
  .list-filter{ padding:5px 11px; border-radius:999px; font-size:11.5px; font-weight:700; border:1px solid var(--line); background:#fff; color:var(--muted); cursor:pointer; transition:all .15s var(--ease); }
  .list-filter.active{ background:var(--navy); color:#fff; border-color:var(--navy); }
  .list-filter:hover:not(.active){ border-color:#d7d2c8; }
  .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45; }
  .video-modal-backdrop{ display:none; position:fixed; inset:0; background:rgba(10,15,25,0.78); z-index:200; align-items:center; justify-content:center; padding:24px; }
  .video-modal-backdrop.show{ display:flex; }
  .video-modal{ width:100%; max-width:820px; background:#000; border-radius:14px; overflow:hidden; box-shadow:0 30px 80px rgba(0,0,0,0.5); position:relative; }
  .video-modal video{ width:100%; max-height:75vh; display:block; background:#000; }
  .video-modal-head{ display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#12213a; color:#fff; }
  .video-modal-head h4{ margin:0; font-size:13.5px; font-weight:700; }
  .video-modal-close{ background:rgba(255,255,255,0.1); border:none; color:#fff; width:30px; height:30px; border-radius:8px; cursor:pointer; font-size:16px; line-height:1; flex-shrink:0; }
  .video-modal-close:hover{ background:rgba(255,255,255,0.2); }

  /* Bulk bar */
  .bulk-bar{
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    padding:12px 14px; background:var(--navy-soft); border-radius:10px;
    margin-bottom:14px; border:1px solid var(--line);
  }
  .bulk-bar label{
    display:flex; align-items:center; gap:8px; margin:0; font-size:13px;
    font-weight:700; color:var(--navy); cursor:pointer;
  }
  .bulk-bar input[type="checkbox"]{ width:18px; height:18px; margin:0; accent-color:var(--coral); cursor:pointer; }
  .vid-check{ width:18px; height:18px; margin:0; accent-color:var(--coral); cursor:pointer; flex-shrink:0; }

  /* ===== Upload progress modal ===== */
  .upload-modal-backdrop{
    display:none; position:fixed; inset:0; background:rgba(10,15,25,0.78);
    z-index:300; align-items:center; justify-content:center; padding:24px;
  }
  .upload-modal-backdrop.show{ display:flex; }
  .upload-modal{
    width:100%; max-width:420px; background:#fff; border-radius:16px;
    padding:26px 24px 24px; box-shadow:0 30px 80px rgba(0,0,0,0.35);
    text-align:center;
  }
  .upload-modal h4{ margin:0 0 6px; font-size:15.5px; font-weight:800; color:var(--navy); }
  .upload-modal p{ margin:0 0 18px; font-size:12.5px; color:var(--muted); }
  .upload-progress-ring{
    width:110px; height:110px; margin:0 auto 16px; position:relative;
  }
  .upload-progress-ring svg{ transform:rotate(-90deg); width:110px; height:110px; }
  .upload-progress-ring circle{
    fill:none; stroke-width:9; stroke-linecap:round;
  }
  .upload-progress-ring .track{ stroke:var(--line-soft); }
  .upload-progress-ring .bar{
    stroke:url(#uploadGradient);
    stroke-dasharray:301.6; stroke-dashoffset:301.6;
    transition:stroke-dashoffset .18s ease;
  }
  .upload-progress-pct{
    position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    font-size:20px; font-weight:800; color:var(--navy);
  }
  .upload-progress-bar-wrap{
    width:100%; height:8px; border-radius:99px; background:var(--line-soft);
    overflow:hidden; margin-bottom:10px;
  }
  .upload-progress-bar-fill{
    height:100%; width:0%; border-radius:99px;
    background:linear-gradient(90deg, var(--coral), var(--coral-dark));
    transition:width .18s ease;
  }
  .upload-status-text{ font-size:12.5px; font-weight:700; color:var(--navy-2); margin-bottom:2px; }
  .upload-status-sub{ font-size:11.5px; color:var(--muted); }

  @media (max-width:1100px){ .grid{ grid-template-columns:1fr; } }
  @media (max-width:880px){ :root{ --sidebar-w:230px; } .sidebar{ transform:translateX(-100%); } .sidebar.open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,0.3); } .main{ margin-left:0; } .menu-toggle{ display:flex; } .sidebar-backdrop.show{ display:block; } }
  @media (max-width:560px){ .topbar{ padding:0 16px; } .content{ padding:18px 16px 40px; } .admin-chip .name, .admin-chip .role{ display:none; } .admin-chip{ padding:6px; } .vid-item{ flex-wrap:wrap; } }
  @media (prefers-reduced-motion: reduce){ *{ animation-duration:0.001ms !important; transition-duration:0.001ms !important; } }
</style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-mark">SC</span>
    <span>Sipway English Academy<span class="sub">ADMIN PANEL</span></span>
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
    <a class="nav-item active" href="admin_practice_videos.php">
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
        <h2>Practice AI Videos</h2>
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
      <h1>Practice Videos 🎬</h1>
      <p>Language අනුව students ට practice videos upload / manage කරන්න.</p>
    </div>

    <?php if ($message): ?>
      <div class="alert <?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <div class="debug-box">
      <strong>Server check:</strong>
      upload_max_filesize = <?php echo htmlspecialchars($phpUploadMax); ?> |
      post_max_size = <?php echo htmlspecialchars($phpPostMax); ?> |
      folder writable = <?php echo $dirWritable ? 'YES ✅' : 'NO ❌'; ?>
    </div>

    <div class="grid">
      <!-- Upload Panel -->
      <div class="panel">
        <h3>Upload New Video</h3>
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <input type="hidden" name="action" value="upload">
          <input type="hidden" name="language" id="languageInput" value="en">

          <label>Title *</label>
          <input type="text" name="title" required placeholder="e.g. Self Introduction Practice">

          <label>Description</label>
          <textarea name="description" placeholder="Short description..."></textarea>

          <label>Target Language *</label>
          <div class="lang-picker" id="langPicker">
            <?php foreach ($langOptions as $code => $meta): ?>
              <label class="lang-option <?php echo $code === 'en' ? 'selected' : ''; ?>" data-lang="<?php echo $code; ?>">
                <input type="radio" name="lang_radio" value="<?php echo $code; ?>" <?php echo $code === 'en' ? 'checked' : ''; ?>>
                <span class="flag"><?php echo $meta['flag']; ?></span>
                <span><?php echo htmlspecialchars($meta['label']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <p class="lang-hint" id="langHint">🇬🇧 English — මේ video එක English students ට විතරක් පේනවා.</p>

          <label>Level</label>
          <select name="level">
            <option>Beginner</option>
            <option>Intermediate</option>
            <option>Advanced</option>
          </select>

          <label>Duration (optional)</label>
          <input type="text" name="duration_label" placeholder="e.g. 5 min">

          <label>Sort order</label>
          <input type="number" name="sort_order" value="0">

          <label>Video file * (MP4 / WebM)</label>
          <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov" required>

          <label>Thumbnail (optional)</label>
          <input type="file" name="thumbnail" accept="image/*">

          <button type="submit" class="btn btn-primary" id="uploadSubmitBtn">Upload Video</button>
        </form>
      </div>

      <!-- Videos List Panel -->
      <div class="panel">
        <h3>Uploaded Videos (<?php echo count($videos); ?>)</h3>

        <div class="list-filters" id="listFilters">
          <span class="list-filter active" data-lang="all">All</span>
          <?php foreach ($langOptions as $code => $meta): ?>
            <span class="list-filter" data-lang="<?php echo $code; ?>">
              <?php echo $meta['flag']; ?> <?php echo htmlspecialchars($meta['label']); ?>
            </span>
          <?php endforeach; ?>
        </div>

        <?php if (empty($videos)): ?>
          <p style="color:var(--muted); font-size:13.5px;">No videos in database yet.</p>
        <?php else: ?>

          <!-- ========== ONE CLEAN FORM FOR BULK DELETE ========== -->
          <form method="POST" id="bulkDeleteForm" onsubmit="return confirmBulkDelete();">
            <input type="hidden" name="action" value="bulk_delete">

            <div class="bulk-bar">
              <label>
                <input type="checkbox" id="selectAll">
                Select All
              </label>
              <button type="submit" class="btn btn-sm btn-danger" style="width:auto; padding:8px 16px;">
                🗑 Delete Selected
              </button>
              <span id="selectedCount" style="font-size:12.5px; color:var(--muted); font-weight:600;">0 selected</span>
            </div>

            <div class="vid-list" id="vidList">
              <?php foreach ($videos as $v):
                $code = strtolower($v['language'] ?? 'en');
                $meta = $langOptions[$code] ?? ['flag' => '🌐', 'label' => $code];
              ?>
                <div class="vid-item" data-lang="<?php echo htmlspecialchars($code); ?>">
                  <!-- Checkbox -->
                  <input type="checkbox" class="vid-check" name="selected_ids[]" value="<?php echo (int)$v['id']; ?>">

                  <?php if (!empty($v['thumbnail_path'])): ?>
                    <img class="vid-thumb" src="<?php echo htmlspecialchars($v['thumbnail_path']); ?>" alt=""
                         onclick="openVideoModal('<?php echo htmlspecialchars($v['video_path'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($v['title'], ENT_QUOTES); ?>')">
                  <?php else: ?>
                    <div class="vid-thumb" style="display:flex;align-items:center;justify-content:center;background:#1a1a2e;color:#7dd3fc;"
                         onclick="openVideoModal('<?php echo htmlspecialchars($v['video_path'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($v['title'], ENT_QUOTES); ?>')">▶</div>
                  <?php endif; ?>

                  <div class="vid-info">
                    <h4><?php echo htmlspecialchars($v['title']); ?></h4>
                    <p><?php echo htmlspecialchars(mb_strimwidth($v['description'] ?? '', 0, 80, '…')); ?></p>
                    <div class="vid-meta">
                      <span class="lang-chip"><?php echo $meta['flag']; ?> <?php echo htmlspecialchars($meta['label']); ?></span>
                      <span><?php echo htmlspecialchars($v['level']); ?></span>
                      <?php if (!empty($v['duration_label'])): ?>
                        <span><?php echo htmlspecialchars($v['duration_label']); ?></span>
                      <?php endif; ?>
                      <span class="badge <?php echo htmlspecialchars($v['status']); ?>"><?php echo htmlspecialchars($v['status']); ?></span>
                    </div>
                  </div>

                  <div style="display:flex;flex-direction:column;gap:6px;min-width:88px;">
                    <button type="button" class="btn btn-sm btn-view"
                            onclick="openVideoModal('<?php echo htmlspecialchars($v['video_path'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($v['title'], ENT_QUOTES); ?>')">
                      ▶ View
                    </button>

                    <!-- Single Toggle (no nested form) -->
                    <button type="button" class="btn btn-sm btn-ghost"
                            onclick="singleAction('toggle', <?php echo (int)$v['id']; ?>)">
                      <?php echo $v['status'] === 'active' ? 'Hide' : 'Show'; ?>
                    </button>

                    <!-- Single Delete (no nested form) -->
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="singleAction('delete', <?php echo (int)$v['id']; ?>)">
                      Delete
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Video preview modal -->
<div class="video-modal-backdrop" id="videoModalBackdrop">
  <div class="video-modal">
    <div class="video-modal-head">
      <h4 id="videoModalTitle">Video</h4>
      <button type="button" class="video-modal-close" id="videoModalClose">✕</button>
    </div>
    <video id="videoModalPlayer" controls playsinline></video>
  </div>
</div>

<!-- Upload progress modal -->
<div class="upload-modal-backdrop" id="uploadModalBackdrop">
  <div class="upload-modal">
    <h4>Uploading Video</h4>
    <p>කරුණාකර මෙම window එක close කරන්න එපා...</p>

    <div class="upload-progress-ring">
      <svg viewBox="0 0 110 110">
        <defs>
          <linearGradient id="uploadGradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#e8825f"/>
            <stop offset="100%" stop-color="#d66c47"/>
          </linearGradient>
        </defs>
        <circle class="track" cx="55" cy="55" r="48"></circle>
        <circle class="bar" id="uploadRingBar" cx="55" cy="55" r="48"></circle>
      </svg>
      <div class="upload-progress-pct" id="uploadPctText">0%</div>
    </div>

    <div class="upload-progress-bar-wrap">
      <div class="upload-progress-bar-fill" id="uploadBarFill"></div>
    </div>

    <div class="upload-status-text" id="uploadStatusText">Preparing upload...</div>
    <div class="upload-status-sub" id="uploadStatusSub">0 MB / 0 MB</div>
  </div>
</div>

<script>
(function(){
  const adminSession = JSON.parse(localStorage.getItem('sipwayAdmin') || 'null');
  if (adminSession && adminSession.username) {
    document.getElementById('adminName').textContent = adminSession.username;
    document.getElementById('adminAvatar').textContent = adminSession.username.charAt(0).toUpperCase();
  }

  document.getElementById('todayDate').textContent = new Date().toLocaleDateString('en-GB', {
    weekday:'long', year:'numeric', month:'long', day:'numeric'
  });

  const LANG_META = <?php echo json_encode($langOptions); ?>;

  document.querySelectorAll('.lang-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.lang-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      const code = opt.dataset.lang;
      document.getElementById('languageInput').value = code;
      const m = LANG_META[code] || { flag: '🌐', label: code };
      document.getElementById('langHint').textContent =
        `${m.flag} ${m.label} — මේ video එක ${m.label} students ට විතරක් පේනවා.`;
    });
  });

  document.querySelectorAll('.list-filter').forEach(f => {
    f.addEventListener('click', () => {
      document.querySelectorAll('.list-filter').forEach(x => x.classList.remove('active'));
      f.classList.add('active');
      const lang = f.dataset.lang;
      document.querySelectorAll('.vid-item').forEach(item => {
        item.style.display = (lang === 'all' || item.dataset.lang === lang) ? 'flex' : 'none';
      });
    });
  });

  // ===== Select All + Count =====
  const selectAll = document.getElementById('selectAll');
  const checkboxes = () => document.querySelectorAll('.vid-check');
  const selectedCountEl = document.getElementById('selectedCount');

  function updateSelectedCount() {
    const cbs = checkboxes();
    const count = document.querySelectorAll('.vid-check:checked').length;
    if (selectedCountEl) selectedCountEl.textContent = count + ' selected';
    if (selectAll) {
      selectAll.checked = count > 0 && count === cbs.length;
      selectAll.indeterminate = count > 0 && count < cbs.length;
    }
  }

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      checkboxes().forEach(cb => cb.checked = selectAll.checked);
      updateSelectedCount();
    });
  }

  document.addEventListener('change', function(e) {
    if (e.target.classList.contains('vid-check')) {
      updateSelectedCount();
    }
  });

  // Confirm bulk delete
  window.confirmBulkDelete = function() {
    const count = document.querySelectorAll('.vid-check:checked').length;
    if (count === 0) {
      alert('Please select at least one video to delete.');
      return false;
    }
    return confirm('Are you sure you want to delete ' + count + ' selected video(s)?\nThis cannot be undone.');
  };

  // ===== Single action (Toggle / Delete) - no nested form =====
  window.singleAction = function(action, id) {
    if (action === 'delete') {
      if (!confirm('Delete this video?')) return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.innerHTML = `
      <input type="hidden" name="action" value="${action}">
      <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
  };

  // Sidebar
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

  // Video modal
  const videoModalBackdrop = document.getElementById('videoModalBackdrop');
  const videoModalPlayer   = document.getElementById('videoModalPlayer');
  const videoModalTitle    = document.getElementById('videoModalTitle');
  const videoModalClose    = document.getElementById('videoModalClose');

  window.openVideoModal = function(path, title) {
    videoModalPlayer.src = path;
    videoModalTitle.textContent = title || 'Video';
    videoModalBackdrop.classList.add('show');
    videoModalPlayer.play().catch(() => {});
  };

  function closeVideoModal() {
    videoModalPlayer.pause();
    videoModalPlayer.removeAttribute('src');
    videoModalPlayer.load();
    videoModalBackdrop.classList.remove('show');
  }

  videoModalClose.addEventListener('click', closeVideoModal);
  videoModalBackdrop.addEventListener('click', (e) => {
    if (e.target === videoModalBackdrop) closeVideoModal();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeVideoModal();
  });

  // ===== Upload progress (AJAX + XHR upload progress) =====
  const uploadForm        = document.getElementById('uploadForm');
  const uploadSubmitBtn   = document.getElementById('uploadSubmitBtn');
  const uploadModal       = document.getElementById('uploadModalBackdrop');
  const uploadBarFill     = document.getElementById('uploadBarFill');
  const uploadRingBar     = document.getElementById('uploadRingBar');
  const uploadPctText     = document.getElementById('uploadPctText');
  const uploadStatusText  = document.getElementById('uploadStatusText');
  const uploadStatusSub   = document.getElementById('uploadStatusSub');

  const RING_CIRCUMFERENCE = 2 * Math.PI * 48; // r=48

  function bytesToMB(bytes) {
    return (bytes / (1024 * 1024)).toFixed(1);
  }

  function setUploadProgress(percent, loadedBytes, totalBytes) {
    percent = Math.max(0, Math.min(100, percent));
    uploadBarFill.style.width = percent + '%';
    uploadPctText.textContent = percent + '%';
    const offset = RING_CIRCUMFERENCE - (percent / 100) * RING_CIRCUMFERENCE;
    uploadRingBar.style.strokeDashoffset = offset;
    if (typeof loadedBytes === 'number' && typeof totalBytes === 'number' && totalBytes > 0) {
      uploadStatusSub.textContent = bytesToMB(loadedBytes) + ' MB / ' + bytesToMB(totalBytes) + ' MB';
    }
  }

  function showUploadModal() {
    setUploadProgress(0, 0, 0);
    uploadStatusText.textContent = 'Preparing upload...';
    uploadStatusSub.textContent = '';
    uploadModal.classList.add('show');
  }

  function hideUploadModal() {
    uploadModal.classList.remove('show');
  }

  if (uploadForm) {
    uploadForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const fileInput = uploadForm.querySelector('input[name="video"]');
      if (fileInput && fileInput.files.length === 0) {
        alert('Please choose a video file first.');
        return;
      }

      const formData = new FormData(uploadForm);
      const xhr = new XMLHttpRequest();

      xhr.open('POST', window.location.href, true);

      showUploadModal();
      uploadSubmitBtn.disabled = true;
      uploadSubmitBtn.textContent = 'Uploading...';

      xhr.upload.addEventListener('progress', function (evt) {
        if (evt.lengthComputable) {
          const percent = Math.round((evt.loaded / evt.total) * 100);
          uploadStatusText.textContent = percent < 100 ? 'Uploading video...' : 'Finishing up...';
          setUploadProgress(percent, evt.loaded, evt.total);
        }
      });

      xhr.upload.addEventListener('load', function () {
        // Upload bytes fully sent to server, server is now processing (DB insert, moving file, etc.)
        setUploadProgress(100);
        uploadStatusText.textContent = 'Processing on server...';
      });

      xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 400) {
          uploadStatusText.textContent = 'Done!';
          // Replace the whole page with the fresh server response
          // so the success/error message + updated video list show correctly.
          document.open();
          document.write(xhr.responseText);
          document.close();
        } else {
          hideUploadModal();
          uploadSubmitBtn.disabled = false;
          uploadSubmitBtn.textContent = 'Upload Video';
          alert('Upload failed (server returned status ' + xhr.status + '). Please try again.');
        }
      };

      xhr.onerror = function () {
        hideUploadModal();
        uploadSubmitBtn.disabled = false;
        uploadSubmitBtn.textContent = 'Upload Video';
        alert('Upload failed due to a network error. Please check your connection and try again.');
      };

      xhr.send(formData);
    });
  }

  // Badges (same as before)
  const BADGE_POLL_INTERVAL_MS = 15000;
  async function updateBookingsBadge() {
    try {
      const res = await fetch('get_bookings.php');
      const data = await res.json();
      if (!data.success) return;
      const pending = data.data.filter(b => (b.status || '').toLowerCase() === 'pending').length;
      const badge = document.getElementById('pendingBookingsBadge');
      if (pending > 0) { badge.style.display = 'inline-block'; badge.textContent = pending; }
      else { badge.style.display = 'none'; }
    } catch (e) {}
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
    } catch (e) {}
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
    } catch (e) {}
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
    } catch (e) {}
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
    } catch (e) {}
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
    } catch (e) {}
  }

  updateBookingsBadge(); updateSupportBadge(); updateStudentsBadge();
  updateActivationsBadge(); updateTeachersBadge(); updateAvailabilityBadge();
  setInterval(updateBookingsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateSupportBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateStudentsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateActivationsBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateTeachersBadge, BADGE_POLL_INTERVAL_MS);
  setInterval(updateAvailabilityBadge, BADGE_POLL_INTERVAL_MS);
})();
</script>
</body>
</html>