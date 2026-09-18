<?php
require_once 'db.php';

// ========== APPROVE ==========
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];

    $stmt = $conn->prepare("
        SELECT aap.id, avp.duration_days
        FROM activated_ai_video_packages aap
        JOIN ai_video_packages avp ON avp.id = aap.package_id
        WHERE aap.id = ? AND aap.status = 'pending'
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $days   = (int)$row['duration_days'];
        $expiry = date('Y-m-d', strtotime("+{$days} days"));

        $upd = $conn->prepare("
            UPDATE activated_ai_video_packages
            SET status = 'active',
                payment_status = 'paid',
                activated_at = NOW(),
                expiry_date = ?
            WHERE id = ?
        ");
        $upd->bind_param('si', $expiry, $id);
        $upd->execute();
        $upd->close();
    }
    header('Location: admin_activated_ai_video_packages.php?ok=1');
    exit;
}

// ========== REJECT ==========
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $conn->query("UPDATE activated_ai_video_packages SET status = 'rejected' WHERE id = $id");
    header('Location: admin_activated_ai_video_packages.php?ok=1');
    exit;
}

// ========== DELETE ==========
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM activated_ai_video_packages WHERE id = $id");
    header('Location: admin_activated_ai_video_packages.php?ok=1');
    exit;
}

// ========== UPDATE STATUS (Edit) ==========
if (isset($_POST['update_status'])) {
    $id     = (int)$_POST['id'];
    $status = $_POST['status'] ?? 'pending';
    $allowed = ['pending', 'active', 'rejected', 'expired', 'completed'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE activated_ai_video_packages SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: admin_activated_ai_video_packages.php?ok=1');
    exit;
}

// ========== FETCH DATA ==========
$res = $conn->query("
    SELECT 
        aap.*,
        s.full_name,
        s.email,
        avp.name AS package_name,
        avp.duration_days
    FROM activated_ai_video_packages aap
    LEFT JOIN students s ON s.id = aap.student_id
    LEFT JOIN ai_video_packages avp ON avp.id = aap.package_id
    ORDER BY aap.id DESC
    LIMIT 200
");

$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}

// Group by student
$groups = [];
foreach ($rows as $r) {
    $sid = $r['student_id'] ?? 0;
    if (!isset($groups[$sid])) {
        $groups[$sid] = [
            'student_id'   => $sid,
            'student_name' => $r['full_name'] ?? 'Unknown',
            'email'        => $r['email'] ?? '',
            'records'      => []
        ];
    }
    $groups[$sid]['records'][] = $r;
}

// Stats
$total    = count($rows);
$pending  = 0;
$active   = 0;
$rejected = 0;
$expired  = 0;

foreach ($rows as $r) {
    $st = strtolower($r['status'] ?? '');
    if ($st === 'pending')  $pending++;
    elseif ($st === 'active')   $active++;
    elseif ($st === 'rejected') $rejected++;
    elseif ($st === 'expired')  $expired++;
}

function groupStatus($records) {
    $statuses = array_map(fn($a) => strtolower($a['status'] ?? 'pending'), $records);
    if (in_array('pending', $statuses)) return 'pending';
    if (in_array('active', $statuses)) return 'active';
    if (in_array('completed', $statuses)) return 'completed';
    if (in_array('rejected', $statuses)) return 'rejected';
    if (in_array('expired', $statuses)) return 'expired';
    return 'pending';
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sipway Campus - AI Video Activations</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --navy: #0f2a4a;
    --navy-2: #16385f;
    --navy-soft: #eef2f7;
    --coral: #e8825f;
    --coral-dark: #d66c47;
    --coral-soft: #fdece5;
    --bg: #f6f5f3;
    --card: #ffffff;
    --line: #e6e2da;
    --line-soft: #f0ede7;
    --muted: #6b7280;
    --muted-2: #8a93a3;
    --text: #1b2430;
    --danger: #c0392b;
    --danger-soft: #fdecea;
    --success: #1f9d55;
    --success-soft: #e8f8ee;
    --warning: #f2994a;
    --warning-soft: #fdf1e4;
    --info: #2563eb;
    --info-soft: #e6eef7;
    --purple: #7c3aed;
    --purple-soft: #f3e8ff;
    --radius-lg: 16px;
    --shadow-card: 0 10px 34px -12px rgba(15, 42, 74, 0.14), 0 2px 8px rgba(15, 42, 74, 0.05);
    --ease: cubic-bezier(.4, 0, .2, 1);
    --sidebar-w: 250px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Inter', 'Noto Sans Sinhala', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
    -webkit-font-smoothing: antialiased;
  }

  a { text-decoration: none; color: inherit; }

  /* ===== SIDEBAR ===== */
  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0;
    width: var(--sidebar-w);
    background: linear-gradient(180deg, var(--navy) 0%, #0b2039 100%);
    color: #fff;
    display: flex; flex-direction: column;
    z-index: 50;
    transition: transform .25s var(--ease);
  }
  .sidebar-brand {
    display: flex; align-items: center; gap: 10px;
    padding: 22px 22px 20px;
    font-weight: 800; font-size: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }
  .brand-mark {
    width: 34px; height: 34px; border-radius: 9px;
    background: linear-gradient(135deg, var(--coral), var(--coral-dark));
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 800;
    box-shadow: 0 6px 14px rgba(214,108,71,0.4);
  }
  .sidebar-brand .sub {
    display: block; font-size: 10.5px; font-weight: 600;
    color: rgba(255,255,255,0.55); letter-spacing: 1px; margin-top: 2px;
  }
  .nav-group { padding: 18px 12px; flex: 1; overflow-y: auto; }
  .nav-label {
    font-size: 10.5px; font-weight: 700; letter-spacing: 1.2px;
    color: rgba(255,255,255,0.35); text-transform: uppercase;
    padding: 8px 12px 6px;
  }
  .nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 10px;
    font-size: 13.5px; font-weight: 600;
    color: rgba(255,255,255,0.75);
    margin-bottom: 3px; position: relative;
    transition: background .15s, color .15s;
  }
  .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
  .nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
  .nav-item.active {
    background: rgba(232,130,95,0.16); color: #fff;
  }
  .nav-item.active::before {
    content: ""; position: absolute; left: -12px; top: 8px; bottom: 8px;
    width: 3px; border-radius: 3px; background: var(--coral);
  }
  .nav-item .badge-count {
    margin-left: auto; background: var(--coral); color: #fff;
    font-size: 10.5px; font-weight: 800; padding: 2px 7px; border-radius: 20px;
  }
  .sidebar-foot {
    padding: 16px 14px 20px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }
  .logout-btn {
    display: flex; align-items: center; gap: 10px; width: 100%;
    padding: 11px 14px; border-radius: 10px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff; font-weight: 700; font-size: 13px; cursor: pointer;
  }
  .logout-btn:hover { background: rgba(192,57,43,0.35); }
  .logout-btn svg { width: 16px; height: 16px; }

  /* ===== MAIN ===== */
  .main { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }
  .topbar {
    height: 68px; display: flex; align-items: center; justify-content: space-between;
    padding: 0 28px; background: rgba(255,255,255,0.9);
    backdrop-filter: saturate(180%) blur(10px);
    border-bottom: 1px solid var(--line-soft);
    position: sticky; top: 0; z-index: 30;
  }
  .menu-toggle { display: none; background: none; border: none; cursor: pointer; color: var(--navy); padding: 6px; }
  .topbar-title h2 { margin: 0; font-size: 18px; font-weight: 800; color: var(--navy); }
  .topbar-title p { margin: 2px 0 0; font-size: 12.5px; color: var(--muted); font-weight: 500; }
  .admin-chip {
    display: flex; align-items: center; gap: 10px;
    padding: 6px 14px 6px 6px; border-radius: 999px; background: var(--navy-soft);
  }
  .admin-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, var(--navy), var(--navy-2));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 12.5px;
  }
  .admin-chip .name { font-size: 13px; font-weight: 700; color: var(--navy); }
  .admin-chip .role { font-size: 10.5px; color: var(--muted-2); font-weight: 600; }

  .content { padding: 26px 28px 60px; flex: 1; }
  .greeting { margin-bottom: 22px; }
  .greeting h1 { font-size: clamp(20px, 2.4vw, 26px); font-weight: 800; color: var(--navy); margin: 0 0 4px; }
  .greeting p { margin: 0; color: var(--muted); font-size: 14px; }

  /* ===== STATS ===== */
  .stats-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 26px;
  }
  .stat-card {
    background: var(--card); border: 1px solid var(--line-soft);
    border-radius: var(--radius-lg); padding: 20px 22px;
    box-shadow: var(--shadow-card);
    display: flex; flex-direction: column; gap: 12px;
    transition: transform .15s, box-shadow .15s;
  }
  .stat-card:hover { transform: translateY(-2px); box-shadow: 0 16px 40px -14px rgba(15,42,74,0.2); }
  .stat-icon {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
  }
  .stat-icon svg { width: 20px; height: 20px; }
  .stat-icon.green { background: var(--success-soft); color: var(--success); }
  .stat-icon.orange { background: var(--warning-soft); color: var(--warning); }
  .stat-icon.coral { background: var(--coral-soft); color: var(--coral-dark); }
  .stat-icon.blue { background: var(--navy-soft); color: var(--navy-2); }
  .stat-value { font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
  .stat-label { font-size: 12.5px; color: var(--muted); font-weight: 600; }

  /* ===== PANEL ===== */
  .panel {
    background: var(--card); border: 1px solid var(--line-soft);
    border-radius: var(--radius-lg); box-shadow: var(--shadow-card); overflow: hidden;
  }
  .panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 22px; border-bottom: 1px solid var(--line-soft); gap: 10px; flex-wrap: wrap;
  }
  .panel-head h3 { margin: 0; font-size: 15.5px; font-weight: 800; color: var(--navy); }
  .panel-head p { margin: 2px 0 0; font-size: 12px; color: var(--muted); }

  .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
  .pill {
    padding: 6px 13px; border-radius: 999px; font-size: 11.5px; font-weight: 700;
    border: 1px solid var(--line); background: #fff; color: var(--muted); cursor: pointer;
  }
  .pill.active { background: var(--navy); border-color: var(--navy); color: #fff; }

  .search-box-wrap { position: relative; display: flex; align-items: center; }
  .search-box-wrap svg { position: absolute; left: 12px; width: 15px; height: 15px; color: var(--muted-2); }
  .search-box {
    padding: 9px 14px 9px 34px; border: 1px solid var(--line); border-radius: 8px;
    font-size: 13px; background: var(--bg); min-width: 220px; font-family: inherit;
  }
  .search-box:focus { outline: none; border-color: var(--coral); background: #fff; }

  /* ===== TABLE (GROUPED STYLE) ===== */
  .table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; min-width: 900px; }
  thead th {
    text-align: left; font-size: 11px; font-weight: 700; color: var(--muted-2);
    text-transform: uppercase; letter-spacing: 0.6px;
    padding: 12px 22px; background: #fbfaf9; border-bottom: 1px solid var(--line-soft);
  }
  tbody td {
    padding: 14px 22px; font-size: 13.5px; border-bottom: 1px solid var(--line-soft);
    vertical-align: middle;
  }

  .student-row {
    cursor: pointer;
    transition: background .12s var(--ease);
  }
  .student-row:hover { background: #fbfaf9; }
  .student-row.expanded { background: var(--navy-soft); }
  .student-row td { padding: 12px 22px; }

  .student-row-inner {
    display: flex; align-items: center; gap: 10px;
  }
  .expand-chevron {
    width: 20px; height: 20px; flex-shrink: 0;
    color: var(--muted-2); transition: transform .2s var(--ease);
    display: flex; align-items: center; justify-content: center;
  }
  .student-row.expanded .expand-chevron {
    transform: rotate(90deg); color: var(--coral-dark);
  }

  .person-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11.5px; font-weight: 800; color: #fff;
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 100%);
    flex-shrink: 0;
  }
  .person-info { display: flex; flex-direction: column; }
  .person-name {
    font-weight: 700; font-size: 13.5px;
    display: flex; align-items: center; gap: 7px; flex-wrap: wrap;
  }
  .person-sub { font-size: 11.5px; color: var(--muted-2); font-weight: 500; }

  .group-status-tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 999px;
    font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.4px;
  }
  .group-status-tag::before {
    content: ""; width: 5px; height: 5px; border-radius: 50%; background: currentColor;
  }
  .group-status-tag.status-pending { background: var(--warning-soft); color: var(--warning); }
  .group-status-tag.status-active { background: var(--success-soft); color: var(--success); }
  .group-status-tag.status-completed { background: var(--info-soft); color: var(--info); }
  .group-status-tag.status-cancelled,
  .group-status-tag.status-rejected { background: var(--danger-soft); color: var(--danger); }
  .group-status-tag.status-expired { background: var(--navy-soft); color: var(--navy-2); }

  .detail-row td { padding: 0; border-bottom: 1px solid var(--line-soft); }
  .detail-wrap {
    background: #fbfaf9;
    padding: 6px 22px 18px 58px;
  }

  .nested-table { width: 100%; border-collapse: collapse; min-width: 0; }
  .nested-table thead th {
    background: transparent; padding: 8px 14px; font-size: 10.5px;
    border-bottom: 1px solid var(--line);
  }
  .nested-table tbody td {
    padding: 12px 14px; font-size: 13px;
    border-bottom: 1px solid var(--line-soft);
    background: #fff; vertical-align: middle;
  }
  .nested-table tbody tr:last-child td { border-bottom: none; }
  .nested-table tbody tr.row-pending td { background: #fffdf8; }
  .nested-table tbody tr:first-child td:first-child { border-top-left-radius: 10px; }
  .nested-table tbody tr:first-child td:last-child { border-top-right-radius: 10px; }
  .nested-table tbody tr:last-child td:first-child { border-bottom-left-radius: 10px; }
  .nested-table tbody tr:last-child td:last-child { border-bottom-right-radius: 10px; }

  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px; font-size: 11px; font-weight: 800;
  }
  .status-badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
  .status-badge.active { background: var(--success-soft); color: var(--success); }
  .status-badge.pending { background: var(--warning-soft); color: var(--warning); }
  .status-badge.rejected { background: var(--danger-soft); color: var(--danger); }
  .status-badge.expired { background: var(--navy-soft); color: var(--navy-2); }
  .status-badge.completed { background: var(--info-soft); color: var(--info); }

  /* Payment badges (same as Activated Packages) */
  .pay-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px; font-size: 11px; font-weight: 800;
  }
  .pay-online {
    background: var(--info-soft); color: var(--info);
  }
  .pay-bank {
    background: var(--purple-soft); color: var(--purple);
  }

  /* Action buttons (same style as screenshot) */
  .action-cell {
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
  }
  .icon-btn {
    width: 30px; height: 30px; border-radius: 8px; border: none;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .15s var(--ease); flex-shrink: 0;
    text-decoration: none;
  }
  .icon-btn svg { width: 15px; height: 15px; }
  .icon-btn:active { transform: scale(.92); }
  .btn-edit {
    background: var(--navy-soft); color: var(--navy);
  }
  .btn-edit:hover { background: #dfe7f0; }
  .btn-delete {
    background: var(--danger-soft); color: var(--danger);
  }
  .btn-delete:hover { background: #fadbd8; }
  .btn-approve {
    background: var(--success-soft); color: var(--success);
  }
  .btn-approve:hover { background: #d4f2df; }
  .btn-reject {
    background: var(--danger-soft); color: var(--danger);
  }
  .btn-reject:hover { background: #fadbd8; }
  .btn-receipt {
    background: var(--purple-soft); color: var(--purple);
  }
  .btn-receipt:hover { background: #e9d5ff; }

  .slip-link { color: var(--coral-dark); font-weight: 700; font-size: 12.5px; }
  .slip-link:hover { text-decoration: underline; }

  .empty-state { padding: 50px 20px; text-align: center; color: var(--muted-2); font-size: 14px; }

  .toast {
    position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-16px);
    background: var(--navy); color: #fff; padding: 13px 22px; border-radius: 10px;
    font-size: 13.5px; font-weight: 600; opacity: 0; pointer-events: none;
    transition: opacity .25s, transform .25s; z-index: 100;
    box-shadow: 0 12px 30px rgba(15,42,74,0.3);
  }
  .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
  .toast.success-toast { background: var(--success); }

  /* Modal */
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15, 42, 74, 0.45); z-index: 200;
    align-items: center; justify-content: center; padding: 20px;
  }
  .modal-overlay.show { display: flex; }
  .modal-box {
    background: #fff; border-radius: var(--radius-lg);
    width: 100%; max-width: 420px;
    box-shadow: 0 30px 70px -20px rgba(15, 42, 74, 0.4);
    overflow: hidden;
  }
  .modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px; border-bottom: 1px solid var(--line-soft);
  }
  .modal-head h3 { margin: 0; font-size: 16px; font-weight: 800; color: var(--navy); }
  .modal-close {
    background: var(--navy-soft); border: none; width: 30px; height: 30px;
    border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;
    color: var(--navy); font-size: 16px;
  }
  .modal-body { padding: 20px 22px; }
  .form-group { margin-bottom: 14px; }
  .form-group label {
    display: block; font-size: 12.5px; font-weight: 700; color: var(--navy); margin-bottom: 6px;
  }
  .form-group select {
    width: 100%; padding: 11px 13px; border: 1px solid var(--line);
    border-radius: 9px; font-size: 13.5px; background: var(--bg);
  }
  .info-line {
    font-size: 13px; color: var(--muted); margin: 0 0 14px;
    background: var(--navy-soft); padding: 10px 13px; border-radius: 9px;
  }
  .modal-foot {
    display: flex; gap: 10px; padding: 16px 22px 20px;
  }
  .btn-cancel, .btn-save {
    flex: 1; padding: 12px; border-radius: 9px; font-weight: 700;
    font-size: 13.5px; cursor: pointer; border: none;
  }
  .btn-cancel { background: var(--navy-soft); color: var(--navy); }
  .btn-save {
    background: linear-gradient(135deg, var(--coral) 0%, var(--coral-dark) 100%);
    color: #fff;
  }

  .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(15,42,74,0.4); z-index: 45; }

  @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 880px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); box-shadow: 0 0 40px rgba(0,0,0,0.3); }
    .main { margin-left: 0; }
    .menu-toggle { display: flex; }
    .sidebar-backdrop.show { display: block; }
  }
  @media (max-width: 560px) {
    .stats-grid { grid-template-columns: 1fr; }
    .topbar { padding: 0 16px; }
    .content { padding: 18px 16px 40px; }
    .admin-chip .name, .admin-chip .role { display: none; }
    .detail-wrap { padding-left: 22px; }
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
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
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
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8M8 11h6"/></svg>
      Vocabulary Videos
    </a>
    <a class="nav-item" href="admin_activated_vocabulary_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8M8 11h6"/></svg>
      Vocabulary Activations
    </a>

    <div class="nav-label">AI Videos</div>
    <a class="nav-item active" href="admin_activated_ai_video_packages.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>
      AI Video Activations
      <?php if ($pending > 0): ?>
        <span class="badge-count"><?= $pending ?></span>
      <?php endif; ?>
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
    <a class="nav-item" href="admin_subjects.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      Subjects / Types
    </a>

    <div class="nav-label">Support</div>
    <a class="nav-item" href="admin_support_requests.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Support Requests
    </a>

    <div class="nav-label">Chat</div>
    <a class="nav-item" href="admin_chat.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Student Chat
    </a>
  </nav>
  <div class="sidebar-foot">
    <button class="logout-btn" onclick="location.href='admin_logout.php'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Logout
    </button>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button class="menu-toggle" id="menuToggle">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
      <div class="topbar-title">
        <h2>AI Video Activations</h2>
        <p id="todayDate"></p>
      </div>
    </div>
    <div class="admin-chip">
      <span class="admin-avatar">A</span>
      <div>
        <div class="name">Admin</div>
        <div class="role">Administrator</div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="greeting">
      <h1>AI Video Package Activations</h1>
      <p>Students ලා AI Video packages activate කරන requests manage කරන්න. Student එකකට click කරලා packages බලන්න.</p>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <div class="stat-value"><?= $active ?></div>
        <div class="stat-label">Active</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </div>
        <div class="stat-value"><?= $pending ?></div>
        <div class="stat-label">Pending Approval</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon coral">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </div>
        <div class="stat-value"><?= $rejected ?></div>
        <div class="stat-label">Rejected</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
        </div>
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-label">Total Requests</div>
      </div>
    </div>

    <!-- TABLE PANEL -->
    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>All AI Video Activations</h3>
          <p>Student එකක් click කරලා packages බලන්න. Pending ඒවා Approve / Reject කරන්න.</p>
        </div>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <div class="filter-pills" id="filterPills">
            <span class="pill active" data-filter="all">All</span>
            <span class="pill" data-filter="pending">Pending</span>
            <span class="pill" data-filter="active">Active</span>
            <span class="pill" data-filter="rejected">Rejected</span>
            <span class="pill" data-filter="expired">Expired</span>
          </div>
          <div class="search-box-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" class="search-box" id="searchBox" placeholder="Search student, package, ref...">
          </div>
        </div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php if (empty($groups)): ?>
              <tr><td colspan="1"><div class="empty-state">Records නැහැ.</div></td></tr>
            <?php else: ?>
              <?php foreach ($groups as $g):
                $overallStatus = groupStatus($g['records']);
                $pkgCount = count($g['records']);
                $initials = mb_substr($g['student_name'], 0, 1);
                $searchText = strtolower(
                  ($g['student_name'] ?? '') . ' ' .
                  ($g['email'] ?? '') . ' ' .
                  implode(' ', array_map(fn($r) => ($r['package_name'] ?? '') . ' ' . ($r['reference_no'] ?? '') . ' ' . ($r['id'] ?? ''), $g['records']))
                );
              ?>
              <tr class="student-row"
                  data-student-id="<?= (int)$g['student_id'] ?>"
                  data-status="<?= htmlspecialchars($overallStatus) ?>"
                  data-search="<?= htmlspecialchars($searchText) ?>">
                <td colspan="1">
                  <div class="student-row-inner">
                    <span class="expand-chevron">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg>
                    </span>
                    <span class="person-avatar"><?= htmlspecialchars(strtoupper($initials)) ?></span>
                    <div class="person-info">
                      <div class="person-name">
                        <span class="group-status-tag status-<?= $overallStatus ?>"><?= $overallStatus ?></span>
                        <?= htmlspecialchars($g['student_name']) ?>
                      </div>
                      <div class="person-sub">
                        ID: <?= (int)$g['student_id'] ?> &middot; <?= $pkgCount ?> package<?= $pkgCount === 1 ? '' : 's' ?>
                        <?php if ($g['email']): ?> &middot; <?= htmlspecialchars($g['email']) ?><?php endif; ?>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <tr class="detail-row" data-student-id="<?= (int)$g['student_id'] ?>" style="display:none;">
                <td colspan="1">
                  <div class="detail-wrap">
                    <table class="nested-table">
                      <thead>
                        <tr>
                          <th>Package</th>
                          <th>Price</th>
                          <th>Duration</th>
                          <th>Payment</th>
                          <th>Status</th>
                          <th>Date</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($g['records'] as $r):
                          $st = strtolower($r['status'] ?? 'pending');
                          $payMethod = strtolower($r['payment_method'] ?? 'online');
                          $isBank = in_array($payMethod, ['bank_transfer', 'bank', 'bank receipt', 'slip']);
                          $isPending = $st === 'pending';
                          $dateFormatted = !empty($r['activated_at']) ? date('d/m/Y', strtotime($r['activated_at'])) : 
                                           (!empty($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : '—');
                        ?>
                        <tr class="<?= $isPending ? 'row-pending' : '' ?>" data-status="<?= htmlspecialchars($st) ?>">
                          <td>
                            <div class="person-name"><?= htmlspecialchars($r['package_name'] ?? 'Unknown') ?></div>
                            <?php if (!empty($r['reference_no']) || !empty($r['order_id'])): ?>
                              <div class="person-sub">Ref: <?= htmlspecialchars($r['reference_no'] ?? $r['order_id'] ?? '') ?></div>
                            <?php endif; ?>
                          </td>
                          <td>Rs. <?= number_format((float)($r['amount'] ?? 0), 0) ?></td>
                          <td><?= !empty($r['duration_days']) ? (int)$r['duration_days'] . ' days' : '—' ?></td>
                          <td>
                            <?php if ($isBank): ?>
                              <span class="pay-badge pay-bank">🏦 Bank Receipt</span>
                            <?php else: ?>
                              <span class="pay-badge pay-online">💳 Online</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="status-badge <?= $st ?>">
                              <?= htmlspecialchars(ucfirst($r['status'] ?? 'pending')) ?>
                            </span>
                          </td>
                          <td><?= $dateFormatted ?></td>
                          <td>
                            <div class="action-cell">
                              <?php if ($isBank && (!empty($r['slip_path']) || !empty($r['receipt_path']))): ?>
                                <a class="icon-btn btn-receipt" href="<?= htmlspecialchars($r['slip_path'] ?? $r['receipt_path']) ?>" target="_blank" title="View Receipt">
                                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                                </a>
                              <?php endif; ?>

                              <?php if ($isPending): ?>
                                <a class="icon-btn btn-approve" href="?approve=<?= (int)$r['id'] ?>"
                                   onclick="return confirm('මෙම request එක Approve කරන්නද?')" title="Approve">
                                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                </a>
                                <a class="icon-btn btn-reject" href="?reject=<?= (int)$r['id'] ?>"
                                   onclick="return confirm('මෙම request එක Reject කරන්නද?')" title="Reject">
                                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </a>
                              <?php endif; ?>

                              <!-- Edit button -->
                              <button class="icon-btn btn-edit" title="Edit"
                                      onclick="openEditModal(<?= (int)$r['id'] ?>, '<?= htmlspecialchars($r['package_name'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($g['student_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($st) ?>')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                              </button>

                              <!-- Delete button -->
                              <a class="icon-btn btn-delete" href="?delete=<?= (int)$r['id'] ?>"
                                 onclick="return confirm('මෙම activation එක Delete කරන්නද? Undo කරන්න බෑ.')" title="Delete">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z"/></svg>
                              </a>
                            </div>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Edit Status Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Edit Activation</h3>
      <button class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <p class="info-line" id="editInfoLine">Student — Package</p>
        <input type="hidden" name="id" id="editId">
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="editStatus">
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
            <option value="expired">Expired</option>
          </select>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
        <button type="submit" name="update_status" class="btn-save">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
  // Date
  document.getElementById('todayDate').textContent = new Date().toLocaleDateString('en-GB', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });

  // Toast
  <?php if (isset($_GET['ok'])): ?>
    const t = document.getElementById('toast');
    t.textContent = 'Updated successfully!';
    t.className = 'toast show success-toast';
    setTimeout(() => t.classList.remove('show'), 2500);
  <?php endif; ?>

  // Mobile sidebar
  document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarBackdrop').classList.toggle('show');
  });
  document.getElementById('sidebarBackdrop')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('show');
  });

  // Expand / Collapse
  document.querySelectorAll('.student-row').forEach(row => {
    row.addEventListener('click', (e) => {
      // Don't expand when clicking action buttons
      if (e.target.closest('.icon-btn') || e.target.closest('a') || e.target.closest('button')) return;

      const sid = row.dataset.studentId;
      const detailRow = document.querySelector(`.detail-row[data-student-id="${sid}"]`);
      if (!detailRow) return;
      const isOpen = detailRow.style.display !== 'none';
      detailRow.style.display = isOpen ? 'none' : 'table-row';
      row.classList.toggle('expanded', !isOpen);
    });
  });

  // Filter + Search
  let currentFilter = 'all';
  const pills = document.querySelectorAll('.pill');
  const searchBox = document.getElementById('searchBox');
  const studentRows = document.querySelectorAll('.student-row');

  function applyFilter() {
    const q = searchBox.value.trim().toLowerCase();
    studentRows.forEach(row => {
      const status = row.dataset.status;
      const searchText = row.dataset.search || '';
      const matchFilter = currentFilter === 'all' || status === currentFilter;
      const matchSearch = !q || searchText.includes(q);
      const show = matchFilter && matchSearch;
      row.style.display = show ? '' : 'none';

      const sid = row.dataset.studentId;
      const detail = document.querySelector(`.detail-row[data-student-id="${sid}"]`);
      if (detail && !show) {
        detail.style.display = 'none';
        row.classList.remove('expanded');
      }
    });
  }

  pills.forEach(p => {
    p.addEventListener('click', () => {
      pills.forEach(x => x.classList.remove('active'));
      p.classList.add('active');
      currentFilter = p.dataset.filter;
      applyFilter();
    });
  });

  searchBox.addEventListener('input', applyFilter);

  // Edit Modal
  function openEditModal(id, packageName, studentName, currentStatus) {
    document.getElementById('editId').value = id;
    document.getElementById('editInfoLine').innerHTML = `<b>${studentName}</b> — ${packageName}`;
    document.getElementById('editStatus').value = currentStatus || 'pending';
    document.getElementById('editModal').classList.add('show');
  }
  function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
  }
  document.getElementById('editModal').addEventListener('click', (e) => {
    if (e.target.id === 'editModal') closeEditModal();
  });
</script>
</body>
</html>