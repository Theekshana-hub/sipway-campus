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
$lecturerGender = '';
$lecturerEmail = '';
$lecturerNic = '';
$lecturerPhone = '';
$lecturerUsername = '';
$lecturerPhoto = null;

$stmt = $conn->prepare("SELECT full_name, gender, subject, qualifications, email, nic, phone, username, photo FROM lecturers WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $lecturerId);
$stmt->execute();
$stmt->bind_result($dbFullName, $dbGender, $dbSubject, $dbQualifications, $dbEmail, $dbNic, $dbPhone, $dbUsername, $dbPhoto);
if ($stmt->fetch()) {
    $lecturerName = $dbFullName;
    $lecturerGender = $dbGender;
    $lecturerSubject = $dbSubject;
    $lecturerQualifications = $dbQualifications ?? '';
    $lecturerEmail = $dbEmail;
    $lecturerNic = $dbNic;
    $lecturerPhone = $dbPhone;
    $lecturerUsername = $dbUsername;
    $lecturerPhoto = $dbPhoto;
}
$stmt->close();

$firstName = htmlspecialchars(explode(' ', trim($lecturerName))[0]);
$photoUrl = $lecturerPhoto ? 'uploads/lecturers/' . htmlspecialchars($lecturerPhoto) : '';
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Settings - Sipway Campus</title>
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
  --success: #10b981;
  --success-soft: #d1fae5;
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

/* ===== Layout ===== */
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

.nav-item:hover {
  background: rgba(255,255,255,0.08);
  color: #fff;
}

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
  transition: background .15s;
}
.sidebar-close:hover { background: rgba(255,255,255,0.2); }
.sidebar-close svg { width: 16px; height: 16px; }

.sidebar-top-row { display: flex; align-items: center; }

.main {
  flex: 1;
  min-width: 0;
  background: linear-gradient(160deg, #f4f0ff 0%, #faf8ff 40%, #f0eaff 100%);
}

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
  transition: background .15s;
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
  max-width: 720px;
  margin: 0 auto;
  padding: 32px 26px 70px;
}

.page-title {
  font-size: 26px;
  font-weight: 800;
  color: var(--text);
  margin: 0 0 6px;
  letter-spacing: -0.5px;
}

.page-sub {
  font-size: 14.5px;
  color: var(--muted);
  margin: 0 0 28px;
  line-height: 1.6;
}

/* ===== Profile Card ===== */
.profile-card {
  background: var(--card);
  border: 1px solid var(--line-soft);
  border-radius: 22px;
  box-shadow: var(--shadow-card);
  overflow: hidden;
}

/* Photo header */
.profile-header {
  background: linear-gradient(135deg, #f3e8ff 0%, #fdf2f8 55%, #fff 100%);
  padding: 28px 28px 24px;
  border-bottom: 1px solid var(--line-soft);
  display: flex;
  align-items: center;
  gap: 22px;
  flex-wrap: wrap;
}

.profile-photo-wrap { position: relative; flex-shrink: 0; }

.profile-photo-preview {
  width: 92px;
  height: 92px;
  border-radius: 22px;
  object-fit: cover;
  background: #fff;
  border: 3px solid #fff;
  box-shadow: 0 8px 24px -6px rgba(124, 58, 237, 0.25);
  display: block;
}

.profile-photo-fallback {
  width: 92px;
  height: 92px;
  border-radius: 22px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  font-weight: 800;
  border: 3px solid #fff;
  box-shadow: 0 8px 24px -6px rgba(124, 58, 237, 0.25);
}

.profile-photo-actions { flex: 1; min-width: 200px; }

.profile-photo-actions h3 {
  font-size: 16px;
  font-weight: 800;
  color: var(--text);
  margin: 0 0 4px;
}

.profile-photo-actions .hint {
  font-size: 12.5px;
  color: var(--muted);
  margin-bottom: 12px;
}

.file-btn-wrap {
  position: relative;
  display: inline-block;
}

.file-btn-wrap input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}

.file-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 11px;
  background: #fff;
  border: 1.5px solid var(--line);
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  cursor: pointer;
  transition: all .18s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.file-btn:hover {
  border-color: #a855f7;
  background: var(--purple-soft);
  color: #6b21a8;
}

.file-btn svg { width: 16px; height: 16px; }

/* Form body */
.profile-body { padding: 28px; }

.section-block { margin-bottom: 28px; }
.section-block:last-of-type { margin-bottom: 8px; }

.section-label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 12.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: #a855f7;
  margin: 0 0 18px;
}

.section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, var(--line) 0%, transparent 100%);
}

/* ===== Improved form fields ===== */
.form-group { margin-bottom: 18px; }

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group label {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
}

.form-group label svg {
  width: 15px;
  height: 15px;
  color: #a855f7;
  flex-shrink: 0;
  opacity: 0.85;
}

.form-group label span {
  font-weight: 500;
  color: var(--muted-2);
  font-size: 12px;
}

.input-wrap {
  position: relative;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 13px 15px;
  border: 1.5px solid var(--line);
  border-radius: 12px;
  font-size: 14px;
  font-family: inherit;
  background: var(--bg-soft);
  appearance: none;
  transition: border-color .15s, box-shadow .15s, background .15s;
  color: var(--text);
}

.form-group select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  padding-right: 38px;
  cursor: pointer;
}

.form-group textarea {
  resize: vertical;
  min-height: 110px;
  line-height: 1.55;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #a855f7;
  box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.14);
  background: #fff;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
  color: var(--muted-2);
}

/* Field hint under inputs */
.field-hint {
  font-size: 11.5px;
  color: var(--muted-2);
  margin-top: 6px;
  line-height: 1.4;
}

/* Save button */
.form-actions {
  margin-top: 8px;
  padding-top: 22px;
  border-top: 1px solid var(--line-soft);
}

.add-btn {
  width: 100%;
  padding: 15px;
  border: none;
  border-radius: 13px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-weight: 800;
  font-size: 15px;
  cursor: pointer;
  box-shadow: 0 10px 28px -6px rgba(168, 85, 247, 0.45);
  transition: all .18s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.add-btn:hover:not(:disabled) {
  filter: brightness(1.05);
  transform: translateY(-1px);
  box-shadow: 0 14px 32px -6px rgba(168, 85, 247, 0.55);
}

.add-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
}

.add-btn svg { width: 18px; height: 18px; }

.form-msg {
  font-size: 13.5px;
  font-weight: 600;
  min-height: 20px;
  margin-top: 14px;
  text-align: center;
}

/* ===== Mobile ===== */
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

  .content { padding: 22px 14px 52px; }
  .page-title { font-size: 22px; }
  .page-sub { font-size: 13.5px; margin-bottom: 22px; }

  .profile-header { padding: 22px 18px 20px; }
  .profile-body { padding: 22px 18px; }
  .profile-photo-preview,
  .profile-photo-fallback { width: 80px; height: 80px; border-radius: 18px; font-size: 28px; }
}

@media (max-width: 560px) {
  .form-row { grid-template-columns: 1fr; }
  .profile-header { flex-direction: column; align-items: flex-start; }
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

    <a href="lecturer-dashboard.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
      Availability
    </a>

    <a href="session_logs.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
      Session Logs
    </a>

    <a href="lecturer_profile.php" class="nav-item active">
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
      <div class="topbar-title">Profile</div>
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
      <h1 class="page-title">Profile Settings</h1>
      <p class="page-sub">ඔබේ සියලුම details මෙතනින් update කරගන්න පුළුවන් — name, gender, subject, qualifications, email, NIC, phone, username, password, photo.</p>

      <div class="profile-card">
        <!-- Photo header -->
        <div class="profile-header">
          <div class="profile-photo-wrap">
            <?php if ($photoUrl): ?>
              <img src="<?php echo $photoUrl; ?>" class="profile-photo-preview" id="profilePhotoPreview" alt="Profile photo" onerror="this.style.display='none'; document.getElementById('profilePhotoFallback').style.display='flex';">
              <div class="profile-photo-fallback" id="profilePhotoFallback" style="display:none;"><?php echo strtoupper(substr($firstName,0,1)); ?></div>
            <?php else: ?>
              <img src="" class="profile-photo-preview" id="profilePhotoPreview" alt="Profile photo" style="display:none;">
              <div class="profile-photo-fallback" id="profilePhotoFallback"><?php echo strtoupper(substr($firstName,0,1)); ?></div>
            <?php endif; ?>
          </div>
          <div class="profile-photo-actions">
            <h3>Profile Photo</h3>
            <p class="hint">JPG, PNG හෝ WEBP — උපරිම 3MB</p>
            <div class="file-btn-wrap">
              <div class="file-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Choose Photo
              </div>
              <input type="file" id="profilePhoto" accept="image/png, image/jpeg, image/webp">
            </div>
          </div>
        </div>

        <!-- Form body -->
        <div class="profile-body">

          <!-- ===== Basic Info ===== -->
          <div class="section-block">
            <div class="section-label">Basic Info</div>

            <div class="form-row">
              <div class="form-group">
                <label for="profileName">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Full Name
                </label>
                <input type="text" id="profileName" value="<?php echo htmlspecialchars($lecturerName); ?>" placeholder="Your full name">
              </div>

              <div class="form-group">
                <label for="profileGender">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                  Gender
                </label>
                <select id="profileGender">
                  <option value="">Select gender...</option>
                  <option value="Male" <?php echo $lecturerGender === 'Male' ? 'selected' : ''; ?>>Male</option>
                  <option value="Female" <?php echo $lecturerGender === 'Female' ? 'selected' : ''; ?>>Female</option>
                  <option value="Other" <?php echo $lecturerGender === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="profileSubject">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
                Subject
              </label>
              <input type="text" id="profileSubject" value="<?php echo htmlspecialchars((string)$lecturerSubject); ?>" placeholder="e.g. IELTS, Spoken English">
            </div>

            <div class="form-group">
              <label for="profileQualifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10l-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/></svg>
                Qualifications
              </label>
              <textarea id="profileQualifications" placeholder="e.g. BA in English (Hons), TESOL Certified, 5+ years teaching experience"><?php echo htmlspecialchars($lecturerQualifications); ?></textarea>
              <div class="field-hint">ඔබේ degrees, certificates, experience කෙටියෙන් ලියන්න.</div>
            </div>
          </div>

          <!-- ===== Contact ===== -->
          <div class="section-block">
            <div class="section-label">Contact Details</div>

            <div class="form-group">
              <label for="profileEmail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 6l8 6 8-6"/></svg>
                Email Address
              </label>
              <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($lecturerEmail); ?>" placeholder="you@example.com">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="profileNic">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h6"/></svg>
                  NIC Number
                </label>
                <input type="text" id="profileNic" value="<?php echo htmlspecialchars($lecturerNic); ?>" maxlength="12" placeholder="XXXXXXXXXXXX">
              </div>

              <div class="form-group">
                <label for="profilePhone">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                  Phone Number
                </label>
                <input type="tel" id="profilePhone" value="<?php echo htmlspecialchars($lecturerPhone ?? ''); ?>" maxlength="15" placeholder="07XXXXXXXX">
              </div>
            </div>
          </div>

          <!-- ===== Login ===== -->
          <div class="section-block">
            <div class="section-label">Login Details</div>

            <div class="form-group">
              <label for="profileUsername">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Username
              </label>
              <input type="text" id="profileUsername" value="<?php echo htmlspecialchars($lecturerUsername); ?>" placeholder="Your login username">
            </div>

            <div class="form-group">
              <label for="profilePassword">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                New Password <span>(optional)</span>
              </label>
              <input type="password" id="profilePassword" placeholder="වෙනස් කරන්න ඕන නම් විතරක් type කරන්න">
              <div class="field-hint">Password වෙනස් කරන්න ඕන නම් පමණක් මෙහි type කරන්න.</div>
            </div>
          </div>

          <div class="form-actions">
            <button class="add-btn" id="saveProfileBtn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Save Changes
            </button>
            <div class="form-msg" id="profileMsg"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
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

document.getElementById('saveProfileBtn').addEventListener('click', async () => {
  const name = document.getElementById('profileName').value.trim();
  const gender = document.getElementById('profileGender').value;
  const subject = document.getElementById('profileSubject').value.trim();
  const qualifications = document.getElementById('profileQualifications').value.trim();
  const email = document.getElementById('profileEmail').value.trim();
  const nic = document.getElementById('profileNic').value.trim();
  const phone = document.getElementById('profilePhone').value.trim();
  const username = document.getElementById('profileUsername').value.trim();
  const newPassword = document.getElementById('profilePassword').value.trim();
  const photoFile = document.getElementById('profilePhoto').files[0];
  const msg = document.getElementById('profileMsg');
  const btn = document.getElementById('saveProfileBtn');

  if (!name)     { msg.style.color = '#ef4444'; msg.textContent = 'Name එක ඕන'; return; }
  if (!email)    { msg.style.color = '#ef4444'; msg.textContent = 'Email එක ඕන'; return; }
  if (!nic)      { msg.style.color = '#ef4444'; msg.textContent = 'NIC එක ඕන'; return; }
  if (!phone)    { msg.style.color = '#ef4444'; msg.textContent = 'Phone number එක ඕන'; return; }
  if (!username) { msg.style.color = '#ef4444'; msg.textContent = 'Username එක ඕන'; return; }

  btn.disabled = true;
  btn.innerHTML = 'Saving...';

  try {
    let res;
    if (photoFile) {
      const formData = new FormData();
      formData.append('name', name);
      formData.append('gender', gender);
      formData.append('subject', subject);
      formData.append('qualifications', qualifications);
      formData.append('email', email);
      formData.append('nic', nic);
      formData.append('phone', phone);
      formData.append('username', username);
      formData.append('new_password', newPassword);
      formData.append('photo', photoFile);
      res = await fetch('update_lecturer_profile.php', { method: 'POST', body: formData });
    } else {
      res = await fetch('update_lecturer_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, gender, subject, qualifications, email, nic, phone, username, new_password: newPassword })
      });
    }

    const data = await res.json();
    msg.style.color = data.success ? '#10b981' : '#ef4444';
    msg.textContent = data.message;

    if (data.success) {
      document.getElementById('profilePassword').value = '';
      document.getElementById('profilePhoto').value = '';
      const topbarAvatar = document.getElementById('topbarAvatar');
      if (data.photo) {
        topbarAvatar.innerHTML = `<img src="uploads/lecturers/${data.photo}" alt="${name}">`;
      }
    }
  } catch(e) {
    msg.style.color = '#ef4444';
    msg.textContent = 'Server error';
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Changes`;
  }
});
</script>
</body>
</html>