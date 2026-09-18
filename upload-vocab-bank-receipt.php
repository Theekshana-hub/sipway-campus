<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (!isset($conn) || $conn === null) {
    die("Database connection failed.");
}

if (!isset($_SESSION['student_id'])) {
    header('Location: vocabulary-practice.php');
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$packageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$error = '';
$success = false;

// Validate vocabulary package
$pkg = null;
if ($packageId > 0) {
    $stmt = $conn->prepare("
        SELECT id, package_name, price, duration_label
        FROM vocabulary_packages
        WHERE id = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->bind_param('i', $packageId);
    $stmt->execute();
    $pkg = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$pkg) {
    header('Location: vocabulary-practice.php?error=package_not_found');
    exit;
}

// Already active / pending?
$stateStmt = $conn->prepare("
    SELECT id, status
    FROM activated_vocabulary_packages
    WHERE student_id = ? AND status IN ('active','pending')
    LIMIT 1
");
$stateStmt->bind_param('i', $studentId);
$stateStmt->execute();
$existing = $stateStmt->get_result()->fetch_assoc();
$stateStmt->close();

if ($existing) {
    header('Location: vocabulary-practice.php?error=already_active');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionRef = trim($_POST['transaction_ref'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    if ($transactionRef === '') {
        $error = 'Transaction / Reference number අනිවාර්යයි.';
    } elseif (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Bank receipt file එකක් upload කරන්න.';
    } else {
        $file = $_FILES['receipt'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        // Verify actual MIME type from file content, not just client-supplied header
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($actualType, $allowed)) {
            $error = 'අනුමත file types: JPG, PNG, WEBP, PDF පමණි.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File size 5MB ට වඩා අඩු විය යුතුයි.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'])) {
                $error = 'අනුමත extensions: jpg, jpeg, png, webp, pdf';
            } else {
                $uploadDir = __DIR__ . '/uploads/vocab_receipts/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $safeName = 'vocab_receipt_' . $studentId . '_' . $packageId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $safeName;
                $dbPath   = 'uploads/vocab_receipts/' . $safeName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    // Columns: adjust if your table differs
                    $ins = $conn->prepare("
                        INSERT INTO activated_vocabulary_packages
                        (student_id, package_id, status, payment_method, transaction_ref, receipt_path, notes)
                        VALUES (?, ?, 'pending', 'bank_transfer', ?, ?, ?)
                    ");
                    $ins->bind_param('iisss', $studentId, $packageId, $transactionRef, $dbPath, $notes);

                    if ($ins->execute()) {
                        $success = true;
                    } else {
                        @unlink($destPath);
                        $error = 'Database error. කරුණාකර නැවත උත්සාහ කරන්න.';
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bank Receipt Upload - Vocabulary | Sipway Campus</title>
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

body {
  font-family: 'Inter', 'Noto Sans Sinhala', sans-serif;
  background:
    radial-gradient(1200px 600px at -10% -10%, rgba(236,72,153,0.10), transparent 60%),
    radial-gradient(1200px 600px at 110% 10%, rgba(124,58,237,0.14), transparent 55%),
    linear-gradient(160deg, var(--bg-1) 0%, var(--bg-2) 55%, #f3ecff 100%);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.wrap {
  width: 100%;
  max-width: 460px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  justify-content: center;
  margin-bottom: 18px;
}
.brand .logo {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--purple), var(--pink));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 800;
  font-size: 15px;
  box-shadow: 0 6px 16px -4px rgba(124,58,237,0.5);
}
.brand span {
  font-weight: 800;
  font-size: 15px;
  letter-spacing: -0.2px;
  color: var(--text);
}

.card {
  background: var(--card);
  border-radius: 22px;
  box-shadow: 0 25px 60px -20px rgba(124, 58, 237, 0.28), 0 4px 12px -4px rgba(30,27,75,0.06);
  border: 1px solid var(--line);
  width: 100%;
  padding: 30px 26px 26px;
  position: relative;
  overflow: hidden;
}
.card::before {
  content: "";
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 5px;
  background: linear-gradient(90deg, var(--purple), var(--pink));
}

.back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: var(--muted);
  text-decoration: none;
  margin-bottom: 18px;
  transition: color .15s, transform .15s;
}
.back svg { width: 14px; height: 14px; }
.back:hover { color: var(--purple); transform: translateX(-2px); }

.header-row { margin-bottom: 20px; }
h1 {
  font-size: 21px;
  font-weight: 800;
  letter-spacing: -0.3px;
  margin-bottom: 6px;
}
.sub {
  font-size: 13.5px;
  color: var(--muted);
  line-height: 1.55;
}

.pkg-box {
  background: linear-gradient(135deg, var(--purple-soft), #fdf2ff);
  border: 1px solid #ecd9ff;
  border-radius: var(--radius);
  padding: 16px 18px;
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.pkg-box .pkg-icon {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  border-radius: 12px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px -2px rgba(124,58,237,0.25);
}
.pkg-box .pkg-icon svg { width: 20px; height: 20px; color: var(--purple); }
.pkg-box strong { display: block; font-size: 15px; margin-bottom: 3px; }
.pkg-box .meta { font-size: 12.5px; color: #6b21a8; font-weight: 600; }

.bank-info {
  background: var(--amber-soft);
  border: 1px solid var(--amber-line);
  border-radius: var(--radius-sm);
  padding: 14px 16px;
  font-size: 13px;
  margin-bottom: 20px;
  line-height: 1.65;
  position: relative;
}
.bank-info .title-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
  color: var(--amber);
  font-weight: 700;
}
.bank-info .title-row svg { width: 14px; height: 14px; }
.bank-info .row { display: flex; justify-content: space-between; gap: 10px; color: #78350f; }
.bank-info .row + .row { margin-top: 2px; }
.bank-info .row b { color: #451a03; font-weight: 700; }

.field { margin-bottom: 16px; }
.field label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 7px;
  color: #3730a3;
}
.field label .opt { font-weight: 500; color: var(--muted); }
.field input[type="text"], .field textarea {
  width: 100%;
  padding: 12px 14px;
  border: 1.5px solid var(--line);
  border-radius: var(--radius-sm);
  font-size: 14px;
  font-family: inherit;
  outline: none;
  background: #faf8ff;
  transition: border-color .15s, box-shadow .15s, background .15s;
}
.field input[type="text"]:focus, .field textarea:focus {
  border-color: #a855f7;
  box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
  background: #fff;
}
.field textarea { resize: vertical; min-height: 68px; }
.hint { font-size: 12px; color: var(--muted); margin-top: 6px; }

/* Custom file upload */
.file-drop {
  position: relative;
  border: 1.5px dashed #c9b8f0;
  border-radius: var(--radius-sm);
  background: #faf8ff;
  padding: 20px 16px;
  text-align: center;
  cursor: pointer;
  transition: border-color .15s, background .15s;
}
.file-drop:hover, .file-drop.drag {
  border-color: var(--purple);
  background: var(--purple-soft);
}
.file-drop input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}
.file-drop .drop-icon {
  width: 38px;
  height: 38px;
  margin: 0 auto 10px;
  border-radius: 10px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px -3px rgba(124,58,237,0.3);
}
.file-drop .drop-icon svg { width: 18px; height: 18px; color: var(--purple); }
.file-drop .drop-title { font-size: 13.5px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.file-drop .drop-sub { font-size: 12px; color: var(--muted); }
.file-drop .file-chosen {
  display: none;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: var(--purple-dark);
}
.file-drop .file-chosen svg { width: 16px; height: 16px; flex-shrink: 0; }
.file-drop.has-file .drop-icon,
.file-drop.has-file .drop-title,
.file-drop.has-file .drop-sub { display: none; }
.file-drop.has-file .file-chosen { display: inline-flex; }
.file-drop.has-file { border-style: solid; border-color: #86efac; background: #f0fdf4; }

.error {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  background: var(--danger-soft);
  color: var(--danger);
  border: 1px solid var(--danger-line);
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  font-size: 13.5px;
  font-weight: 600;
  margin-bottom: 18px;
  line-height: 1.5;
}
.error svg { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; }

.btn {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-weight: 700;
  font-size: 14.5px;
  cursor: pointer;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 10px 24px -8px rgba(168,85,247,0.55);
  transition: filter .2s, transform .15s, box-shadow .2s;
  text-decoration: none;
}
.btn svg { width: 16px; height: 16px; }
.btn:hover { filter: brightness(1.06); transform: translateY(-1px); box-shadow: 0 14px 28px -8px rgba(168,85,247,0.6); }
.btn:active { transform: translateY(0); }

.success-box { text-align: center; padding: 14px 0 4px; }
.success-box .icon {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: var(--success-soft);
  color: var(--success);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 18px;
  box-shadow: 0 0 0 8px rgba(16,185,129,0.08);
}
.success-box .icon svg { width: 30px; height: 30px; }
.success-box h2 { font-size: 19px; margin-bottom: 10px; font-weight: 800; }
.success-box p { font-size: 14px; color: var(--muted); line-height: 1.65; margin-bottom: 22px; padding: 0 6px; }
</style>
</head>
<body>
  <div class="wrap">
    <div class="brand">
      <div class="logo">S</div>
      <span>Sipway Campus</span>
    </div>

    <div class="card">
      <?php if ($success): ?>
        <div class="success-box">
          <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          </div>
          <h2>Receipt Uploaded!</h2>
          <p>ඔබගේ bank receipt එක Admin වෙත යවා ඇත. Admin අනුමත කළ පසු Vocabulary package එක active වී videos unlock වේ.</p>
          <a href="vocabulary-practice.php" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
            Vocabulary වෙත යන්න
          </a>
        </div>
      <?php else: ?>
        <a href="vocabulary-practice.php" class="back">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
          Back to Vocabulary
        </a>

        <div class="header-row">
          <h1>Bank Transfer Receipt</h1>
          <p class="sub">ගෙවීම bank transfer මගින් කර receipt එක upload කරන්න. Admin අනුමත කළ පසු package එක active වේ.</p>
        </div>

        <div class="pkg-box">
          <div class="pkg-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
          </div>
          <div>
            <strong><?php echo htmlspecialchars($pkg['package_name']); ?></strong>
            <span class="meta">Rs. <?php echo number_format((float)$pkg['price'], 0); ?> · <?php echo htmlspecialchars($pkg['duration_label'] ?: 'Access'); ?></span>
          </div>
        </div>

        <!-- Bank details - ඔබේ ගිණුම් විස්තර මෙහි දාන්න -->
        <div class="bank-info">
          <div class="title-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="18" height="9" rx="1"></rect><path d="M3 10h18L12 3z"></path><line x1="7" y1="14" x2="7" y2="16"></line><line x1="12" y1="14" x2="12" y2="16"></line><line x1="17" y1="14" x2="17" y2="16"></line></svg>
            ගෙවිය යුතු Bank Account
          </div>
          <div class="row"><span>Bank</span><b>Commercial Bank / DFCC</b></div>
          <div class="row"><span>Account Name</span><b>Sipway Campus</b></div>
          <div class="row"><span>Account No</span><b>1234567890</b></div>
          <div class="row"><span>Branch</span><b>Colombo</b></div>
        </div>

        <?php if ($error): ?>
          <div class="error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="receiptForm">
          <div class="field">
            <label for="transaction_ref">Transaction / Reference Number *</label>
            <input type="text" id="transaction_ref" name="transaction_ref" required
                   value="<?php echo htmlspecialchars($_POST['transaction_ref'] ?? ''); ?>"
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

          <div class="field">
            <label for="notes">Notes <span class="opt">(optional)</span></label>
            <textarea id="notes" name="notes" placeholder="අමතර විස්තර තිබේ නම්..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
          </div>

          <button type="submit" class="btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            Upload Receipt &amp; Submit
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const fileInput = document.getElementById('receipt');
    const fileDrop = document.getElementById('fileDrop');
    const fileNameEl = document.getElementById('fileName');

    if (fileInput && fileDrop) {
      fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files.length > 0) {
          fileNameEl.textContent = fileInput.files[0].name;
          fileDrop.classList.add('has-file');
        } else {
          fileDrop.classList.remove('has-file');
        }
      });

      ['dragenter', 'dragover'].forEach(evt => {
        fileDrop.addEventListener(evt, e => {
          e.preventDefault();
          fileDrop.classList.add('drag');
        });
      });
      ['dragleave', 'drop'].forEach(evt => {
        fileDrop.addEventListener(evt, e => {
          e.preventDefault();
          fileDrop.classList.remove('drag');
        });
      });
      fileDrop.addEventListener('drop', e => {
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
          fileInput.files = e.dataTransfer.files;
          fileNameEl.textContent = e.dataTransfer.files[0].name;
          fileDrop.classList.add('has-file');
        }
      });
    }
  </script>
</body>
</html>