<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}
$isLoggedIn = isset($_SESSION['student_id']);

// ---------- Defaults (guest) ----------
$studentId       = 0;
$studentName     = 'Guest';
$firstName       = 'Guest';
$fullNameSafe    = 'Guest';
$photoUrl        = null;
$studentLanguage = 'en';
$hasActivePackage = false;
$activePackageSubject = null;

if ($isLoggedIn) {
    $studentId   = (int)$_SESSION['student_id'];
    $studentName = $_SESSION['student_name'] ?? 'Guest';

    $stmt = $conn->prepare("SELECT full_name, language, profile_photo FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $studentName = $row['full_name'];
        $firstName    = htmlspecialchars(explode(' ', trim($studentName))[0]);
        $fullNameSafe = htmlspecialchars($studentName);
        $studentLanguage = $row['language'] ?: 'en';
        $studentPhoto = $row['profile_photo'] ?? null;
        if ($studentPhoto && file_exists(__DIR__ . '/' . $studentPhoto)) {
            $photoUrl = htmlspecialchars($studentPhoto);
        }
    } else {
        session_destroy();
        session_start();
        $isLoggedIn = false;
        $studentId  = 0;
        $studentName = 'Guest';
        $firstName   = 'Guest';
        $fullNameSafe = 'Guest';
    }
    $stmt->close();

    // Active package
    $stmtPkg = $conn->prepare("
        SELECT ap.id, p.package_name
        FROM activated_packages ap
        JOIN packages p ON p.id = ap.package_id
        WHERE ap.student_id = ? AND ap.status = 'active' AND ap.sessions_remaining > 0
        LIMIT 1
    ");
    $stmtPkg->bind_param("i", $studentId);
    $stmtPkg->execute();
    $resPkg = $stmtPkg->get_result();
    if ($rowPkg = $resPkg->fetch_assoc()) {
        $hasActivePackage = true;
        $activePackageSubject = $rowPkg['package_name'] ?? null;
    }
    $stmtPkg->close();
}

$studentLanguageJs = json_encode(strtolower(trim($studentLanguage)));
$activePackageSubjectJs = json_encode($activePackageSubject !== null && $activePackageSubject !== '' ? strtolower(trim($activePackageSubject)) : null);

// Language meta
$langMeta = [
    'en' => ['code' => 'gb', 'label' => 'English'],
    'de' => ['code' => 'de', 'label' => 'German'],
    'zh' => ['code' => 'cn', 'label' => 'Chinese'],
    'ja' => ['code' => 'jp', 'label' => 'Japanese'],
    'fr' => ['code' => 'fr', 'label' => 'French'],
    'hi' => ['code' => 'in', 'label' => 'Hindi'],
    'ru' => ['code' => 'ru', 'label' => 'Russian'],
    'ar' => ['code' => 'sa', 'label' => 'Arabic'],
    'ta' => ['code' => 'in', 'label' => 'Tamil'],
    'si' => ['code' => 'lk', 'label' => 'Sinhala'],
    'it' => ['code' => 'it', 'label' => 'Italian'],
];
$currentLang = strtolower(trim($studentLanguage));
if (!isset($langMeta[$currentLang])) {
    $currentLang = 'en';
}
$langCode    = $langMeta[$currentLang]['code'];
$langLabel   = $langMeta[$currentLang]['label'];
$langFlagUrl = "https://flagcdn.com/w40/{$langCode}.png";

function resolveLecturerPhoto($photo){
    if (empty($photo)) return null;
    $photo = trim($photo);
    if (preg_match('#^https?://#i', $photo)) return $photo;
    if (strpos($photo, '/') !== false) return $photo;
    return 'uploads/lecturers/' . $photo;
}

// ===== Fetch ALL lecturers =====
$lecturersRaw = [];
$resLect = $conn->query("SELECT id, full_name, email, qualifications, subject, photo, language FROM lecturers ORDER BY full_name ASC");
if ($resLect) {
    while ($r = $resLect->fetch_assoc()) {
        $r['id'] = (int)$r['id'];
        $r['photo'] = resolveLecturerPhoto($r['photo']);
        $r['language'] = strtolower(trim($r['language'] ?? 'en'));
        $r['slots_by_subject'] = [];
        $lecturersRaw[$r['id']] = $r;
    }
}

// ===== Fetch availability WITH subject (approved + future only) =====
// ★ FIX: now also joins bookings to know how many students already booked each
//   slot, so a slot that another student has already taken shows as
//   "Already Booked" instead of a live "Book Now" button.
$stmtAvail = $conn->prepare("
    SELECT
        la.id AS slot_id,
        la.lecturer_id, la.subject, la.date, la.start_time, la.end_time,
        la.status, la.room_name, la.is_free,
        la.session_type, la.max_capacity,
        COUNT(b.id) AS booked_count
    FROM lecturer_availability la
    LEFT JOIN bookings b
        ON b.lecturer_id = la.lecturer_id
       AND b.session_date = la.date
       AND b.session_time = la.start_time
       AND b.status IN ('Pending','Accepted')
    WHERE la.date >= CURDATE()
      AND la.status IN ('scheduled','live')
      AND la.approval_status = 'approved'
    GROUP BY la.id
    ORDER BY la.date ASC, la.start_time ASC
");
$stmtAvail->execute();
$resAvail = $stmtAvail->get_result();
while ($a = $resAvail->fetch_assoc()) {
    $lid = (int)$a['lecturer_id'];
    if (!isset($lecturersRaw[$lid])) continue;
    $slotSubject = trim($a['subject'] ?? '');
    if ($slotSubject === '') $slotSubject = '—';
    $a['is_free'] = (int)($a['is_free'] ?? 0);
    $a['subject'] = $slotSubject;

    // ★ FIX: work out capacity + whether this slot is already fully booked
    //   by someone else (session_type / max_capacity come from lecturer_availability;
    //   if your table doesn't have those columns yet, this still safely
    //   defaults to individual = 1 seat / group = 10 seats).
    $sessionType = strtolower(trim($a['session_type'] ?? 'individual'));
    if (!in_array($sessionType, ['group', 'individual'], true)) {
        $sessionType = 'individual';
    }
    $maxCapacity = (int)($a['max_capacity'] ?? 0);
    if ($maxCapacity <= 0) {
        $maxCapacity = ($sessionType === 'group') ? 10 : 1;
    }
    $bookedCount = (int)($a['booked_count'] ?? 0);

    $a['slot_id']      = (int)($a['slot_id'] ?? 0);
    $a['session_type'] = $sessionType;
    $a['max_capacity'] = $maxCapacity;
    $a['booked_count'] = $bookedCount;
    $a['is_full']      = ($a['is_free'] != 1) && ($bookedCount >= $maxCapacity);

    if (!isset($lecturersRaw[$lid]['slots_by_subject'][$slotSubject])) {
        $lecturersRaw[$lid]['slots_by_subject'][$slotSubject] = [];
    }
    if (count($lecturersRaw[$lid]['slots_by_subject'][$slotSubject]) < 6) {
        $lecturersRaw[$lid]['slots_by_subject'][$slotSubject][] = $a;
    }
}
$stmtAvail->close();

// ===== Expand multi-subject lecturers → one card per subject =====
$lecturers = [];
foreach ($lecturersRaw as $lect) {
    $subjects = array_filter(array_map('trim', explode(',', $lect['subject'] ?? '')));
    if (empty($subjects)) {
        $subjects = ['General'];
    }
    foreach ($subjects as $subj) {
        $card = $lect;
        unset($card['slots_by_subject']);
        $card['subject'] = $subj;
        $card['_cardKey'] = $lect['id'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $subj);
        $card['slots'] = $lect['slots_by_subject'][$subj] ?? [];
        $lecturers[] = $card;
    }
}

// ===== Student's own bookings =====
$myBookings = [];
if ($isLoggedIn && $studentId > 0) {
    $stmtB = $conn->prepare("
        SELECT lecturer_id, session_date, session_time, status
        FROM bookings
        WHERE student_id = ? AND status IN ('Pending', 'Accepted')
    ");
    $stmtB->bind_param("i", $studentId);
    $stmtB->execute();
    $resB = $stmtB->get_result();
    while ($b = $resB->fetch_assoc()) {
        $myBookings[] = [
            'lecturer_id' => (int)$b['lecturer_id'],
            'date'        => $b['session_date'],
            'time'        => date('H:i', strtotime($b['session_time'])),
            'status'      => $b['status'],
        ];
    }
    $stmtB->close();
}
$myBookingsJson = json_encode($myBookings);

// ===== REGISTER GUIDE VIDEO (for How to Register modal) =====
$registerVideo = null; // source_type, player_type, player_src, title

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

        // YouTube: watch, embed, shorts and youtu.be links
        if (preg_match(
            '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i',
            $url,
            $m
        )) {
            $registerVideo['player_type'] = 'youtube';
            $registerVideo['player_src']  = 'https://www.youtube.com/embed/' . $m[1];
        }
        // Vimeo
        elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            $registerVideo['player_type'] = 'vimeo';
            $registerVideo['player_src']  = 'https://player.vimeo.com/video/' . $m[1];
        }
        // Direct video URL
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
$lecturersJson = json_encode(array_values($lecturers));
?>
<!DOCTYPE html>
<html lang="si" data-lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lecturer Details - Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --sidebar-text: #ffffff;
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
  background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; min-height: 100vh;
}
a { color: inherit; text-decoration: none; }
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes scaleIn { from { opacity:0; transform:scale(0.94); } to { opacity:1; transform:scale(1); } }
@keyframes spin { to { transform: rotate(360deg); } }
.animate-up { animation: fadeUp .5s var(--ease) both; }
.delay-1 { animation-delay: .08s; }
.delay-2 { animation-delay: .16s; }

/* TOPBAR */
.topbar {
  height: 64px; display: flex; align-items: center; gap: 14px;
  padding: 0 22px; background: var(--topbar-bg); position: sticky; top: 0; z-index: 50;
}
.burger {
  background: none; border: none; cursor: pointer; padding: 8px; display: flex;
  border-radius: 10px; color: #e0e7ff; flex-shrink: 0; transition: background .2s;
}
.burger:hover { background: rgba(255,255,255,0.08); }
.burger svg { width: 22px; height: 22px; }
.logo {
  display: flex; align-items: center; gap: 10px; font-weight: 800; color: #fff;
  font-size: 15.5px; letter-spacing: -0.3px; flex-shrink: 0; white-space: nowrap;
}
.logo-mark {
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 13px; font-weight: 800;
  box-shadow: 0 4px 12px -3px rgba(239,68,68,0.5);
}
.lang-nav-badge {
  display: flex; align-items: center; gap: 8px;
  padding: 5px 14px 5px 8px; border-radius: 999px;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
  margin-left: 6px; flex-shrink: 0;
}
.lang-flag-img {
  width: 26px; height: 18px; object-fit: cover; border-radius: 3px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2); flex-shrink: 0;
}
.lang-nav-text { display: flex; flex-direction: column; line-height: 1.15; }
.lang-nav-label { font-size: 12.5px; font-weight: 800; color: #fff; }
.lang-nav-sub { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }
.top-links { margin-left: auto; display: flex; align-items: center; gap: 22px; }
.top-links a { font-size: 13px; font-weight: 600; color: #94a3b8; transition: color .2s; }
.top-links a:hover { color: #fff; }
.user-menu {
  display: flex; align-items: center; gap: 8px; cursor: pointer;
  padding: 5px 10px; border-radius: 999px; position: relative; flex-shrink: 0; transition: background .2s;
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
  position: absolute; top: calc(100% + 10px); right: 0;
  background: var(--card); border: 1px solid var(--line);
  border-radius: var(--radius-md); box-shadow: var(--shadow-hover);
  min-width: 190px; padding: 6px; display: none; z-index: 60;
  animation: scaleIn .2s var(--ease);
}
.dropdown.show { display: block; }
.dropdown a {
  display: block; padding: 11px 13px; font-size: 13.5px; font-weight: 600;
  border-radius: 9px; color: var(--text); transition: background .15s;
}
.dropdown a:hover { background: var(--purple-soft); }
.dropdown a.danger { color: #b91c1c; }
.topbar-login-btn {
  padding: 10px 20px; border: none; border-radius: 999px; flex-shrink: 0;
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  font-weight: 800; font-size: 13px; letter-spacing: 0.3px; cursor: pointer;
  box-shadow: 0 8px 18px -5px rgba(168,85,247,0.45); transition: filter .2s, transform .15s;
}
.topbar-login-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }

/* LAYOUT */
.shell { display: flex; min-height: calc(100vh - 64px); }
.sidebar {
  width: 260px; flex-shrink: 0;
  background: linear-gradient(180deg, #0f0c29 0%, #1a1440 50%, #1e1b4b 100%);
  padding: 22px 14px; display: flex; flex-direction: column; gap: 4px;
  position: sticky; top: 64px; align-self: flex-start;
  height: calc(100vh - 64px); overflow-y: auto; transition: transform .3s var(--ease);
}
.nav-item {
  display: flex; align-items: center; gap: 12px; padding: 12px 16px;
  border-radius: 12px; font-weight: 600; font-size: 14px; color: #fff; cursor: pointer;
  transition: all .2s var(--ease);
}
.nav-item svg { width: 19px; height: 19px; flex-shrink: 0; opacity: 0.85; color: #fff; }
.nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
.nav-item.active {
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  box-shadow: 0 8px 24px -6px rgba(168,85,247,0.5);
}
.nav-item.active svg { opacity: 1; }
.nav-item .badge-new {
  margin-left: auto; font-size: 10px; font-weight: 800; padding: 3px 8px;
  border-radius: 999px; background: linear-gradient(135deg, #a855f7, #6366f1); color: #fff;
}
.side-divider { height: 1px; background: rgba(255,255,255,0.08); margin: 14px 8px; }
.side-illustration { margin-top: auto; padding: 16px 8px 8px; text-align: center; }
.backdrop {
  display: none; position: fixed; inset: 0;
  background: rgba(15,12,41,0.5); backdrop-filter: blur(3px); z-index: 45;
}
.backdrop.show { display: block; animation: fadeIn .25s; }
.main {
  flex: 1; padding: 28px clamp(16px, 3vw, 36px) 50px; min-width: 0;
  background: linear-gradient(160deg, #f4f0ff 0%, #faf8ff 40%, #f0eaff 100%);
  position: relative; overflow: hidden;
}
.main::before {
  content: ''; position: absolute; top: -80px; right: -60px; width: 320px; height: 320px;
  background: radial-gradient(circle, rgba(168,85,247,0.12) 0%, transparent 70%);
  pointer-events: none;
}
.page-title {
  font-size: 28px; font-weight: 800; color: var(--text);
  letter-spacing: -0.5px; margin: 0 0 6px 0; position: relative; z-index: 1;
}
.page-subtitle {
  font-size: 13.5px; color: var(--muted); font-weight: 600;
  margin: 0 0 22px 0; position: relative; z-index: 1;
}
.search-bar {
  display: flex; align-items: center; gap: 10px;
  background: var(--card); border: 1.5px solid var(--line);
  border-radius: var(--radius-md); padding: 11px 14px; margin-bottom: 14px;
  max-width: 420px; position: relative; z-index: 1;
}
.search-bar:focus-within {
  border-color: #a855f7; box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
}
.search-bar svg { width: 17px; height: 17px; color: var(--muted-2); flex-shrink: 0; }
.search-bar input {
  border: none; outline: none; background: transparent;
  font-size: 13.5px; font-weight: 600; color: var(--text); width: 100%; font-family: inherit;
}
.filter-row {
  display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
  margin-bottom: 22px; position: relative; z-index: 1;
}
.subject-filter-wrap { position: relative; min-width: 220px; }
.subject-filter-wrap select {
  width: 100%; padding: 11px 40px 11px 14px;
  border: 1.5px solid var(--line); border-radius: var(--radius-md);
  background: var(--card); font-size: 13.5px; font-weight: 600;
  color: var(--text); font-family: inherit; appearance: none; cursor: pointer; outline: none;
}
.subject-filter-wrap select:focus {
  border-color: #a855f7; box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
}
.subject-filter-wrap::after {
  content: ''; position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent;
  border-top: 6px solid var(--muted-2); pointer-events: none;
}
.subject-filter-wrap select:disabled {
  background: var(--bg-soft); color: var(--muted-2); cursor: not-allowed;
}
.locked-subject-note {
  font-size: 12px; font-weight: 700; color: #6b21a8;
  background: var(--purple-soft); border: 1px solid rgba(168,85,247,0.25);
  padding: 8px 14px; border-radius: 999px;
  display: inline-flex; align-items: center; gap: 6px;
}
.lect-list {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 22px; position: relative; z-index: 1;
}
.lect-card {
  background: var(--card); border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg); box-shadow: var(--shadow-card);
  display: flex; flex-direction: column; overflow: hidden;
  transition: box-shadow .3s var(--ease), transform .3s var(--ease), border-color .3s;
}
.lect-card:hover { box-shadow: var(--shadow-hover); transform: translateY(-6px); border-color: #e9d5ff; }
.lect-card-photo {
  position: relative; width: 100%; height: 260px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--purple-soft), var(--line-soft)); overflow: hidden;
}
.lect-card-photo img {
  width: 100%; height: 100%; object-fit: contain; object-position: center;
  display: block; background: #fff; transition: transform .45s var(--ease);
}
.lect-card:hover .lect-card-photo img { transform: scale(1.06); }
.lect-card-photo::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(15,12,41,0.65) 0%, rgba(15,12,41,0.05) 38%, rgba(15,12,41,0) 55%);
  pointer-events: none;
}
.lect-photo-fallback {
  width: 100%; height: 100%; min-height: 260px;
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 54px;
}
.lect-card-lang-chip {
  position: absolute; top: 12px; right: 12px; z-index: 2;
  display: flex; align-items: center; gap: 6px;
  padding: 5px 12px 5px 7px; border-radius: 999px;
  background: rgba(255,255,255,0.95); backdrop-filter: blur(4px);
  box-shadow: 0 4px 14px rgba(0,0,0,0.16);
  font-size: 11px; font-weight: 800; color: #6b21a8; white-space: nowrap;
}
.lect-card-lang-chip img { width: 17px; height: 12px; object-fit: cover; border-radius: 2px; flex-shrink: 0; }
.lect-card-name-row {
  position: absolute; left: 14px; right: 14px; bottom: 12px; z-index: 2;
  display: flex; flex-direction: column; gap: 7px;
}
.lect-card-name {
  font-size: 18px; font-weight: 800; color: #fff; margin: 0; line-height: 1.25;
  text-shadow: 0 2px 10px rgba(0,0,0,0.45);
}
.lect-card-subject-chip {
  display: inline-flex; align-items: center; gap: 5px;
  align-self: flex-start; padding: 5px 13px; border-radius: 999px;
  font-size: 10.5px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase;
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  box-shadow: 0 6px 16px -3px rgba(124,58,237,0.65);
}
.lect-card-body {
  flex: 1; min-width: 0; padding: 16px 18px 18px;
  display: flex; flex-direction: column; gap: 12px;
}
.lect-section-label {
  font-size: 10.5px; font-weight: 800; color: var(--muted-2);
  text-transform: uppercase; letter-spacing: .5px; margin: 0 0 5px;
}
.lect-qual-box { position: relative; }
.lect-qual-content {
  max-height: 52px; overflow: hidden; transition: max-height .35s var(--ease);
}
.lect-qual-content.expanded { max-height: 2000px; }
.lect-qual-content.no-collapse { max-height: none; }
.lect-qual-para { font-size: 13px; color: var(--text); line-height: 1.55; margin: 0 0 6px; font-weight: 500; }
.lect-qual-para.muted { color: var(--muted-2); font-style: italic; }
.lect-qual-para strong { color: #6b21a8; font-weight: 800; }
.lect-qual-list { margin: 0 0 4px; padding-left: 16px; display: flex; flex-direction: column; gap: 4px; }
.lect-qual-list li { font-size: 13px; color: var(--text); line-height: 1.5; font-weight: 500; }
.lect-qual-fade {
  position: absolute; left: 0; right: 0; bottom: 22px; height: 28px;
  background: linear-gradient(to bottom, transparent, var(--card)); pointer-events: none;
}
.lect-qual-toggle {
  display: inline-flex; background: none; border: none; padding: 4px 0 0; margin-top: 2px;
  color: #6b21a8; font-weight: 800; font-size: 11.5px; cursor: pointer; font-family: inherit;
}
.lect-slots-wrap { border-top: 1px solid var(--line-soft); padding-top: 12px; margin-top: auto; }
.lect-slot-list {
  display: flex; flex-direction: column; gap: 8px;
  max-height: 176px; overflow-y: auto; padding-right: 2px;
}
.lect-slot-list::-webkit-scrollbar { width: 5px; }
.lect-slot-list::-webkit-scrollbar-thumb { background: var(--line); border-radius: 999px; }
.lect-slot-chip {
  display: flex; flex-direction: column; align-items: stretch; gap: 10px;
  padding: 11px 12px; border: 1px solid var(--line-soft); border-radius: 12px;
  background: var(--bg-soft); font-size: 12px;
  transition: border-color .2s var(--ease), box-shadow .2s var(--ease), transform .2s var(--ease);
}
.lect-slot-chip:hover {
  border-color: #d8b4fe; box-shadow: 0 6px 16px -8px rgba(124,58,237,0.35); transform: translateY(-1px);
}
.lect-slot-when { display: flex; align-items: center; gap: 10px; min-width: 0; width: 100%; }

/* Date pill */
.lect-slot-date-pill {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  width: 40px; padding: 5px 4px; border-radius: 9px; flex-shrink: 0;
  background: #fff; border: 1px solid var(--line);
}
.lect-slot-date-pill .dow {
  font-size: 8.5px; font-weight: 800; color: #a855f7; text-transform: uppercase; letter-spacing: .4px; line-height: 1.2;
}
.lect-slot-date-pill .dnum { font-size: 15px; font-weight: 800; color: var(--text); line-height: 1.1; }
.lect-slot-date-pill .mon {
  font-size: 8px; font-weight: 700; color: var(--muted-2); text-transform: uppercase; letter-spacing: .3px; line-height: 1.2;
}

/* Time block */
.lect-slot-time-block { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; }
.lect-slot-time-range {
  font-weight: 800; color: var(--text); font-size: 13.5px;
  display: flex; align-items: center; gap: 5px; white-space: nowrap;
  overflow: hidden; text-overflow: ellipsis;
}
.lect-slot-time-range svg { width: 13px; height: 13px; color: #a855f7; flex-shrink: 0; }
.lect-slot-meta-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.lect-slot-duration { font-size: 10.5px; color: var(--muted-2); font-weight: 700; white-space: nowrap; }

.lect-slot-book-btn {
  width: 100%; padding: 9px 14px; border: none; border-radius: 9px; flex-shrink: 0;
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  font-weight: 800; font-size: 12px; cursor: pointer; white-space: nowrap;
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  box-shadow: 0 6px 16px -5px rgba(168,85,247,0.55);
  transition: filter .15s, transform .15s, box-shadow .15s;
}
.lect-slot-book-btn svg { width: 13px; height: 13px; flex-shrink: 0; }
.lect-slot-book-btn:hover { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 8px 20px -5px rgba(168,85,247,0.65); }
.lect-slot-book-btn.locked {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  box-shadow: 0 6px 16px -5px rgba(245,158,11,0.55);
}
.lect-slot-book-btn.locked:hover { box-shadow: 0 8px 20px -5px rgba(245,158,11,0.65); }
.lect-slot-chip.is-free {
  border-color: #a7f3d0;
  background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
}
.lect-slot-chip.is-free .lect-slot-date-pill { border-color: #a7f3d0; }
.lect-slot-chip.is-full {
  border-color: #fecaca;
  background: linear-gradient(135deg, #fef2f2, #fff5f5);
  opacity: 0.9;
}
.lect-slot-chip.is-full .lect-slot-date-pill { border-color: #fecaca; }
.lect-free-display {
  width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  padding: 9px 14px; border-radius: 9px; flex-shrink: 0;
  background: rgba(16, 185, 129, 0.12); color: #059669;
  font-weight: 800; font-size: 12px; white-space: nowrap;
  border: 1px dashed #6ee7b7; cursor: default; user-select: none;
}
.lect-free-badge {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 8px; border-radius: 999px;
  background: linear-gradient(135deg, #10b981, #059669); color: #fff;
  font-size: 9.5px; font-weight: 800; letter-spacing: 0.3px;
  box-shadow: 0 2px 8px -2px rgba(16, 185, 129, 0.45); flex-shrink: 0;
}
.lect-slot-booked-badge {
  width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  padding: 9px 14px; border-radius: 9px; flex-shrink: 0;
  background: var(--success-soft); color: var(--success);
  font-weight: 800; font-size: 12px; white-space: nowrap; cursor: default; user-select: none;
}
.lect-slot-pending-badge {
  width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  padding: 9px 14px; border-radius: 9px; flex-shrink: 0;
  background: #fef3c7; color: #b45309;
  font-weight: 800; font-size: 12px; white-space: nowrap; cursor: default; user-select: none;
}
/* ★ FIX: badge shown when ANOTHER student has already taken this slot */
.lect-slot-full-badge {
  width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  padding: 9px 14px; border-radius: 9px; flex-shrink: 0;
  background: #fee2e2; color: #b91c1c;
  font-weight: 800; font-size: 12px; white-space: nowrap; cursor: default; user-select: none;
}
.lect-no-slots { font-size: 12.5px; color: var(--muted-2); font-style: italic; padding: 4px 0; }
.no-results {
  grid-column: 1 / -1;
  text-align: center; padding: 60px 20px; color: var(--muted); font-size: 14.5px; font-weight: 600;
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
  box-shadow: 0 10px 24px -6px rgba(168,85,247,0.45);
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.gp-btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); }
.gp-btn-primary:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
.gp-btn-primary.loading { pointer-events: none; }
.gp-btn-primary.loading::after {
  content: ''; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite;
}
.gp-switch-text {
  text-align: center; margin-top: 18px; font-size: 13.5px; color: var(--muted); font-weight: 500;
}
.gp-switch-text a { color: var(--purple); font-weight: 800; }
.gp-switch-text a:hover { text-decoration: underline; }
.gp-toast {
  position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(80px);
  background: #1e1b4b; color: #fff; padding: 14px 22px; border-radius: 12px;
  font-size: 13.5px; font-weight: 600; box-shadow: 0 12px 40px rgba(0,0,0,0.25);
  z-index: 500; opacity: 0; transition: all .35s var(--ease); max-width: 90%;
  text-align: center;
}
.gp-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.gp-toast.gp-error-toast { background: #b91c1c; }

/* Language custom dropdown */
.gp-lang-select { position: relative; }
.gp-lang-trigger {
  width: 100%; padding: 12.5px 14px; border-radius: var(--radius-sm);
  border: 1.5px solid var(--line); background: var(--bg-soft); color: var(--text);
  font-size: 14px; font-weight: 600; font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  transition: border-color .15s, box-shadow .15s;
}
.gp-lang-trigger:hover, .gp-lang-trigger.open {
  border-color: #a855f7; background: var(--card);
  box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
}
.gp-lang-trigger svg { width: 16px; height: 16px; color: var(--muted-2); transition: transform .2s; }
.gp-lang-trigger.open svg { transform: rotate(180deg); }
.gp-lang-options {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0;
  background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-sm);
  box-shadow: var(--shadow-hover); max-height: 220px; overflow-y: auto;
  display: none; z-index: 20; padding: 6px;
}
.gp-lang-options.open { display: block; animation: scaleIn .2s var(--ease); }
.gp-lang-option {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 13.5px; font-weight: 600;
  transition: background .15s;
}
.gp-lang-option:hover { background: var(--purple-soft); }
.gp-lang-option.selected { background: var(--purple-soft); color: #6b21a8; }
.gp-lang-option-left { display: flex; align-items: center; gap: 10px; }
.gp-lang-flag { width: 22px; height: 16px; border-radius: 2px; flex-shrink: 0; }
.gp-lang-tick { width: 16px; height: 16px; color: #a855f7; opacity: 0; }
.gp-lang-option.selected .gp-lang-tick { opacity: 1; }
.gp-lang-option-loading { padding: 14px; text-align: center; color: var(--muted); font-size: 13px; }

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

/* ========== RESPONSIVE (Dashboard-style) ========== */
@media (max-width: 1100px) {
  .lect-list { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
}

@media (max-width: 900px) {
  .top-links { display: none; }
  .page-title { font-size: 24px; }
}

@media (max-width: 820px) {
  .sidebar {
    position: fixed; left: 0; top: 64px; transform: translateX(-100%);
    width: 280px; height: calc(100vh - 64px); z-index: 46;
    box-shadow: 0 0 40px rgba(0,0,0,0.3);
  }
  .sidebar.open { transform: translateX(0); }
  .main { padding: 22px 16px 40px; }
  .search-bar { max-width: 100%; }
  .subject-filter-wrap { min-width: 100%; flex: 1; }
  .filter-row { flex-direction: column; align-items: stretch; }
  .locked-subject-note { width: 100%; justify-content: center; }
  .lect-list { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
  .lect-card-photo { height: 220px; }
  .lect-photo-fallback { min-height: 220px; font-size: 48px; }
}

@media (max-width: 640px) {
  .topbar { padding: 0 12px; gap: 10px; }
  .logo span:last-child { display: none; }
  .lang-nav-text { display: none; }
  .user-name { display: none; }
  .main { padding: 18px 12px 36px; }
  .page-title { font-size: 22px; }
  .page-subtitle { font-size: 12.5px; margin-bottom: 16px; }
  .lect-list { grid-template-columns: 1fr; gap: 14px; }
  .lect-card-photo { height: 200px; }
  .lect-photo-fallback { min-height: 200px; font-size: 44px; }
  .lect-card-name { font-size: 15.5px; }
  .lect-card-body { padding: 14px 14px 16px; }
  .lect-slot-date-pill { width: 44px; }
  .gp-card { padding: 24px 18px; width: 100%; }
  .auth-modal-overlay { padding: 12px; align-items: flex-start; }
}

@media (max-width: 400px) {
  .topbar { padding: 0 8px; gap: 6px; }
  .logo-mark { width: 30px; height: 30px; font-size: 12px; }
  .topbar-login-btn { padding: 8px 14px; font-size: 12px; }
  .page-title { font-size: 20px; }
  .lect-card-photo { height: 180px; }
  .lect-photo-fallback { min-height: 180px; font-size: 40px; }
  .lect-card-lang-chip { font-size: 10px; padding: 4px 8px 4px 5px; }
  .search-bar { padding: 10px 12px; }
  .search-bar input { font-size: 13px; }
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

<!-- ========== TOPBAR ========== -->
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
    <img class="lang-flag-img" src="<?php echo htmlspecialchars($langFlagUrl); ?>" alt="">
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
        <a href="edit_profile.php">✏️ My Profile</a>
       
        <a href="logout.php" class="danger">Log out</a>
      </div>
    </div>
  <?php else: ?>
    <button class="topbar-login-btn" onclick="openAuthModal(false)">Login / Register</button>
  <?php endif; ?>
</header>

<div class="backdrop" id="backdrop"></div>

<div class="shell">
  <!-- ========== SIDEBAR ========== -->
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
    <a href="lecturer-details.php" class="nav-item active">
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

  <!-- ========== MAIN ========== -->
  <main class="main">
    <h1 class="page-title animate-up">Find Your Lecturer</h1>
    <p class="page-subtitle animate-up delay-1">
      Subject එකකට වෙනම lecturers සහ available slots පෙනෙනවා.
    </p>
    <div class="search-bar animate-up delay-1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <input type="text" id="lectSearchInput" placeholder="Search by name or subject...">
    </div>
    <div class="filter-row animate-up delay-2">
      <div class="subject-filter-wrap">
        <select id="subjectFilter">
          <option value="">All Subjects</option>
        </select>
      </div>
      <?php if ($isLoggedIn && $hasActivePackage && $activePackageSubject): ?>
        <span class="locked-subject-note">
          📦 Active package: <strong><?php echo htmlspecialchars($activePackageSubject); ?></strong>
        </span>
      <?php endif; ?>
    </div>
    <div class="lect-list" id="lectGrid"></div>
  </main>
</div>

<!-- ========== AUTH MODAL (Login + Register) ========== -->
<div class="auth-modal-overlay" id="authModalOverlay">
  <!-- LOGIN CARD -->
  <div class="gp-card" id="gpLoginCard">
    <button class="auth-modal-close" onclick="closeAuthModal()" aria-label="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="gp-head">
      <p class="gp-eyebrow">Welcome back</p>
      <h1>Login to Sipway</h1>
      <p>Enter your email and password to continue</p>
    </div>
    <form id="gpLoginForm" autocomplete="on">
      <div class="gp-field">
        <label>Email</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
          <input type="email" id="gpLoginEmail" placeholder="you@example.com" autocomplete="email">
        </div>
        <div class="gp-error" id="gpLoginEmailErr">⚠ Valid email required</div>
      </div>
      <div class="gp-field">
        <label>Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpLoginPass" placeholder="Your password" autocomplete="current-password">
          <button type="button" class="gp-toggle-pass" data-target="gpLoginPass" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-error" id="gpLoginPassErr">⚠ Password required</div>
      </div>
      <div class="gp-row-inline">
        <label class="gp-checkbox-label"><input type="checkbox" id="gpRemember"> Remember me</label>
   
      </div>
      <button type="submit" class="gp-btn-primary" id="gpLoginBtn">Login</button>
    </form>
    <p class="gp-switch-text">Don't have an account? <a href="javascript:void(0)" id="gpGoRegister">Register</a></p>
  </div>

  <!-- REGISTER CARD -->
  <div class="gp-card gp-hidden" id="gpRegisterCard">
    <button class="auth-modal-close" onclick="closeAuthModal()" aria-label="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="gp-head">
      <p class="gp-eyebrow">Join Sipway</p>
      <h1>Create Account</h1>
      <p>Fill in the details to get started</p>
    </div>
    <form id="gpRegisterForm" autocomplete="on" enctype="multipart/form-data">
      <div class="gp-field">
        <label>Full Name</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <input type="text" id="gpRegName" placeholder="Your full name" autocomplete="name">
        </div>
        <div class="gp-error" id="gpRegNameErr">⚠ Full name required</div>
      </div>
      <div class="gp-field">
        <label>Email</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
          <input type="email" id="gpRegEmail" placeholder="you@example.com" autocomplete="email">
        </div>
        <div class="gp-error" id="gpRegEmailErr">⚠ Valid email required</div>
      </div>
      <div class="gp-field">
        <label>Mobile (07XXXXXXXX)</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
          <input type="tel" id="gpRegMobile" placeholder="07XXXXXXXX" maxlength="10" autocomplete="tel">
        </div>
        <div class="gp-error" id="gpRegMobileErr">⚠ Valid 10-digit mobile required</div>
      </div>
      <div class="gp-field">
        <label>Language</label>
        <div class="gp-lang-select" id="gpLangSelect">
          <button type="button" class="gp-lang-trigger" id="gpLangTrigger" aria-expanded="false">
            <span id="gpLangCurrent">Select language</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <ul class="gp-lang-options" id="gpLangOptions" role="listbox"></ul>
          <input type="hidden" id="gpRegLanguage" name="language" value="en">
        </div>
      </div>
      
      <div class="gp-field">
        <label>Profile Photo (optional)</label>
        <input type="file" id="gpRegPhoto" accept="image/jpeg,image/png,image/webp" style="padding:10px 14px;">
      </div>
      <div class="gp-field">
        <label>Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpRegPass" placeholder="Min 6 characters" autocomplete="new-password">
          <button type="button" class="gp-toggle-pass" data-target="gpRegPass" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-strength-meter" id="gpStrengthMeter"><span></span><span></span><span></span><span></span></div>
        <div class="gp-strength-label" id="gpStrengthLabel">Minimum 6 characters</div>
        <div class="gp-error" id="gpRegPassErr">⚠ Password must be at least 6 characters</div>
      </div>
      <div class="gp-field">
        <label>Confirm Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpRegPass2" placeholder="Re-enter password" autocomplete="new-password">
          <button type="button" class="gp-toggle-pass" data-target="gpRegPass2" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-error" id="gpRegPass2Err">⚠ Passwords do not match</div>
      </div>
      <button type="submit" class="gp-btn-primary" id="gpRegisterBtn">Create Account</button>
    </form>
    <p class="gp-switch-text">Already have an account? <a href="javascript:void(0)" id="gpGoLogin">Login</a></p>
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
(function(){
  const IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  const HAS_ACTIVE_PACKAGE = <?php echo $hasActivePackage ? 'true' : 'false'; ?>;
  const STUDENT_LANGUAGE = <?php echo $studentLanguageJs; ?>;
  const ACTIVE_PACKAGE_SUBJECT = <?php echo $activePackageSubjectJs; ?>;
  const MY_BOOKINGS = <?php echo $myBookingsJson; ?>;
  let ALL_LECTURERS = <?php echo $lecturersJson; ?>;

  const LANG_MAP = {
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

  function normalizeLang(c){ return String(c||'en').trim().toLowerCase(); }

  function getBookingForSlot(lecturerId, date, startTime) {
    const t = String(startTime).slice(0,5);
    return MY_BOOKINGS.find(b =>
      b.lecturer_id === lecturerId &&
      b.date === date &&
      b.time === t
    ) || null;
  }

  // Language + active package subject filter
  let LECTURERS = ALL_LECTURERS.filter(l => {
    if (IS_LOGGED_IN) {
      if (normalizeLang(l.language) !== normalizeLang(STUDENT_LANGUAGE)) return false;
      if (HAS_ACTIVE_PACKAGE && ACTIVE_PACKAGE_SUBJECT) {
        return (l.subject || '').toLowerCase().trim() === ACTIVE_PACKAGE_SUBJECT.toLowerCase().trim();
      }
    }
    return true;
  });

  // Load subjects into dropdown
  (function loadSubjects(){
    const select = document.getElementById('subjectFilter');
    if (!select) return;
    if (IS_LOGGED_IN && HAS_ACTIVE_PACKAGE && ACTIVE_PACKAGE_SUBJECT) {
      select.innerHTML = `<option value="${ACTIVE_PACKAGE_SUBJECT}">${ACTIVE_PACKAGE_SUBJECT}</option>`;
      select.value = ACTIVE_PACKAGE_SUBJECT;
      select.disabled = true;
      return;
    }
    fetch('subjects_api.php')
      .then(res => res.json())
      .then(data => {
        if (!data.success || !Array.isArray(data.data)) return;
        data.data.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.name;
          opt.textContent = s.name;
          select.appendChild(opt);
        });
      })
      .catch(err => console.error('Subject list load failed:', err));
  })();

  function fmtDate(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
  }
  function fmtTime(t) {
    const [h, m] = String(t).split(':').map(Number);
    const d = new Date(); d.setHours(h, m, 0);
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
  }
  function fmtDatePill(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    return {
      dow: d.toLocaleDateString('en-GB', { weekday: 'short' }),
      dnum: d.getDate(),
      mon: d.toLocaleDateString('en-GB', { month: 'short' })
    };
  }
  function calcDuration(start, end) {
    const [sh, sm] = String(start).split(':').map(Number);
    const [eh, em] = String(end).split(':').map(Number);
    let mins = (eh * 60 + em) - (sh * 60 + sm);
    if (mins < 0) mins += 24 * 60;
    if (mins <= 0) return '';
    if (mins % 60 === 0) return (mins / 60) + 'h session';
    if (mins < 60) return mins + 'm session';
    return Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm session';
  }
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function qualificationsToHtml(raw) {
    if (!raw || !raw.trim()) return '<p class="lect-qual-para muted">Not provided yet</p>';
    const original = raw.replace(/\r\n/g, '\n').trim();
    let lines = original.split('\n').map(l => l.trim()).filter(Boolean);
    if (lines.length <= 1) lines = original.split(/\s\*\s+/).map(l => l.trim()).filter(Boolean);
    const parsed = lines.map(line => {
      const isBullet = /^\*\s+/.test(line);
      let clean = escapeHtml(line.replace(/^\*\s+/, ''));
      clean = clean.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\*(.+?)\*/g, '<strong>$1</strong>');
      return { isBullet, html: clean };
    });
    let html = '', bullets = [];
    const flush = () => {
      if (bullets.length) {
        html += `<ul class="lect-qual-list">${bullets.map(b => `<li>${b}</li>`).join('')}</ul>`;
        bullets = [];
      }
    };
    parsed.forEach(({ isBullet, html: h }) => {
      if (isBullet) bullets.push(h);
      else { flush(); html += `<p class="lect-qual-para">${h}</p>`; }
    });
    flush();
    return html || `<p class="lect-qual-para">${escapeHtml(original)}</p>`;
  }
  function toggleQual(cardKey) {
    const content = document.getElementById('qual-content-' + cardKey);
    const btn = document.getElementById('qual-btn-' + cardKey);
    const fade = document.getElementById('qual-fade-' + cardKey);
    if (!content) return;
    const expanded = content.classList.toggle('expanded');
    if (fade) fade.style.display = expanded ? 'none' : 'block';
    if (btn) btn.textContent = expanded ? 'Show less ▲' : 'Show more ▼';
  }
  window.toggleQual = toggleQual;

  function bookSlot(lecturerId, lecturerName, date, start, end, isFree, slotId) {
    if (!IS_LOGGED_IN) {
      openAuthModal(false);
      return;
    }
    if (!isFree && !HAS_ACTIVE_PACKAGE) {
      alert('Package එකක් active නැති නිසා session එකක් book කරන්න බැහැ. කරුණාකර මුලින් package එකක් activate කරන්න.');
      window.location.href = 'packages.php';
      return;
    }
    const params = new URLSearchParams({
      lecturer_id: lecturerId,
      lecturer_name: lecturerName,
      date: date,
      start: start,
      end: end,
      is_free: isFree ? '1' : '0'
    });
    if (slotId && Number(slotId) > 0) {
      params.set('availability_id', slotId);
    }
    window.location.href = `book-session.php?${params.toString()}`;
  }
  window.bookSlot = bookSlot;

  function renderLecturerCard(l) {
    const initials = (l.full_name || '?').charAt(0).toUpperCase();
    const photoHtml = l.photo
      ? `<img src="${l.photo}" alt="${escapeHtml(l.full_name || '')}" onerror="this.outerHTML='<div class=&quot;lect-photo-fallback&quot;>${initials}</div>'">`
      : `<div class="lect-photo-fallback">${initials}</div>`;
    const slotsHtml = (l.slots && l.slots.length > 0)
      ? l.slots.map(s => {
          const nameEscaped = (l.full_name || '').replace(/'/g, "\\'");
          const isFree = Number(s.is_free) === 1;
          const freeBadge = isFree ? `<span class="lect-free-badge">FREE</span>` : '';
          const startShort = String(s.start_time).slice(0,5);
          const endShort = String(s.end_time).slice(0,5);
          const existingBooking = getBookingForSlot(l.id, s.date, s.start_time);

          // ★ FIX: figure out if this slot is already fully booked by
          //   someone else (any student, not just the current one).
          const bookedCount = Number(s.booked_count) || 0;
          const maxCapacity = Number(s.max_capacity) || 1;
          const isFull = !isFree && (Boolean(s.is_full) || bookedCount >= maxCapacity);

          let chipClass = 'lect-slot-chip';
          if (isFree) chipClass += ' is-free';
          else if (isFull && !existingBooking) chipClass += ' is-full';

          let actionHtml;
          if (isFree) {
            actionHtml = `<span class="lect-free-display">🎁 Free Session</span>`;
          } else if (existingBooking) {
            actionHtml = existingBooking.status === 'Accepted'
              ? `<span class="lect-slot-booked-badge">✔ Already Booked</span>`
              : `<span class="lect-slot-pending-badge">⏳ Pending Approval</span>`;
          } else if (isFull) {
            // ★ FIX: another student already took this slot — no Book Now button
            actionHtml = `<span class="lect-slot-full-badge">🚫 Already Booked</span>`;
          } else {
            let btnLabel, btnClass, btnIcon;
            if (!IS_LOGGED_IN) {
              btnLabel = 'Login to Book';
              btnClass = 'locked';
              btnIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>`;
            } else if (!HAS_ACTIVE_PACKAGE) {
              btnLabel = 'Activate';
              btnClass = 'locked';
              btnIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>`;
            } else {
              btnLabel = 'Book Now';
              btnClass = '';
              btnIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>`;
            }
            actionHtml = `<button class="lect-slot-book-btn ${btnClass}" onclick="bookSlot(${l.id}, '${nameEscaped}', '${s.date}', '${startShort}', '${endShort}', false, ${Number(s.slot_id) || 0})">${btnIcon}${btnLabel}</button>`;
          }
          const dp = fmtDatePill(s.date);
          const duration = calcDuration(startShort, endShort);
          return `
            <div class="${chipClass}">
              <span class="lect-slot-when">
                <span class="lect-slot-date-pill">
                  <span class="dow">${dp.dow}</span>
                  <span class="dnum">${dp.dnum}</span>
                  <span class="mon">${dp.mon}</span>
                </span>
                <span class="lect-slot-time-block">
                  <span class="lect-slot-time-range">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
                    ${fmtTime(s.start_time)} – ${fmtTime(s.end_time)}
                  </span>
                  <span class="lect-slot-meta-row">
                    ${duration ? `<span class="lect-slot-duration">${duration}</span>` : ''}
                    ${freeBadge}
                  </span>
                </span>
              </span>
              ${actionHtml}
            </div>`;
        }).join('')
      : '';
    const qualRaw = l.qualifications || '';
    const isLong = qualRaw.length > 160;
    const qualHtml = qualificationsToHtml(qualRaw);
    const langKey = normalizeLang(l.language);
    const langInfo = LANG_MAP[langKey] || { code: 'gb', label: langKey || 'English' };
    const cardKey = l._cardKey || (l.id + '_' + (l.subject || 'x'));
    return `
      <div class="lect-card" data-name="${(l.full_name||'').toLowerCase()}" data-subject="${(l.subject||'').toLowerCase()}">
        <div class="lect-card-photo">
          ${photoHtml}
          <span class="lect-card-lang-chip">
            <img src="https://flagcdn.com/w40/${langInfo.code}.png" alt="${escapeHtml(langInfo.label)}">
            ${escapeHtml(langInfo.label)}
          </span>
          <div class="lect-card-name-row">
            <span class="lect-card-subject-chip">${escapeHtml(l.subject || 'General')}</span>
            <p class="lect-card-name">${escapeHtml(l.full_name || '')}</p>
          </div>
        </div>
        <div class="lect-card-body">
          <div class="lect-qual-box">
            <p class="lect-section-label">Qualifications</p>
            <div class="lect-qual-content${isLong ? '' : ' no-collapse'}" id="qual-content-${cardKey}">${qualHtml}</div>
            ${isLong ? `
              <div class="lect-qual-fade" id="qual-fade-${cardKey}"></div>
              <button type="button" class="lect-qual-toggle" id="qual-btn-${cardKey}" onclick="toggleQual('${cardKey}')">Show more ▼</button>
            ` : ''}
          </div>
          <div class="lect-slots-wrap">
            <p class="lect-section-label">Available Dates — ${escapeHtml(l.subject || '')}</p>
            <div class="lect-slot-list">
              ${slotsHtml || '<div class="lect-no-slots">දැන් available slots නැහැ</div>'}
            </div>
          </div>
        </div>
      </div>`;
  }

  function renderGrid(list) {
    const grid = document.getElementById('lectGrid');
    if (list.length === 0) {
      if (IS_LOGGED_IN) {
        const label = (LANG_MAP[normalizeLang(STUDENT_LANGUAGE)] || {}).label || STUDENT_LANGUAGE;
        if (HAS_ACTIVE_PACKAGE && ACTIVE_PACKAGE_SUBJECT) {
          grid.innerHTML = `<div class="no-results">ඔයාගේ active package එකේ subject එකට (${ACTIVE_PACKAGE_SUBJECT}) සහ language එකට (${label}) match වෙන lecturers හම්බුණේ නෑ.</div>`;
        } else {
          grid.innerHTML = `<div class="no-results">ඔයාගේ language එකට (${label}) lecturers හම්බුණේ නෑ හෝ තෝරාගත් subject එකට match වෙන කෙනෙක් නැහැ.</div>`;
        }
      } else {
        grid.innerHTML = `<div class="no-results">Lecturers හම්බුණේ නෑ.</div>`;
      }
      return;
    }
    grid.innerHTML = list.map(renderLecturerCard).join('');
  }

  function applyFilters() {
    const q = (document.getElementById('lectSearchInput').value || '').trim().toLowerCase();
    const subjectSelect = document.getElementById('subjectFilter');
    const selectedSubject = subjectSelect && !subjectSelect.disabled
      ? (subjectSelect.value || '').trim().toLowerCase()
      : '';
    let filtered = LECTURERS;
    if (selectedSubject) {
      filtered = filtered.filter(l => (l.subject || '').toLowerCase() === selectedSubject);
    }
    if (q) {
      filtered = filtered.filter(l =>
        (l.full_name || '').toLowerCase().includes(q) ||
        (l.subject || '').toLowerCase().includes(q)
      );
    }
    renderGrid(filtered);
  }

  renderGrid(LECTURERS);
  document.getElementById('lectSearchInput').addEventListener('input', applyFilters);
  document.getElementById('subjectFilter').addEventListener('change', applyFilters);

  // Sidebar
  const sidebar = document.getElementById('sidebar');
  const burgerBtn = document.getElementById('burgerBtn');
  const backdrop = document.getElementById('backdrop');
  function openSidebar() { sidebar.classList.add('open'); backdrop.classList.add('show'); }
  function closeSidebar() { sidebar.classList.remove('open'); backdrop.classList.remove('show'); }
  burgerBtn.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
  backdrop.addEventListener('click', closeSidebar);

  // User dropdown
  const userMenu = document.getElementById('userMenu');
  if (userMenu) {
    const userDropdown = document.getElementById('userDropdown');
    userMenu.addEventListener('click', (e) => { userDropdown.classList.toggle('show'); e.stopPropagation(); });
    document.addEventListener('click', () => userDropdown.classList.remove('show'));
  }

  // ========== AUTH MODAL LOGIC ==========
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
  function closeAuthModal() {
    authOverlay.classList.remove('show');
  }
  window.openAuthModal = openAuthModal;
  window.closeAuthModal = closeAuthModal;

  authOverlay.addEventListener('click', (e) => {
    if (e.target === authOverlay) closeAuthModal();
  });

  document.getElementById('gpGoRegister').addEventListener('click', () => openAuthModal(true));
  document.getElementById('gpGoLogin').addEventListener('click', () => openAuthModal(false));

  function showToast(msg, isError) {
    gpToast.textContent = (isError ? '⚠ ' : '✓ ') + msg;
    gpToast.classList.toggle('gp-error-toast', !!isError);
    gpToast.classList.add('show');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => gpToast.classList.remove('show'), 2800);
  }

  function isValidEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); }
  function isValidMobile(v) { return /^0\d{9}$/.test(v.trim()); }
  function setFieldState(input, errEl, valid) {
    input.classList.toggle('gp-invalid', !valid);
    input.classList.toggle('gp-valid', valid);
    errEl.classList.toggle('show', !valid);
  }

  // Password toggle
  document.querySelectorAll('.gp-toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      const isPass = target.type === 'password';
      target.type = isPass ? 'text' : 'password';
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
        setTimeout(() => { window.location.href = 'lecturer-details.php'; }, 600);
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

  // Protect auth-required links
  document.querySelectorAll('[data-requires-auth="1"]').forEach(link => {
    link.addEventListener('click', function(e) {
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
})();
</script>
</body>
</html>