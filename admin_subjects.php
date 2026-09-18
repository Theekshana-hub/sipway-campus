<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subjects / Types - Sipway Campus Admin</title>
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
    --success-soft:#e8f8ee;
    --warning:#f2994a;
    --warning-soft:#fdf1e4;
    --radius-lg:16px;
    --radius-md:10px;
    --radius-sm:8px;
    --shadow-card:0 10px 34px -12px rgba(15,42,74,0.14), 0 2px 8px rgba(15,42,74,0.05);
    --ease:cubic-bezier(.4,0,.2,1);
    --sidebar-w:250px;
  }
  *{ box-sizing:border-box; }
  body{
    margin:0;
    font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    -webkit-font-smoothing:antialiased;
  }
  a{ text-decoration:none; color:inherit; }
  .sidebar{
    position:fixed; top:0; left:0; bottom:0; width:var(--sidebar-w);
    background:linear-gradient(180deg, var(--navy) 0%, #0b2039 100%);
    color:#fff; display:flex; flex-direction:column; z-index:50;
    transition:transform .25s var(--ease);
  }
  .sidebar-brand{
    display:flex; align-items:center; gap:10px; padding:22px 22px 20px;
    font-weight:800; font-size:15px; letter-spacing:0.3px;
    border-bottom:1px solid rgba(255,255,255,0.08);
  }
  .brand-mark{
    width:34px; height:34px; border-radius:9px;
    background:linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:800; flex-shrink:0;
    box-shadow:0 6px 14px rgba(214,108,71,0.4);
  }
  .sidebar-brand .sub{
    display:block; font-size:10.5px; font-weight:600;
    color:rgba(255,255,255,0.55); letter-spacing:1px; margin-top:2px;
  }
  .nav-group{ padding:18px 12px; flex:1; overflow-y:auto; }
  .nav-label{
    font-size:10.5px; font-weight:700; letter-spacing:1.2px;
    color:rgba(255,255,255,0.35); text-transform:uppercase; padding:8px 12px 6px;
  }
  .nav-item{
    display:flex; align-items:center; gap:12px; padding:11px 14px;
    border-radius:10px; font-size:13.5px; font-weight:600;
    color:rgba(255,255,255,0.75); cursor:pointer; margin-bottom:3px;
    transition:background .15s var(--ease), color .15s var(--ease);
    position:relative;
  }
  .nav-item svg{ width:18px; height:18px; flex-shrink:0; }
  .nav-item:hover{ background:rgba(255,255,255,0.06); color:#fff; }
  .nav-item.active{ background:rgba(232,130,95,0.16); color:#fff; }
  .nav-item.active::before{
    content:""; position:absolute; left:-12px; top:8px; bottom:8px;
    width:3px; border-radius:3px; background:var(--coral);
  }
  .sidebar-foot{ padding:16px 14px 20px; border-top:1px solid rgba(255,255,255,0.08); }
  .logout-btn{
    display:flex; align-items:center; gap:10px; width:100%; padding:11px 14px;
    border-radius:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
    color:#fff; font-weight:700; font-size:13px; cursor:pointer;
    transition:background .15s var(--ease);
  }
  .logout-btn:hover{ background:rgba(192,57,43,0.35); border-color:rgba(192,57,43,0.5); }
  .logout-btn svg{ width:16px; height:16px; }
  .main{ margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
  .topbar{
    height:68px; display:flex; align-items:center; justify-content:space-between;
    padding:0 28px; background:rgba(255,255,255,0.9); backdrop-filter:saturate(180%) blur(10px);
    border-bottom:1px solid var(--line-soft); position:sticky; top:0; z-index:30; gap:16px;
  }
  .menu-toggle{ display:none; background:none; border:none; cursor:pointer; color:var(--navy); padding:6px; }
  .topbar-title h2{ margin:0; font-size:18px; font-weight:800; color:var(--navy); letter-spacing:-0.2px; }
  .topbar-title p{ margin:2px 0 0; font-size:12.5px; color:var(--muted); font-weight:500; }
  .content{ padding:26px 28px 60px; flex:1; max-width:760px; }
  .greeting{ margin-bottom:22px; }
  .greeting h1{ font-size:clamp(20px,2.4vw,26px); font-weight:800; color:var(--navy); margin:0 0 4px; letter-spacing:-0.3px; }
  .greeting p{ margin:0; color:var(--muted); font-size:14px; }
  .panel{
    background:var(--card); border:1px solid var(--line-soft); border-radius:var(--radius-lg);
    box-shadow:var(--shadow-card); overflow:hidden; margin-bottom:20px;
  }
  .panel-head{ padding:20px 22px; border-bottom:1px solid var(--line-soft); }
  .panel-head h3{ margin:0; font-size:15.5px; font-weight:800; color:var(--navy); }
  .panel-head p{ margin:2px 0 0; font-size:12px; color:var(--muted); }
  .panel-body{ padding:22px; }
  .add-form{ display:flex; gap:10px; }
  .add-form input{
    flex:1; padding:11px 14px; border:1.5px solid var(--line); border-radius:var(--radius-sm);
    font-size:13.5px; font-family:inherit; outline:none; background:var(--bg); color:var(--text);
  }
  .add-form input:focus{ border-color:var(--coral); background:#fff; }
  .add-form button{
    padding:11px 20px; border:none; border-radius:var(--radius-sm);
    background:linear-gradient(135deg, var(--coral), var(--coral-dark)); color:#fff;
    font-weight:800; font-size:13px; cursor:pointer; white-space:nowrap;
    transition:filter .15s;
  }
  .add-form button:hover{ filter:brightness(1.05); }
  .add-form button:disabled{ opacity:.6; cursor:not-allowed; }
  .subject-list{ display:flex; flex-direction:column; gap:8px; margin-top:18px; }
  .subject-row{
    display:flex; align-items:center; gap:10px; padding:11px 14px;
    border:1px solid var(--line-soft); border-radius:var(--radius-sm); background:var(--bg);
  }
  .subject-row .s-name{ flex:1; font-size:13.5px; font-weight:700; color:var(--text); }
  .subject-row input.edit-input{
    flex:1; padding:8px 10px; border:1.5px solid var(--coral); border-radius:7px;
    font-size:13.5px; font-family:inherit; outline:none;
  }
  .subject-row .row-actions{ display:flex; gap:6px; flex-shrink:0; }
  .subject-row button{
    width:30px; height:30px; border-radius:7px; border:1px solid var(--line);
    background:#fff; color:var(--muted); cursor:pointer;
    display:flex; align-items:center; justify-content:center;
  }
  .subject-row button:hover{ border-color:var(--navy-2); color:var(--navy-2); }
  .subject-row button svg{ width:14px; height:14px; }
  .subject-row .del-btn{ background:var(--danger-soft); color:var(--danger); border-color:transparent; }
  .subject-row .del-btn:hover{ background:var(--danger); color:#fff; }
  .subject-row .save-btn{ background:var(--success-soft); color:var(--success); border-color:transparent; }
  .subject-row .save-btn:hover{ background:var(--success); color:#fff; }
  .empty-state{ padding:34px 20px; text-align:center; color:var(--muted-2); font-size:13px; }
  .toast{
    position:fixed; top:20px; left:50%; transform:translateX(-50%) translateY(-16px);
    background:var(--navy); color:#fff; padding:13px 22px; border-radius:10px;
    font-size:13.5px; font-weight:600; opacity:0; pointer-events:none;
    transition:opacity .25s var(--ease), transform .25s var(--ease); z-index:100;
    box-shadow:0 12px 30px rgba(15,42,74,0.3);
  }
  .toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }
  .toast.error-toast{ background:var(--danger); }
  .toast.success-toast{ background:var(--success); }
  .sidebar-backdrop{ display:none; position:fixed; inset:0; background:rgba(15,42,74,0.4); z-index:45; }
  @media (max-width:880px){
    :root{ --sidebar-w:230px; }
    .sidebar{ transform:translateX(-100%); }
    .sidebar.open{ transform:translateX(0); box-shadow:0 0 40px rgba(0,0,0,0.3); }
    .main{ margin-left:0; }
    .menu-toggle{ display:flex; }
    .sidebar-backdrop.show{ display:block; }
  }
  @media (max-width:560px){
    .topbar{ padding:0 16px; }
    .content{ padding:18px 16px 40px; }
    .add-form{ flex-direction:column; }
  }
</style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-mark">SC</span>
    <span>
      Sipway English Accademy
      <span class="sub">ADMIN PANEL</span>
    </span>
  </div>
  <nav class="nav-group">
    <div class="nav-label">Students</div>
    <a class="nav-item" href="admin-dashboard.html">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
      Student Booking Time
    </a>
    <a class="nav-item" href="students.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
      Students
    </a>
    <a class="nav-item" href="admin_practice_videos.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>
      Practice Videos
    </a>
 
    <a class="nav-item" href="admin_activated_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
      Student Activated Packages
    </a>
    <a class="nav-item" href="admin_student_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 2a10 10 0 0 1 10 10h-10z"/></svg>
      Student Packages
    </a>
    <a class="nav-item" href="admin_mobile_gate.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <rect x="5" y="2" width="14" height="20" rx="2"/>
    <line x1="12" y1="18" x2="12.01" y2="18"/>
  </svg>
  Mobile Gate Logs
</a>
<a class="nav-item" href="admin_languages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        <path d="M8 7h8M8 11h6"/>
      </svg>
      Languages
    </a>
  <div class="nav-label">Vocabulary</div>
       <a class="nav-item" href="admin_vocabulary_videos.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        <path d="M8 7h8M8 11h6"/>
      </svg>
      Vocabulary Videos
    </a>

<a class="nav-item" href="admin_activated_vocabulary_packages.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    <path d="M8 7h8M8 11h6"/>
  </svg>
  Vocabulary Activations
  <span class="badge-count" id="pendingVocabBadge" style="display:none;">0</span>
</a>
  <div class="nav-label">AI Videos</div>
    <a class="nav-item" href="admin_activated_ai_video_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>
      AI Video Activations
     
    </a>
    <div class="nav-label">Lecturers</div>
    <a class="nav-item" href="teachers.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      Teachers
    </a>
    <a class="nav-item" href="admin_availability_requests.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
      Lecture Time Requests
    </a>

    <div class="nav-label">Packages</div>
    <a class="nav-item" href="admin_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
      Packages
    </a>

    <a class="nav-item active" href="admin_subjects.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Subjects / Types
    </a>

    <div class="nav-label">Support</div>
    <a class="nav-item" href="admin_support_requests.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Support Requests
    </a>

    <div class="nav-label">Reports</div>
    <a class="nav-item" href="filter.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
      Filter
    </a>
        <div class="nav-label">Chat</div>
<a class="nav-item" href="admin_chat.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
  Student Chat
</a>

      <div class="nav-label"> Register Video</div>
<a class="nav-item" href="admin_register_video.php">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <rect x="2" y="4" width="20" height="14" rx="2"/>
    <path d="M10 9l5 3-5 3V9z"/>
  </svg>
  Register Video
</a>
  </nav>
  <div class="sidebar-foot">
    <button class="logout-btn" id="logoutBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Logout
    </button>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:14px;">
      <button class="menu-toggle" id="menuToggle">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title">
        <h2>Subjects / Types</h2>
        <p>Lecturer dashboard eke dropdown ekata pennana options manage karanna</p>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="greeting">
      <h1>Subject / Type List 📋</h1>
      <p>Meතන add karana options thamai lecturer-details.php eke "Subject/Type" dropdown eken pennanne.</p>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Add New Subject / Type</h3>
        <p>Example: IELTS, Spoken English, Business English, Kids English</p>
      </div>
      <div class="panel-body">
        <form class="add-form" id="addForm">
          <input type="text" id="newSubjectInput" placeholder="Subject / Type name eka type karanna..." maxlength="100">
          <button type="submit" id="addBtn">+ Add</button>
        </form>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Existing Subjects / Types</h3>
        <p>Edit / Delete karanna icon click karanna</p>
      </div>
      <div class="panel-body">
        <div class="subject-list" id="subjectList">
          <div class="empty-state">Loading...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){
  const listEl = document.getElementById('subjectList');
  const addForm = document.getElementById('addForm');
  const newInput = document.getElementById('newSubjectInput');
  const addBtn = document.getElementById('addBtn');

  function showToast(msg, type = '') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = 'toast show' + (type ? ' ' + type : '');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  async function loadSubjects() {
    listEl.innerHTML = '<div class="empty-state">Loading...</div>';
    try {
      const res = await fetch('subjects_api.php');
      const data = await res.json();
      if (!data.success) {
        listEl.innerHTML = `<div class="empty-state">Error: ${escapeHtml(data.message || 'Load failed')}</div>`;
        return;
      }
      renderList(data.data || []);
    } catch (err) {
      console.error(err);
      listEl.innerHTML = '<div class="empty-state">Server connect unuwe na. subjects_api.php check karanna.</div>';
    }
  }

  function renderList(subjects) {
    if (subjects.length === 0) {
      listEl.innerHTML = '<div class="empty-state">Subjects add karala nathi. Uda form eken add karanna.</div>';
      return;
    }
    listEl.innerHTML = subjects.map(s => `
      <div class="subject-row" data-id="${s.id}">
        <span class="s-name">${escapeHtml(s.name)}</span>
        <div class="row-actions">
          <button class="edit-btn" title="Edit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="del-btn" title="Delete">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z"/></svg>
          </button>
        </div>
      </div>
    `).join('');

    listEl.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', () => startEdit(btn.closest('.subject-row')));
    });
    listEl.querySelectorAll('.del-btn').forEach(btn => {
      btn.addEventListener('click', () => deleteSubject(btn.closest('.subject-row')));
    });
  }

  function startEdit(row) {
    const id = row.dataset.id;
    const nameSpan = row.querySelector('.s-name');
    const currentName = nameSpan.textContent;
    row.innerHTML = `
      <input type="text" class="edit-input" value="${escapeHtml(currentName)}" maxlength="100">
      <div class="row-actions">
        <button class="save-btn" title="Save">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        </button>
        <button class="cancel-btn" title="Cancel">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
    `;
    const input = row.querySelector('.edit-input');
    input.focus();
    input.select();
    row.querySelector('.save-btn').addEventListener('click', () => saveEdit(id, input.value));
    row.querySelector('.cancel-btn').addEventListener('click', loadSubjects);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') saveEdit(id, input.value);
      if (e.key === 'Escape') loadSubjects();
    });
  }

  async function saveEdit(id, name) {
    name = name.trim();
    if (!name) { showToast('Name eka empty karanna baha', 'error-toast'); return; }
    try {
      const res = await fetch('subjects_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', id, name })
      });
      const data = await res.json();
      if (data.success) {
        showToast('✅ Update unuwa', 'success-toast');
        loadSubjects();
      } else {
        showToast(data.message || 'Update failed', 'error-toast');
      }
    } catch (err) {
      console.error(err);
      showToast('Error occurred', 'error-toast');
    }
  }

  async function deleteSubject(row) {
    const id = row.dataset.id;
    const name = row.querySelector('.s-name').textContent;
    if (!confirm(`"${name}" delete karanna sure da?`)) return;
    try {
      const res = await fetch('subjects_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id })
      });
      const data = await res.json();
      if (data.success) {
        showToast('🗑️ Delete unuwa', 'success-toast');
        loadSubjects();
      } else {
        showToast(data.message || 'Delete failed', 'error-toast');
      }
    } catch (err) {
      console.error(err);
      showToast('Error occurred', 'error-toast');
    }
  }

  addForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = newInput.value.trim();
    if (!name) { showToast('Subject name eka type karanna', 'error-toast'); return; }
    addBtn.disabled = true;
    try {
      const res = await fetch('subjects_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', name })
      });
      const data = await res.json();
      if (data.success) {
        showToast('✅ Add unuwa', 'success-toast');
        newInput.value = '';
        loadSubjects();
      } else {
        showToast(data.message || 'Add failed', 'error-toast');
      }
    } catch (err) {
      console.error(err);
      showToast('Error occurred', 'error-toast');
    } finally {
      addBtn.disabled = false;
    }
  });

  document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarBackdrop').classList.toggle('show');
  });
  document.getElementById('sidebarBackdrop')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('show');
  });
document.getElementById('logoutBtn')?.addEventListener('click', () => {
  window.location.href = 'admin_logout.php';
});

  loadSubjects();
})();
</script>
</body>
</html>