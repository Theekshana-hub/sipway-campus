<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: index.html");
    exit();
}
require_once 'db.php';
if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}

$studentId = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT full_name, email, mobile, language, gender, address, profile_photo FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$student) {
    session_destroy();
    header("Location: index.html");
    exit();
}

$fullNameSafe = htmlspecialchars($student['full_name']);
$emailSafe    = htmlspecialchars($student['email']);
$mobileSafe   = htmlspecialchars($student['mobile']);
$genderVal    = $student['gender'] ?: 'Other';
$addressSafe  = htmlspecialchars($student['address'] ?? '');
$photoUrl     = $student['profile_photo'] ? htmlspecialchars($student['profile_photo']) : '';
$firstName    = htmlspecialchars(explode(' ', trim($student['full_name']))[0]);
$initials     = strtoupper(substr(trim($student['full_name']), 0, 1));
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile - Sipway Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy:#1e1b4b;
    --navy-2:#7c3aed;
    --navy-soft:#f3e8ff;
    --coral:#a855f7;
    --coral-dark:#7c3aed;
    --coral-soft:#f3e8ff;
    --bg:#f4f0ff;
    --card:#ffffff;
    --line:#e9e5f5;
    --line-soft:#f3f0fa;
    --muted:#6b7280;
    --muted-2:#9ca3af;
    --text:#1e1b4b;
    --danger:#b91c1c;
    --success:#10b981;
    --success-soft:#d1fae5;
    --radius-lg:20px;
    --radius-md:14px;
    --radius-sm:10px;
    --shadow-card:0 12px 36px -12px rgba(124,58,237,0.14);
    --ease:cubic-bezier(.4,0,.2,1);
  }

  html[data-theme="dark"]{
    --navy:#c4b5fd;
    --navy-2:#a855f7;
    --navy-soft:#241f3d;
    --coral:#ec4899;
    --coral-dark:#a855f7;
    --coral-soft:#2e2540;
    --bg:#0f0c29;
    --card:#1a1440;
    --line:#332e58;
    --line-soft:#241f3d;
    --muted:#a89fce;
    --muted-2:#78708f;
    --text:#f4efe9;
    --danger:#ff6b6b;
    --success:#3ec97a;
    --success-soft:#173425;
    --shadow-card:0 12px 36px -12px rgba(0,0,0,0.5);
  }

  *{ box-sizing:border-box; margin:0; padding:0; }
  body{
    font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    -webkit-font-smoothing:antialiased;
    transition:background .3s var(--ease), color .3s var(--ease);
  }

  /* ========== TOPBAR ========== */
  .topbar{
    height:64px;
    display:flex;
    align-items:center;
    gap:12px;
    padding:0 20px;
    background:var(--card);
    border-bottom:1px solid var(--line-soft);
    position:sticky;
    top:0;
    z-index:50;
    backdrop-filter:blur(12px);
  }
  .back-btn{
    display:inline-flex;
    align-items:center;
    gap:7px;
    font-weight:700;
    color:var(--navy-2);
    text-decoration:none;
    font-size:13.5px;
    padding:8px 14px;
    border-radius:999px;
    border:1px solid var(--line);
    background:var(--bg);
    transition:all .2s;
  }
  .back-btn:hover{
    background:var(--navy-soft);
    border-color:var(--coral);
    color:var(--coral-dark);
  }
  .back-btn svg{ width:16px; height:16px; }
  .topbar-title{
    font-weight:800;
    color:var(--text);
    font-size:15.5px;
    letter-spacing:-0.3px;
  }
  .theme-toggle{
    margin-left:auto;
    position:relative;
    width:50px; height:26px;
    flex-shrink:0;
    border-radius:999px;
    border:1px solid var(--line);
    background:linear-gradient(135deg, #ede4fb, #f3e8ff);
    cursor:pointer;
    display:flex;
    align-items:center;
    padding:2px;
    transition:background .3s, border-color .3s;
  }
  html[data-theme="dark"] .theme-toggle{
    background:linear-gradient(135deg, #241f3d, #2e2760);
  }
  .theme-toggle .toggle-knob{
    width:20px; height:20px;
    border-radius:50%;
    background:linear-gradient(135deg, var(--coral), var(--coral-dark));
    display:flex; align-items:center; justify-content:center;
    color:#fff;
    box-shadow:0 3px 8px rgba(124,58,237,0.4);
    transform:translateX(0);
    transition:transform .35s var(--ease), background .3s;
  }
  html[data-theme="dark"] .theme-toggle .toggle-knob{
    transform:translateX(24px);
    background:linear-gradient(135deg, #a855f7, #ec4899);
  }
  .theme-toggle .toggle-knob svg{ width:12px; height:12px; }

  /* ========== LAYOUT ========== */
  .wrap{
    max-width:580px;
    margin:32px auto 60px;
    padding:0 16px;
  }

  /* Hero header on card */
  .card{
    background:var(--card);
    border:1px solid var(--line-soft);
    border-radius:var(--radius-lg);
    box-shadow:var(--shadow-card);
    overflow:hidden;
    transition:background .3s, border-color .3s;
  }
  .card-hero{
    background:
      radial-gradient(circle at 20% 20%, rgba(255,255,255,0.12), transparent 45%),
      linear-gradient(135deg, #0f0c29 0%, #7c3aed 50%, #ec4899 100%);
    padding:28px 28px 56px;
    position:relative;
    text-align:center;
  }
  .card-hero h1{
    font-size:20px;
    font-weight:800;
    color:#fff;
    margin:0 0 4px 0;
    letter-spacing:-0.3px;
  }
  .card-hero p{
    font-size:12.5px;
    color:rgba(255,255,255,0.75);
    font-weight:600;
    margin:0;
  }
  .card-body{
    padding:0 28px 32px;
    margin-top:-42px;
    position:relative;
  }

  /* Photo */
  .photo-block{
    display:flex;
    flex-direction:column;
    align-items:center;
    margin-bottom:28px;
  }
  .photo-wrap{
    position:relative;
    width:96px;
    height:96px;
  }
  .photo-preview{
    width:96px; height:96px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid var(--card);
    box-shadow:0 8px 24px rgba(0,0,0,0.18);
    background:var(--navy-soft);
    display:block;
  }
  .photo-preview-fallback{
    width:96px; height:96px;
    border-radius:50%;
    background:linear-gradient(135deg, #a855f7, #ec4899);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    font-size:36px;
    border:4px solid var(--card);
    box-shadow:0 8px 24px rgba(0,0,0,0.18);
  }
  .photo-edit-btn{
    position:absolute;
    bottom:2px; right:2px;
    width:32px; height:32px;
    border-radius:50%;
    border:2px solid var(--card);
    background:linear-gradient(135deg, #a855f7, #ec4899);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    box-shadow:0 4px 12px rgba(168,85,247,0.4);
    transition:transform .2s, filter .2s;
  }
  .photo-edit-btn:hover{ transform:scale(1.08); filter:brightness(1.08); }
  .photo-edit-btn svg{ width:14px; height:14px; }
  .photo-edit-btn input[type=file]{ display:none; }
  .photo-hint{
    font-size:11.5px;
    color:var(--muted);
    margin-top:10px;
    font-weight:600;
  }

  /* Form fields */
  .section-label{
    font-size:11px;
    font-weight:800;
    color:var(--muted-2);
    text-transform:uppercase;
    letter-spacing:0.6px;
    margin:0 0 12px 0;
  }
  .field{ margin-bottom:16px; }
  .field label{
    display:block;
    font-size:12.5px;
    font-weight:700;
    margin-bottom:7px;
    color:var(--text);
  }
  .field input, .field select, .field textarea{
    width:100%;
    padding:13px 14px;
    border-radius:var(--radius-sm);
    border:1.5px solid var(--line);
    font-size:14.5px;
    font-family:inherit;
    background:var(--bg);
    color:var(--text);
    outline:none;
    transition:border-color .2s, background .2s, box-shadow .2s;
  }
  .field input:focus, .field select:focus, .field textarea:focus{
    border-color:var(--coral);
    background:var(--card);
    box-shadow:0 0 0 4px color-mix(in srgb, var(--coral) 14%, transparent);
  }
  .field input:disabled{
    opacity:0.6;
    cursor:not-allowed;
    background:var(--line-soft);
  }
  .field .locked-note{
    font-size:11.5px;
    color:var(--muted);
    margin-top:6px;
    display:flex;
    align-items:center;
    gap:5px;
  }
  .field .locked-note svg{ width:12px; height:12px; flex-shrink:0; }

  .row2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
  }

  .divider{
    height:1px;
    background:var(--line-soft);
    margin:26px 0 20px;
  }

  .btn-primary{
    width:100%;
    padding:15px;
    border:none;
    border-radius:var(--radius-sm);
    background:linear-gradient(135deg, #a855f7, #ec4899);
    color:#fff;
    font-weight:800;
    font-size:14.5px;
    letter-spacing:0.3px;
    cursor:pointer;
    box-shadow:0 10px 24px -6px color-mix(in srgb, var(--coral) 50%, transparent);
    transition:filter .2s, transform .15s, box-shadow .2s;
    margin-top:8px;
  }
  .btn-primary:hover{
    filter:brightness(1.06);
    transform:translateY(-1px);
    box-shadow:0 14px 28px -6px color-mix(in srgb, var(--coral) 55%, transparent);
  }
  .btn-primary:active{ transform:translateY(0); }
  .btn-primary:disabled{ opacity:0.7; cursor:not-allowed; transform:none; }

  /* Toast */
  .toast{
    position:fixed;
    top:20px;
    left:50%;
    transform:translateX(-50%) translateY(-20px);
    background:var(--navy);
    color:#fff;
    padding:14px 24px;
    border-radius:12px;
    font-size:13.5px;
    font-weight:700;
    opacity:0;
    pointer-events:none;
    transition:opacity .3s var(--ease), transform .3s var(--ease);
    z-index:100;
    box-shadow:0 12px 32px rgba(0,0,0,0.25);
  }
  .toast.show{
    opacity:1;
    transform:translateX(-50%) translateY(0);
  }
  .toast.error-toast{ background:var(--danger); }
  .toast.success-toast{ background:var(--success); }

  @media (max-width:560px){
    .wrap{ margin:20px auto 48px; }
    .card-hero{ padding:22px 18px 50px; }
    .card-body{ padding:0 18px 24px; }
    .row2{ grid-template-columns:1fr; }
    .card-hero h1{ font-size:18px; }
  }
</style>
</head>
<body>

<div class="topbar">
  <a href="dashboard.php" class="back-btn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
    Back
  </a>
  <span class="topbar-title">Edit Profile</span>
  <button class="theme-toggle" id="themeToggleBtn" aria-label="Toggle dark mode" title="Toggle dark / light mode">
    <span class="toggle-knob">
      <svg viewBox="0 0 24 24" fill="currentColor" id="themeKnobIcon"><circle cx="12" cy="12" r="5"/></svg>
    </span>
  </button>
</div>

<div class="wrap">
  <div class="card">
    <div class="card-hero">
      <h1>My Profile</h1>
      <p>Update your personal details</p>
    </div>

    <div class="card-body">
      <form id="editForm" novalidate>
        <!-- Photo -->
        <div class="photo-block">
          <div class="photo-wrap">
            <?php if ($photoUrl): ?>
              <img class="photo-preview" id="photoPreview" src="<?php echo $photoUrl; ?>" alt="Profile photo">
            <?php else: ?>
              <div class="photo-preview-fallback" id="photoFallback"><?php echo $initials; ?></div>
              <img class="photo-preview" id="photoPreview" style="display:none;" alt="Profile photo">
            <?php endif; ?>
            <label class="photo-edit-btn" for="profilePhotoInput" title="Change photo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
              <input type="file" id="profilePhotoInput" accept="image/png,image/jpeg,image/webp">
            </label>
          </div>
          <div class="photo-hint">JPG, PNG or WEBP · max 3MB</div>
        </div>

        <p class="section-label">Personal Information</p>

        <div class="row2">
          <div class="field">
            <label for="fullName">Full name</label>
            <input type="text" id="fullName" value="<?php echo $fullNameSafe; ?>" required>
          </div>
          <div class="field">
            <label for="mobile">Mobile number</label>
            <input type="tel" id="mobile" maxlength="10" value="<?php echo $mobileSafe; ?>" required>
          </div>
        </div>

        <div class="field">
          <label>Email address</label>
          <input type="email" value="<?php echo $emailSafe; ?>" disabled>
          <div class="locked-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            Email cannot be changed. Contact support if needed.
          </div>
        </div>

        <div class="row2">
          <div class="field">
            <label for="gender">Gender</label>
            <select id="gender">
              <option value="Male"   <?php echo $genderVal==='Male'?'selected':''; ?>>Male</option>
              <option value="Female" <?php echo $genderVal==='Female'?'selected':''; ?>>Female</option>
              <option value="Other"  <?php echo $genderVal==='Other'?'selected':''; ?>>Other</option>
            </select>
          </div>
          <div class="field">
            <label for="address">Address</label>
            <input type="text" id="address" value="<?php echo $addressSafe; ?>" placeholder="Your address">
          </div>
        </div>

        <div class="divider"></div>
        <p class="section-label">Change Password (optional)</p>

        <div class="row2">
          <div class="field">
            <label for="newPassword">New password</label>
            <input type="password" id="newPassword" placeholder="Leave blank to keep current">
          </div>
          <div class="field">
            <label for="confirmPassword">Confirm password</label>
            <input type="password" id="confirmPassword" placeholder="Re-enter new password">
          </div>
        </div>

        <button type="submit" class="btn-primary" id="saveBtn">Save Changes</button>
      </form>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
  // Theme toggle
  (function(){
    const root = document.documentElement;
    const btn = document.getElementById('themeToggleBtn');
    const knobIcon = document.getElementById('themeKnobIcon');
    const sunPath = '<circle cx="12" cy="12" r="5"/>';
    const moonPath = '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>';
    function applyTheme(theme){
      root.setAttribute('data-theme', theme);
      if (knobIcon) knobIcon.innerHTML = theme === 'dark' ? moonPath : sunPath;
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

  const toast = document.getElementById('toast');
  function showToast(msg, isError){
    toast.textContent = (isError ? '⚠ ' : '✓ ') + msg;
    toast.classList.remove('error-toast', 'success-toast');
    toast.classList.add(isError ? 'error-toast' : 'success-toast');
    toast.classList.add('show');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => toast.classList.remove('show'), 2800);
  }

  const photoInput = document.getElementById('profilePhotoInput');
  const photoPreview = document.getElementById('photoPreview');
  const photoFallback = document.getElementById('photoFallback');

  photoInput.addEventListener('change', () => {
    const file = photoInput.files[0];
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) {
      showToast('Image must be under 3MB.', true);
      photoInput.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreview.src = e.target.result;
      photoPreview.style.display = 'block';
      if (photoFallback) photoFallback.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });

  const form = document.getElementById('editForm');
  const saveBtn = document.getElementById('saveBtn');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const newPass = document.getElementById('newPassword').value;
    const confirmPass = document.getElementById('confirmPassword').value;

    if (newPass || confirmPass) {
      if (newPass.length < 6) { showToast('Password must be at least 6 characters.', true); return; }
      if (newPass !== confirmPass) { showToast('Passwords do not match.', true); return; }
    }

    const mobile = document.getElementById('mobile').value.trim();
    if (!/^0\d{9}$/.test(mobile)) { showToast('Enter a valid 10-digit mobile number.', true); return; }

    const fullName = document.getElementById('fullName').value.trim();
    if (!fullName) { showToast('Full name is required.', true); return; }

    const fd = new FormData();
    fd.append('fullName', fullName);
    fd.append('mobile', mobile);
    fd.append('gender', document.getElementById('gender').value);
    fd.append('address', document.getElementById('address').value.trim());
    if (newPass) fd.append('newPassword', newPass);
    if (photoInput.files[0]) fd.append('profilePhoto', photoInput.files[0]);

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    try {
      const res = await fetch('update_profile.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Profile updated successfully!');
        setTimeout(() => window.location.href = 'dashboard.php', 900);
      } else {
        showToast(data.message || 'Could not update profile.', true);
      }
    } catch (err) {
      showToast('Could not connect to the server.', true);
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save Changes';
    }
  });
</script>
</body>
</html>