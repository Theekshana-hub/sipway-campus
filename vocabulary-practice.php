<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once 'db.php';
if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}
$isLoggedIn = isset($_SESSION['student_id']);
$studentId              = null;
$studentName            = '';
$studentLanguage        = 'en';
$studentPhoto           = null;
$firstName              = '';
$photoUrl               = null;
$hasVocabularyPackage   = false;
$activeVocabPackageId   = null;
$pendingVocabPackageId  = null;
$activeVocabPackageName = '';
$vocabPackages          = [];
$vocabVideos            = [];
if ($isLoggedIn) {
    $studentId   = (int)$_SESSION['student_id'];
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
    $firstName = htmlspecialchars(explode(' ', trim($studentName))[0]);
}
$studentNameJs     = json_encode($studentName);
$studentLanguageJs = $isLoggedIn ? json_encode($studentLanguage) : 'null';

$langMeta = [
    'en' => ['flag' => 'https://flagcdn.com/w40/gb.png', 'label' => 'English', 'code' => 'gb'],
    'de' => ['flag' => 'https://flagcdn.com/w40/de.png', 'label' => 'German',  'code' => 'de'],
    'zh' => ['flag' => 'https://flagcdn.com/w40/cn.png', 'label' => 'Chinese', 'code' => 'cn'],
    'ja' => ['flag' => 'https://flagcdn.com/w40/jp.png', 'label' => 'Japanese','code' => 'jp'],
    'fr' => ['flag' => 'https://flagcdn.com/w40/fr.png', 'label' => 'French',  'code' => 'fr'],
    'hi' => ['flag' => 'https://flagcdn.com/w40/in.png', 'label' => 'Hindi',   'code' => 'in'],
    'ru' => ['flag' => 'https://flagcdn.com/w40/ru.png', 'label' => 'Russian', 'code' => 'ru'],
    'ar' => ['flag' => 'https://flagcdn.com/w40/sa.png', 'label' => 'Arabic',  'code' => 'sa'],
    'ta' => ['flag' => 'https://flagcdn.com/w40/in.png', 'label' => 'Tamil',   'code' => 'in'],
    'si' => ['flag' => 'https://flagcdn.com/w40/lk.png', 'label' => 'Sinhala', 'code' => 'lk'],
    'it' => ['flag' => 'https://flagcdn.com/w40/it.png', 'label' => 'Italian', 'code' => 'it'],
];
$currentLang = strtolower($studentLanguage);
if (!isset($langMeta[$currentLang])) {
    $currentLang = 'en';
}
$langFlag  = $langMeta[$currentLang]['flag'];
$langLabel = $langMeta[$currentLang]['label'];
if ($isLoggedIn && $studentPhoto && file_exists(__DIR__ . '/' . $studentPhoto)) {
    $photoUrl = htmlspecialchars($studentPhoto);
}
$sqlPkg = "SELECT id, package_name, price, duration_label, description, is_offer
           FROM vocabulary_packages
           WHERE status = 'active'
           ORDER BY sort_order ASC, id ASC";
$resPkg = $conn->query($sqlPkg);
if ($resPkg) {
    while ($row = $resPkg->fetch_assoc()) {
        $vocabPackages[] = $row;
    }
}
if ($isLoggedIn && $studentId > 0) {
    $stmtState = $conn->prepare("
        SELECT av.package_id, av.status, vp.package_name
        FROM activated_vocabulary_packages av
        INNER JOIN vocabulary_packages vp ON av.package_id = vp.id
        WHERE av.student_id = ?
          AND av.status IN ('active', 'pending')
        ORDER BY FIELD(av.status, 'active', 'pending'), av.id DESC
        LIMIT 1
    ");
    if ($stmtState) {
        $stmtState->bind_param('i', $studentId);
        $stmtState->execute();
        $stateRow = $stmtState->get_result()->fetch_assoc();
        $stmtState->close();
        if ($stateRow) {
            if ($stateRow['status'] === 'active') {
                $hasVocabularyPackage   = true;
                $activeVocabPackageId   = (int)$stateRow['package_id'];
                $activeVocabPackageName = $stateRow['package_name'];
            } elseif ($stateRow['status'] === 'pending') {
                $pendingVocabPackageId = (int)$stateRow['package_id'];
            }
        }
    }
}
if ($isLoggedIn && $hasVocabularyPackage) {
    $stmtV = $conn->prepare("
        SELECT id, title, description, video_path, thumbnail_path, language, duration_seconds, created_at
        FROM vocabulary_videos
        WHERE is_active = 1
          AND (language = ? OR language = 'en' OR language IS NULL OR language = '')
        ORDER BY id ASC
    ");
    if ($stmtV) {
        $stmtV->bind_param("s", $currentLang);
        $stmtV->execute();
        $resV = $stmtV->get_result();
        while ($row = $resV->fetch_assoc()) {
            $vocabVideos[] = $row;
        }
        $stmtV->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="si" data-lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vocabulary Practice - Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
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
  --success: #10b981;
  --success-soft: #d1fae5;
  --amber: #f59e0b;
  --amber-soft: #fef3c7;
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
  background: var(--bg); color: var(--text); min-height: 100vh;
}
a { color: inherit; text-decoration: none; }
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes scaleIn { from { opacity:0; transform:scale(0.94); } to { opacity:1; transform:scale(1); } }
@keyframes spin { to { transform: rotate(360deg); } }
.animate-up { animation: fadeUp .5s var(--ease) both; }
.delay-1 { animation-delay: .08s; }
.delay-2 { animation-delay: .16s; }
.topbar {
  height: 64px; display: flex; align-items: center; gap: 14px;
  padding: 0 22px; background: var(--topbar-bg); position: sticky; top: 0; z-index: 50;
}
.burger { background: none; border: none; cursor: pointer; padding: 8px; display: flex; border-radius: 10px; color: #e0e7ff; }
.burger svg { width: 22px; height: 22px; }
.logo { display: flex; align-items: center; gap: 10px; font-weight: 800; color: #fff; font-size: 15.5px; white-space: nowrap; }
.logo-mark {
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  display: flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; font-weight: 800;
}
.lang-nav-badge {
  display: flex; align-items: center; gap: 8px; padding: 5px 14px 5px 8px; border-radius: 999px;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); margin-left: 6px;
}
.lang-flag-big img { width: 26px; height: 18px; border-radius: 3px; object-fit: cover; display: block; }
.lang-nav-text { display: flex; flex-direction: column; line-height: 1.15; }
.lang-nav-label { font-size: 12.5px; font-weight: 800; color: #fff; }
.lang-nav-sub { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.top-links { margin-left: auto; display: flex; gap: 22px; }
.top-links a { font-size: 13px; font-weight: 600; color: #94a3b8; }
.top-links a:hover { color: #fff; }
.user-menu {
  display: flex; align-items: center; gap: 8px; cursor: pointer;
  padding: 5px 10px; border-radius: 999px; position: relative;
}
.user-menu:hover { background: rgba(255,255,255,0.08); }
.avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  display: flex; align-items: center; justify-content: center;
  color: #fff; overflow: hidden; font-weight: 700; font-size: 13px;
}
.avatar img { width: 100%; height: 100%; object-fit: cover; }
.user-menu .chev { width: 13px; height: 13px; color: #94a3b8; }
.user-name { font-size: 13px; font-weight: 700; color: #fff; }
.dropdown {
  position: absolute; top: calc(100% + 10px); right: 0; background: var(--card);
  border: 1px solid var(--line); border-radius: var(--radius-md); box-shadow: var(--shadow-hover);
  min-width: 190px; padding: 6px; display: none; z-index: 60;
}
.dropdown.show { display: block; }
.dropdown a { display: block; padding: 11px 13px; font-size: 13.5px; font-weight: 600; border-radius: 9px; color: var(--text); }
.dropdown a:hover { background: var(--purple-soft); }
.dropdown a.danger { color: #b91c1c; }
.topbar-login-btn {
  padding: 10px 20px; border: none; border-radius: 999px;
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff; font-weight: 800; font-size: 13px; cursor: pointer;
}
.shell { display: flex; min-height: calc(100vh - 64px); }
.sidebar {
  width: 260px; flex-shrink: 0;
  background: linear-gradient(180deg, #0f0c29 0%, #1a1440 50%, #1e1b4b 100%);
  padding: 22px 14px; display: flex; flex-direction: column; gap: 4px;
  position: sticky; top: 64px; align-self: flex-start; height: calc(100vh - 64px); overflow-y: auto;
}
.nav-item {
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px;
  font-weight: 600; font-size: 14px; color: #fff; text-decoration: none; transition: all .2s;
}
.nav-item svg { width: 19px; height: 19px; flex-shrink: 0; color: #fff; }
.nav-item:hover { background: rgba(255,255,255,0.12); }
.nav-item.active {
  background: linear-gradient(135deg, #a855f7, #ec4899);
  box-shadow: 0 8px 24px -6px rgba(168,85,247,0.5);
}
.nav-item .badge-new {
  margin-left: auto; font-size: 10px; font-weight: 800; padding: 3px 8px;
  border-radius: 999px; background: linear-gradient(135deg, #a855f7, #6366f1); color: #fff;
}
.side-divider { height: 1px; background: rgba(255,255,255,0.15); margin: 14px 8px; }
.side-illustration { margin-top: auto; padding: 16px 8px 8px; text-align: center; }
.backdrop {
  display: none; position: fixed; inset: 0; background: rgba(15,12,41,0.5);
  backdrop-filter: blur(3px); z-index: 45;
}
.backdrop.show { display: block; }
.main {
  flex: 1; padding: 28px clamp(16px, 3vw, 36px) 50px; min-width: 0;
  background: linear-gradient(160deg, #f4f0ff 0%, #faf8ff 40%, #f0eaff 100%);
}
.page-title { font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 6px; }
.page-subtitle { font-size: 14px; color: var(--muted); font-weight: 500; margin-bottom: 22px; }
.guest-banner, .pending-banner, .active-banner {
  display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
  padding: 16px 20px; border-radius: var(--radius-md); margin-bottom: 22px; font-weight: 600;
}
.guest-banner { background: var(--purple-soft); border: 1px solid rgba(168,85,247,0.25); color: #6b21a8; }
.guest-banner p { flex: 1; min-width: 200px; font-size: 13.5px; font-weight: 700; }
.pending-banner { background: var(--amber-soft); border: 1px solid rgba(245,158,11,0.3); color: #b45309; font-size: 14px; }
.active-banner { background: var(--success-soft); border: 1px solid rgba(16,185,129,0.3); color: #065f46; font-size: 14px; }
.btn-primary {
  padding: 12px 26px; border: none; border-radius: var(--radius-sm);
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  font-weight: 800; font-size: 13px; cursor: pointer;
}
.section-label {
  font-size: 16.5px; font-weight: 800; color: var(--text);
  margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;
}
.pkg-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px; margin-bottom: 28px;
}
.pkg-card {
  background: var(--card); border: 1px solid var(--line-soft); border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card); overflow: hidden; display: flex; flex-direction: column;
  transition: all .3s; position: relative;
}
.pkg-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-hover); }
.pkg-card.offer {
  border: 2px solid transparent;
  background: linear-gradient(var(--card), var(--card)) padding-box,
              linear-gradient(135deg, #a855f7, #ec4899) border-box;
}
.offer-badge {
  position: absolute; top: 16px; right: 16px;
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  font-size: 11px; font-weight: 800; padding: 5px 12px; border-radius: 999px; z-index: 2;
}
.pkg-head { padding: 24px 24px 18px; display: flex; justify-content: space-between; gap: 14px; }
.pkg-icon {
  width: 52px; height: 52px; border-radius: 14px; background: var(--purple-soft);
  display: flex; align-items: center; justify-content: center; font-size: 24px;
}
.pkg-name { font-size: 17px; font-weight: 800; margin-bottom: 6px; }
.pkg-price {
  font-size: 26px; font-weight: 800;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.pkg-body { padding: 0 24px 24px; display: flex; flex-direction: column; gap: 16px; flex: 1; }
.meta-chip {
  display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px;
  background: var(--bg-soft); border-radius: 999px; font-size: 12.5px; font-weight: 700;
}
.pkg-desc { font-size: 13.5px; color: var(--muted); line-height: 1.6; }
.pkg-features { display: flex; flex-direction: column; gap: 8px; }
.feature { display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; }
.feature-check {
  width: 20px; height: 20px; border-radius: 50%; background: var(--success-soft);
  color: var(--success); display: flex; align-items: center; justify-content: center;
}
.feature-check svg { width: 12px; height: 12px; }
.pkg-cta {
  margin-top: auto; width: 100%; padding: 14px; border: none; border-radius: var(--radius-sm);
  font-family: inherit; font-size: 14.5px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.pkg-cta.activate {
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  box-shadow: 0 6px 18px rgba(168,85,247,0.3);
}
.pkg-cta.activate:hover { filter: brightness(1.08); }
.pkg-cta.koko-pay {
  background: linear-gradient(135deg, #00b4d8, #0077b6); color: #fff;
  box-shadow: 0 6px 18px rgba(0,119,182,0.35);
}
.pkg-cta.koko-pay:hover { filter: brightness(1.08); }
.pkg-cta.login-needed { background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff; }
.pkg-cta.pending { background: var(--amber-soft); color: #b45309; cursor: default; }
.empty-msg {
  text-align: center; color: var(--muted); font-size: 15px; font-weight: 600;
  padding: 48px 20px; grid-column: 1 / -1;
}
.panel {
  background: var(--card); border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg); box-shadow: var(--shadow-card); padding: 24px;
}
.panel h2 {
  font-size: 16.5px; font-weight: 800; margin: 0 0 16px 0;
  display: flex; align-items: center; gap: 8px;
}
.vocab-stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.vocab-stat-chip {
  display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;
  border-radius: 999px; background: var(--purple-soft); color: #6b21a8;
  font-size: 12.5px; font-weight: 700;
}
.vocab-stat-chip.green { background: var(--success-soft); color: #065f46; }
.vocab-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px;
}
.vocab-card {
  background: var(--bg-soft); border: 1px solid var(--line-soft);
  border-radius: 16px; overflow: hidden; transition: all .25s; cursor: pointer;
  position: relative;
}
.vocab-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
.vocab-order-badge {
  position: absolute; top: 10px; left: 10px; z-index: 3;
  min-width: 30px; height: 30px; padding: 0 8px; border-radius: 999px;
  background: rgba(15,12,41,0.85); backdrop-filter: blur(2px);
  color: #fff; font-size: 13px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.25);
  border: 1.5px solid rgba(255,255,255,0.35);
}
.vocab-thumb {
  width: 100%; aspect-ratio: 16/9;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  display: flex; align-items: center; justify-content: center;
  color: #fff; position: relative; overflow: hidden;
}
.vocab-thumb img { width: 100%; height: 100%; object-fit: cover; }
.vocab-thumb .play-overlay {
  position: absolute; inset: 0; background: rgba(15,12,41,0.4);
  display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .25s;
}
.vocab-card:hover .play-overlay { opacity: 1; }
.vocab-thumb .play-btn {
  width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.95);
  display: flex; align-items: center; justify-content: center; color: #a855f7;
}
.vocab-duration {
  position: absolute; bottom: 8px; right: 8px; padding: 3px 8px;
  border-radius: 6px; background: rgba(0,0,0,0.7); color: #fff; font-size: 11px; font-weight: 700;
}
.vocab-body { padding: 14px 16px 16px; }
.vocab-title {
  font-size: 14.5px; font-weight: 800; margin: 0 0 6px 0; line-height: 1.35;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.vocab-desc {
  font-size: 12.5px; color: var(--muted); margin: 0 0 10px 0; line-height: 1.45;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.vocab-meta-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.lang-badge {
  display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px;
  border-radius: 999px; background: var(--purple-soft); color: #6b21a8;
  font-size: 10.5px; font-weight: 800;
}
.lesson-tag {
  font-size: 10.5px; font-weight: 800; color: var(--purple);
  letter-spacing: .3px; text-transform: uppercase;
}
.empty-videos { text-align: center; padding: 48px 20px; color: var(--muted); }
.empty-videos p { font-size: 14.5px; font-weight: 700; color: var(--text); margin: 0 0 6px 0; }
.locked-hint {
  text-align: center; padding: 28px 16px; border-radius: var(--radius-md);
  background: var(--bg-soft); border: 1px dashed var(--line); margin-top: 8px;
  color: var(--muted); font-size: 13.5px; font-weight: 600;
}
.video-modal-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(11,18,32,0.88); backdrop-filter: blur(6px);
  z-index: 500; align-items: center; justify-content: center; padding: 20px;
}
.video-modal-overlay.show { display: flex; animation: fadeIn .25s; }
.video-modal-box {
  background: #0f0c29; border-radius: var(--radius-lg); width: 100%; max-width: 920px; overflow: hidden;
}
.video-modal-header {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 14px 20px; background: #fff;
}
.video-modal-title {
  font-size: 15px; font-weight: 800; color: var(--text); flex: 1;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.video-modal-close {
  padding: 9px 16px; border-radius: 9px; border: 1px solid #fce7f3;
  background: #fce7f3; color: #be185d; font-weight: 800; font-size: 13px; cursor: pointer;
}
.video-modal-player { width: 100%; background: #000; aspect-ratio: 16/9; }
.video-modal-player video { width: 100%; height: 100%; max-height: 70vh; outline: none; }
.video-modal-desc { padding: 16px 20px 20px; background: #fff; font-size: 13.5px; color: var(--muted); }

/* ========== AUTH MODAL (Login / Register) ========== */
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

@media (max-width: 900px) { .top-links { display: none; } }
@media (max-width: 820px) {
  .sidebar {
    position: fixed; left: 0; top: 64px; transform: translateX(-100%);
    width: 280px; height: calc(100vh - 64px); z-index: 46; box-shadow: 0 0 40px rgba(0,0,0,0.3);
  }
  .sidebar.open { transform: translateX(0); }
  .lang-nav-text { display: none; }
}
@media (max-width: 560px) {
  .topbar { padding: 0 10px; }
  .logo span:not(.logo-mark) { display: none; }
  .user-name { display: none; }
  .main { padding: 18px 12px 36px; }
  .page-title { font-size: 22px; }
  .pkg-grid, .vocab-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<header class="topbar">
  <button class="burger" id="burgerBtn" type="button" aria-label="Menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <a href="index.php" class="logo">
    <span class="logo-mark">SC</span>
    <span>Sipway English Academy</span>
  </a>
  <?php if ($isLoggedIn): ?>
  <div class="lang-nav-badge">
    <div class="lang-flag-big"><img src="<?php echo htmlspecialchars($langFlag); ?>" alt=""></div>
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
        <?php echo strtoupper(substr($firstName ?: 'S', 0, 1)); ?>
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
  <button class="topbar-login-btn" type="button" onclick="openAuthModal(false)">Login / Register</button>
  <?php endif; ?>
</header>
<div class="backdrop" id="backdrop"></div>
<div class="shell">
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
    <a href="vocabulary-practice.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        <path d="M8 7h8M8 11h6"/>
      </svg>
      Vocabulary Practice
      <span class="badge-new">New</span>
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
    <div class="side-illustration">
      <svg viewBox="0 0 200 180" fill="none" style="max-width:170px;margin:0 auto;display:block;">
        <ellipse cx="100" cy="160" rx="70" ry="12" fill="rgba(168,85,247,0.15)"/>
        <rect x="60" y="80" width="50" height="45" rx="4" fill="#c084fc"/>
        <path d="M70 70 L100 55 L130 70 L100 85 Z" fill="#fbbf24"/>
        <rect x="95" y="70" width="10" height="25" fill="#f59e0b"/>
      </svg>
    </div>
  </aside>
  <main class="main">
    <h1 class="page-title animate-up">Vocabulary Practice</h1>
    <p class="page-subtitle animate-up">
      <?php if ($hasVocabularyPackage): ?>
        Package active — videos පිළිවෙලට (Video 1, 2, 3...) බලන්න පුළුවන්. 📚
      <?php else: ?>
        Vocabulary package එකක් activate කළාම videos unlock වෙනවා.
      <?php endif; ?>
    </p>
    <?php if (!$isLoggedIn): ?>
      <div class="guest-banner animate-up delay-1">
        <p>🔐 Login කරලා Vocabulary package activate කරන්න. ඊට පස්සේ videos බලන්න පුළුවන්.</p>
        <button class="btn-primary" type="button" onclick="openAuthModal(false)">Login / Register</button>
      </div>
    <?php endif; ?>
    <?php if ($isLoggedIn && $pendingVocabPackageId !== null && !$hasVocabularyPackage): ?>
      <div class="pending-banner animate-up delay-1">
        ⏳ ගෙවීම තහවුරු වෙමින් පවතී. Complete වුණාම videos unlock වෙනවා.
      </div>
    <?php endif; ?>
    <?php if ($hasVocabularyPackage): ?>
      <div class="active-banner animate-up delay-1">
        ✓ Active: <?php echo htmlspecialchars($activeVocabPackageName ?: 'Vocabulary Package'); ?>
      </div>
      <div class="panel animate-up delay-1">
        <h2>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          </svg>
          Practice Videos
        </h2>
        <div class="vocab-stats">
          <span class="vocab-stat-chip green">✓ Unlocked</span>
          <span class="vocab-stat-chip"><?php echo count($vocabVideos); ?> video<?php echo count($vocabVideos) !== 1 ? 's' : ''; ?></span>
          <span class="vocab-stat-chip">📌 Watch in order 1 → <?php echo count($vocabVideos); ?></span>
        </div>
        <?php if (count($vocabVideos) === 0): ?>
          <div class="empty-videos">
            <p>තවම videos upload කරලා නැහැ</p>
            <span>Admin → Vocabulary Videos එකතු කළ පසු මෙතන පෙනෙනවා.</span>
          </div>
        <?php else: ?>
          <div class="vocab-grid">
            <?php
            $vocabIndex = 0;
            foreach ($vocabVideos as $v):
              $vocabIndex++;
              $thumb = !empty($v['thumbnail_path']) ? htmlspecialchars($v['thumbnail_path']) : null;
              $video = htmlspecialchars($v['video_path']);
              $title = htmlspecialchars($v['title']);
              $desc  = htmlspecialchars($v['description'] ?? '');
              $dur   = (int)($v['duration_seconds'] ?? 0);
              $durStr = $dur > 0 ? sprintf('%d:%02d', floor($dur / 60), $dur % 60) : '';
              $vLang = strtolower($v['language'] ?? 'en');
              $langInfo = $langMeta[$vLang] ?? $langMeta['en'];
            ?>
            <div class="vocab-card"
                 data-video="<?php echo $video; ?>"
                 data-title="<?php echo $title; ?>"
                 data-desc="<?php echo $desc; ?>"
                 onclick="openVideoPlayer(this)">
              <div class="vocab-thumb">
                <span class="vocab-order-badge">#<?php echo $vocabIndex; ?></span>
                <?php if ($thumb): ?>
                  <img src="<?php echo $thumb; ?>" alt="" onerror="this.style.display='none'">
                <?php else: ?>
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                <?php endif; ?>
                <div class="play-overlay">
                  <div class="play-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                  </div>
                </div>
                <?php if ($durStr): ?><span class="vocab-duration"><?php echo $durStr; ?></span><?php endif; ?>
              </div>
              <div class="vocab-body">
                <span class="lesson-tag">Video <?php echo $vocabIndex; ?></span>
                <h3 class="vocab-title"><?php echo $title; ?></h3>
                <?php if ($desc): ?><p class="vocab-desc"><?php echo $desc; ?></p><?php endif; ?>
                <div class="vocab-meta-row">
                  <span class="lang-badge">
                    <img src="<?php echo $langInfo['flag']; ?>" width="14" height="10" style="border-radius:2px;object-fit:cover;" alt="">
                    <?php echo htmlspecialchars($langInfo['label']); ?>
                  </span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <h2 class="section-label animate-up delay-1">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
          <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
        </svg>
        Vocabulary Packages
      </h2>
      <div class="pkg-grid animate-up delay-2">
        <?php if (count($vocabPackages) === 0): ?>
          <div class="empty-msg">
            Vocabulary packages තවම නැහැ.<br>
            <span style="font-size:13px;font-weight:500;">Admin → Packages → <b>Vocabulary Packages</b> tab එකෙන් package එකක් හදන්න.</span>
          </div>
        <?php else: ?>
          <?php foreach ($vocabPackages as $pkg):
            $isThisPending = ($pendingVocabPackageId !== null && $pendingVocabPackageId === (int)$pkg['id']);
          ?>
          <div class="pkg-card <?php echo ((int)$pkg['is_offer'] === 1) ? 'offer' : ''; ?>">
            <?php if ((int)$pkg['is_offer'] === 1): ?>
              <span class="offer-badge">BEST VALUE</span>
            <?php endif; ?>
            <div class="pkg-head">
              <div>
                <div class="pkg-name"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                <div class="pkg-price">Rs. <?php echo number_format((float)$pkg['price'], 0); ?></div>
              </div>
              <div class="pkg-icon">📚</div>
            </div>
            <div class="pkg-body">
              <div>
                <span class="meta-chip"><?php echo htmlspecialchars($pkg['duration_label'] ?: 'Access'); ?></span>
              </div>
              <?php if (!empty($pkg['description'])): ?>
                <p class="pkg-desc"><?php echo htmlspecialchars($pkg['description']); ?></p>
              <?php endif; ?>
              <div class="pkg-features">
                <div class="feature">
                  <span class="feature-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg></span>
                  All Vocabulary Videos
                </div>
                <div class="feature">
                  <span class="feature-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg></span>
                  Watch Anytime
                </div>
                <div class="feature">
                  <span class="feature-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg></span>
                  Improve Word Power
                </div>
              </div>
              <?php if (!$isLoggedIn): ?>
                <button class="pkg-cta login-needed" type="button" onclick="openAuthModal(false)">🔐 Login to Activate</button>
              <?php elseif ($isThisPending): ?>
                <button class="pkg-cta pending" type="button" disabled>Payment Processing…</button>
              <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:10px; margin-top:auto;">
                  <!-- Card / DFCC Online Payment -->
                  <button class="pkg-cta activate"
                          type="button"
                          data-id="<?php echo (int)$pkg['id']; ?>"
                          data-name="<?php echo htmlspecialchars($pkg['package_name']); ?>"
                          data-price="<?php echo (float)$pkg['price']; ?>">
                    💳 Online Payment (Card)
                  </button>

                  <!-- Koko BNPL Payment -->
                  <button class="pkg-cta koko-pay"
                          type="button"
                          data-id="<?php echo (int)$pkg['id']; ?>"
                          data-name="<?php echo htmlspecialchars($pkg['package_name']); ?>"
                          data-price="<?php echo (float)$pkg['price']; ?>">
                    🟣 Pay with Koko (BNPL)
                  </button>

                  <!-- Bank Transfer -->
                  <a href="upload-vocab-bank-receipt.php?package_id=<?php echo (int)$pkg['id']; ?>"
                     class="pkg-cta"
                     style="background:#f3e8ff; color:#6b21a8; text-decoration:none; box-shadow:none;">
                    🏦 Bank Transfer / Upload Receipt
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="locked-hint animate-up delay-2">
        🔒 Videos මෙතනින් බලන්න — package එකක් activate කළාට පස්සේ unlock වෙනවා.
      </div>
    <?php endif; ?>
  </main>
</div>

<!-- ==================== VIDEO MODAL ==================== -->
<div class="video-modal-overlay" id="videoModalOverlay">
  <div class="video-modal-box">
    <div class="video-modal-header">
      <div class="video-modal-title" id="videoModalTitle">Video</div>
      <button class="video-modal-close" id="videoModalCloseBtn" type="button">Close</button>
    </div>
    <div class="video-modal-player">
      <video id="videoModalPlayer" controls playsinline controlsList="nodownload"></video>
    </div>
    <div class="video-modal-desc" id="videoModalDesc" style="display:none;"></div>
  </div>
</div>

<!-- ==================== AUTH MODAL (Login / Register) ==================== -->
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

<script>
  const IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  const STUDENT_NAME = <?php echo $studentNameJs; ?>;
  const STUDENT_LANGUAGE = <?php echo $studentLanguageJs; ?>;

  const sidebar = document.getElementById('sidebar');
  const burgerBtn = document.getElementById('burgerBtn');
  const backdrop = document.getElementById('backdrop');
  burgerBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('show');
  });
  backdrop.addEventListener('click', () => {
    sidebar.classList.remove('open');
    backdrop.classList.remove('show');
  });
  const userMenu = document.getElementById('userMenu');
  if (userMenu) {
    const userDropdown = document.getElementById('userDropdown');
    userMenu.addEventListener('click', (e) => {
      userDropdown.classList.toggle('show');
      e.stopPropagation();
    });
    document.addEventListener('click', () => userDropdown.classList.remove('show'));
  }

  // ==================== AUTH MODAL HELPERS ====================
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

  // ==================== LOGIN / REGISTER FORM LOGIC ====================
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

    // Login validation + submit
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
          setTimeout(() => { window.location.href = 'vocabulary-practice.php'; }, 600);
        } else {
          showToast(result.message || 'Invalid email or password.', true);
        }
      } catch (err) {
        showToast('Could not connect to the server. Please try again.', true);
      } finally {
        loginBtn.classList.remove('loading'); loginBtn.disabled = false;
      }
    });

    // Register validation + submit
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

  // ==================== Online Payment → Checkout Page (Vocabulary) ====================
  document.querySelectorAll('.pkg-cta.activate').forEach(btn => {
    btn.addEventListener('click', function () {
      if (!IS_LOGGED_IN) {
        openAuthModal(false);
        return;
      }

      const packageId = this.dataset.id;
      if (!packageId) {
        alert('Invalid package selection.');
        return;
      }

      this.innerHTML = 'Redirecting to Checkout...';
      this.disabled = true;

      window.location.href = 'checkout.php?package_id=' + encodeURIComponent(packageId) + '&type=vocabulary';
    });
  });

  // ==================== Koko BNPL Payment ====================
  document.querySelectorAll('.pkg-cta.koko-pay').forEach(btn => {
    btn.addEventListener('click', function () {
      if (!IS_LOGGED_IN) {
        openAuthModal(false);
        return;
      }

      const packageId    = this.dataset.id;
      const packageName  = this.dataset.name || 'Vocabulary Package';
      const packagePrice = parseFloat(this.dataset.price);
      const originalText = this.innerHTML;

      if (!packageId || !packagePrice || packagePrice <= 0) {
        alert('Invalid package data');
        return;
      }

      this.innerHTML = 'Redirecting to Koko...';
      this.disabled = true;

      fetch('initiate-koko-payment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id: packageId,
          name: packageName,
          price: packagePrice,
          type: 'vocabulary'
        })
      })
      .then(async (response) => {
        const contentType = response.headers.get('content-type') || '';
        const text = await response.text();

        if (contentType.includes('application/json') || text.trim().startsWith('{')) {
          try {
            const data = JSON.parse(text);
            if (data.success === false) {
              alert(data.message || 'Koko payment initiation failed');
              this.innerHTML = originalText;
              this.disabled = false;
              return;
            }
            // If API returns a redirect URL
            if (data.redirect_url) {
              window.location.href = data.redirect_url;
              return;
            }
          } catch (e) {}
        }

        // If HTML form / redirect page returned
        document.open();
        document.write(text);
        document.close();
      })
      .catch(err => {
        console.error(err);
        alert('Koko payment initiation failed. Please try again.');
        this.innerHTML = originalText;
        this.disabled = false;
      });
    });
  });

  // ==================== Video Player ====================
  const videoOverlay = document.getElementById('videoModalOverlay');
  const videoPlayer  = document.getElementById('videoModalPlayer');
  const videoTitle   = document.getElementById('videoModalTitle');
  const videoDesc    = document.getElementById('videoModalDesc');

  function openVideoPlayer(card) {
    videoTitle.textContent = card.dataset.title || 'Video';
    if (card.dataset.desc) {
      videoDesc.textContent = card.dataset.desc;
      videoDesc.style.display = 'block';
    } else {
      videoDesc.style.display = 'none';
    }
    videoPlayer.src = card.dataset.video;
    videoOverlay.classList.add('show');
    videoPlayer.play().catch(() => {});
  }

  function closeVideoPlayer() {
    videoPlayer.pause();
    videoPlayer.removeAttribute('src');
    videoPlayer.load();
    videoOverlay.classList.remove('show');
  }

  window.openVideoPlayer = openVideoPlayer;
  document.getElementById('videoModalCloseBtn').addEventListener('click', closeVideoPlayer);
  videoOverlay.addEventListener('click', (e) => {
    if (e.target === videoOverlay) closeVideoPlayer();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && videoOverlay.classList.contains('show')) closeVideoPlayer();
  });
</script>
</body>
</html>