<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
if (!isset($conn) || $conn === null) { die("Database connection failed."); }

$isLoggedIn = isset($_SESSION['student_id']);
$studentId = null; $studentName = 'Guest'; $firstName = 'Guest'; $fullNameSafe = 'Guest';
$photoUrl = null; $studentLanguage = 'en'; $videos = []; $tableOk = false;
$aiVideoStatus = 'none'; $aiVideoPackageName = ''; $aiVideoPackageInfo = null; $hasActivePackage = false;

if ($isLoggedIn) {
    $studentId = (int)$_SESSION['student_id'];
    $studentName = $_SESSION['student_name'] ?? 'Guest';
    $stmt = $conn->prepare("SELECT full_name, language, profile_photo FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $studentName = $row['full_name'];
        $firstName = htmlspecialchars(explode(' ', trim($studentName))[0]);
        $fullNameSafe = htmlspecialchars($studentName);
        $studentLanguage = $row['language'] ?: 'en';
        $studentPhoto = $row['profile_photo'] ?? null;
        if ($studentPhoto && file_exists(__DIR__ . '/' . $studentPhoto)) {
            $photoUrl = htmlspecialchars($studentPhoto);
        }
    } else {
        session_destroy(); session_start();
        $isLoggedIn = false; $studentId = null; $studentName = 'Guest'; $firstName = 'Guest'; $fullNameSafe = 'Guest';
    }
    $stmt->close();
}

if ($isLoggedIn && $studentId > 0) {
    $avCheck = $conn->query("SHOW TABLES LIKE 'activated_ai_video_packages'");
    if ($avCheck && $avCheck->num_rows > 0) {
        $avStmt = $conn->prepare("SELECT aap.status, aap.expiry_date, avp.name FROM activated_ai_video_packages aap JOIN ai_video_packages avp ON avp.id = aap.package_id WHERE aap.student_id = ? ORDER BY aap.id DESC LIMIT 1");
        $avStmt->bind_param("i", $studentId);
        $avStmt->execute();
        $avResult = $avStmt->get_result();
        if ($avRow = $avResult->fetch_assoc()) {
            $aiVideoPackageName = $avRow['name'];
            if ($avRow['status'] === 'active') {
                $aiVideoStatus = ($avRow['expiry_date'] && strtotime($avRow['expiry_date']) < time()) ? 'expired' : 'active';
            } elseif ($avRow['status'] === 'pending') {
                $aiVideoStatus = 'pending';
            } else {
                $aiVideoStatus = 'none';
            }
        }
        $avStmt->close();
    }
}
$hasActivePackage = ($aiVideoStatus === 'active');

$avPkgCheck = $conn->query("SHOW TABLES LIKE 'ai_video_packages'");
if ($avPkgCheck && $avPkgCheck->num_rows > 0) {
    $pkgRes = $conn->query("SELECT id, name, price, duration_days FROM ai_video_packages WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    if ($pkgRes && $pkgRow = $pkgRes->fetch_assoc()) { $aiVideoPackageInfo = $pkgRow; }
}

$langMeta = [
    'en'=>['code'=>'gb','label'=>'English'],'de'=>['code'=>'de','label'=>'German'],
    'zh'=>['code'=>'cn','label'=>'Chinese'],'ja'=>['code'=>'jp','label'=>'Japanese'],
    'fr'=>['code'=>'fr','label'=>'French'],'hi'=>['code'=>'in','label'=>'Hindi'],
    'ru'=>['code'=>'ru','label'=>'Russian'],'ar'=>['code'=>'sa','label'=>'Arabic'],
    'ta'=>['code'=>'in','label'=>'Tamil'],'si'=>['code'=>'lk','label'=>'Sinhala'],
    'it'=>['code'=>'it','label'=>'Italian'],
];
$currentLang = strtolower($studentLanguage);
if (!isset($langMeta[$currentLang])) $currentLang = 'en';
$langCode = $langMeta[$currentLang]['code'];
$langLabel = $langMeta[$currentLang]['label'];
$langFlagUrl = "https://flagcdn.com/w40/{$langCode}.png";

if ($isLoggedIn && $hasActivePackage) {
    $check = $conn->query("SHOW TABLES LIKE 'practice_videos'");
    if ($check && $check->num_rows > 0) {
        $tableOk = true;
        $stmtV = $conn->prepare("SELECT id, title, description, language, level, video_path, thumbnail_path, duration_label FROM practice_videos WHERE status = 'active' AND LOWER(language) = LOWER(?) ORDER BY sort_order ASC, id ASC");
        if ($stmtV) {
            $stmtV->bind_param('s', $currentLang);
            $stmtV->execute();
            $resV = $stmtV->get_result();
            while ($v = $resV->fetch_assoc()) { $videos[] = $v; }
            $stmtV->close();
        }
    }
}

$registerVideo = null;
$regRes = $conn->query("SELECT id, title, source_type, video_path, video_url FROM register_guide_video WHERE status = 'active' ORDER BY id DESC LIMIT 1");
if ($regRes && $regRow = $regRes->fetch_assoc()) {
    $registerVideo = ['title'=>$regRow['title']?:'How to Register','source_type'=>$regRow['source_type'],'player_type'=>'file','player_src'=>$regRow['video_path']??''];
    if ($regRow['source_type'] === 'link' && !empty($regRow['video_url'])) {
        $url = trim($regRow['video_url']);
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            $registerVideo['player_type'] = 'youtube';
            $registerVideo['player_src'] = 'https://www.youtube.com/embed/'.$m[1];
        } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            $registerVideo['player_type'] = 'vimeo';
            $registerVideo['player_src'] = 'https://player.vimeo.com/video/'.$m[1];
        } else {
            $registerVideo['player_type'] = 'direct';
            $registerVideo['player_src'] = $url;
        }
    }
}
$registerVideoJs = json_encode($registerVideo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$conn->close();
$videosJson = json_encode($videos);
$tableMissing = $isLoggedIn && $hasActivePackage && !$tableOk;
$checkoutUrl = 'ai-video-checkout.php?package_id=' . (int)($aiVideoPackageInfo['id'] ?? 0);
$priceLabel = $aiVideoPackageInfo ? ' (Rs. ' . number_format((float)$aiVideoPackageInfo['price'], 2) . ')' : '';
?>
<!DOCTYPE html>
<html lang="si" data-lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Practice with AI Video — Sipway Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--topbar-bg:#0b0a1f;--bg:#f4f0ff;--bg-soft:#faf8ff;--card:#fff;--text:#1e1b4b;--muted:#6b7280;--muted-2:#9ca3af;--line:#e9e5f5;--line-soft:#f3f0fa;--purple:#7c3aed;--purple-soft:#f3e8ff;--pink:#ec4899;--success:#10b981;--warn:#f59e0b;--warn-soft:#fef3c7;--radius-lg:20px;--radius-md:14px;--radius-sm:10px;--shadow-card:0 8px 30px -8px rgba(124,58,237,.08);--shadow-hover:0 16px 40px -12px rgba(124,58,237,.14);--ease:cubic-bezier(.4,0,.2,1)}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,'Noto Sans Sinhala',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
a{color:inherit;text-decoration:none}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes scaleIn{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:scale(1)}}
@keyframes spin{to{transform:rotate(360deg)}}
.animate-up{animation:fadeUp .5s var(--ease) both}.delay-1{animation-delay:.08s}.delay-2{animation-delay:.16s}
.topbar{height:64px;display:flex;align-items:center;gap:14px;padding:0 22px;background:var(--topbar-bg);position:sticky;top:0;z-index:50}
.burger{background:none;border:none;cursor:pointer;padding:8px;display:flex;border-radius:10px;color:#e0e7ff}
.burger svg{width:22px;height:22px}
.logo{display:flex;align-items:center;gap:10px;font-weight:800;color:#fff;font-size:15.5px}
.logo-image{width:80px;height:80px;object-fit:contain;border-radius:10px}
.lang-nav-badge{display:flex;align-items:center;gap:8px;padding:5px 14px 5px 8px;border-radius:999px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);margin-left:6px}
.lang-flag-img{width:26px;height:18px;object-fit:cover;border-radius:3px}
.lang-nav-label{font-size:12.5px;font-weight:800;color:#fff}
.lang-nav-sub{font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase}
.top-links{margin-left:auto;display:flex;gap:22px}
.top-links a{font-size:13px;font-weight:600;color:#94a3b8}
.top-links a:hover{color:#fff}
.user-menu{display:flex;align-items:center;gap:8px;cursor:pointer;padding:5px 10px;border-radius:999px;position:relative}
.user-menu:hover{background:rgba(255,255,255,.08)}
.avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#a855f7,#ec4899);display:flex;align-items:center;justify-content:center;color:#fff;overflow:hidden;font-weight:700;font-size:13px}
.avatar img{width:100%;height:100%;object-fit:cover}
.user-name{font-size:13px;font-weight:700;color:#fff}
.chev{width:13px;height:13px;color:#94a3b8}
.dropdown{position:absolute;top:calc(100% + 10px);right:0;background:var(--card);border:1px solid var(--line);border-radius:var(--radius-md);box-shadow:var(--shadow-hover);min-width:190px;padding:6px;display:none;z-index:60}
.dropdown.show{display:block}
.dropdown a{display:block;padding:11px 13px;font-size:13.5px;font-weight:600;border-radius:9px}
.dropdown a:hover{background:var(--purple-soft)}
.dropdown a.danger{color:#b91c1c}
.topbar-login-btn{padding:10px 20px;border:none;border-radius:999px;background:linear-gradient(135deg,#a855f7,#ec4899);color:#fff;font-weight:800;font-size:13px;cursor:pointer}
.shell{display:flex;min-height:calc(100vh - 64px)}
.sidebar{width:260px;flex-shrink:0;background:linear-gradient(180deg,#0f0c29,#1a1440 50%,#1e1b4b);padding:22px 14px;display:flex;flex-direction:column;gap:4px;position:sticky;top:64px;align-self:flex-start;height:calc(100vh - 64px);overflow-y:auto;transition:transform .3s}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;font-weight:600;font-size:14px;color:#fff}
.nav-item svg{width:19px;height:19px;opacity:.85}
.nav-item:hover{background:rgba(255,255,255,.06)}
.nav-item.active{background:linear-gradient(135deg,#a855f7,#ec4899);box-shadow:0 8px 24px -6px rgba(168,85,247,.5)}
.nav-item .badge-new{margin-left:auto;font-size:10px;font-weight:800;padding:3px 8px;border-radius:999px;background:linear-gradient(135deg,#a855f7,#6366f1)}
.side-divider{height:1px;background:rgba(255,255,255,.08);margin:14px 8px}
.backdrop{display:none;position:fixed;inset:0;background:rgba(15,12,41,.5);z-index:45}
.backdrop.show{display:block}
.main{flex:1;padding:28px clamp(16px,3vw,36px) 50px;min-width:0;background:linear-gradient(160deg,#f4f0ff,#faf8ff 40%,#f0eaff);position:relative}
.page-title{font-size:28px;font-weight:800;margin:0 0 6px;position:relative;z-index:1}
.page-subtitle{font-size:13.5px;color:var(--muted);font-weight:600;margin:0 0 24px;position:relative;z-index:1}
.guest-banner{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:16px 20px;border-radius:var(--radius-md);margin-bottom:22px;background:var(--purple-soft);border:1px solid rgba(168,85,247,.25);position:relative;z-index:1}
.guest-banner.pending{background:var(--warn-soft);border-color:rgba(245,158,11,.35)}
.guest-banner.pending p{color:#92400e}
.guest-banner p{flex:1;min-width:200px;font-size:13.5px;font-weight:700;color:#6b21a8}
.guest-banner button,.guest-banner a.btn{padding:10px 20px;border:none;border-radius:var(--radius-sm);background:linear-gradient(135deg,#a855f7,#ec4899);color:#fff;font-weight:800;font-size:12.5px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;box-shadow:0 8px 18px -5px rgba(168,85,247,.4)}
.layout{display:grid;grid-template-columns:1.5fr 1fr;gap:22px;align-items:start;position:relative;z-index:1}
.player-panel,.list-panel{background:var(--card);border:1px solid var(--line-soft);border-radius:var(--radius-lg);box-shadow:var(--shadow-card)}
.player-panel{overflow:hidden}
.player-wrap{background:#0f0c29;aspect-ratio:16/9;display:flex;align-items:center;justify-content:center}
.player-wrap video{width:100%;height:100%;object-fit:contain;background:#000}
.player-empty{color:#c4b5fd;text-align:center;padding:40px 20px}
.player-empty svg{width:48px;height:48px;margin-bottom:12px;opacity:.7}
.player-empty p{font-size:13.5px;font-weight:600;color:#94a3b8}
.player-info{padding:16px 18px;border-top:1px solid var(--line-soft)}
.player-info h3{margin:0 0 6px;font-size:16px;font-weight:800}
.player-info p{margin:0;font-size:13px;color:var(--muted);line-height:1.5}
.player-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.tag{display:inline-flex;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:800;background:var(--purple-soft);color:#6b21a8}
.tag.level{background:#fce7f3;color:#be185d}
.list-panel{padding:18px;max-height:calc(100vh - 180px);overflow-y:auto}
.list-panel h3{margin:0 0 14px;font-size:15px;font-weight:800}
.progress-bar-wrap{margin-bottom:14px}
.progress-bar-label{font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px;display:flex;justify-content:space-between}
.progress-bar{height:8px;background:var(--line-soft);border-radius:999px;overflow:hidden}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,#a855f7,#ec4899);border-radius:999px;transition:width .4s}
.filter-row{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.filter-pill{padding:6px 12px;border-radius:999px;font-size:11.5px;font-weight:700;border:1px solid var(--line);background:var(--card);color:var(--muted);cursor:pointer}
.filter-pill.active{background:linear-gradient(135deg,#a855f7,#ec4899);color:#fff;border-color:transparent}
.video-card{display:flex;gap:12px;padding:12px;border-radius:var(--radius-md);border:1.5px solid var(--line-soft);cursor:pointer;margin-bottom:10px;position:relative;background:var(--card)}
.video-card:hover{border-color:#a855f7;background:var(--purple-soft)}
.video-card.active{border-color:#a855f7;background:var(--purple-soft);box-shadow:0 0 0 1px #a855f7}
.video-card.locked{opacity:.55;cursor:not-allowed;filter:grayscale(.4)}
.video-card .thumb{width:96px;height:54px;border-radius:8px;background:#1a1440;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#c4b5fd;font-size:18px;overflow:hidden;position:relative}
.video-card .thumb img{width:100%;height:100%;object-fit:cover}
.video-card .thumb .lock-overlay{position:absolute;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px}
.video-card .meta{flex:1;min-width:0}
.video-card .meta h4{margin:0 0 3px;font-size:13px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.video-card .meta p{margin:0;font-size:11.5px;color:var(--muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.video-card .meta .sub{margin-top:4px;font-size:10.5px;font-weight:700;color:var(--muted-2)}
.video-card .status-badge{position:absolute;top:8px;right:8px;font-size:10px;font-weight:800;padding:2px 7px;border-radius:999px}
.status-badge.watched{background:#dcfce7;color:#166534}
.status-badge.locked-badge{background:#fee2e2;color:#991b1b}
.status-badge.current{background:#ede9fe;color:#5b21b6}
.empty-list{text-align:center;padding:40px 16px;color:var(--muted);font-size:13.5px;font-weight:600;line-height:1.6}
.auth-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,12,41,.65);backdrop-filter:blur(8px);z-index:400;align-items:center;justify-content:center;padding:20px}
.auth-modal-overlay.show{display:flex}
.auth-modal-close{position:absolute;top:14px;right:14px;width:34px;height:34px;border-radius:11px;border:none;background:var(--purple-soft);color:var(--purple);cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:2}
.gp-card{background:var(--card);border-radius:var(--radius-lg);padding:32px 28px;box-shadow:0 40px 90px -20px rgba(0,0,0,.4);border:1px solid var(--line-soft);width:440px;max-width:100%;position:relative;margin:auto}
.gp-hidden{display:none!important}
.gp-head{text-align:center;margin-bottom:24px}
.gp-head .gp-eyebrow{font-size:12.5px;font-weight:700;color:#a855f7;text-transform:uppercase;letter-spacing:1.2px;margin:0 0 8px}
.gp-head h1{font-size:22px;margin:0 0 6px;font-weight:800}
.gp-head p{color:var(--muted);font-size:13px;margin:0}
.gp-field{margin-bottom:15px}
.gp-field label{display:block;font-size:12.5px;margin-bottom:7px;font-weight:600}
.gp-field input,.gp-field select{width:100%;padding:12.5px 14px;border-radius:var(--radius-sm);border:1.5px solid var(--line);font-size:14px;background:var(--bg-soft);color:var(--text);font-family:inherit}
.gp-field input:focus,.gp-field select:focus{outline:none;border-color:#a855f7;box-shadow:0 0 0 4px rgba(168,85,247,.14)}
.gp-error{font-size:12px;color:#ef4444;margin-top:6px;display:none}
.gp-error.show{display:block}
.gp-btn-primary{width:100%;padding:14px;border:none;border-radius:var(--radius-sm);background:linear-gradient(135deg,#a855f7,#ec4899);color:#fff;font-weight:800;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px}
.gp-btn-primary .gp-spinner{width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:none}
.gp-btn-primary.loading .gp-spinner{display:inline-block}
.gp-switch-row{text-align:center;margin-top:22px;font-size:13.5px;color:var(--muted)}
.gp-switch-row a{color:#a855f7;font-weight:800;cursor:pointer}
.gp-toast{position:fixed;top:20px;left:50%;transform:translateX(-50%) translateY(-16px);background:#0f0c29;color:#fff;padding:13px 22px;border-radius:10px;font-size:13.5px;font-weight:600;opacity:0;z-index:600;transition:all .25s}
.gp-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
.gp-toast.gp-error-toast{background:#ef4444}
.howto-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,12,41,.75);z-index:450;align-items:center;justify-content:center;padding:20px}
.howto-modal-overlay.show{display:flex}
.howto-modal-box{background:var(--card);border-radius:20px;width:100%;max-width:720px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column}
.howto-modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:linear-gradient(135deg,#0f0c29,#a855f7);color:#fff}
.howto-modal-header h3{font-size:16px;font-weight:800;margin:0}
.howto-modal-close{width:34px;height:34px;border-radius:10px;border:none;background:rgba(255,255,255,.18);color:#fff;cursor:pointer}
.howto-modal-body{background:#000}
.howto-modal-body video,.howto-modal-body iframe{width:100%;display:block;border:none}
.howto-modal-body iframe{aspect-ratio:16/9;min-height:320px}
.howto-modal-footer{padding:14px 20px;text-align:center;background:var(--bg-soft);border-top:1px solid var(--line-soft)}
.howto-modal-footer p{font-size:13px;color:var(--muted);margin:0 0 10px;font-weight:600}
.howto-modal-footer .btn-primary{padding:10px 22px;border:none;border-radius:var(--radius-sm);background:linear-gradient(135deg,#a855f7,#ec4899);color:#fff;font-weight:800;cursor:pointer}
@media(max-width:900px){.top-links{display:none}.layout{grid-template-columns:1fr}.list-panel{max-height:none}}
@media(max-width:820px){.sidebar{position:fixed;left:0;top:64px;transform:translateX(-100%);width:280px;z-index:46}.sidebar.open{transform:translateX(0)}}
@media(max-width:560px){.main{padding:18px 12px 36px}.page-title{font-size:22px}.guest-banner{flex-direction:column;align-items:flex-start}.user-name,.lang-nav-text{display:none}}
</style>
</head>
<body>
<header class="topbar">
  <button class="burger" id="burgerBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
  <a href="index.php" class="logo"><img src="images/logo.png" alt="Logo" class="logo-image"><span></span></a>
  <?php if ($isLoggedIn): ?>
  <div class="lang-nav-badge">
    <img class="lang-flag-img" src="<?php echo $langFlagUrl; ?>" width="26" height="18" alt="">
    <div><div class="lang-nav-label"><?php echo htmlspecialchars($langLabel); ?></div><div class="lang-nav-sub">Your Language</div></div>
  </div>
  <?php endif; ?>
  <nav class="top-links">
    <a href="about_sipway_campus.php">About</a>
    <a href="terms_of_use.php">Terms</a>
    <a href="privacy_policy.php">Privacy</a>
  </nav>
  <?php if ($isLoggedIn): ?>
  <div class="user-menu" id="userMenu">
    <span class="avatar"><?php if ($photoUrl): ?><img src="<?php echo $photoUrl; ?>" alt=""><?php else: ?><?php echo strtoupper(substr($firstName,0,1)); ?><?php endif; ?></span>
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
  <a href="index.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Dashboard</a>
  <a href="packages.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> Packages</a>
  <a href="session_progress.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg> My Progress</a>
  <a href="practice-ai-video.php" class="nav-item active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg> Practice with AI Video <span class="badge-new">New</span></a>
  <a href="student_chat.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Chat with Admin</a>
  <div class="side-divider"></div>
  <a href="faq-support.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> FAQs & Support</a>
  <a href="lecturer-details.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Lecturer Details</a>
  <a href="javascript:void(0)" class="nav-item" id="howToRegisterBtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> How to Register</a>
</aside>
<main class="main">
  <h1 class="page-title animate-up">Practice with AI Video</h1>
  <p class="page-subtitle animate-up delay-1">
    <?php if ($isLoggedIn): ?>
    <img src="<?php echo $langFlagUrl; ?>" width="18" height="12" style="border-radius:2px;vertical-align:middle;margin-right:4px" alt="">
    <?php echo htmlspecialchars($langLabel); ?> language එකට අදාළ videos · එකක් අවසානය දක්වා බලලා ඊළඟ එක unlock වෙනවා
    <?php else: ?>එකක් අවසානය දක්වා බලලා ඊළඟ එක unlock වෙනවා<?php endif; ?>
  </p>

  <?php if (!$isLoggedIn): ?>
  <div class="guest-banner animate-up delay-1">
    <p>🔒 <strong>Practice with AI Video</strong> feature එක භාවිතා කිරීමට Login / Register වෙන්න ඕනේ.</p>
    <button type="button" onclick="openAuthModal(false)">Login / Register</button>
  </div>
  <?php elseif ($aiVideoStatus === 'pending'): ?>
  <div class="guest-banner pending animate-up delay-1">
    <p>⏳ ඔයාගේ <strong>AI Video Practice Package</strong> activation request එක Admin approve කරන තුරු ඉන්න.</p>
  </div>
  <?php elseif ($aiVideoStatus === 'expired'): ?>
  <div class="guest-banner animate-up delay-1">
    <p>⌛ ඔයාගේ <strong><?php echo htmlspecialchars($aiVideoPackageName ?: 'AI Video Practice Package'); ?></strong> එක expire වෙලා. නැවත activate කරගන්න.</p>
    <a href="<?php echo htmlspecialchars($checkoutUrl); ?>" class="btn">Re-activate<?php echo $priceLabel; ?></a>
  </div>
  <?php elseif (!$hasActivePackage): ?>
  <div class="guest-banner animate-up delay-1">
    <p>🔒 <strong>Practice with AI Video</strong> feature එක භාවිතා කිරීමට වෙනම <strong>AI Video Package</strong> එකක් activate කරගන්න ඕනේ.</p>
    <a href="<?php echo htmlspecialchars($checkoutUrl); ?>" class="btn">Pay & Activate<?php echo $priceLabel; ?></a>
  </div>
  <?php endif; ?>

  <div class="layout animate-up delay-2">
    <div class="player-panel">
      <div class="player-wrap" id="playerWrap">
        <div class="player-empty" id="playerEmpty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>
          <p>List එකෙන් video එකක් select කරන්න</p>
        </div>
        <video id="mainVideo" controls playsinline style="display:none"></video>
      </div>
      <div class="player-info" id="playerInfo" style="display:none">
        <h3 id="playerTitle">—</h3>
        <p id="playerDesc">—</p>
        <div class="player-meta">
          <span class="tag" id="playerLang">—</span>
          <span class="tag level" id="playerLevel">—</span>
          <span class="tag level" id="playerDuration" style="display:none">—</span>
        </div>
      </div>
    </div>
    <div class="list-panel">
      <h3><?php if ($isLoggedIn): ?><img src="<?php echo $langFlagUrl; ?>" width="18" height="12" style="border-radius:2px;vertical-align:middle;margin-right:4px" alt=""><?php echo htmlspecialchars($langLabel); ?> Videos<?php else: ?>Videos<?php endif; ?></h3>
      <div class="progress-bar-wrap" id="progressWrap" style="display:none">
        <div class="progress-bar-label"><span>Progress</span><span id="progressText">0 / 0</span></div>
        <div class="progress-bar"><div class="progress-bar-fill" id="progressFill" style="width:0%"></div></div>
      </div>
      <div class="filter-row">
        <span class="filter-pill active" data-filter="all">All</span>
        <span class="filter-pill" data-filter="Beginner">Beginner</span>
        <span class="filter-pill" data-filter="Intermediate">Intermediate</span>
        <span class="filter-pill" data-filter="Advanced">Advanced</span>
      </div>
      <?php if ($tableMissing): ?><div class="empty-list"><strong>practice_videos</strong> table එක නැහැ.</div><?php endif; ?>
      <div id="videoList"></div>
    </div>
  </div>
</main>
</div>

<div class="auth-modal-overlay" id="authModalOverlay">
  <div class="gp-card" id="gpLoginCard">
    <button class="auth-modal-close" onclick="closeAuthModal()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    <div class="gp-head"><p class="gp-eyebrow">Sipway Campus</p><h1>Welcome Back</h1><p>Login to continue</p></div>
    <form id="gpLoginForm" novalidate>
      <div class="gp-field"><label>Email</label><input type="email" id="gpLoginEmail" placeholder="you@example.com"><div class="gp-error" id="gpLoginEmailErr">⚠ Valid email එකක් දෙන්න</div></div>
      <div class="gp-field"><label>Password</label><input type="password" id="gpLoginPass" placeholder="••••••••"><div class="gp-error" id="gpLoginPassErr">⚠ Password එක ඇතුළත් කරන්න</div></div>
      <button type="submit" class="gp-btn-primary" id="gpLoginBtn"><span class="gp-spinner"></span><span class="gp-btn-text">Login</span></button>
    </form>
    <div class="gp-switch-row">Don't have an account? <a id="gpGoRegister">Register</a></div>
  </div>
  <div class="gp-card gp-hidden" id="gpRegisterCard">
    <button class="auth-modal-close" onclick="closeAuthModal()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    <div class="gp-head"><p class="gp-eyebrow">Sipway Campus</p><h1>Create Account</h1><p>Join and start learning</p></div>
    <form id="gpRegisterForm" novalidate enctype="multipart/form-data">
      <div class="gp-field"><label>Full Name</label><input type="text" id="gpRegName" placeholder="Your full name"><div class="gp-error" id="gpRegNameErr">⚠ Name එක ඇතුළත් කරන්න</div></div>
      <div class="gp-field"><label>Email</label><input type="email" id="gpRegEmail" placeholder="you@example.com"><div class="gp-error" id="gpRegEmailErr">⚠ Valid email එකක් දෙන්න</div></div>
      <div class="gp-field"><label>Mobile</label><input type="tel" id="gpRegMobile" placeholder="07XXXXXXXX" maxlength="10"><div class="gp-error" id="gpRegMobileErr">⚠ 10 digit mobile</div></div>
      <div class="gp-field"><label>Language</label><select id="gpRegLanguage"><option value="en">English</option><option value="si">Sinhala</option><option value="ta">Tamil</option><option value="de">German</option><option value="fr">French</option><option value="zh">Chinese</option><option value="ja">Japanese</option><option value="hi">Hindi</option><option value="ru">Russian</option><option value="ar">Arabic</option><option value="it">Italian</option></select></div>
      <div class="gp-field"><label>Photo (optional)</label><input type="file" id="gpRegPhoto" accept="image/*"></div>
      <div class="gp-field"><label>Password</label><input type="password" id="gpRegPass" placeholder="Min 6 characters"><div class="gp-error" id="gpRegPassErr">⚠ අවම 6 අක්ෂර</div></div>
      <div class="gp-field"><label>Confirm Password</label><input type="password" id="gpRegPass2" placeholder="Repeat"><div class="gp-error" id="gpRegPass2Err">⚠ Passwords ගැලපෙන්නේ නැහැ</div></div>
      <button type="submit" class="gp-btn-primary" id="gpRegisterBtn"><span class="gp-spinner"></span><span class="gp-btn-text">Create Account</span></button>
    </form>
    <div class="gp-switch-row">Already have an account? <a id="gpGoLogin">Login</a></div>
  </div>
</div>
<div class="gp-toast" id="gpToast"></div>

<div class="howto-modal-overlay" id="howtoModalOverlay">
  <div class="howto-modal-box">
    <div class="howto-modal-header">
      <h3 id="howtoModalTitle">How to Register – Video Guide</h3>
      <button class="howto-modal-close" id="howtoModalCloseBtn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="howto-modal-body" id="howtoModalBody"></div>
    <div class="howto-modal-footer">
      <p>Video එක බලලා Register කරන්න.</p>
      <button class="btn-primary" onclick="closeHowtoModal();openAuthModal(true)">Register Now</button>
    </div>
  </div>
</div>

<script>
const IS_LOGGED_IN=<?php echo $isLoggedIn?'true':'false';?>;
const HAS_ACTIVE_PACKAGE=<?php echo $hasActivePackage?'true':'false';?>;
const VIDEOS=<?php echo $videosJson;?>;
const STUDENT_LANG=<?php echo json_encode($currentLang);?>;
const STUDENT_ID=<?php echo (int)($studentId??0);?>;
const LANG_MAP={en:'🇬🇧 English',de:'🇩🇪 German',zh:'🇨🇳 Chinese',ja:'🇯🇵 Japanese',fr:'🇫🇷 French',hi:'🇮🇳 Hindi',ru:'🇷🇺 Russian',ar:'🇸🇦 Arabic',ta:'🇮🇳 Tamil',si:'🇱🇰 Sinhala',it:'🇮🇹 Italian'};
const STORAGE_KEY='sipway_watched_videos_'+STUDENT_ID+'_'+STUDENT_LANG;
const REGISTER_VIDEO=<?php echo $registerVideoJs?:'null';?>;

const authOverlay=document.getElementById('authModalOverlay');
const loginCard=document.getElementById('gpLoginCard');
const registerCard=document.getElementById('gpRegisterCard');
const gpToast=document.getElementById('gpToast');
function openAuthModal(isReg){authOverlay.classList.add('show');if(isReg){loginCard.classList.add('gp-hidden');registerCard.classList.remove('gp-hidden')}else{registerCard.classList.add('gp-hidden');loginCard.classList.remove('gp-hidden')}}
function closeAuthModal(){authOverlay.classList.remove('show')}
window.openAuthModal=openAuthModal;window.closeAuthModal=closeAuthModal;
authOverlay.addEventListener('click',e=>{if(e.target===authOverlay)closeAuthModal()});
document.getElementById('gpGoRegister').onclick=()=>openAuthModal(true);
document.getElementById('gpGoLogin').onclick=()=>openAuthModal(false);
function showToast(msg,err){gpToast.textContent=(err?'⚠ ':'✓ ')+msg;gpToast.classList.toggle('gp-error-toast',!!err);gpToast.classList.add('show');clearTimeout(showToast._t);showToast._t=setTimeout(()=>gpToast.classList.remove('show'),2600)}
function isValidEmail(v){return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim())}
function isValidMobile(v){return /^0\d{9}$/.test(v.trim())}
function setFieldState(input,errEl,valid){input.style.borderColor=valid?'':'#ef4444';errEl.classList.toggle('show',!valid)}

document.getElementById('gpLoginForm').onsubmit=async function(e){
  e.preventDefault();
  const em=document.getElementById('gpLoginEmail'),pw=document.getElementById('gpLoginPass'),btn=document.getElementById('gpLoginBtn');
  let ok=true;
  setFieldState(em,document.getElementById('gpLoginEmailErr'),isValidEmail(em.value));if(!isValidEmail(em.value))ok=false;
  setFieldState(pw,document.getElementById('gpLoginPassErr'),pw.value.length>0);if(!pw.value)ok=false;
  if(!ok)return;
  btn.classList.add('loading');btn.disabled=true;
  try{
    const res=await fetch('login.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:em.value.trim(),password:pw.value})});
    const r=await res.json();
    if(r.success){showToast(r.message||'Login successful!');setTimeout(()=>location.href='practice-ai-video.php',600)}
    else showToast(r.message||'Invalid email or password.',true);
  }catch(err){showToast('Server error.',true)}
  finally{btn.classList.remove('loading');btn.disabled=false}
};

document.getElementById('gpRegisterForm').onsubmit=async function(e){
  e.preventDefault();
  const n=document.getElementById('gpRegName'),em=document.getElementById('gpRegEmail'),m=document.getElementById('gpRegMobile'),
        p=document.getElementById('gpRegPass'),p2=document.getElementById('gpRegPass2'),btn=document.getElementById('gpRegisterBtn');
  let ok=true;
  setFieldState(n,document.getElementById('gpRegNameErr'),n.value.trim().length>0);if(!n.value.trim())ok=false;
  setFieldState(em,document.getElementById('gpRegEmailErr'),isValidEmail(em.value));if(!isValidEmail(em.value))ok=false;
  setFieldState(m,document.getElementById('gpRegMobileErr'),isValidMobile(m.value));if(!isValidMobile(m.value))ok=false;
  setFieldState(p,document.getElementById('gpRegPassErr'),p.value.length>=6);if(p.value.length<6)ok=false;
  setFieldState(p2,document.getElementById('gpRegPass2Err'),p2.value===p.value);if(p2.value!==p.value)ok=false;
  if(!ok){showToast('Please fix fields.',true);return}
  btn.classList.add('loading');btn.disabled=true;
  const fd=new FormData();
  fd.append('fullName',n.value.trim());fd.append('email',em.value.trim());fd.append('mobile',m.value.trim());
  fd.append('language',document.getElementById('gpRegLanguage').value);fd.append('password',p.value);
  const ph=document.getElementById('gpRegPhoto').files[0];if(ph)fd.append('profilePhoto',ph);
  try{
    const res=await fetch('register.php',{method:'POST',body:fd});
    const r=await res.json();
    if(r.success){showToast(r.message||'Account created!');setTimeout(()=>openAuthModal(false),900)}
    else showToast(r.message||'Could not create account.',true);
  }catch(err){showToast('Server error.',true)}
  finally{btn.classList.remove('loading');btn.disabled=false}
};

function getWatched(){try{return JSON.parse(localStorage.getItem(STORAGE_KEY)||'[]')}catch(e){return[]}}
function saveWatched(a){try{localStorage.setItem(STORAGE_KEY,JSON.stringify(a))}catch(e){}}
function markWatched(id){const l=getWatched();if(!l.includes(Number(id))){l.push(Number(id));saveWatched(l)}}
function isWatched(id){return getWatched().includes(Number(id))}
function isUnlocked(id){const idx=VIDEOS.findIndex(v=>Number(v.id)===Number(id));if(idx<=0)return idx===0;return isWatched(VIDEOS[idx-1].id)}
function updateProgressBar(){
  const w=document.getElementById('progressWrap');
  if(!VIDEOS.length){w.style.display='none';return}
  w.style.display='block';
  const c=VIDEOS.filter(v=>isWatched(v.id)).length,t=VIDEOS.length;
  document.getElementById('progressText').textContent=c+' / '+t;
  document.getElementById('progressFill').style.width=(t?Math.round(c/t*100):0)+'%';
}
function escapeHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}
function escapeAttr(s){return escapeHtml(s).replace(/'/g,'&#39;')}

const sidebar=document.getElementById('sidebar'),burgerBtn=document.getElementById('burgerBtn'),backdrop=document.getElementById('backdrop');
burgerBtn.onclick=()=>{sidebar.classList.toggle('open');backdrop.classList.toggle('show')};
backdrop.onclick=()=>{sidebar.classList.remove('open');backdrop.classList.remove('show')};
const userMenu=document.getElementById('userMenu');
if(userMenu){const dd=document.getElementById('userDropdown');userMenu.onclick=e=>{dd.classList.toggle('show');e.stopPropagation()};document.onclick=()=>dd.classList.remove('show')}

let currentFilter='all',activeId=null;
function renderList(){
  const list=document.getElementById('videoList');
  let items=currentFilter==='all'?VIDEOS:VIDEOS.filter(v=>(v.level||'')===currentFilter);
  if(!items.length){list.innerHTML=`<div class="empty-list">${LANG_MAP[STUDENT_LANG]||STUDENT_LANG} language එකට videos තාම නැහැ.</div>`;updateProgressBar();return}
  list.innerHTML=items.map(v=>{
    const unlocked=isUnlocked(v.id),watched=isWatched(v.id),isActive=activeId==v.id;
    const badge=watched?'<span class="status-badge watched">✓ Watched</span>':!unlocked?'<span class="status-badge locked-badge">🔒 Locked</span>':'<span class="status-badge current">▶ Available</span>';
    const thumb=v.thumbnail_path?`<img src="${escapeAttr(v.thumbnail_path)}" alt="">`:'▶';
    const lock=unlocked?'':'<div class="lock-overlay">🔒</div>';
    return `<div class="video-card ${isActive?'active':''} ${!unlocked?'locked':''}" onclick="${unlocked?`playVideo(${v.id})`:`showLockedMsg()`}"><div class="thumb">${thumb}${lock}</div><div class="meta"><h4>${escapeHtml(v.title)}</h4><p>${escapeHtml(v.description||'')}</p><div class="sub">${LANG_MAP[v.language]||v.language} · ${escapeHtml(v.level||'')}${v.duration_label?' · '+escapeHtml(v.duration_label):''}</div></div>${badge}</div>`;
  }).join('');
  updateProgressBar();
}
function showLockedMsg(){alert('මේ video එක තාම lock වෙලා තියෙනවා.\nපෙර video එක අවසානය දක්වා බලලා unlock කරගන්න.')}
function playVideo(id){
  if(!IS_LOGGED_IN||!HAS_ACTIVE_PACKAGE){openAuthModal(false);return}
  const v=VIDEOS.find(x=>x.id==id);if(!v||!isUnlocked(id)){showLockedMsg();return}
  activeId=id;
  const video=document.getElementById('mainVideo'),empty=document.getElementById('playerEmpty'),info=document.getElementById('playerInfo');
  empty.style.display='none';video.style.display='block';video.src=v.video_path;video.load();video.play().catch(()=>{});
  info.style.display='block';
  document.getElementById('playerTitle').textContent=v.title||'';
  document.getElementById('playerDesc').textContent=v.description||'';
  document.getElementById('playerLang').textContent=LANG_MAP[v.language]||v.language;
  document.getElementById('playerLevel').textContent=v.level||'';
  const dur=document.getElementById('playerDuration');
  if(v.duration_label){dur.style.display='inline-flex';dur.textContent=v.duration_label}else{dur.style.display='none'}
  renderList();
}
window.playVideo=playVideo;window.showLockedMsg=showLockedMsg;
document.getElementById('mainVideo')?.addEventListener('ended',()=>{if(activeId!=null){markWatched(activeId);renderList()}});
document.querySelectorAll('.filter-pill').forEach(p=>p.onclick=()=>{document.querySelectorAll('.filter-pill').forEach(x=>x.classList.remove('active'));p.classList.add('active');currentFilter=p.dataset.filter;renderList()});

if(IS_LOGGED_IN&&HAS_ACTIVE_PACKAGE){renderList();const first=VIDEOS.find(v=>isUnlocked(v.id));if(first)playVideo(first.id)}
else document.getElementById('videoList').innerHTML='<div class="empty-list">Login කරලා AI Video Package එක activate කරගත්තම videos මෙතන පේනවා.</div>';

function openHowtoModal(){
  if(!REGISTER_VIDEO||!REGISTER_VIDEO.player_src){alert('Register guide video තවම නැහැ.');return}
  document.getElementById('howtoModalTitle').textContent=REGISTER_VIDEO.title||'How to Register';
  const type=REGISTER_VIDEO.player_type,src=REGISTER_VIDEO.player_src,body=document.getElementById('howtoModalBody');
  if(type==='youtube'||type==='vimeo'){const s=src+(src.includes('?')?'&':'?')+'autoplay=1';body.innerHTML=`<iframe src="${s.replace(/"/g,'&quot;')}" allow="autoplay;fullscreen" allowfullscreen></iframe>`}
  else body.innerHTML=`<video controls playsinline autoplay style="width:100%;max-height:70vh;background:#000"><source src="${src.replace(/"/g,'&quot;')}"></video>`;
  document.getElementById('howtoModalOverlay').classList.add('show');
}
function closeHowtoModal(){document.getElementById('howtoModalOverlay').classList.remove('show');document.getElementById('howtoModalBody').innerHTML=''}
window.openHowtoModal=openHowtoModal;window.closeHowtoModal=closeHowtoModal;
document.getElementById('howToRegisterBtn')?.addEventListener('click',e=>{e.preventDefault();openHowtoModal()});
document.getElementById('howtoModalCloseBtn')?.addEventListener('click',closeHowtoModal);
document.getElementById('howtoModalOverlay')?.addEventListener('click',e=>{if(e.target.id==='howtoModalOverlay')closeHowtoModal()});
</script>
</body>
</html>