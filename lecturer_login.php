<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sipway Campus - Lecturer Login</title>
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
    --danger:#c0392b;
    --danger-soft:#fdecea;
    --success:#1f9d55;
    --radius-lg:16px;
    --radius-sm:8px;
    --shadow-card:0 24px 60px -12px rgba(15,42,74,0.14), 0 2px 8px rgba(15,42,74,0.06);
    --ease:cubic-bezier(.4,0,.2,1);
  }

  *{ box-sizing:border-box; }
  html{ -webkit-text-size-adjust:100%; }
  body{
    margin:0;
    font-family:'Inter', 'Noto Sans Sinhala', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    background:
      radial-gradient(1100px 500px at 85% -10%, rgba(232,130,95,0.08), transparent),
      radial-gradient(900px 500px at -5% 110%, rgba(15,42,74,0.05), transparent),
      var(--bg);
    color:var(--text);
    min-height:100vh;
    -webkit-font-smoothing:antialiased;
    display:flex;
    flex-direction:column;
  }

  .topbar{
    height:60px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 clamp(16px, 4vw, 40px);
    background:rgba(255,255,255,0.85);
    backdrop-filter:saturate(180%) blur(10px);
    border-bottom:1px solid var(--line-soft);
    position:sticky;
    top:0;
    z-index:40;
  }
  .topbar .logo{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:800;
    color:var(--navy);
    letter-spacing:0.2px;
    font-size:15px;
  }
  .logo-mark{
    width:30px; height:30px;
    border-radius:8px;
    background:linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:13px; font-weight:800;
    flex-shrink:0;
  }

  .wrap{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:clamp(28px, 5vw, 64px) clamp(16px, 4vw, 24px);
  }

  .auth-panel{
    width:420px;
    max-width:100%;
  }
  .card{
    background:var(--card);
    border-radius:var(--radius-lg);
    padding:clamp(28px, 4vw, 44px) clamp(24px, 4vw, 40px);
    box-shadow:var(--shadow-card);
    border:1px solid var(--line-soft);
    animation:cardIn .45s var(--ease);
  }
  @keyframes cardIn{
    from{ opacity:0; transform:translateY(10px); }
    to{ opacity:1; transform:translateY(0); }
  }

  .card-head{ text-align:center; margin-bottom:26px; }
  .card h1{
    font-size:clamp(24px, 3vw, 30px);
    color:var(--navy);
    margin:0 0 4px 0;
    font-weight:800;
    letter-spacing:-0.4px;
  }
  .subtext{
    color:var(--muted);
    font-size:14px;
    line-height:1.65;
    margin:0;
  }

  .lecturer-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:var(--coral-soft);
    color:var(--coral-dark);
    font-weight:800;
    font-size:11px;
    letter-spacing:0.6px;
    padding:5px 12px;
    border-radius:999px;
    margin-bottom:10px;
    text-transform:uppercase;
  }

  .field{ margin-bottom:16px; position:relative; }
  .field label{
    display:block;
    font-size:12.5px;
    color:var(--text);
    margin-bottom:7px;
    font-weight:600;
  }
  .input-shell{
    position:relative;
    display:flex;
    align-items:center;
  }
  .input-icon{
    position:absolute;
    left:14px;
    width:18px; height:18px;
    color:var(--muted-2);
    pointer-events:none;
    display:flex;
    flex-shrink:0;
  }
  .field input{
    width:100%;
    padding:13px 14px 13px 40px;
    border-radius:var(--radius-sm);
    border:1.5px solid var(--line);
    font-size:14.5px;
    font-family:inherit;
    outline:none;
    background:#fbfaf9;
    color:var(--text);
    transition:border-color .15s var(--ease), box-shadow .15s var(--ease), background .15s var(--ease);
  }
  .field input::placeholder{ color:var(--muted-2); }
  .field input:hover{ border-color:#d7d2c8; }
  .field input:focus{
    border-color:var(--coral);
    background:#fff;
    box-shadow:0 0 0 4px rgba(232,130,95,0.14);
  }
  .field input.invalid{
    border-color:var(--danger);
    background:var(--danger-soft);
  }
  .field input.invalid:focus{
    box-shadow:0 0 0 4px rgba(192,57,43,0.12);
  }
  .field input.valid{
    border-color:var(--success);
  }

  .toggle-pass{
    position:absolute;
    right:12px;
    background:none;
    border:none;
    cursor:pointer;
    color:var(--muted-2);
    padding:6px;
    display:flex;
    align-items:center;
    border-radius:6px;
  }
  .toggle-pass:hover{ color:var(--navy-2); background:var(--navy-soft); }
  .toggle-pass svg{ width:18px; height:18px; }

  .error{
    font-size:12px;
    color:var(--danger);
    margin-top:6px;
    display:none;
    align-items:center;
    gap:5px;
    font-weight:500;
  }
  .error.show{ display:flex; animation:shake .3s var(--ease); }
  @keyframes shake{
    0%,100%{ transform:translateX(0); }
    25%{ transform:translateX(-3px); }
    75%{ transform:translateX(3px); }
  }

  .row-inline{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:2px 0 20px 0;
    flex-wrap:wrap;
    gap:8px;
  }
  .checkbox-label{
    font-size:13px;
    color:var(--muted);
    display:flex;
    align-items:center;
    gap:7px;
    font-weight:500;
    cursor:pointer;
    user-select:none;
  }
  .checkbox-label input{
    width:16px; height:16px;
    accent-color:var(--coral);
    cursor:pointer;
  }
  .row-inline a{
    font-size:13px;
    color:var(--navy-2);
    text-decoration:none;
    font-weight:700;
  }
  .row-inline a:hover{ color:var(--coral-dark); }

  .btn-primary{
    width:100%;
    padding:14.5px;
    border:none;
    border-radius:var(--radius-sm);
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color:#fff;
    font-weight:800;
    font-size:14px;
    letter-spacing:0.4px;
    cursor:pointer;
    transition:transform .12s var(--ease), box-shadow .2s var(--ease), filter .15s;
    box-shadow:0 10px 24px -6px rgba(214,108,71,0.55);
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
  }
  .btn-primary:hover{ filter:brightness(1.04); box-shadow:0 14px 28px -6px rgba(214,108,71,0.6); }
  .btn-primary:active{ transform:translateY(1px) scale(.995); }
  .btn-primary:disabled{ opacity:.7; cursor:not-allowed; }
  .btn-primary .spinner{
    width:16px; height:16px;
    border:2px solid rgba(255,255,255,0.4);
    border-top-color:#fff;
    border-radius:50%;
    animation:spin .7s linear infinite;
    display:none;
  }
  .btn-primary.loading .spinner{ display:inline-block; }
  .btn-primary.loading .btn-text{ opacity:0.85; }
  @keyframes spin{ to{ transform:rotate(360deg); } }

  .switch-row{
    text-align:center;
    margin-top:26px;
    font-size:13.5px;
    color:var(--muted);
  }
  .switch-row a{
    color:var(--coral-dark);
    font-weight:800;
    text-decoration:none;
  }
  .switch-row a:hover{ text-decoration:underline; }

  a:focus-visible, button:focus-visible, input:focus-visible{
    outline:2.5px solid var(--coral);
    outline-offset:2px;
  }

  .toast{
    position:fixed;
    top:20px;
    left:50%;
    transform:translateX(-50%) translateY(-16px);
    background:var(--navy);
    color:#fff;
    padding:13px 22px;
    border-radius:10px;
    font-size:13.5px;
    font-weight:600;
    opacity:0;
    pointer-events:none;
    transition:opacity .25s var(--ease), transform .25s var(--ease);
    z-index:50;
    box-shadow:0 12px 30px rgba(15,42,74,0.3);
    display:flex;
    align-items:center;
    gap:10px;
    max-width:90vw;
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error-toast{ background:var(--danger); }

  @media (max-width:640px){
    .topbar{ padding:0 16px; }
    .wrap{ padding:20px 14px 40px; }
    .card{ padding:30px 20px; border-radius:14px; }
    .card h1{ font-size:23px; }
  }

  @media (prefers-reduced-motion: reduce){
    *{ animation-duration:0.001ms !important; transition-duration:0.001ms !important; }
  }
</style>
</head>
<body>

<div class="topbar">
  <span class="logo"><span class="logo-mark">SC</span>Sipway English Accademy</span>
</div>

<div class="wrap">
  <div class="auth-panel">
    <div class="card" id="lecturerCard">
      <div class="card-head">
        <span class="lecturer-badge">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          Lecturer Portal
        </span>
        <h1>Lecturer Login</h1>
        <p class="subtext">ඔබේ ලෙක්චරර් ගිණුමට පිවිසෙන්න.</p>
      </div>

      <form id="lecturerForm" novalidate>
        <div class="field">
          <label for="lecturerEmail">Email address</label>
          <div class="input-shell">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 6l-10 7L2 6"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
            </span>
            <input type="email" id="lecturerEmail" placeholder="you@example.com" autocomplete="email" required>
          </div>
          <div class="error" id="lecturerEmailErr">⚠ Please enter a valid email address.</div>
        </div>

        <div class="field">
          <label for="lecturerPass">Password</label>
          <div class="input-shell">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            </span>
            <input type="password" id="lecturerPass" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="toggle-pass" data-target="lecturerPass" aria-label="Show password">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="error" id="lecturerPassErr">⚠ Password is required.</div>
        </div>

        <div class="row-inline">
          <label class="checkbox-label"><input type="checkbox"> Remember me</label>
          <a href="#">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary" id="lecturerBtn">
          <span class="spinner"></span>
          <span class="btn-text">LOGIN</span>
        </button>
      </form>

      <p class="switch-row">Not a lecturer? <a href="index.html">Back to student login</a></p>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
  const toast = document.getElementById('toast');

  function showToast(msg, isError){
    toast.textContent = (isError ? '⚠ ' : '✓ ') + msg;
    toast.classList.toggle('error-toast', !!isError);
    toast.classList.add('show');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(()=> toast.classList.remove('show'), 2600);
  }

  function isValidEmail(v){
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());
  }

  function setFieldState(input, errEl, valid){
    input.classList.toggle('invalid', !valid);
    input.classList.toggle('valid', valid);
    errEl.classList.toggle('show', !valid);
  }

  document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      const isPass = target.type === 'password';
      target.type = isPass ? 'text' : 'password';
      btn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
    });
  });

  const lecturerEmail = document.getElementById('lecturerEmail');
  const lecturerPass = document.getElementById('lecturerPass');
  const lecturerEmailErr = document.getElementById('lecturerEmailErr');
  const lecturerPassErr = document.getElementById('lecturerPassErr');

  lecturerEmail.addEventListener('input', () => {
    if(lecturerEmail.value.length) setFieldState(lecturerEmail, lecturerEmailErr, isValidEmail(lecturerEmail.value));
  });
  lecturerPass.addEventListener('input', () => {
    if(lecturerPass.value.length) setFieldState(lecturerPass, lecturerPassErr, lecturerPass.value.length > 0);
  });

  // LECTURER LOGIN FORM HANDLING
  // NOTE: this posts to 'lecturer-auth.php'. Point this at whatever your
  // existing password_hash()/password_verify() lecturer-auth endpoint is
  // named in the backend, if it differs.
  const lecturerForm = document.getElementById('lecturerForm');
  const lecturerBtn = document.getElementById('lecturerBtn');

  lecturerForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    let ok = true;

    const emailOk = isValidEmail(lecturerEmail.value);
    setFieldState(lecturerEmail, lecturerEmailErr, emailOk);
    if (!emailOk) ok = false;

    const passOk = lecturerPass.value.length > 0;
    setFieldState(lecturerPass, lecturerPassErr, passOk);
    if (!passOk) ok = false;

    if (!ok) return;

    lecturerBtn.classList.add('loading');
    lecturerBtn.disabled = true;

    try {
      const res = await fetch('lecturer-auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: lecturerEmail.value.trim(),
          password: lecturerPass.value
        })
      });

      const result = await res.json();

      if (result.success) {
        showToast(result.message || 'Login successful! Welcome back.');

        lecturerForm.reset();
        [lecturerEmail, lecturerPass].forEach(field => field.classList.remove('valid', 'invalid'));

        if (result.lecturer) {
          localStorage.setItem('sipwayLecturer', JSON.stringify(result.lecturer));
        }

        setTimeout(() => { window.location.href = 'lecturer-dashboard.php'; }, 1000);

      } else {
        showToast(result.message || 'Invalid email or password.', true);
      }
    } catch (err) {
      console.error(err);
      showToast('Could not connect to the server. Please try again.', true);
    } finally {
      lecturerBtn.classList.remove('loading');
      lecturerBtn.disabled = false;
    }
  });
})();
</script>

</body>
</html>