<?php
session_start();
if (!isset($_SESSION['lecturer_id'])) {
    header("Location: lecturer-login.php");
    exit();
}

require_once 'db.php';
$lecturerId = (int)$_SESSION['lecturer_id'];
$lecturerName = $_SESSION['lecturer_name'] ?? 'Lecturer';
$lecturerSubject = $_SESSION['lecturer_subject'] ?? '';
$lecturerQualifications = '';
$lecturerPhoto = null;

$stmt = $conn->prepare("SELECT full_name, subject, qualifications, photo FROM lecturers WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $lecturerId);
$stmt->execute();
$stmt->bind_result($dbFullName, $dbSubject, $dbQualifications, $dbPhoto);
if ($stmt->fetch()) {
    $lecturerName = $dbFullName;
    $lecturerSubject = $dbSubject;
    $lecturerQualifications = $dbQualifications ?? '';
    $lecturerPhoto = $dbPhoto;
}
$stmt->close();

$firstName = htmlspecialchars(explode(' ', trim($lecturerName))[0]);
$photoUrl = $lecturerPhoto ? 'uploads/lecturers/' . htmlspecialchars($lecturerPhoto) : '';
$lecturerNameJs = json_encode($lecturerName);
$lecturerSubjectsJs = json_encode(array_filter(array_map('trim', explode(',', $lecturerSubject))));
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lecturer Dashboard - Sipway Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
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
  --danger: #ef4444;
  --danger-soft: #fef2f2;
  --radius-lg: 20px;
  --radius-md: 14px;
  --radius-sm: 10px;
  --shadow-card: 0 8px 30px -8px rgba(124, 58, 237, 0.08);
  --shadow-hover: 0 16px 40px -12px rgba(124, 58, 237, 0.14);
  --ease: cubic-bezier(.4,0,.2,1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', 'Noto Sans Sinhala', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  -webkit-font-smoothing: antialiased;
  min-height: 100vh;
}

.app { display: flex; min-height: 100vh; }

.sidebar {
  width: 250px;
  flex-shrink: 0;
  background: linear-gradient(180deg, #0f0c29 0%, #1a1440 50%, #1e1b4b 100%);
  padding: 22px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  z-index: 60;
  transition: transform .3s var(--ease);
}

.logo {
  display: flex;
  align-items: center;
  gap: 11px;
  font-weight: 800;
  color: #fff;
  font-size: 15px;
  padding: 0 8px 22px;
  letter-spacing: -0.3px;
}

.logo-mark {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 800;
  font-size: 13px;
  box-shadow: 0 4px 14px -3px rgba(168, 85, 247, 0.5);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  color: #c4b5fd;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  font-family: inherit;
  text-decoration: none;
  transition: all .2s var(--ease);
}

.nav-item svg { width: 18px; height: 18px; flex-shrink: 0; opacity: 0.9; }
.nav-item:hover { background: rgba(255,255,255,0.08); color: #fff; }
.nav-item.active {
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  box-shadow: 0 8px 24px -6px rgba(168, 85, 247, 0.5);
}
.nav-item.active svg { opacity: 1; }

.sidebar-bottom { margin-top: auto; padding-top: 14px; }

.logout-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  color: #f9a8d4;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
  transition: all .2s;
}
.logout-link:hover { background: rgba(236, 72, 153, 0.15); color: #fff; }
.logout-link svg { width: 18px; height: 18px; }

.sidebar-overlay { display: none; }

.sidebar-close {
  display: none;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.1);
  color: #fff;
  margin-left: auto;
  cursor: pointer;
}
.sidebar-close:hover { background: rgba(255,255,255,0.2); }
.sidebar-close svg { width: 16px; height: 16px; }

.sidebar-top-row { display: flex; align-items: center; }

.main { flex: 1; min-width: 0; background: linear-gradient(160deg, #f4f0ff 0%, #faf8ff 40%, #f0eaff 100%); }

.topbar {
  height: 66px;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 0 26px;
  background: #0b0a1f;
  position: sticky;
  top: 0;
  z-index: 40;
}

.hamburger-btn {
  display: none;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.08);
  color: #e0e7ff;
  cursor: pointer;
  flex-shrink: 0;
}
.hamburger-btn:hover { background: rgba(255,255,255,0.14); }
.hamburger-btn svg { width: 20px; height: 20px; }

.topbar-title {
  font-size: 16.5px;
  font-weight: 800;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: -0.3px;
}

.topbar-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }

.lecturer-chip {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 14px 6px 6px;
  border-radius: 999px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.1);
}

.lecturer-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 12px;
  overflow: hidden;
  flex-shrink: 0;
}
.lecturer-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

.lecturer-chip .name { font-size: 13px; font-weight: 700; color: #fff; }
.lecturer-chip .subj { font-size: 11px; color: #94a3b8; font-weight: 600; }

.content {
  max-width: 1100px;
  margin: 0 auto;
  padding: 30px 26px 70px;
}

.section { display: none; }
.section.active { display: block; animation: fadeUp .35s var(--ease) both; }

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  color: var(--text);
  margin: 0 0 5px;
  letter-spacing: -0.4px;
}

.page-sub {
  font-size: 14px;
  color: var(--muted);
  margin: 0 0 26px;
  line-height: 1.55;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}
@media (max-width: 900px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

.stat-card {
  background: var(--card);
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  padding: 20px;
  transition: transform .2s, box-shadow .2s;
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-hover);
}

.stat-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}
.stat-icon svg { width: 20px; height: 20px; }
.stat-icon.blue { background: var(--purple-soft); color: var(--purple); }
.stat-icon.orange { background: #fce7f3; color: #be185d; }
.stat-icon.green { background: var(--success-soft); color: var(--success); }
.stat-icon.amber { background: var(--amber-soft); color: var(--amber); }

.stat-value {
  font-size: 27px;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.5px;
}
.stat-label {
  font-size: 12.5px;
  color: var(--muted);
  font-weight: 600;
  margin-top: 3px;
}

.panel {
  background: var(--card);
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  padding: 24px;
  margin-bottom: 20px;
  transition: box-shadow .25s;
}
.panel:hover { box-shadow: var(--shadow-hover); }

.panel h2 {
  font-size: 16px;
  font-weight: 800;
  color: var(--text);
  margin: 0 0 18px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-pills {
  display: flex;
  gap: 8px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}
.pill {
  padding: 8px 16px;
  border-radius: 999px;
  border: 1px solid var(--line);
  background: #fff;
  font-size: 13px;
  font-weight: 600;
  color: var(--muted);
  cursor: pointer;
  transition: all .18s;
}
.pill:hover { border-color: #a855f7; color: #6b21a8; }
.pill.active {
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 4px 14px -3px rgba(168, 85, 247, 0.4);
}

.booking-item, .slot-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 15px 16px;
  border: 1px solid var(--line-soft);
  border-radius: 14px;
  margin-bottom: 10px;
  flex-wrap: wrap;
  background: var(--card);
  transition: all .2s;
}
.booking-item:hover, .slot-item:hover {
  border-color: #ddd6fe;
  box-shadow: 0 8px 22px -8px rgba(124, 58, 237, 0.12);
  transform: translateY(-1px);
}

.booking-avatar, .slot-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: var(--purple-soft);
  color: var(--purple);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 15px;
  flex-shrink: 0;
}
.slot-icon { background: var(--success-soft); color: var(--success); }
.slot-icon svg { width: 20px; height: 20px; }

.booking-info, .slot-info { flex: 1; min-width: 150px; }
.booking-name, .slot-date { font-size: 14px; font-weight: 800; color: var(--text); }
.booking-meta, .slot-time { font-size: 12.5px; color: var(--muted); font-weight: 500; margin-top: 3px; }

.status-badge {
  font-size: 11.5px;
  font-weight: 700;
  padding: 5px 12px;
  border-radius: 999px;
  text-transform: capitalize;
}
.status-badge.pending { background: var(--amber-soft); color: var(--amber); }
.status-badge.approved { background: var(--success-soft); color: var(--success); }
.status-badge.rejected { background: var(--danger-soft); color: var(--danger); }

.booking-actions, .slot-item-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.btn-approve, .btn-reject {
  padding: 9px 15px;
  border-radius: 9px;
  border: none;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all .15s;
}
.btn-approve { background: var(--success); color: #fff; }
.btn-approve:hover { filter: brightness(1.08); transform: translateY(-1px); }
.btn-reject { background: var(--danger-soft); color: var(--danger); }
.btn-reject:hover { background: var(--danger); color: #fff; }

.btn-meeting {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 16px;
  border-radius: 9px;
  border: none;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  box-shadow: 0 4px 14px -3px rgba(168, 85, 247, 0.45);
  transition: all .18s;
}
.btn-meeting:hover {
  filter: brightness(1.06);
  transform: translateY(-1px);
  box-shadow: 0 6px 18px -3px rgba(168, 85, 247, 0.55);
}
.btn-meeting svg { width: 15px; height: 15px; }

.live-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 700;
  padding: 5px 12px;
  border-radius: 999px;
  background: #fce7f3;
  color: #be185d;
}
.live-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #ec4899;
  animation: pulseDot 1.2s infinite;
}
@keyframes pulseDot {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.35; }
}

.btn-end {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 15px;
  border-radius: 9px;
  border: 1px solid #fce7f3;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  background: #fce7f3;
  color: #be185d;
  transition: all .15s;
}
.btn-end:hover { background: #be185d; color: #fff; }
.btn-end svg { width: 14px; height: 14px; }

.meeting-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.meeting-with { font-size: 13.5px; font-weight: 600; color: var(--muted); }
.meeting-with span { color: var(--purple); font-weight: 800; }

.btn-leave {
  padding: 10px 18px;
  border-radius: 9px;
  border: 1px solid #fce7f3;
  background: #fce7f3;
  color: #be185d;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all .15s;
}
.btn-leave:hover { background: #be185d; color: #fff; }

.meeting-open-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 18px;
  text-align: center;
  padding: 64px 24px;
  background: linear-gradient(160deg, #0f0c29 0%, #1a1440 100%);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  color: #fff;
}
.meeting-open-panel svg { width: 48px; height: 48px; opacity: 0.9; color: #c4b5fd; }
.meeting-open-panel p {
  font-size: 13.5px;
  color: #a5b4fc;
  max-width: 460px;
  margin: 0;
  line-height: 1.6;
}
.meeting-hint {
  font-size: 12px;
  color: var(--muted-2);
  margin-top: 12px;
}

.form-group { margin-bottom: 18px; }
.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 7px;
}
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px 14px;
  border: 1.5px solid var(--line);
  border-radius: 10px;
  font-size: 14px;
  font-family: inherit;
  background: var(--bg-soft);
  transition: border-color .15s, box-shadow .15s;
}
.form-group textarea { resize: vertical; min-height: 90px; }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #a855f7;
  box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.14);
  background: #fff;
}

.form-row { display: flex; gap: 14px; }
.form-row .form-group { flex: 1; }

.add-btn {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-weight: 800;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 8px 22px -5px rgba(168, 85, 247, 0.4);
  transition: all .18s;
}
.add-btn:hover:not(:disabled) {
  filter: brightness(1.05);
  transform: translateY(-1px);
}
.add-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.form-msg {
  font-size: 13px;
  font-weight: 600;
  min-height: 18px;
  margin-top: 12px;
}

.approval-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 700;
  padding: 6px 13px;
  border-radius: 999px;
  white-space: nowrap;
}
.approval-badge.pending-approval { background: var(--amber-soft); color: var(--amber); }
.approval-badge.rejected-approval { background: var(--danger-soft); color: var(--danger); }

.profile-panel { max-width: 500px; }
.profile-photo-row {
  display: flex;
  align-items: center;
  gap: 18px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.profile-photo-preview {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  object-fit: cover;
  background: var(--purple-soft);
  border: 2px solid var(--line);
  flex-shrink: 0;
}
.profile-photo-fallback {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 800;
  flex-shrink: 0;
}
.profile-photo-actions { flex: 1; min-width: 180px; }
.profile-photo-actions input[type="file"] { font-size: 13px; max-width: 100%; }
.profile-photo-actions .hint { font-size: 11.5px; color: var(--muted-2); margin-top: 7px; }

.empty-msg {
  text-align: center;
  padding: 44px 12px;
  color: var(--muted-2);
  font-size: 13.5px;
}

.slot-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 640px;
  overflow-y: auto;
}

.form-error {
  font-size: 12px;
  color: var(--danger);
  margin-top: 5px;
  font-weight: 600;
  display: none;
}
.form-error.show { display: block; }

.form-hint {
  font-size: 11.5px;
  color: var(--muted-2);
  margin-top: 5px;
}

@media (max-width: 860px) {
  .hamburger-btn { display: flex; }
  .sidebar-close { display: flex; }

  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 270px;
    max-width: 82vw;
    transform: translateX(-100%);
    box-shadow: 0 0 0 rgba(0,0,0,0);
  }
  .sidebar.open {
    transform: translateX(0);
    box-shadow: 16px 0 40px -12px rgba(15, 12, 41, 0.4);
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(15, 12, 41, 0.55);
    backdrop-filter: blur(3px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .22s;
    z-index: 55;
  }
  .sidebar-overlay.open {
    opacity: 1;
    pointer-events: auto;
  }

  .topbar { padding: 0 14px; gap: 10px; }
  .topbar-title { font-size: 15px; }
  .lecturer-chip .subj { display: none; }

  .content { padding: 20px 14px 52px; }
  .page-title { font-size: 21px; }
  .page-sub { font-size: 13.5px; margin-bottom: 20px; }

  .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
  .stat-card { padding: 16px; }
  .stat-value { font-size: 23px; }

  .panel { padding: 18px; margin-bottom: 16px; }
  .form-row { flex-direction: column; gap: 0; }

  .booking-item, .slot-item { padding: 14px; }
  .booking-actions, .slot-item-actions { width: 100%; }
  .booking-actions .btn-approve,
  .booking-actions .btn-reject,
  .booking-actions .btn-meeting,
  .slot-item-actions .btn-meeting,
  .slot-item-actions .btn-end {
    flex: 1;
    justify-content: center;
  }

  .meeting-topbar { flex-direction: column; align-items: flex-start; }
  .btn-leave { width: 100%; }
  .profile-panel { max-width: 100%; }

  .filter-pills {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 4px;
    -webkit-overflow-scrolling: touch;
  }
  .filter-pills .pill { flex-shrink: 0; }
}

@media (max-width: 420px) {
  .stats-grid { grid-template-columns: 1fr; }
  .booking-item, .slot-item {
    flex-direction: column;
    align-items: flex-start;
  }
  .booking-avatar, .slot-icon { margin-bottom: 4px; }
}
</style>
</head>
<body>
<div class="app">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="sidebar" id="sidebar">
    <div class="sidebar-top-row">
      <div class="logo"><span class="logo-mark">SC</span>Sipway English Accademy</div>
      <button class="sidebar-close" id="sidebarCloseBtn" aria-label="Close menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <button class="nav-item active" data-tab="availability">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
      Availability
    </button>

    <a href="session_logs.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
      Session Logs
    </a>

    <a href="lecturer_profile.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
      Profile
    </a>

    <div class="sidebar-bottom">
      <a href="lecturer_logout.php" class="logout-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        Logout
      </a>
    </div>
  </div>

  <div class="main">
    <div class="topbar">
      <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title" id="topbarTitle">Availability</div>
      <div class="topbar-right">
        <div class="lecturer-chip">
          <span class="lecturer-avatar" id="topbarAvatar">
            <?php if ($photoUrl): ?>
              <img src="<?php echo $photoUrl; ?>" alt="<?php echo $firstName; ?>" onerror="this.parentElement.textContent='<?php echo strtoupper(substr($firstName,0,1)); ?>';">
            <?php else: ?>
              <?php echo strtoupper(substr($firstName,0,1)); ?>
            <?php endif; ?>
          </span>
          <div>
            <div class="name"><?php echo $firstName; ?></div>
            <div class="subj"><?php echo htmlspecialchars($lecturerSubject ?? ''); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <!-- Overview -->
      <div class="section" id="section-overview">
        <h1 class="page-title">Welcome, <?php echo $firstName; ?> 👋</h1>
        <p class="page-sub">ඔබේ sessions, students, සහ availability එකේ quick summary එක මෙතන.</p>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg></div>
            <div class="stat-value" id="statUpcoming">0</div>
            <div class="stat-label">Upcoming Sessions</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></div>
            <div class="stat-value" id="statPending">0</div>
            <div class="stat-label">Pending Approvals</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
            <div class="stat-value" id="statStudents">0</div>
            <div class="stat-label">Students Taught</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
            <div class="stat-value" id="statSlots">0</div>
            <div class="stat-label">Open Slots</div>
          </div>
        </div>

        <div class="panel">
          <h2>🕒 Next Up</h2>
          <div class="slot-list" id="nextUpList">
            <div class="empty-msg">Loading...</div>
          </div>
        </div>
      </div>

      <!-- My Sessions -->
      <div class="section" id="section-sessions">
        <h1 class="page-title">My Sessions</h1>
        <p class="page-sub">Students book කරපු sessions okkoma මෙතන, status එක update කරන්න. Approved session එකකට "Start Meeting" click කරලා video call එක පටන් ගන්න.</p>

        <div class="panel">
          <div class="filter-pills" id="filterPills">
            <button class="pill active" data-filter="all">All</button>
            <button class="pill" data-filter="pending">Pending</button>
            <button class="pill" data-filter="approved">Approved</button>
            <button class="pill" data-filter="rejected">Rejected</button>
          </div>
          <div id="bookingsList">
            <div class="empty-msg">Loading...</div>
          </div>
        </div>
      </div>

      <!-- Meeting -->
      <div class="section" id="section-meeting">
        <div class="meeting-topbar">
          <div>
            <h1 class="page-title" style="margin-bottom:2px;">Live Meeting</h1>
            <div class="meeting-with">Session with <span id="meetingStudentName">-</span></div>
          </div>
          <button class="btn-leave" id="leaveMeetingBtn">⏻ Leave Meeting</button>
        </div>

        <div class="meeting-open-panel">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
          <p>Meeting eka අලුත් browser tab එකක open උනා. Tab eka accidentally close උනොත් "Reopen Meeting Tab" click කරන්න. Session එක client-side embed එකකින් run කරන්නෙ නැති නිසා 5-minute demo-limit එක apply වෙන්නෙ නෑ. "Leave Meeting" click කරහම window එක විතරයි close වෙන්නෙ — session එක DB එකේ තවම "live" විදිහට තියෙනවා, ඕන වෙලාවක "Open" click කරලා ආපහු enter වෙන්න පුළුවන්. Session එක සම්පූර්ණයෙන්ම නවත්වන්න ඕන නම් "End Session" button එක තමයි click කරන්න ඕන.</p>
          <button class="btn-meeting" id="reopenMeetingBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
            Reopen Meeting Tab
          </button>
        </div>
        <div class="meeting-hint">Powered by Jitsi Meet — free, browser eken direct join wenawa, account ekak oona na.</div>
      </div>

      <!-- Availability -->
      <div class="section active" id="section-availability">
        <h1 class="page-title">Availability Schedule</h1>
        <p class="page-sub">Admin විසින් ඔබට add කරලා තියෙන time slots මෙතන බලන්න. Live session එකක් start කරන්නත්, අවශ්‍ය නම් end කරන්නත් පුළුවන්.</p>

        <!-- Existing Slots -->
        <div class="panel">
          <h2>📅 Your Upcoming Slots</h2>
          <div class="slot-list" id="slotList">
            <div class="empty-msg">Loading...</div>
          </div>
        </div>
      </div>

      <!-- Profile -->
      <div class="section" id="section-profile">
        <h1 class="page-title">Profile Settings</h1>
        <p class="page-sub">ඔබේ නම, subject, qualifications, photo, password මෙතනින් update කරන්න.</p>

        <div class="panel profile-panel">
          <div class="profile-photo-row">
            <?php if ($photoUrl): ?>
              <img src="<?php echo $photoUrl; ?>" class="profile-photo-preview" id="profilePhotoPreview" alt="Profile photo" onerror="this.style.display='none'; document.getElementById('profilePhotoFallback').style.display='flex';">
              <div class="profile-photo-fallback" id="profilePhotoFallback" style="display:none;"><?php echo strtoupper(substr($firstName,0,1)); ?></div>
            <?php else: ?>
              <img src="" class="profile-photo-preview" id="profilePhotoPreview" alt="Profile photo" style="display:none;">
              <div class="profile-photo-fallback" id="profilePhotoFallback"><?php echo strtoupper(substr($firstName,0,1)); ?></div>
            <?php endif; ?>
            <div class="profile-photo-actions">
              <label for="profilePhoto" style="display:block; font-size:13px; font-weight:700; color:var(--text); margin-bottom:6px;">Profile Photo</label>
              <input type="file" id="profilePhoto" accept="image/png, image/jpeg, image/webp">
              <div class="hint">JPG, PNG, WEBP — Max 3MB</div>
            </div>
          </div>

          <div class="form-group">
            <label for="profileName">Full Name</label>
            <input type="text" id="profileName" value="<?php echo htmlspecialchars($lecturerName); ?>">
          </div>
          <div class="form-group">
            <label for="profileSubject">Subject</label>
            <input type="text" id="profileSubject" value="<?php echo htmlspecialchars($lecturerSubject); ?>">
          </div>
          <div class="form-group">
            <label for="profileQualifications">Qualifications</label>
            <textarea id="profileQualifications" placeholder="e.g. BA in English (Hons), TESOL Certified"><?php echo htmlspecialchars($lecturerQualifications); ?></textarea>
          </div>
          <div class="form-group">
            <label for="profilePassword">New Password <span style="font-weight:500;color:var(--muted-2);">(optional)</span></label>
            <input type="password" id="profilePassword" placeholder="වෙනස් කරන්න ඕන නම් විතරක් type කරන්න">
          </div>
          <button class="add-btn" id="saveProfileBtn">Save Changes</button>
          <div class="form-msg" id="profileMsg"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const LECTURER_NAME = <?php echo $lecturerNameJs; ?>;
const LECTURER_SUBJECTS = <?php echo $lecturerSubjectsJs; ?>;

/* ===== Mobile sidebar ===== */
const sidebarEl = document.getElementById('sidebar');
const sidebarOverlayEl = document.getElementById('sidebarOverlay');
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

function openSidebar(){
  sidebarEl.classList.add('open');
  sidebarOverlayEl.classList.add('open');
}
function closeSidebar(){
  sidebarEl.classList.remove('open');
  sidebarOverlayEl.classList.remove('open');
}
hamburgerBtn.addEventListener('click', openSidebar);
sidebarCloseBtn.addEventListener('click', closeSidebar);
sidebarOverlayEl.addEventListener('click', closeSidebar);

/* ===== Tab switching ===== */
const navItems = document.querySelectorAll('.nav-item[data-tab]');
const sections = document.querySelectorAll('.section');
const topbarTitle = document.getElementById('topbarTitle');
const titles = { overview: 'Overview', sessions: 'My Sessions', availability: 'Availability', profile: 'Profile', meeting: 'Live Meeting' };

function switchTab(tab){
  navItems.forEach(b => b.classList.remove('active'));
  const btn = document.querySelector(`.nav-item[data-tab="${tab}"]`);
  if (btn) btn.classList.add('active');
  sections.forEach(s => s.classList.remove('active'));
  document.getElementById('section-' + tab).classList.add('active');
  topbarTitle.textContent = titles[tab];
  if (tab === 'overview') loadStats();
  if (tab === 'sessions') loadBookings();
  if (tab === 'availability') loadSlots();
  closeSidebar();
}

navItems.forEach(btn => {
  btn.addEventListener('click', () => switchTab(btn.dataset.tab));
});

/* ===== Overview stats ===== */
async function loadStats(){
  try {
    const res = await fetch('get_lecturer_stats.php');
    const data = await res.json();
    if (!data.success) return;
    document.getElementById('statUpcoming').textContent = data.data.upcoming_sessions;
    document.getElementById('statPending').textContent = data.data.pending_approvals;
    document.getElementById('statStudents').textContent = data.data.total_students;
    document.getElementById('statSlots').textContent = data.data.available_slots;
  } catch(e) {}
  loadNextUp();
}

async function loadNextUp(){
  const el = document.getElementById('nextUpList');
  try {
    const res = await fetch('get_lecturer_bookings.php');
    const data = await res.json();
    if (!data.success) { el.innerHTML = `<div class="empty-msg">Error loading</div>`; return; }
    const today = new Date().toISOString().split('T')[0];
    const upcoming = data.data
      .filter(b => b.status === 'approved' && b.date >= today)
      .slice(0, 5);
    if (upcoming.length === 0) {
      el.innerHTML = `<div class="empty-msg">Upcoming approved sessions නැහැ.</div>`;
      return;
    }
    el.innerHTML = upcoming.map(b => `
      <div class="slot-item">
        <div class="slot-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        </div>
        <div class="slot-info">
          <div class="slot-date">${b.student_name} — ${b.package_name}</div>
          <div class="slot-time">${b.date} at ${b.time}</div>
        </div>
      </div>`).join('');
  } catch(e) {
    el.innerHTML = `<div class="empty-msg">Server error.</div>`;
  }
}

/* ===== My Sessions ===== */
let allBookings = [];
let currentFilter = 'all';

document.getElementById('filterPills').addEventListener('click', (e) => {
  if (!e.target.classList.contains('pill')) return;
  document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
  e.target.classList.add('active');
  currentFilter = e.target.dataset.filter;
  renderBookings();
});

async function loadBookings(){
  const el = document.getElementById('bookingsList');
  el.innerHTML = `<div class="empty-msg">Loading...</div>`;
  try {
    const res = await fetch('get_lecturer_bookings.php');
    const data = await res.json();
    if (!data.success) { el.innerHTML = `<div class="empty-msg">${data.message || 'Error'}</div>`; return; }
    allBookings = data.data;
    renderBookings();
  } catch(e) {
    el.innerHTML = `<div class="empty-msg">Server error.</div>`;
  }
}

function renderBookings(){
  const el = document.getElementById('bookingsList');
  const filtered = currentFilter === 'all' ? allBookings : allBookings.filter(b => b.status === currentFilter);
  if (filtered.length === 0) {
    el.innerHTML = `<div class="empty-msg">Sessions නැහැ.</div>`;
    return;
  }
  el.innerHTML = filtered.map(b => `
    <div class="booking-item">
      <div class="booking-avatar">${b.student_name.charAt(0).toUpperCase()}</div>
      <div class="booking-info">
        <div class="booking-name">${b.student_name}</div>
        <div class="booking-meta">${b.package_name} · ${b.date} at ${b.time}</div>
      </div>
      ${b.status === 'pending' ? `
        <div class="booking-actions">
          <button class="btn-approve" onclick="updateStatus(${b.id}, 'approved')">Approve</button>
          <button class="btn-reject" onclick="updateStatus(${b.id}, 'rejected')">Reject</button>
        </div>
      ` : b.status === 'approved' ? `
        <div class="booking-actions">
          <button class="btn-meeting" onclick='startMeeting(${b.id}, ${JSON.stringify(b.student_name)})'>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
            Start Meeting
          </button>
          <span class="status-badge ${b.status}">${b.status}</span>
        </div>
      ` : `<span class="status-badge ${b.status}">${b.status}</span>`}
    </div>`).join('');
}

window.updateStatus = async function(bookingId, status){
  try {
    const res = await fetch('update_lecturer_booking_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ booking_id: bookingId, status })
    });
    const data = await res.json();
    if (data.success) {
      await loadBookings();
      loadStats();
    } else {
      alert(data.message || 'Update failed');
    }
  } catch(e) {
    alert('Server error');
  }
};

/* ===== Live Meeting ===== */
let meetingWindow = null;
let activeSlotId = null;
let currentMeetingUrl = null;

window.startMeeting = function(bookingId, studentName){
  const roomName = `SipwayCampus-Session-${bookingId}`;
  activeSlotId = null;
  openMeetingRoom(roomName, studentName);
};

window.startSession = async function(slotId){
  try {
    const res = await fetch('start_session.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ slot_id: slotId })
    });
    const data = await res.json();
    if (!data.success) { alert(data.message || 'Session start කරන්න බැරි උනා'); return; }
    activeSlotId = slotId;
    openMeetingRoom(data.room_name, 'Live Session');
  } catch(e) {
    alert('Server error');
  }
};

window.openLiveSlot = function(slotId, roomName){
  activeSlotId = slotId;
  openMeetingRoom(roomName, 'Live Session');
};

function openMeetingRoom(roomName, label){
  document.getElementById('meetingStudentName').textContent = label;
  const url = 'https://meet.jit.si/' + encodeURIComponent(roomName)
    + '#config.prejoinPageEnabled=false'
    + '&userInfo.displayName=' + encodeURIComponent(LECTURER_NAME);
  currentMeetingUrl = url;
  meetingWindow = window.open(url, '_blank', 'noopener,noreferrer');
  switchTab('meeting');
}

document.getElementById('reopenMeetingBtn').addEventListener('click', () => {
  if (!currentMeetingUrl) return;
  meetingWindow = window.open(currentMeetingUrl, '_blank', 'noopener,noreferrer');
});

document.getElementById('leaveMeetingBtn').addEventListener('click', () => leaveMeeting(true));

function leaveMeeting(goBackToSessions){
  if (meetingWindow && !meetingWindow.closed) {
    meetingWindow.close();
  }
  meetingWindow = null;
  if (activeSlotId !== null) {
    const slotIdToEnd = activeSlotId;
    activeSlotId = null;
    fetch('end-session.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ slot_id: slotIdToEnd })
    }).catch(() => {}).finally(() => {
      if (goBackToSessions) loadSlots();
    });
  }
  if (goBackToSessions) {
    switchTab('availability');
  }
}

/* ===== Availability ===== */
const slotList = document.getElementById('slotList');

async function loadSlots(){
  slotList.innerHTML = `<div class="empty-msg">Loading...</div>`;
  try {
    const res = await fetch('get_my_availability.php');
    const data = await res.json();
    if (!data.success) { slotList.innerHTML = `<div class="empty-msg">${data.message || 'Error loading slots'}</div>`; return; }
    if (data.data.length === 0) { slotList.innerHTML = `<div class="empty-msg">තවම කිසිම slot එකක් නැහැ.</div>`; return; }

    slotList.innerHTML = data.data.map(s => {
      const d = new Date(s.date + 'T00:00:00');
      const dateLabel = d.toLocaleDateString('en-GB', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
      const isLive = s.status === 'live';
      const approvalStatus = s.approval_status || 'approved';
      const isPending = approvalStatus === 'pending';
      const isRejected = approvalStatus === 'rejected';
      const isCompleted = s.status === 'ended';
      const subjectLabel = s.subject ? ` · ${s.subject}` : '';

      let actionButton;
      if (isCompleted) {
        actionButton = `
          <span class="approval-badge rejected-approval">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            Session Ended
          </span>`;
      } else if (isPending) {
        actionButton = `
          <span class="approval-badge pending-approval">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            Waiting for Admin Approval
          </span>`;
      } else if (isRejected) {
        actionButton = `
          <span class="approval-badge rejected-approval">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            Rejected by Admin
          </span>`;
      } else if (isLive) {
        actionButton = `<span class="live-badge"><span class="live-dot"></span>LIVE</span>
     <button class="btn-meeting" onclick='openLiveSlot(${s.id}, ${JSON.stringify(s.room_name)})'>
       <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
       Open
     </button>
     <button class="btn-end" onclick="endSessionNow(${s.id})">
       <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
       End Session
     </button>`;
      } else {
        actionButton = `<button class="btn-meeting" onclick="startSession(${s.id})">
       <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
       Start Session
     </button>
     <button class="btn-end" onclick="endSessionNow(${s.id})" title="End this session">
       <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
       End Session
     </button>`;
      }

      return `
        <div class="slot-item">
          <div class="slot-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
          </div>
          <div class="slot-info">
            <div class="slot-date">${dateLabel}${subjectLabel}</div>
            <div class="slot-time">${s.start} - ${s.end}</div>
          </div>
          <div class="slot-item-actions">
            ${actionButton}
          </div>
        </div>`;
    }).join('');
  } catch(e) {
    slotList.innerHTML = `<div class="empty-msg">Server error. Try again.</div>`;
  }
}

/* ===== Profile photo preview ===== */
document.getElementById('profilePhoto').addEventListener('change', function(){
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const img = document.getElementById('profilePhotoPreview');
    const fallback = document.getElementById('profilePhotoFallback');
    img.src = e.target.result;
    img.style.display = 'block';
    fallback.style.display = 'none';
  };
  reader.readAsDataURL(file);
});

/* ===== Profile save ===== */
document.getElementById('saveProfileBtn').addEventListener('click', async () => {
  const name = document.getElementById('profileName').value.trim();
  const subject = document.getElementById('profileSubject').value.trim();
  const qualifications = document.getElementById('profileQualifications').value.trim();
  const newPassword = document.getElementById('profilePassword').value.trim();
  const photoFile = document.getElementById('profilePhoto').files[0];
  const msg = document.getElementById('profileMsg');
  const btn = document.getElementById('saveProfileBtn');
  if (!name) { msg.style.color = '#ef4444'; msg.textContent = 'Name එක ඕන'; return; }

  btn.disabled = true;
  btn.textContent = 'Saving...';
  try {
    let res;
    if (photoFile) {
      const formData = new FormData();
      formData.append('name', name);
      formData.append('subject', subject);
      formData.append('qualifications', qualifications);
      formData.append('new_password', newPassword);
      formData.append('photo', photoFile);
      res = await fetch('update_lecturer_profile.php', { method: 'POST', body: formData });
    } else {
      res = await fetch('update_lecturer_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, subject, qualifications, new_password: newPassword })
      });
    }
    const data = await res.json();
    msg.style.color = data.success ? '#10b981' : '#ef4444';
    msg.textContent = data.message;
    if (data.success) {
      document.getElementById('profilePassword').value = '';
      document.getElementById('profilePhoto').value = '';
      if (data.photo) {
        const topbarAvatar = document.getElementById('topbarAvatar');
        topbarAvatar.innerHTML = `<img src="uploads/lecturers/${data.photo}" alt="${name}">`;
      }
    }
  } catch(e) {
    msg.style.color = '#ef4444';
    msg.textContent = 'Server error';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save Changes';
  }
});

/* ===== Init ===== */
switchTab('availability');

window.endSessionNow = async function(slotId){
  if (!confirm('මේ live session එක end කරන්නද?')) return;
  if (activeSlotId === slotId) {
    if (meetingWindow && !meetingWindow.closed) {
      meetingWindow.close();
    }
    meetingWindow = null;
    activeSlotId = null;
  }
  try {
    const res = await fetch('end_session.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ slot_id: slotId })
    });
    const data = await res.json();
    if (data.success === false) {
      alert(data.message || 'Session end කරන්න බැරි උනා');
      return;
    }
    await loadSlots();
    loadStats();
  } catch(e) {
    alert('Server error');
  }
};
</script>
</body>
</html>