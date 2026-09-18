<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}


unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_logged_in']);

$isLoggedIn  = true;
$studentId   = (int)$_SESSION['student_id'];
$studentName = $_SESSION['student_name'] ?? 'Student';
$firstName   = htmlspecialchars(explode(' ', trim($studentName))[0]);

$photoUrl        = null;
$studentLanguage = 'en';
$langFlag        = 'https://flagcdn.com/w40/gb.png';
$langLabel       = 'English';

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

$stmt = $conn->prepare("SELECT profile_photo, language FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
    if (!empty($row['profile_photo']) && file_exists(__DIR__ . '/' . $row['profile_photo'])) {
        $photoUrl = htmlspecialchars($row['profile_photo']);
    }
    $studentLanguage = strtolower(trim($row['language'] ?? 'en'));
    if (!isset($langMeta[$studentLanguage])) {
        $studentLanguage = 'en';
    }
    $langFlag  = $langMeta[$studentLanguage]['flag'];
    $langLabel = $langMeta[$studentLanguage]['label'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat with Admin - Sipway Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --purple: #7c3aed;
  --pink: #ec4899;
  --bg: #f4f0ff;
  --card: #ffffff;
  --text: #1e1b4b;
  --muted: #6b7280;
  --line: #e9e5f5;
  --line-soft: #f3f0fa;
  --topbar-bg: #0b0a1f;
  --sidebar-bg: linear-gradient(180deg, #0f0c29 0%, #1a1440 50%, #1e1b4b 100%);
  --radius-md: 14px;
  --radius-lg: 20px;
  --shadow-card: 0 8px 30px -8px rgba(124, 58, 237, 0.08);
  --shadow-hover: 0 16px 40px -12px rgba(124, 58, 237, 0.14);
  --ease: cubic-bezier(.4,0,.2,1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', 'Noto Sans Sinhala', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--bg);
  color: var(--text);
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  -webkit-font-smoothing: antialiased;
}

a { color: inherit; text-decoration: none; }

/* ========== TOPBAR ========== */
.topbar {
  height: 64px;
  background: var(--topbar-bg);
  display: flex;
  align-items: center;
  padding: 0 22px;
  gap: 14px;
  flex-shrink: 0;
  z-index: 50;
  position: sticky;
  top: 0;
}
.burger {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  display: flex;
  color: #e0e7ff;
  border-radius: 10px;
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
  margin-left: 8px;
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
.user-name { font-size: 13px; font-weight: 700; color: #fff; }
.user-menu .chev { width: 13px; height: 13px; color: #94a3b8; }

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
.dropdown a:hover { background: #f3e8ff; }
.dropdown a.danger { color: #b91c1c; }

/* ========== LAYOUT ========== */
.shell {
  display: flex;
  flex: 1;
  overflow: hidden;
  min-height: 0;
}

/* ========== SIDEBAR ========== */
.sidebar {
  width: 260px;
  flex-shrink: 0;
  background: linear-gradient(180deg, #0f0c29 0%, #1a1440 50%, #1e1b4b 100%);
  padding: 22px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  overflow-y: auto;
  position: sticky;
  top: 64px;
  align-self: flex-start;
  height: calc(100vh - 64px);
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
  color: rgba(255,255,255,0.85);
  cursor: pointer;
  transition: all .2s var(--ease);
}
.nav-item svg {
  width: 19px;
  height: 19px;
  flex-shrink: 0;
  opacity: 0.9;
  color: #fff;
}
.nav-item:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
}
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
.side-illustration img,
.side-illustration svg {
  width: 100%;
  max-width: 180px;
  height: auto;
  opacity: 0.9;
  display: block;
  margin: 0 auto;
}

/* Backdrop for mobile */
.backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15,12,41,0.5);
  backdrop-filter: blur(3px);
  z-index: 45;
}
.backdrop.show { display: block; }

/* ========== CHAT AREA ========== */
.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: linear-gradient(160deg, #f4f0ff 0%, #faf8ff 40%, #f0eaff 100%);
}
.chat-header {
  padding: 16px 22px;
  background: var(--card);
  border-bottom: 1px solid var(--line);
  font-weight: 800;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  box-shadow: var(--shadow-card);
}
.admin-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 17px;
  flex-shrink: 0;
}
.chat-header-info small {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--muted);
  margin-top: 2px;
}

/* Messages container — MUST stay a column flexbox for left/right split to work */
.messages {
  flex: 1;
  overflow-y: auto;
  padding: 22px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Row wrapper guarantees full-width row so align-self left/right actually has room to move */
.msg-row {
  display: flex;
  width: 100%;
}
.msg-row.row-student { justify-content: flex-end; }
.msg-row.row-admin   { justify-content: flex-start; }

.msg {
  max-width: 70%;
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 14.5px;
  line-height: 1.5;
  word-wrap: break-word;
}
.msg-time {
  font-size: 10.5px;
  opacity: 0.75;
  margin-top: 5px;
}

/* ========== STUDENT (me, in this page) → RIGHT (purple) ========== */
.msg.student {
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  border-bottom-right-radius: 4px;
  box-shadow: 0 6px 16px -4px rgba(168,85,247,0.35);
}
.msg.student .msg-time {
  text-align: right;
  color: rgba(255,255,255,0.85);
}

/* ========== ADMIN (them, in this page) → LEFT (white) ========== */
.msg.admin {
  background: #fff;
  border: 1px solid var(--line);
  border-bottom-left-radius: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  color: var(--text);
}
.msg.admin .msg-time {
  text-align: left;
  color: var(--muted);
}

.empty-chat {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  font-weight: 600;
  gap: 12px;
  text-align: center;
  padding: 40px;
}

/* Input */
.input-area {
  padding: 14px 18px;
  background: var(--card);
  border-top: 1px solid var(--line);
  display: flex;
  gap: 10px;
  align-items: center;
  flex-shrink: 0;
}
.input-area input {
  flex: 1;
  padding: 13px 16px;
  border: 1.5px solid var(--line);
  border-radius: 12px;
  font-size: 14.5px;
  font-family: inherit;
  outline: none;
  background: #faf8ff;
  transition: border-color .15s, background .15s;
}
.input-area input:focus {
  border-color: #a855f7;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(168,85,247,0.12);
}
.input-area button {
  padding: 0 24px;
  height: 48px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-weight: 800;
  font-size: 14px;
  cursor: pointer;
  transition: filter .15s;
}
.input-area button:hover { filter: brightness(1.06); }
.input-area button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
  .backdrop.show { display: block; }
}
@media (max-width: 560px) {
  .topbar { padding: 0 10px; gap: 8px; }
  .logo span:not(.logo-mark) { display: none; }
  .lang-nav-text { display: none; }
  .user-name { display: none; }
  .chat-header { padding: 14px 16px; }
  .messages { padding: 16px; }
  .msg { max-width: 85%; }
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

    <a href="student_chat.php" class="nav-item active">
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

  <div class="chat-main">
    <div class="chat-header">
      <div class="admin-avatar">A</div>
      <div class="chat-header-info">
        <div>Sipway Admin</div>
        <small>We usually reply within a few hours</small>
      </div>
    </div>

    <div class="messages" id="messages">
      <div class="empty-chat">
        <div style="font-size:42px;">💬</div>
        <div>Admin එක්ක chat එක start කරන්න<br>ඔබේ ප්‍රශ්නය type කරලා Send කරන්න</div>
      </div>
    </div>

    <div class="input-area">
      <input type="text" id="msgInput" placeholder="Type your message to Admin..." autocomplete="off">
      <button id="sendBtn">Send</button>
    </div>
  </div>
</div>

<script>
const MY_ID = <?php echo $studentId; ?>;
let conversationId = null;

function escapeHtml(t) {
  return String(t ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function formatTime(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}


 
function isFromStudent(m) {
  const adminFlag = m.is_admin ?? m.isAdmin ?? m.admin ?? null;
  if (adminFlag === true || adminFlag === 1 || adminFlag === '1') return false;

  const rawType = (
    m.sender_type ?? m.senderType ?? m.type ?? m.role ?? m.from ?? m.sender ?? ''
  );
  const senderType = String(rawType).toLowerCase().trim();

  if (['admin', 'staff', 'support', 'teacher', 'lecturer'].includes(senderType)) return false;
  if (['student', 'user', 'me', 'self'].includes(senderType)) return true;

  const rawId = (
    m.sender_id ?? m.senderId ?? m.user_id ?? m.userId ?? m.from_id ?? m.fromId ?? m.student_id ?? null
  );
  if (rawId !== null && rawId !== undefined && rawId !== '') {
    return Number(rawId) === MY_ID;
  }

  return true;
}

const sidebar   = document.getElementById('sidebar');
const burgerBtn = document.getElementById('burgerBtn');
const backdrop  = document.getElementById('backdrop');

function openSidebar()  { sidebar.classList.add('open'); backdrop.classList.add('show'); }
function closeSidebar() { sidebar.classList.remove('open'); backdrop.classList.remove('show'); }

burgerBtn.addEventListener('click', () => {
  sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
});
backdrop.addEventListener('click', closeSidebar);

const userMenu = document.getElementById('userMenu');
if (userMenu) {
  const userDropdown = document.getElementById('userDropdown');
  userMenu.addEventListener('click', (e) => {
    userDropdown.classList.toggle('show');
    e.stopPropagation();
  });
  document.addEventListener('click', () => userDropdown.classList.remove('show'));
}

const howToRegisterBtn = document.getElementById('howToRegisterBtn');
if (howToRegisterBtn) {
  howToRegisterBtn.addEventListener('click', function(e) {
    e.preventDefault();
    window.location.href = 'index.php';
  });
}

async function initConversation() {
  try {
    const res = await fetch('chat_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'get_or_create_admin_chat' })
    });
    const data = await res.json();
    if (data.success) {
      conversationId = data.conversation_id;
      await loadMessages();
    } else {
      alert(data.message || 'Could not start chat');
    }
  } catch (err) {
    console.error(err);
    alert('Server error. Please try again.');
  }
}

async function loadMessages() {
  if (!conversationId) return;
  try {
    const res = await fetch(`chat_api.php?action=get_messages&conversation_id=${conversationId}`);
    const data = await res.json();
    const box = document.getElementById('messages');

    if (!data.success || !data.data || data.data.length === 0) {
      box.innerHTML = `
        <div class="empty-chat">
          <div style="font-size:42px;">💬</div>
          <div>Admin එක්ක chat එක start කරන්න<br>ඔබේ ප්‍රශ්නය type කරලා Send කරන්න</div>
        </div>`;
      return;
    }

    box.innerHTML = data.data.map(m => {
      const studentMsg = isFromStudent(m);

      const bubbleCls = studentMsg ? 'student' : 'admin';
      const rowCls     = studentMsg ? 'row-student' : 'row-admin';

      return `
        <div class="msg-row ${rowCls}">
          <div class="msg ${bubbleCls}">
            ${escapeHtml(m.message)}
            <div class="msg-time">${formatTime(m.created_at)}</div>
          </div>
        </div>`;
    }).join('');

    box.scrollTop = box.scrollHeight;
  } catch (err) {
    console.error(err);
  }
}

async function sendMessage() {
  const input = document.getElementById('msgInput');
  const text = input.value.trim();
  if (!text || !conversationId) return;

  input.value = '';
  document.getElementById('sendBtn').disabled = true;

  try {
    await fetch('chat_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'send',
        conversation_id: conversationId,
        message: text
      })
    });
    await loadMessages();
  } catch (err) {
    alert('Failed to send message');
  } finally {
    document.getElementById('sendBtn').disabled = false;
    input.focus();
  }
}

document.getElementById('sendBtn').addEventListener('click', sendMessage);
document.getElementById('msgInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') sendMessage();
});

// Start
initConversation();
setInterval(loadMessages, 6000);
</script>
</body>
</html>