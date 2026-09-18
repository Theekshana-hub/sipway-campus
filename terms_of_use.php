<?php
session_start();
require_once 'db.php';
if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}
$isLoggedIn = isset($_SESSION['student_id']);
// ---------- Defaults (guest state) ----------
$studentId       = null;
$studentName     = '';
$studentLanguage = 'en';
$firstName       = '';
$fullNameSafe    = '';
$photoUrl        = null;
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
        $studentPhoto    = $row['profile_photo'] ?? null;
        if ($studentPhoto && file_exists(__DIR__ . '/' . $studentPhoto)) {
            $photoUrl = htmlspecialchars($studentPhoto);
        }
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
$studentNameJs = json_encode($studentName);

// ===== REGISTER GUIDE VIDEO (for How to Register modal) =====
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
// ===== Language Flag (Image-based) =====
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
?>
<!DOCTYPE html>
<html lang="si" data-lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terms of Use — Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
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
@keyframes spin { to { transform: rotate(360deg); } }
.animate-up { animation: fadeUp .5s var(--ease) both; }
.delay-1 { animation-delay: .08s; }
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
  cursor: default;
  transition: background .2s;
}
.lang-nav-badge:hover { background: rgba(255,255,255,0.1); }
.lang-flag-big img {
  width: 26px;
  height: 18px;
  object-fit: cover;
  border-radius: 3px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
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
.top-links a.current { color: #c4b5fd; }
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
  box-shadow: 0 8px 18px -5px rgba(168,85,247,0.5);
  transition: filter .2s, transform .15s;
}
.topbar-login-btn:hover { filter: brightness(1.06); transform: translateY(-1px); }
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
.nav-item svg { width: 19px; height: 19px; flex-shrink: 0; opacity: 0.85; color: #fff; }
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
/* ===== Terms of Use content ===== */
.page-head {
  background: linear-gradient(135deg, #0f0c29 0%, #7c3aed 50%, #ec4899 100%);
  color: #fff;
  padding: 48px 30px;
  text-align: center;
  border-radius: var(--radius-lg);
  margin-bottom: 26px;
  position: relative;
  z-index: 1;
  box-shadow: 0 16px 40px -12px rgba(124,58,237,0.35);
}
.page-head h1 {
  font-size: 1.9rem;
  font-weight: 800;
  margin: 0 0 10px 0;
  letter-spacing: -0.4px;
}
.page-head p {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 0.8rem;
  color: rgba(255,255,255,0.85);
}
.panel {
  background: var(--card);
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  padding: 30px clamp(20px,4vw,40px);
  position: relative;
  z-index: 1;
  transition: box-shadow .3s;
}
.panel:hover { box-shadow: var(--shadow-hover); }
.panel > p.intro {
  font-size: 14px;
  color: var(--text);
  line-height: 1.7;
  margin: 0 0 8px 0;
}
.panel h2 {
  font-size: 15.5px;
  font-weight: 800;
  color: var(--text);
  margin: 26px 0 10px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}
.panel h2::before {
  content: '';
  width: 6px;
  height: 16px;
  border-radius: 3px;
  background: linear-gradient(180deg, #a855f7, #ec4899);
  display: inline-block;
}
.panel p, .panel li {
  font-size: 13.5px;
  color: var(--text);
  line-height: 1.7;
  margin: 0 0 8px 0;
}
.panel ul {
  padding-left: 20px;
  margin: 0 0 8px 0;
}

/* ========== AUTH MODAL ========== */
.auth-modal-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(15,12,41,0.65); backdrop-filter: blur(8px);
  z-index: 400; align-items: center; justify-content: center;
  padding: 20px; overflow-y: auto;
}
.auth-modal-overlay.show { display: flex; animation: fadeIn .25s; }
.auth-modal-close {
  position: absolute; top: 14px; right: 14px; width: 34px; height: 34px;
  border-radius: 11px; border: none; background: var(--purple-soft); color: var(--purple);
  cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 2;
  transition: all .25s;
}
.auth-modal-close:hover { background: #fce7f3; color: #be185d; transform: rotate(90deg); }
.gp-card {
  background: var(--card); border-radius: var(--radius-lg);
  padding: clamp(28px, 4vw, 40px) clamp(22px, 4vw, 34px);
  box-shadow: 0 40px 90px -20px rgba(0,0,0,0.4); border: 1px solid var(--line-soft);
  width: 440px; max-width: 100%; position: relative; margin: auto;
  animation: scaleIn .3s var(--ease);
}
.gp-hidden { display: none !important; }
.gp-head { text-align: center; margin-bottom: 24px; }
.gp-head .gp-eyebrow {
  font-size: 12.5px; font-weight: 700; color: #a855f7;
  text-transform: uppercase; letter-spacing: 1.2px; margin: 0 0 8px 0;
}
.gp-head h1 {
  font-size: clamp(20px, 3vw, 25px); color: var(--text); margin: 0 0 6px 0;
  font-weight: 800; letter-spacing: -0.4px;
}
.gp-head p { color: var(--muted); font-size: 13px; line-height: 1.6; margin: 0; }
.gp-field { margin-bottom: 15px; position: relative; }
.gp-field label { display: block; font-size: 12.5px; color: var(--text); margin-bottom: 7px; font-weight: 600; }
.gp-input-shell { position: relative; display: flex; align-items: center; }
.gp-input-icon {
  position: absolute; left: 14px; width: 18px; height: 18px; color: var(--muted-2);
  pointer-events: none; display: flex; flex-shrink: 0;
}
.gp-field input, .gp-field select {
  width: 100%; padding: 12.5px 14px 12.5px 40px; border-radius: var(--radius-sm);
  border: 1.5px solid var(--line); font-size: 14px; font-family: inherit; outline: none;
  background: var(--bg-soft); color: var(--text);
  transition: border-color .15s, box-shadow .15s, background .15s;
}
.gp-field select { padding-left: 14px; cursor: pointer; }
.gp-field input::placeholder { color: var(--muted-2); }
.gp-field input:focus, .gp-field select:focus {
  border-color: #a855f7; background: var(--card);
  box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
}
.gp-field input.gp-invalid { border-color: #ef4444; background: #fef2f2; }
.gp-field input.gp-valid { border-color: var(--success); }
.gp-toggle-pass {
  position: absolute; right: 12px; background: none; border: none; cursor: pointer;
  color: var(--muted-2); padding: 6px; display: flex; align-items: center; border-radius: 6px;
}
.gp-toggle-pass:hover { color: var(--purple); background: var(--purple-soft); }
.gp-toggle-pass svg { width: 18px; height: 18px; }
.gp-error {
  font-size: 12px; color: #ef4444; margin-top: 6px; display: none;
  align-items: center; gap: 5px; font-weight: 500;
}
.gp-error.show { display: flex; }
.gp-strength-meter { display: flex; gap: 4px; margin-top: 8px; height: 4px; }
.gp-strength-meter span { flex: 1; border-radius: 2px; background: var(--line); transition: background .2s; }
.gp-strength-label { font-size: 11px; color: var(--muted-2); margin-top: 5px; font-weight: 600; }
.gp-row-inline {
  display: flex; justify-content: space-between; align-items: center;
  margin: 2px 0 18px 0; flex-wrap: wrap; gap: 8px;
}
.gp-checkbox-label {
  font-size: 13px; color: var(--muted); display: flex; align-items: center;
  gap: 7px; font-weight: 500; cursor: pointer; user-select: none;
}
.gp-checkbox-label input { width: 16px; height: 16px; accent-color: #a855f7; cursor: pointer; }
.gp-row-inline a { font-size: 13px; color: var(--purple); font-weight: 700; }
.gp-row-inline a:hover { color: #7c3aed; }
.gp-btn-primary {
  width: 100%; padding: 14px; border: none; border-radius: var(--radius-sm);
  background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); color: #fff;
  font-weight: 800; font-size: 14px; letter-spacing: 0.4px; cursor: pointer;
  transition: transform .12s, box-shadow .2s, filter .15s;
  box-shadow: 0 10px 24px -6px rgba(168,85,247,0.4);
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.gp-btn-primary:hover { filter: brightness(1.04); }
.gp-btn-primary:disabled { opacity: .7; cursor: not-allowed; }
.gp-btn-primary .gp-spinner {
  width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; display: none;
}
.gp-btn-primary.loading .gp-spinner { display: inline-block; }
.gp-btn-primary.loading .gp-btn-text { opacity: 0.85; }
.gp-switch-row { text-align: center; margin-top: 22px; font-size: 13.5px; color: var(--muted); }
.gp-switch-row a { color: #a855f7; font-weight: 800; cursor: pointer; }
.gp-switch-row a:hover { text-decoration: underline; }
.gp-lang-select { position: relative; }
.gp-lang-trigger {
  width: 100%; padding: 12.5px 14px 12.5px 40px; border-radius: var(--radius-sm);
  border: 1.5px solid var(--line); font-size: 14px; font-family: inherit; outline: none;
  background: var(--bg-soft); color: var(--text); cursor: pointer;
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  transition: border-color .15s, box-shadow .15s; user-select: none;
}
.gp-lang-trigger.open {
  border-color: #a855f7; background: var(--card);
  box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
}
.gp-lang-trigger .gp-lang-current {
  display: flex; align-items: center; gap: 9px; overflow: hidden; white-space: nowrap;
}
.gp-lang-flag {
  width: 22px; height: 16px; flex-shrink: 0; border-radius: 3px;
  box-shadow: 0 0 0 1px rgba(0,0,0,0.12); overflow: hidden;
}
.gp-lang-caret { width: 16px; height: 16px; color: var(--muted-2); flex-shrink: 0; transition: transform .18s; }
.gp-lang-trigger.open .gp-lang-caret { transform: rotate(180deg); }
.gp-lang-options {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0;
  background: var(--card); border: 1.5px solid var(--line); border-radius: var(--radius-sm);
  box-shadow: var(--shadow-hover); z-index: 20; max-height: 220px; overflow-y: auto;
  padding: 6px; opacity: 0; transform: translateY(-6px); pointer-events: none;
  transition: opacity .15s, transform .15s;
}
.gp-lang-options.open { opacity: 1; transform: translateY(0); pointer-events: auto; }
.gp-lang-option {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 14px;
  font-weight: 500; color: var(--text);
}
.gp-lang-option:hover { background: var(--purple-soft); }
.gp-lang-option-left { display: flex; align-items: center; gap: 10px; }
.gp-lang-tick { width: 16px; height: 16px; color: #a855f7; flex-shrink: 0; opacity: 0; transition: opacity .12s; }
.gp-lang-option.selected { background: var(--purple-soft); font-weight: 700; color: #6b21a8; }
.gp-lang-option.selected .gp-lang-tick { opacity: 1; }
.gp-lang-option-loading { padding: 12px; font-size: 13px; color: var(--muted-2); text-align: center; }
.gp-toast {
  position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-16px);
  background: #0f0c29; color: #fff; padding: 13px 22px; border-radius: 10px;
  font-size: 13.5px; font-weight: 600; opacity: 0; pointer-events: none;
  transition: opacity .25s, transform .25s; z-index: 600;
  box-shadow: 0 12px 30px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 10px; max-width: 90vw;
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
  border: none;
  border-radius: var(--radius-sm);
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-weight: 800;
  cursor: pointer;
  transition: filter .2s, transform .15s;
}
.howto-modal-footer .btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); }

@media (max-width: 560px) {
  .howto-modal-overlay { padding: 10px; }
  .howto-modal-box { border-radius: 16px; max-height: 94vh; }
  .howto-modal-header { padding: 13px 14px; }
  .howto-modal-header h3 { font-size: 14px; }
  .howto-modal-body iframe { min-height: 220px; }
  .howto-modal-footer { padding: 12px 14px; }
}

/* ========== RESPONSIVE ========== */
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
  .lang-nav-badge { padding: 4px 10px 4px 6px; }
  .lang-flag-big img { width: 22px; height: 15px; }
  .lang-nav-label { font-size: 11.5px; }
}
@media (max-width: 560px) {
  .topbar { padding: 0 10px; gap: 8px; }
  .logo span:not(.logo-mark) { display: none; }
  .lang-nav-text { display: none; }
  .user-name { display: none; }
  .main { padding: 18px 12px 36px; }
  .page-head { padding: 34px 18px; }
  .page-head h1 { font-size: 1.5rem; }
  .panel { padding: 18px; }
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
</style>
</head>
<body>
<!-- ==================== TOPBAR ==================== -->
<header class="topbar">
  <button class="burger" id="burgerBtn" aria-label="Toggle menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
  </button>
<a href="index.php" class="logo">
    <img src="images/logo.png" alt="Lingora Logo" class="logo-image">
    <span></span>
</a>
  <?php if ($isLoggedIn): ?>
  <div class="lang-nav-badge" title="Your preferred language">
    <span class="lang-flag-big">
      <img src="<?php echo htmlspecialchars($langFlag); ?>" alt="<?php echo htmlspecialchars($langLabel); ?>" width="26" height="18">
    </span>
    <div class="lang-nav-text">
      <span class="lang-nav-label"><?php echo htmlspecialchars($langLabel); ?></span>
      <span class="lang-nav-sub">Your Language</span>
    </div>
  </div>
  <?php endif; ?>
  <nav class="top-links">
    <a href="about_sipway_campus.php">About Sipway Campus</a>
    <a href="terms_of_use.php" class="current">Terms of Use</a>
    <a href="privacy_policy.php">Privacy Policy</a>
  </nav>
  <?php if ($isLoggedIn): ?>
  <div class="user-menu" id="userMenu">
    <span class="avatar">
      <?php if ($photoUrl): ?>
        <img src="<?php echo $photoUrl; ?>" alt="<?php echo $fullNameSafe; ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <svg style="display:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
      <?php else: ?>
        <?php echo strtoupper(substr($firstName, 0, 1)); ?>
      <?php endif; ?>
    </span>
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
  <!-- ==================== SIDEBAR ==================== -->
  <aside class="sidebar" id="sidebar">
    <a href="index.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Dashboard
    </a>
    <a href="packages.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Packages
    </a>
    <a href="session_progress.php" class="nav-item">
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
        <defs>
          <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#6366f1"/>
            <stop offset="100%" stop-color="#a855f7"/>
          </linearGradient>
        </defs>
      </svg>
    </div>
  </aside>

  <!-- ==================== MAIN ==================== -->
  <main class="main">
    <div class="page-head animate-up">
      <h1>Terms of Use</h1>
      <p>Last updated: August 2026</p>
    </div>

    <div class="panel animate-up delay-1">
      <p class="intro">Welcome to Sipway Campus. By accessing or using our website and services, you agree to be bound by these Terms of Use. Please read them carefully.</p>

      <h2>1. Acceptance of Terms</h2>
      <p>By creating an account, booking sessions, or using any feature of Sipway Campus, you confirm that you have read, understood, and agree to these Terms of Use and our Privacy Policy.</p>

      <h2>2. Eligibility</h2>
      <p>You must be at least 13 years of age to use this platform. If you are under 18, you should use the service with the involvement of a parent or guardian.</p>

      <h2>3. User Accounts</h2>
      <ul>
        <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
        <li>You agree to provide accurate and complete information during registration.</li>
        <li>You must not share your account with others or allow unauthorized access.</li>
      </ul>

      <h2>4. Packages & Payments</h2>
      <ul>
        <li>All package prices are displayed in Sri Lankan Rupees (LKR) unless stated otherwise.</li>
        <li>Payments can be made online or via bank transfer (with receipt upload).</li>
        <li>Packages become active only after successful payment confirmation.</li>
        <li>Refunds are subject to our refund policy and may require admin approval.</li>
      </ul>

      <h2>5. Session Booking</h2>
      <ul>
        <li>You may book sessions only when you have an active package with remaining sessions.</li>
        <li>Bookings are subject to lecturer availability and approval.</li>
        <li>Please arrive on time for scheduled sessions. Late cancellations may result in session deduction.</li>
      </ul>

      <h2>6. Code of Conduct</h2>
      <p>Users must treat lecturers and staff with respect. Harassment, abuse, or inappropriate behaviour may result in account suspension or termination without refund.</p>

      <h2>7. Intellectual Property</h2>
      <p>All content, materials, videos, and branding on Sipway Campus are the property of Sipway Campus or its licensors. You may not copy, distribute, or reuse them without written permission.</p>

      <h2>8. Limitation of Liability</h2>
      <p>Sipway Campus is not liable for any indirect, incidental, or consequential damages arising from the use of the platform or services, to the maximum extent permitted by law.</p>

      <h2>9. Changes to Terms</h2>
      <p>We may update these Terms of Use from time to time. Continued use of the platform after changes constitutes acceptance of the revised terms.</p>

      <h2>10. Contact</h2>
      <p>If you have questions about these Terms, please contact us through the FAQs & Support section or via the contact details provided on the platform.</p>
    </div>
  </main>
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
        <label>Language</label>
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
        <label for="gpRegGender">Gender</label>
        <select id="gpRegGender">
          <option value="">Select</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="gp-field">
        <label for="gpRegAddress">Address (optional)</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
          <input type="text" id="gpRegAddress" placeholder="Your address">
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

<script>
  const IS_LOGGED_IN = <?php echo json_encode($isLoggedIn); ?>;

  // ========== Auth Modal ==========
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
  window.openAuthModal = openAuthModal;
  window.closeAuthModal = closeAuthModal;
  window.requireAuth = requireAuth;
  function goToLogin() { openAuthModal(false); }
  window.goToLogin = goToLogin;

  authOverlay.addEventListener('click', (e) => { if (e.target === authOverlay) closeAuthModal(); });

  // ========== Login / Register form logic ==========
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

    // Language dropdown
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
      let code = (lang.flag || '').toString().trim().toUpperCase();
      if (code && FLAG_SVGS[code]) return code;
      const langCode = normalizeCode(lang.code);
      if (LANG_TO_FLAG[langCode] && FLAG_SVGS[LANG_TO_FLAG[langCode]]) return LANG_TO_FLAG[langCode];
      return 'DEFAULT';
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
      langOptions.querySelectorAll('.gp-lang-option').forEach(o => { o.classList.remove('selected'); o.setAttribute('aria-selected','false'); });
      li.classList.add('selected'); li.setAttribute('aria-selected','true');
      langCurrent.innerHTML = flagImgHtml(li.dataset.flag) + '<span>' + li.dataset.label + '</span>';
      regLanguage.value = normalizeCode(li.dataset.value);
    }
    function buildLangOption(lang, selectDefault){
      const flagCode = resolveFlagCode(lang);
      const codeNorm = normalizeCode(lang.code);
      const li = document.createElement('li');
      li.className = 'gp-lang-option' + (selectDefault ? ' selected' : '');
      li.setAttribute('role','option');
      li.setAttribute('aria-selected', selectDefault ? 'true' : 'false');
      li.dataset.value = codeNorm;
      li.dataset.flag = flagCode;
      li.dataset.label = lang.label;
      li.innerHTML = '<span class="gp-lang-option-left">' + flagImgHtml(flagCode) + '<span>' + lang.label + '</span></span><svg class="gp-lang-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
      li.addEventListener('click', () => { selectLangOption(li); closeLangDropdown(); });
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
          const defaultLang = data.languages.find(l => normalizeCode(l.code) === 'en') || data.languages[0];
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
    loadLanguages();

    // Login
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
          setTimeout(() => { window.location.href = 'terms_of_use.php'; }, 600);
        } else {
          showToast(result.message || 'Invalid email or password.', true);
        }
      } catch (err) {
        showToast('Could not connect to the server. Please try again.', true);
      } finally {
        loginBtn.classList.remove('loading'); loginBtn.disabled = false;
      }
    });

    // Register
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
      fd.append('gender', document.getElementById('gpRegGender').value);
      fd.append('address', document.getElementById('gpRegAddress').value.trim());
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

  // Sidebar
  const sidebar = document.getElementById('sidebar');
  const burgerBtn = document.getElementById('burgerBtn');
  const backdrop = document.getElementById('backdrop');
  function openSidebar(){ sidebar.classList.add('open'); backdrop.classList.add('show'); }
  function closeSidebar(){ sidebar.classList.remove('open'); backdrop.classList.remove('show'); }
  burgerBtn.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });
  backdrop.addEventListener('click', closeSidebar);

  // User dropdown (logged-in only)
  const userMenu = document.getElementById('userMenu');
  if (userMenu) {
    const userDropdown = document.getElementById('userDropdown');
    userMenu.addEventListener('click', (e) => {
      userDropdown.classList.toggle('show');
      e.stopPropagation();
    });
    document.addEventListener('click', () => userDropdown.classList.remove('show'));
  }

  // Gate links that require login → open modal on this page
  document.querySelectorAll('[data-requires-auth="1"]').forEach(link => {
    link.addEventListener('click', function(e){
      if (!IS_LOGGED_IN) {
        e.preventDefault();
        openAuthModal(false);
      }
    });
  });

  // ==================== HOW TO REGISTER VIDEO MODAL ====================
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
</script>
</body>
</html>