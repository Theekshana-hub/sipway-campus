<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: packages.php?error=login_required');
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$packageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;

if ($packageId <= 0) {
    header('Location: practice-ai-video.php?error=invalid_package');
    exit;
}

// Get package
$stmt = $conn->prepare("SELECT id, name, price FROM ai_video_packages WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->bind_param('i', $packageId);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$package) {
    header('Location: practice-ai-video.php?error=package_not_found');
    exit;
}

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $refNo  = trim($_POST['reference_no'] ?? '');
    
    if ($amount <= 0 || empty($refNo)) {
        $errorMsg = 'Please fill all required fields.';
    } elseif (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Please upload a valid deposit slip.';
    } else {
        $allowed = ['jpg','jpeg','png','pdf'];
        $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errorMsg = 'Only JPG, PNG or PDF allowed.';
        } else {
            $uploadDir = 'uploads/bank_receipts/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileName = 'aiv_' . $studentId . '_' . time() . '.' . $ext;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $filePath)) {
                $stmt = $conn->prepare("INSERT INTO activated_ai_video_packages 
                    (student_id, package_id, status, amount, payment_method, reference_no, receipt_path, created_at) 
                    VALUES (?, ?, 'pending', ?, 'bank', ?, ?, NOW())");
                $stmt->bind_param('iidss', $studentId, $packageId, $amount, $refNo, $filePath);
                $stmt->execute();
                $stmt->close();
                
                $successMsg = 'Receipt uploaded successfully! Admin will activate your package soon.';
            } else {
                $errorMsg = 'Failed to upload file. Please try again.';
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Bank Receipt - AI Video Package | Sipway Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--purple:#7c3aed;--bg:#f4f0ff;--card:#fff;--text:#1e1b4b;--muted:#6b7280;--line:#e9e5f5}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.container{max-width:560px;margin:50px auto;padding:0 20px}
.card{background:var(--card);border-radius:20px;padding:32px;box-shadow:0 10px 40px rgba(124,58,237,.1)}
h1{font-size:22px;font-weight:800;margin-bottom:8px}
.sub{color:var(--muted);font-size:14px;margin-bottom:24px}
.field{margin-bottom:18px}
.field label{display:block;font-size:13px;font-weight:700;margin-bottom:6px}
.field input,.field textarea{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:10px;font-size:14px}
.field input:focus{outline:none;border-color:var(--purple)}
.btn{width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,#a855f7,#ec4899);color:#fff;font-weight:800;font-size:15px;cursor:pointer;margin-top:10px}
.alert{padding:14px;border-radius:10px;margin-bottom:20px;font-weight:600;font-size:14px}
.alert.success{background:#d1fae5;color:#065f46}
.alert.error{background:#fee2e2;color:#991b1b}
.back{display:inline-block;margin-bottom:20px;color:var(--muted);font-weight:600;text-decoration:none}
.back:hover{color:var(--purple)}
</style>
</head>
<body>
<div class="container">
  <a href="ai-video-checkout.php?package_id=<?php echo $packageId; ?>" class="back">← Back to Checkout</a>
  
  <div class="card">
    <h1>Upload Bank Deposit Slip</h1>
    <p class="sub"><?php echo htmlspecialchars($package['name']); ?> — Rs. <?php echo number_format($package['price'], 2); ?></p>

    <?php if ($successMsg): ?>
      <div class="alert success"><?php echo htmlspecialchars($successMsg); ?></div>
      <a href="practice-ai-video.php" class="btn" style="display:block;text-align:center;text-decoration:none">Go to AI Video Practice</a>
    <?php else: ?>
      <?php if ($errorMsg): ?>
        <div class="alert error"><?php echo htmlspecialchars($errorMsg); ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="field">
          <label>Amount Paid (Rs.)</label>
          <input type="number" name="amount" step="0.01" value="<?php echo $package['price']; ?>" required>
        </div>
        <div class="field">
          <label>Bank Reference / Transaction No.</label>
          <input type="text" name="reference_no" placeholder="e.g. TXN123456789" required>
        </div>
        <div class="field">
          <label>Upload Deposit Slip (JPG / PNG / PDF)</label>
          <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
        <button type="submit" class="btn">Submit for Verification</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>