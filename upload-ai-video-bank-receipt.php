<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    die("Database connection failed.");
}

if (!isset($_SESSION['student_id'])) {
    header('Location: packages.php?error=login_required');
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$packageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$error     = '';
$success   = false;

if ($packageId <= 0) {
    header('Location: practice-ai-video.php?error=invalid_package');
    exit;
}

// Validate package
$stmt = $conn->prepare("SELECT id, name, price FROM ai_video_packages WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->bind_param('i', $packageId);
$stmt->execute();
$pkg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pkg) {
    header('Location: practice-ai-video.php?error=package_not_found');
    exit;
}

$price = (float)$pkg['price'];

// Already has a pending receipt for this package?
$stateStmt = $conn->prepare("SELECT id FROM activated_ai_video_packages WHERE student_id = ? AND package_id = ? AND status = 'pending' LIMIT 1");
$stateStmt->bind_param('ii', $studentId, $packageId);
$stateStmt->execute();
$existing = $stateStmt->get_result()->fetch_assoc();
$stateStmt->close();

if ($existing) {
    header('Location: practice-ai-video.php?error=already_pending');
    exit;
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refNo = trim($_POST['reference_no'] ?? '');

    if ($refNo === '') {
        $error = 'Transaction / Reference number අනිවාර්යයි.';
    } elseif (mb_strlen($refNo) > 100) {
        $error = 'Reference number එක දිග වැඩියි.';
    } elseif (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Bank receipt file එකක් upload කරන්න.';
    } else {
        $file    = $_FILES['receipt'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        $finfo      = finfo_open(FILEINFO_MIME_TYPE);
        $actualType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($actualType, $allowed, true)) {
            $error = 'අනුමත file types: JPG, PNG, WEBP, PDF පමණි.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File size 5MB ට වඩා අඩු විය යුතුයි.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                $error = 'අනුමත extensions: jpg, jpeg, png, webp, pdf';
            } else {
                $uploadDir = __DIR__ . '/uploads/bank_receipts/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $safeName = 'aiv_' . $studentId . '_' . $packageId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $safeName;
                $dbPath   = 'uploads/bank_receipts/' . $safeName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    // Amount comes from the DB price, not from the user
                    $ins = $conn->prepare("
                        INSERT INTO activated_ai_video_packages
                        (student_id, package_id, status, amount, payment_method, reference_no, receipt_path, created_at)
                        VALUES (?, ?, 'pending', ?, 'bank', ?, ?, NOW())
                    ");
                    $ins->bind_param('iidss', $studentId, $packageId, $price, $refNo, $dbPath);

                    if ($ins->execute()) {
                        $success = true;
                    } else {
                        @unlink($destPath);
                        $error = 'Database error: ' . $conn->error;
                    }
                    $ins->close();
                } else {
                    $error = 'File upload අසාර්ථක විය. කරුණාකර නැවත උත්සාහ කරන්න.';
                }
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Bank Receipt Upload - AI Video Package | Sipway Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Sinhala:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg-1: #ede9fe;
  --bg-2: #fdf4ff;
  --card: #ffffff;
  --text: #1e1b4b;
  --muted: #6b7280;
  --line: #e9e5f5;
  --purple: #7c3aed;
  --purple-dark: #6d28d9;
  --purple-soft: #f3e8ff;
  --pink: #ec4899;
  --success: #059669;
  --success-soft: #d1fae5;
  --amber: #92400e;
  --amber-soft: #fffbeb;
  --amber-line: #fde68a;
  --danger: #b91c1c;
  --danger-soft: #fef2f2;
  --danger-line: #fecaca;
  --radius: 16px;
  --radius-sm: 10px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
html { -webkit-text-size-adjust: 100%; }
body {
  font-family: 'Inter', 'Noto Sans Sinhala', sans-serif;
  background:
    radial-gradient(1200px 600px at -10% -10%, rgba(236,72,153,0.10), transparent 60%),
    radial-gradient(1200px 600px at 110% 10%, rgba(124,58,237,0.14), transparent 55%),
    linear-gradient(160deg, var(--bg-1) 0%, var(--bg-2) 55%, #f3ecff 100%);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}

/* Top brand bar */
.topbar { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 18px 20px; }
.topbar .logo {
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(135deg, var(--purple), var(--pink));
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 800; font-size: 15px;
  box-shadow: 0 6px 16px -4px rgba(124,58,237,0.5); flex-shrink: 0;
}
.topbar span { font-weight: 800; font-size: 15px; letter-spacing: -0.2px; color: var(--text); }

/* Page shell */
.page { width: 100%; min-height: calc(100vh - 70px); display: flex; align-items: stretch; justify-content: center; padding: 0 24px 40px; }
.panel {
  width: 100%; max-width: 1160px;
  display: grid; grid-template-columns: 1fr 1.15fr; gap: 0;
  background: var(--card); border-radius: 24px;
  box-shadow: 0 30px 70px -25px rgba(124,58,237,0.30), 0 4px 12px -4px rgba(30,27,75,0.06);
  border: 1px solid var(--line); overflow: hidden; position: relative;
}
.panel::before {
  content: ""; position: absolute; top: 0; left: 0; right: 0; height: 5px;
  background: linear-gradient(90deg, var(--purple), var(--pink)); z-index: 2;
}

/* Left info side */
.side-left {
  background: linear-gradient(160deg, #f5f1ff 0%, #fdf4ff 100%);
  padding: 44px 40px; border-right: 1px solid var(--line);
  display: flex; flex-direction: column;
}
.back {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 13px; font-weight: 600; color: var(--muted);
  text-decoration: none; margin-bottom: 22px; transition: color .15s, transform .15s;
}
.back svg { width: 14px; height: 14px; flex-shrink: 0; }
.back:hover { color: var(--purple); transform: translateX(-2px); }
h1 { font-size: 26px; font-weight: 800; letter-spacing: -0.3px; margin-bottom: 10px; line-height: 1.25; }
.sub { font-size: 14px; color: var(--muted); line-height: 1.65; margin-bottom: 26px; }
.pkg-box {
  background: linear-gradient(135deg, var(--purple-soft), #fdf2ff);
  border: 1px solid #ecd9ff; border-radius: var(--radius);
  padding: 18px 20px; margin-bottom: 20px;
  display: flex; align-items: flex-start; gap: 14px; flex-wrap: wrap;
}
.pkg-box .pkg-icon {
  width: 42px; height: 42px; flex-shrink: 0; border-radius: 12px; background: #fff;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 10px -2px rgba(124,58,237,0.25);
}
.pkg-box .pkg-icon svg { width: 21px; height: 21px; color: var(--purple); }
.pkg-box .pkg-text { min-width: 0; flex: 1; }
.pkg-box strong { display: block; font-size: 16px; margin-bottom: 4px; word-break: break-word; }
.pkg-box .meta { font-size: 13px; color: #6b21a8; font-weight: 600; display: block; line-height: 1.7; word-break: break-word; }
.bank-info {
  background: var(--amber-soft); border: 1px solid var(--amber-line);
  border-radius: var(--radius-sm); padding: 16px 18px;
  font-size: 13.5px; line-height: 1.7; margin-top: auto;
}
.bank-info .title-row { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; color: var(--amber); font-weight: 700; }
.bank-info .title-row svg { width: 14px; height: 14px; flex-shrink: 0; }
.bank-info .row { display: flex; justify-content: space-between; gap: 10px; color: #78350f; flex-wrap: wrap; }
.bank-info .row + .row { margin-top: 4px; }
.bank-info .row span { flex-shrink: 0; }
.bank-info .row b { color: #451a03; font-weight: 700; text-align: right; word-break: break-word; }

/* Right form side */
.side-right { padding: 44px 44px 40px; display: flex; flex-direction: column; justify-content: center; }
.form-title { font-size: 15px; font-weight: 800; color: var(--text); margin-bottom: 20px; }
.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 7px; color: #3730a3; }
.field input[type="text"], .field input[type="number"] {
  width: 100%; padding: 13px 16px; border: 1.5px solid var(--line);
  border-radius: var(--radius-sm); font-size: 14px; font-family: inherit;
  outline: none; background: #faf8ff; transition: border-color .15s, box-shadow .15s, background .15s;
}
.field input:focus { border-color: #a855f7; box-shadow: 0 0 0 4px rgba(168,85,247,0.14); background: #fff; }
.field input[readonly] { background: #f3f0fb; color: var(--muted); cursor: not-allowed; }
.file-drop {
  position: relative; border: 1.5px dashed #c9b8f0; border-radius: var(--radius-sm);
  background: #faf8ff; padding: 26px 16px; text-align: center; cursor: pointer;
  transition: border-color .15s, background .15s;
}
.file-drop:hover, .file-drop.drag { border-color: var(--purple); background: var(--purple-soft); }
.file-drop input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.file-drop .drop-icon {
  width: 40px; height: 40px; margin: 0 auto 12px; border-radius: 10px; background: #fff;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 10px -3px rgba(124,58,237,0.3);
}
.file-drop .drop-icon svg { width: 19px; height: 19px; color: var(--purple); }
.file-drop .drop-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.file-drop .drop-sub { font-size: 12.5px; color: var(--muted); }
.file-drop .file-chosen {
  display: none; align-items: center; justify-content: center; gap: 8px;
  font-size: 13.5px; font-weight: 700; color: var(--purple-dark); padding: 0 8px; word-break: break-all;
}
.file-drop .file-chosen svg { width: 16px; height: 16px; flex-shrink: 0; }
.file-drop.has-file .drop-icon, .file-drop.has-file .drop-title, .file-drop.has-file .drop-sub { display: none; }
.file-drop.has-file .file-chosen { display: inline-flex; }
.file-drop.has-file { border-style: solid; border-color: #86efac; background: #f0fdf4; }
.error {
  display: flex; align-items: flex-start; gap: 9px;
  background: var(--danger-soft); color: var(--danger); border: 1px solid var(--danger-line);
  border-radius: var(--radius-sm); padding: 12px 14px;
  font-size: 13.5px; font-weight: 600; margin-bottom: 20px; line-height: 1.5;
}
.error svg { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; }
.btn {
  width: 100%; padding: 15px; border: none; border-radius: 12px;
  background: linear-gradient(135deg, #a855f7, #ec4899); color: #fff;
  font-weight: 700; font-size: 15px; cursor: pointer; font-family: inherit;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: 0 10px 24px -8px rgba(168,85,247,0.55);
  transition: filter .2s, transform .15s, box-shadow .2s; text-decoration: none; margin-top: 6px;
}
.btn svg { width: 16px; height: 16px; flex-shrink: 0; }
.btn:hover { filter: brightness(1.06); transform: translateY(-1px); box-shadow: 0 14px 28px -8px rgba(168,85,247,0.6); }
.btn:active { transform: translateY(0); }
.btn:disabled { opacity: .7; cursor: not-allowed; transform: none; }

/* Success state */
.success-wrap { grid-column: 1 / -1; display: flex; align-items: center; justify-content: center; padding: 60px 24px; }
.success-box { text-align: center; max-width: 420px; }
.success-box .icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: var(--success-soft); color: var(--success);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px; box-shadow: 0 0 0 8px rgba(16,185,129,0.08);
}
.success-box .icon svg { width: 32px; height: 32px; }
.success-box h2 { font-size: 21px; margin-bottom: 12px; font-weight: 800; }
.success-box p { font-size: 14.5px; color: var(--muted); line-height: 1.7; margin-bottom: 26px; }

/* Tablet */
@media (max-width: 900px) {
  .panel { grid-template-columns: 1fr; }
  .side-left { border-right: none; border-bottom: 1px solid var(--line); padding: 36px 34px; }
  .side-right { padding: 36px 34px 34px; justify-content: flex-start; }
  .bank-info { margin-top: 0; }
}

/* Mobile */
@media (max-width: 640px) {
  .topbar { padding: 16px; }
  .page { padding: 0 12px 24px; }
  .panel { border-radius: 18px; }
  .side-left { padding: 24px 18px; }
  .side-right { padding: 24px 18px 26px; }
  h1 { font-size: 19px; }
  .sub { font-size: 12.5px; margin-bottom: 20px; }
  .pkg-box { padding: 13px 15px; gap: 10px; }
  .pkg-box .pkg-icon { width: 34px; height: 34px; }
  .pkg-box .pkg-icon svg { width: 17px; height: 17px; }
  .pkg-box strong { font-size: 14px; }
  .pkg-box .meta { font-size: 11.5px; }
  .bank-info { padding: 13px 15px; font-size: 12px; }
  .bank-info .row { font-size: 12px; }
  .field input[type="text"], .field input[type="number"] { padding: 11px 12px; font-size: 13.5px; }
  .file-drop { padding: 18px 10px; }
  .btn { font-size: 13.5px; padding: 13px; }
  .success-box h2 { font-size: 18px; }
  .success-box p { font-size: 13px; }
}
@media (max-width: 380px) {
  .bank-info .row { flex-direction: column; gap: 2px; }
  .bank-info .row b { text-align: left; }
}
</style>
</head>
<body>

  <div class="topbar">
    <div class="logo">S</div>
    <span>Sipway Campus</span>
  </div>

  <div class="page">
    <div class="panel">
      <?php if ($success): ?>
        <div class="success-wrap">
          <div class="success-box">
            <div class="icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h2>Receipt Uploaded!</h2>
            <p>ඔබගේ bank receipt එක Admin වෙත යවා ඇත. Admin අනුමත කළ පසු AI Video package එක active වේ.</p>
            <a href="practice-ai-video.php" class="btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
              AI Video Practice වෙත යන්න
            </a>
          </div>
        </div>
      <?php else: ?>

        <div class="side-left">
          <a href="ai-video-checkout.php?package_id=<?php echo $packageId; ?>" class="back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
            Back to Checkout
          </a>

          <h1>Bank Transfer Receipt</h1>
          <p class="sub">ගෙවීම bank transfer මගින් කර receipt එක upload කරන්න. Admin අනුමත කළ පසු AI Video package එක active වේ.</p>

          <div class="pkg-box">
            <div class="pkg-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
            </div>
            <div class="pkg-text">
              <strong><?php echo htmlspecialchars($pkg['name']); ?></strong>
              <span class="meta">
                Rs. <?php echo number_format($price, 2); ?> · AI VIDEO
              </span>
            </div>
          </div>

          <div class="bank-info">
            <div class="title-row">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="18" height="9" rx="1"></rect><path d="M3 10h18L12 3z"></path><line x1="7" y1="14" x2="7" y2="16"></line><line x1="12" y1="14" x2="12" y2="16"></line><line x1="17" y1="14" x2="17" y2="16"></line></svg>
              ගෙවිය යුතු Bank Account
            </div>
            <div class="row"><span>Bank</span><b>Sampath Bank</b></div>
            <div class="row"><span>Account Name</span><b>Sipway campus pvt ltd</b></div>
            <div class="row"><span>Account No</span><b>100614027332</b></div>
            <div class="row"><span>Branch</span><b>Kurunegala</b></div>
          </div>
        </div>

        <div class="side-right">
          <div class="form-title">Payment Details</div>

          <?php if ($error): ?>
            <div class="error">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <span><?php echo htmlspecialchars($error); ?></span>
            </div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" id="receiptForm">
            <div class="field">
              <label for="amount">Amount to Pay (Rs.)</label>
              <input type="number" id="amount" value="<?php echo htmlspecialchars((string)$price); ?>" readonly>
            </div>

            <div class="field">
              <label for="reference_no">Transaction / Reference Number *</label>
              <input type="text" id="reference_no" name="reference_no" required maxlength="100"
                     value="<?php echo htmlspecialchars($_POST['reference_no'] ?? ''); ?>"
                     placeholder="e.g. TXN123456789">
            </div>

            <div class="field">
              <label for="receipt">Bank Receipt (JPG, PNG, PDF) *</label>
              <div class="file-drop" id="fileDrop">
                <input type="file" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf" required>
                <div class="drop-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                </div>
                <div class="drop-title">Click to upload or drag &amp; drop</div>
                <div class="drop-sub">JPG, PNG, WEBP or PDF · Max 5MB</div>
                <div class="file-chosen">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  <span id="fileName"></span>
                </div>
              </div>
            </div>

            <button type="submit" class="btn" id="submitBtn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
              Upload Receipt &amp; Submit
            </button>
          </form>
        </div>

      <?php endif; ?>
    </div>
  </div>

  <script>
    const fileInput  = document.getElementById('receipt');
    const fileDrop   = document.getElementById('fileDrop');
    const fileNameEl = document.getElementById('fileName');
    const form       = document.getElementById('receiptForm');
    const submitBtn  = document.getElementById('submitBtn');
    const MAX_SIZE   = 5 * 1024 * 1024;

    if (fileInput && fileDrop) {
      const showFile = (file) => {
        fileNameEl.textContent = file.name;
        fileDrop.classList.add('has-file');
      };

      fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files.length > 0) {
          showFile(fileInput.files[0]);
        } else {
          fileDrop.classList.remove('has-file');
        }
      });

      ['dragenter', 'dragover'].forEach(evt => {
        fileDrop.addEventListener(evt, e => { e.preventDefault(); fileDrop.classList.add('drag'); });
      });
      ['dragleave', 'drop'].forEach(evt => {
        fileDrop.addEventListener(evt, e => { e.preventDefault(); fileDrop.classList.remove('drag'); });
      });
      fileDrop.addEventListener('drop', e => {
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
          fileInput.files = e.dataTransfer.files;
          showFile(e.dataTransfer.files[0]);
        }
      });
    }

    if (form) {
      form.addEventListener('submit', (e) => {
        if (fileInput.files.length && fileInput.files[0].size > MAX_SIZE) {
          e.preventDefault();
          alert('File size 5MB ට වඩා අඩු විය යුතුයි.');
          return;
        }
        submitBtn.disabled = true; // double-submit වැළැක්වීමට
      });
    }
  </script>
</body>
</html>