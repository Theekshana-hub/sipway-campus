<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.php");
    exit();
}

require_once 'db.php';

if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}

$studentId = $_SESSION['student_id'];
$studentName = $_SESSION['student_name'] ?? '';

$stmt = $conn->prepare("SELECT full_name FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $studentName = $row['full_name'];
} else {
    session_destroy();
    header("Location: student-login.php");
    exit();
}
$stmt->close();

$firstName    = htmlspecialchars(explode(' ', trim($studentName))[0]);
$fullNameSafe = htmlspecialchars($studentName);

// Checks (once, cached) whether a given column exists on a given table —
// lets us safely reference optional columns like `lecturers.photo` without
// crashing if your schema doesn't have them.
function columnExistsSafe(mysqli $conn, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) return $cache[$key];
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->bind_param("ss", $table, $column);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $cache[$key] = ((int)$res['c'] > 0);
    } catch (mysqli_sql_exception $e) {
        return $cache[$key] = false;
    }
}

// Checks (once, cached) whether a given table exists — lets the page keep
// working with sensible defaults even before class_details_migration.sql is run.
function tableExistsSafe(mysqli $conn, string $table): bool {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->bind_param("s", $table);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $cache[$table] = ((int)$res['c'] > 0);
    } catch (mysqli_sql_exception $e) {
        return $cache[$table] = false;
    }
}

// ==================== FIND THE STUDENT'S CURRENT / MOST RECENT LECTURER ====================
// Priority: latest Accepted booking. Falls back to the most recent booking of
// any status if no Accepted one exists, so the page still shows *someone*.
$lecturer = null;
$photoCol = columnExistsSafe($conn, 'lecturers', 'photo') ? 'l.photo' : 'NULL AS photo';

try {
    $stmt2 = $conn->prepare("
        SELECT l.id, l.full_name, l.email, l.qualifications, l.subject, {$photoCol}
        FROM bookings b
        INNER JOIN lecturers l ON b.lecturer_id = l.id
        WHERE b.student_id = ?
        ORDER BY (b.status = 'Accepted') DESC, b.session_date DESC, b.session_time DESC
        LIMIT 1
    ");
    $stmt2->bind_param("i", $studentId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $lecturer = $res2->fetch_assoc();
    $stmt2->close();
} catch (mysqli_sql_exception $e) {
    $lecturer = null;
}

// ==================== ADMIN-EDITABLE CLASS DETAILS CONTENT ====================
// Defaults used if class_details_migration.sql hasn't been run yet, or the
// admin hasn't saved anything — page never breaks or shows blank content.
$courseTitle = 'Online Spoken Course - 6 months';
$ctaText     = 'පලමු දින නොමිලේ';
$ctaNote     = '*කලින් නොමිලේ ලියාපදිංචි විය යුතුය.';
$taglineText = 'සාම්ප්‍රදායික ඉංග්‍රීසි ඉගැන්වීම කලාවෙන් ඔබ්බට';
$benefits = [
    'ප්‍රධාන පන්තිය සතියට එක් දිනක් වන අතර, ගැටළු සාකච්ඡා කිරීම, නැවත පාඩම් මතක් කර ගැනීම (Revision) සහ Activities සඳහා අතිරේක පන්ති දවස් ඇත. (නොමිලේ)',
    'පන්තියෙන් පසුවද personal instructor වරයෙකු ලබාදෙන අතර, ඔවුන්ගේ උදව් ලබාගත හැක.',
    'ඔබ ඉතාමත් දුර්වලයි නම්, පලමුව ඔබව beginner level පන්තියකට ඇතුල් කෙරේ.',
    'සියලුම පන්තිවල class recordings ලබාදේ (unlimited). Recordings නරඹා තුවද පන්තියට සහභාගී විය හැක.',
    'FluentMe app එකට free access — ඕනෑම වෙලාවක practice කිරීමට හැකියාව ලැබේ.',
    'ඔබගේ progress track කිරීමට "My Progress" පිටුව හරහා පහසුවෙන් නිරීක්ෂණය කළ හැක.',
];

if (tableExistsSafe($conn, 'class_details_content')) {
    $cres = $conn->query("SELECT course_title, cta_text, cta_note, tagline_text FROM class_details_content WHERE id = 1 LIMIT 1");
    if ($cres && $crow = $cres->fetch_assoc()) {
        if (trim((string)$crow['course_title']) !== '') $courseTitle = $crow['course_title'];
        if (trim((string)$crow['cta_text']) !== '')     $ctaText     = $crow['cta_text'];
        if (trim((string)$crow['cta_note']) !== '')     $ctaNote     = $crow['cta_note'];
        if (trim((string)$crow['tagline_text']) !== '') $taglineText = $crow['tagline_text'];
    }
}

if (tableExistsSafe($conn, 'class_details_benefits')) {
    $bres = $conn->query("SELECT benefit_text FROM class_details_benefits ORDER BY sort_order ASC, id ASC");
    if ($bres && $bres->num_rows > 0) {
        $dbBenefits = [];
        while ($brow = $bres->fetch_assoc()) {
            $dbBenefits[] = $brow['benefit_text'];
        }
        $benefits = $dbBenefits;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Class Details - Sipway Campus</title>
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
    --amber:#fbe8bf;
    --amber-line:#f0d9a0;
    --amber-price:#e0421d;
    --green:#1f9d55;
    --gold:#c8860d;
    --gold-soft:#fdf3dd;
    --gold-line:#f0d9a0;
    --success:#1f9d55;
    --success-soft:#e8f8ee;
    --radius-lg:16px;
    --radius-md:10px;
    --radius-sm:8px;
    --shadow-card:0 8px 24px -8px rgba(15,42,74,0.10);
    --ease:cubic-bezier(.4,0,.2,1);
  }

  html[data-theme="dark"]{
    --navy:#e8825f;
    --navy-2:#f0a684;
    --navy-soft:#22293a;
    --coral:#e8825f;
    --coral-dark:#d66c47;
    --coral-soft:#3a2a24;
    --bg:#12151f;
    --card:#1a1e2b;
    --line:#2a2f40;
    --line-soft:#232838;
    --muted:#9aa3b5;
    --muted-2:#6e7789;
    --text:#eef1f6;
    --amber:#2b2515;
    --amber-line:#4a3d1e;
    --amber-price:#ff8a5c;
    --green:#3ec97a;
    --gold:#e0b84e;
    --gold-soft:#2b2515;
    --gold-line:#4a3d1e;
    --success:#3ec97a;
    --success-soft:#20301f;
    --shadow-card:0 8px 24px -8px rgba(0,0,0,0.45);
  }

  html[data-theme="dark"] body{ background:var(--bg); }
  html[data-theme="dark"] .topbar{ background:var(--card); border-bottom-color:var(--line-soft); }
  html[data-theme="dark"] .sidebar{ background:var(--card); border-right-color:var(--line-soft); }
  html[data-theme="dark"] .dropdown{ background:var(--card); border-color:var(--line-soft); box-shadow:0 16px 40px -8px rgba(0,0,0,0.55); }
  html[data-theme="dark"] .panel{ background:var(--card); border-color:var(--line-soft); }
  html[data-theme="dark"] .lect-panel .lect-photo{ border-color:var(--line-soft); }
  html[data-theme="dark"] .course-illustration rect[fill="#eef2f7"],
  html[data-theme="dark"] .course-illustration ellipse[fill="#eef2f7"],
  html[data-theme="dark"] .course-illustration circle[fill="#eef2f7"]{ fill:var(--navy-soft); }
  html[data-theme="dark"] .course-illustration rect[fill="#e6e2da"]{ fill:var(--line-soft); }

  *{ box-sizing:border-box; }
  body{
    margin:0;
    font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    -webkit-font-smoothing:antialiased;
    transition:background .25s var(--ease), color .25s var(--ease);
  }
  a{ color:inherit; }
  /* ---------- Topbar ---------- */
  .topbar{
    height:64px; display:flex; align-items:center; gap:16px;
    padding:0 20px; background:#fff;
    border-bottom:1px solid var(--line-soft);
    position:sticky; top:0; z-index:50;
    transition:background .25s var(--ease), border-color .25s var(--ease);
  }
  .burger{ background:none; border:none; cursor:pointer; padding:8px; display:flex; border-radius:8px; color:var(--navy); flex-shrink:0; }
  .burger:hover{ background:var(--navy-soft); }
  .burger svg{ width:22px; height:22px; }
  .logo{ display:flex; align-items:center; gap:10px; font-weight:800; color:var(--navy); font-size:18px; letter-spacing:-0.2px; text-decoration:none; flex-shrink:0; }
  .logo-mark{ width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg, var(--coral), var(--coral-dark)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:14px; font-weight:800; }
  .top-links{ margin-left:auto; display:flex; align-items:center; gap:28px; }
  .top-links a{ font-size:13.5px; font-weight:600; color:var(--muted); text-decoration:none; white-space:nowrap; }
  .top-links a:hover{ color:var(--navy); }

  /* ===== Theme toggle button ===== */
  .theme-toggle{
    position:relative;
    width:52px;
    height:28px;
    flex-shrink:0;
    border-radius:999px;
    border:1px solid var(--line);
    background:linear-gradient(135deg, #dfe7f2, #eef2f7);
    cursor:pointer;
    display:flex;
    align-items:center;
    padding:2px;
    transition:background .25s var(--ease), border-color .25s var(--ease);
  }
  html[data-theme="dark"] .theme-toggle{
    background:linear-gradient(135deg, #1c2131, #262c40);
    border-color:var(--line);
  }
  .theme-toggle .toggle-knob{
    width:22px; height:22px;
    border-radius:50%;
    background:linear-gradient(135deg, var(--coral), var(--coral-dark));
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    box-shadow:0 3px 8px rgba(214,108,71,0.45);
    transform:translateX(0);
    transition:transform .3s var(--ease), background .3s var(--ease);
  }
  html[data-theme="dark"] .theme-toggle .toggle-knob{
    transform:translateX(24px);
    background:linear-gradient(135deg, #4a5578, #2c3348);
    box-shadow:0 3px 8px rgba(0,0,0,0.5);
  }
  .theme-toggle .toggle-knob svg{ width:13px; height:13px; }
  .theme-toggle .toggle-icon-track{
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 6px;
    pointer-events:none;
  }
  .theme-toggle .toggle-icon-track svg{
    width:13px; height:13px;
    color:var(--muted-2);
  }

  .user-menu{ display:flex; align-items:center; gap:8px; cursor:pointer; padding:6px 10px; border-radius:999px; position:relative; }
  .user-menu:hover{ background:var(--navy-soft); }
  .avatar{ width:30px; height:30px; border-radius:50%; background:var(--navy-soft); display:flex; align-items:center; justify-content:center; color:var(--navy-2); flex-shrink:0; }
  .avatar svg{ width:18px; height:18px; }
  .user-menu .chev{ width:14px; height:14px; color:var(--muted); }
  .user-name{ font-size:13.5px; font-weight:700; color:var(--text); }
  .dropdown{ position:absolute; top:calc(100% + 8px); right:0; background:#fff; border:1px solid var(--line-soft); border-radius:var(--radius-md); box-shadow:0 16px 40px -8px rgba(15,42,74,0.18); min-width:180px; padding:6px; display:none; z-index:60; }
  .dropdown.show{ display:block; }
  .dropdown a{ display:block; padding:10px 12px; font-size:13.5px; font-weight:600; border-radius:8px; text-decoration:none; color:var(--text); }
  .dropdown a:hover{ background:var(--navy-soft); }
  .dropdown a.danger{ color:#c0392b; }
  /* ---------- Layout shell ---------- */
  .shell{ display:flex; min-height:calc(100vh - 64px); }
  .sidebar{
    width:270px; flex-shrink:0; background:#fff;
    border-right:1px solid var(--line-soft); padding:20px 16px;
    display:flex; flex-direction:column; gap:4px;
    position:sticky; top:64px; align-self:flex-start;
    height:calc(100vh - 64px); overflow-y:auto;
    transition:transform .25s var(--ease), background .25s var(--ease), border-color .25s var(--ease);
  }
  .nav-item{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:var(--radius-sm); font-weight:700; font-size:14px; color:var(--navy-2); text-decoration:none; cursor:pointer; }
  .nav-item svg{ width:19px; height:19px; flex-shrink:0; }
  .nav-item:hover{ background:var(--navy-soft); }
  .nav-item.active{ background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff; box-shadow:0 8px 18px -4px rgba(214,108,71,0.4); }
  .side-divider{ height:1px; background:var(--line-soft); margin:14px 6px; }
  .side-link{ display:block; padding:11px 14px; font-size:13.5px; font-weight:600; color:var(--muted); text-decoration:none; border-radius:var(--radius-sm); }
  .side-link:hover{ background:var(--navy-soft); color:var(--navy-2); }
  .side-link.active{ background:var(--navy-soft); color:var(--navy-2); }
  .backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.35); z-index:45; }
  .backdrop.show{ display:block; }
  /* ---------- Main content ---------- */
  .main{ flex:1; padding:28px clamp(16px, 3vw, 32px) 48px; min-width:0; }
  .page-title{ font-size:26px; font-weight:800; color:var(--text); margin:0 0 22px 0; letter-spacing:-0.3px; }

  .panel{
    background:var(--card);
    border:1px solid var(--line-soft);
    border-radius:var(--radius-lg);
    box-shadow:var(--shadow-card);
    padding:26px;
    transition:background .25s var(--ease), border-color .25s var(--ease);
  }
  .panel h2{ font-size:17px; font-weight:800; margin:0 0 18px 0; color:var(--text); }

  /* ---------- Responsive ---------- */
  @media (max-width:820px){
    .top-links{ display:none; }
    .sidebar{ position:fixed; left:0; top:64px; transform:translateX(-100%); width:270px; height:calc(100vh - 64px); z-index:46; box-shadow:0 0 40px rgba(0,0,0,0.15); }
    .sidebar.open{ transform:translateX(0); }
  }
  @media (max-width:480px){
    .main{ padding:20px 14px 40px; }
    .page-title{ font-size:22px; }
  }

  /* ---------- Page-specific ---------- */
  .cd-grid{ display:grid; grid-template-columns:280px 1fr 1fr; gap:22px; align-items:start; }
  @media (max-width:1000px){ .cd-grid{ grid-template-columns:1fr 1fr; } .cd-grid > .lect-panel{ grid-column:1 / -1; } }
  @media (max-width:640px){ .cd-grid{ grid-template-columns:1fr; } .cd-grid > *{ grid-column:auto !important; } }

  .lect-panel{ text-align:center; }
  .lect-panel .lect-photo{
    width:150px; height:150px; border-radius:14px; object-fit:cover;
    margin:0 auto 16px; display:block; border:3px solid var(--line-soft);
  }
  .lect-panel .lect-photo-fallback{
    width:150px; height:150px; border-radius:14px; margin:0 auto 16px;
    background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff;
    display:flex; align-items:center; justify-content:center; font-size:52px; font-weight:800;
  }
  .lect-panel .lect-course-title{
    font-size:16px; font-weight:800; color:var(--navy-2); line-height:1.4; margin:0 0 16px 0;
  }
  .lect-panel .lect-name{ font-size:15px; font-weight:800; color:var(--text); margin:0 0 4px 0; }
  .lect-panel .lect-tagline{ font-size:12.5px; color:var(--muted); line-height:1.6; margin:0; }

  .course-card{ display:flex; flex-direction:column; align-items:center; text-align:center; }
  .course-illustration{ width:100%; max-width:220px; height:150px; margin:0 auto 16px; }
  .course-card h3{ font-size:16px; font-weight:800; color:var(--text); margin:0 0 16px 0; }
  .course-cta{
    width:100%; padding:13px; border:none; border-radius:var(--radius-sm);
    background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff;
    font-weight:800; font-size:13.5px; letter-spacing:0.3px; cursor:pointer;
    box-shadow:0 10px 22px -6px rgba(214,108,71,0.5); transition:filter .15s;
  }
  .course-cta:hover{ filter:brightness(1.06); }
  .course-note{ font-size:11px; color:var(--muted-2); margin-top:10px; }

  .benefits-panel{ grid-column:1 / -1; }
  .benefit-row{ display:flex; align-items:flex-start; gap:12px; padding:11px 0; border-bottom:1px solid var(--line-soft); }
  .benefit-row:last-child{ border-bottom:none; }
  .benefit-check{
    width:22px; height:22px; border-radius:50%; background:var(--success-soft); color:var(--success);
    display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px;
  }
  .benefit-check svg{ width:13px; height:13px; }
  .benefit-text{ font-size:13.5px; color:var(--text); line-height:1.7; }
</style>
</head>
<body>
<div class="topbar">
  <button class="burger" id="burgerBtn" aria-label="Toggle menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
  </button>
  <a href="dashboard.php" class="logo"><span class="logo-mark">SC</span>Sipway English Accademy</a>
  <div class="top-links">
  <a href="about_sipway_campus.php">About Sipway Campus</a>
    <a href="terms_of_use.php">Terms of Use</a>
    <a href="privacy_policy.php">Privacy Policy</a>
  </div>

  <button class="theme-toggle" id="themeToggleBtn" aria-label="Toggle dark mode" title="Toggle dark / light mode">
    <span class="toggle-icon-track">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
    </span>
    <span class="toggle-knob" id="themeToggleKnob">
      <svg viewBox="0 0 24 24" fill="currentColor" id="themeKnobIcon"><circle cx="12" cy="12" r="5"/></svg>
    </span>
  </button>

  <div class="user-menu" id="userMenu">
    <span class="avatar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
    </span>
    <span class="user-name" id="userNameLabel">Hi, <?php echo $firstName; ?></span>
    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
    <div class="dropdown" id="userDropdown">
      <a href="#"><?php echo $fullNameSafe; ?></a>
      <a href="#">Purchase history</a>
      <a href="student_logout.php" class="danger" id="logoutBtn">Log out</a>
    </div>
  </div>
</div>
<div class="backdrop" id="backdrop"></div>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/></svg>
      Dashboard
    </a>
    <a href="packages.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
      Packages
    </a>
    <a href="session_progress.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 2a10 10 0 0 1 10 10h-10z"/></svg>
      My Progress
    </a>

    <div class="side-divider"></div>

    <a href="faq-support.php" class="side-link">FAQs &amp; Support</a>
    <a href="class-details.php" class="side-link active">Class Details</a>
  </aside>

  <main class="main">
    <h1 class="page-title">Class Details</h1>

    <div class="cd-grid">

      <div class="panel lect-panel">
        <?php if ($lecturer && !empty($lecturer['photo'])): ?>
          <img class="lect-photo" src="<?php echo htmlspecialchars($lecturer['photo']); ?>"
               alt="<?php echo htmlspecialchars($lecturer['full_name']); ?>"
               onerror="this.outerHTML='<div class=&quot;lect-photo-fallback&quot;><?php echo htmlspecialchars(mb_substr($lecturer['full_name'] ?? '?', 0, 1)); ?></div>'">
        <?php elseif ($lecturer): ?>
          <div class="lect-photo-fallback"><?php echo htmlspecialchars(mb_substr($lecturer['full_name'], 0, 1)); ?></div>
        <?php else: ?>
          <div class="lect-photo-fallback">?</div>
        <?php endif; ?>

        <p class="lect-course-title">
          <?php echo htmlspecialchars($lecturer['subject'] ?? 'Spoken English'); ?> for Adults
          <?php if ($lecturer): ?>by <?php echo htmlspecialchars($lecturer['full_name']); ?><?php endif; ?>
        </p>

        <?php if ($lecturer): ?>
          <p class="lect-name"><?php echo htmlspecialchars($lecturer['full_name']); ?></p>
        <?php endif; ?>
        <p class="lect-tagline"><?php echo htmlspecialchars($taglineText); ?></p>
      </div>

      <div class="panel course-card">
        <svg class="course-illustration" viewBox="0 0 220 150" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="10" y="10" width="90" height="65" rx="8" fill="#eef2f7"/>
          <rect x="20" y="20" width="70" height="45" rx="4" fill="#16385f"/>
          <circle cx="55" cy="42" r="12" fill="#fff" opacity="0.85"/>
          <rect x="120" y="55" width="90" height="70" rx="45" fill="#fdece5"/>
          <circle cx="165" cy="80" r="22" fill="#e8825f"/>
          <rect x="30" y="85" width="60" height="10" rx="5" fill="#e6e2da"/>
          <rect x="20" y="100" width="16" height="40" rx="6" fill="#d66c47"/>
        </svg>
        <h3><?php echo htmlspecialchars($courseTitle); ?></h3>
        <button class="course-cta" onclick="location.href='packages.php'"><?php echo htmlspecialchars($ctaText); ?></button>
        <p class="course-note"><?php echo htmlspecialchars($ctaNote); ?></p>
      </div>

      <div class="panel benefits-panel">
        <h2>Benefits</h2>
        <?php foreach ($benefits as $b): ?>
          <div class="benefit-row">
            <span class="benefit-check">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
            <span class="benefit-text"><?php echo htmlspecialchars($b); ?></span>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </main>
</div>

<script>

// Dark / Light theme toggle
(function(){
    const root = document.documentElement;
    const btn = document.getElementById('themeToggleBtn');
    const knobIcon = document.getElementById('themeKnobIcon');

    const sunPath = '<circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round"/>';
    const moonPath = '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>';

    function applyTheme(theme){
        root.setAttribute('data-theme', theme);
        knobIcon.innerHTML = theme === 'dark' ? moonPath : sunPath;
        try { localStorage.setItem('sipway_theme', theme); } catch(e) {}
    }

    let saved = null;
    try { saved = localStorage.getItem('sipway_theme'); } catch(e) {}
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(saved || (prefersDark ? 'dark' : 'light'));

    if (btn) {
        btn.addEventListener('click', () => {
            const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        });
    }
})();

// Sidebar toggle (mobile)
const burgerBtn = document.getElementById('burgerBtn');
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('backdrop');

if (burgerBtn) {
    burgerBtn.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('show');
    });
}
if (backdrop) {
    backdrop.addEventListener('click', function () {
        sidebar.classList.remove('open');
        backdrop.classList.remove('show');
    });
}

// User dropdown
const userMenu = document.getElementById('userMenu');
const userDropdown = document.getElementById('userDropdown');
if (userMenu) {
    userMenu.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('show');
    });
    document.addEventListener('click', function () {
        userDropdown.classList.remove('show');
    });
}

</script>

</body>
</html>