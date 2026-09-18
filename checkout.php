<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}

$isLoggedIn = isset($_SESSION['student_id']);
if (!$isLoggedIn) {
    header('Location: packages.php?error=login_required');
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$packageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$packageType = (isset($_GET['type']) && $_GET['type'] === 'vocabulary') ? 'vocabulary' : 'regular';

if ($packageId <= 0) {
    header('Location: packages.php?error=invalid_package');
    exit;
}

$stmt = $conn->prepare("SELECT full_name, email, mobile, profile_photo, language FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$fullName = $student['full_name'] ?: 'Student';
$email = $student['email'] ?: '';
$mobile = $student['mobile'] ?: '';
$firstName = htmlspecialchars(explode(' ', trim($fullName))[0]);

$package = null;
if ($packageType === 'vocabulary') {
    $stmt = $conn->prepare("SELECT id, package_name, price, 'Vocabulary Practice' as duration_label, 'Access to vocabulary practice modules' as description, 0 as total_sessions, NULL as package_type FROM vocabulary_packages WHERE id = ? AND status = 'active' LIMIT 1");
} else {
    $stmt = $conn->prepare("SELECT id, package_name, price, total_sessions, duration_label, description, package_type FROM packages WHERE id = ? AND status = 'active' LIMIT 1");
}
$stmt->bind_param('i', $packageId);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$package) {
    header('Location: packages.php?error=package_not_found');
    exit;
}

// Build the label shown on the pkg-tag badge
if ($packageType === 'vocabulary') {
    $pkgTagLabel = 'VOCABULARY PACKAGE';
} else {
    $pkgTagLabel = ($package['package_type'] === 'group') ? 'GROUP PACKAGE' : 'INDIVIDUAL PACKAGE';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - <?php echo htmlspecialchars($package['package_name']); ?> | Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --topbar-bg: #0b0a1f;
  --bg: #f4f0ff;
  --bg-soft: #faf8ff;
  --card: #ffffff;
  --text: #1e1b4b;
  --muted: #6b7280;
  --line: #e9e5f5;
  --line-soft: #f3f0fa;
  --purple: #7c3aed;
  --purple-soft: #f3e8ff;
  --pink: #ec4899;
  --success: #10b981;
  --success-soft: #d1fae5;
  --radius-lg: 20px;
  --radius-md: 14px;
  --radius-sm: 10px;
  --shadow-card: 0 10px 30px -8px rgba(124, 58, 237, 0.1);
  --shadow-hover: 0 18px 45px -12px rgba(124, 58, 237, 0.16);
  --ease: cubic-bezier(.4,0,.2,1);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', 'Noto Sans Sinhala', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}
a { color: inherit; text-decoration: none; }

/* Header */
.topbar {
  height: 64px;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 0 24px;
  background: var(--topbar-bg);
  position: sticky;
  top: 0;
  z-index: 50;
}
.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  color: #fff;
  font-size: 16px;
}
.logo-mark {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 14px;
  font-weight: 800;
}
.top-links { margin-left: auto; display: flex; align-items: center; gap: 20px; }
.top-links a { font-size: 13px; font-weight: 600; color: #94a3b8; }
.top-links a:hover { color: #fff; }

/* Main Container */
.checkout-container {
  max-width: 1050px;
  margin: 36px auto 60px;
  padding: 0 20px;
}
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--muted);
  margin-bottom: 20px;
}
.breadcrumb a:hover { color: var(--purple); }

.checkout-grid {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 28px;
}
@media (max-width: 860px) {
  .checkout-grid { grid-template-columns: 1fr; }
}

/* Card Section */
.card-box {
  background: var(--card);
  border-radius: var(--radius-lg);
  padding: 30px;
  box-shadow: var(--shadow-card);
  border: 1px solid var(--line-soft);
  margin-bottom: 24px;
}
.card-box-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.card-box-title svg { color: var(--purple); width: 22px; height: 22px; }

/* Student Details Form */
.info-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}
@media (max-width: 500px) { .info-row { grid-template-columns: 1fr; } }

.info-group label {
  display: block;
  font-size: 12.5px;
  font-weight: 700;
  color: var(--muted);
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.info-value {
  padding: 12px 14px;
  background: var(--bg-soft);
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
}

/* Payment Method Selection */
.payment-option {
  border: 2px solid var(--line);
  border-radius: var(--radius-md);
  padding: 20px;
  margin-bottom: 16px;
  cursor: pointer;
  transition: all .25s var(--ease);
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 14px;
}
.payment-option:hover {
  border-color: rgba(168,85,247,0.4);
  background: var(--bg-soft);
}
.payment-option.selected {
  border-color: var(--purple);
  background: var(--purple-soft);
  box-shadow: 0 4px 20px -4px rgba(124,58,237,0.15);
}
.payment-radio {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid var(--muted);
  margin-top: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.payment-option.selected .payment-radio {
  border-color: var(--purple);
  background: var(--purple);
}
.payment-option.selected .payment-radio::after {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #fff;
}
.payment-info h4 {
  font-size: 15px;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 4px;
}
.payment-info p {
  font-size: 12.5px;
  color: var(--muted);
  line-height: 1.5;
}
.card-badges {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}
.badge-img {
  height: 22px;
  background: #fff;
  border-radius: 4px;
  padding: 2px 6px;
  border: 1px solid var(--line);
}

/* Order Summary */
.summary-card {
  background: var(--card);
  border-radius: var(--radius-lg);
  padding: 30px;
  box-shadow: var(--shadow-card);
  border: 1px solid var(--line-soft);
  position: sticky;
  top: 84px;
}
.package-preview {
  padding: 20px;
  border-radius: var(--radius-md);
  background: linear-gradient(135deg, #1e1b4b, #2e2a5e);
  color: #fff;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}
.package-preview::after {
  content: '';
  position: absolute;
  top: -40px;
  right: -40px;
  width: 120px;
  height: 120px;
  background: radial-gradient(circle, rgba(236,72,153,0.3), transparent 70%);
}
.pkg-tag {
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #ec4899;
  margin-bottom: 6px;
}
.pkg-title {
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 8px;
}
.pkg-sub {
  font-size: 13px;
  color: #cbd5e1;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  font-size: 14px;
  color: var(--muted);
}
.summary-row.total {
  border-top: 2px dashed var(--line);
  padding-top: 16px;
  margin-top: 8px;
  font-size: 18px;
  font-weight: 800;
  color: var(--text);
}
.total-amount {
  font-size: 24px;
  font-weight: 800;
  color: var(--purple);
}

.pay-btn {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: var(--radius-md);
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-size: 15px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 8px 25px rgba(168,85,247,0.35);
  transition: all .25s var(--ease);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 20px;
}
.pay-btn:hover {
  filter: brightness(1.08);
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(168,85,247,0.45);
}

.security-footer {
  margin-top: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 12px;
  color: var(--muted);
  font-weight: 600;
}
.security-footer svg { color: var(--success); width: 16px; height: 16px; }
</style>
</head>
<body>

<header class="topbar">
  <a href="packages.php" class="logo">
    <span class="logo-mark">SC</span>
    <span>Sipway Campus</span>
  </a>
  <nav class="top-links">
    <a href="packages.php">&larr; Back to Packages</a>
  </nav>
</header>

<div class="checkout-container">
  <div class="breadcrumb">
    <a href="packages.php">Packages</a>
    <span>&rsaquo;</span>
    <span style="color: var(--text); font-weight:700;">Checkout</span>
  </div>

  <div class="checkout-grid">
    <div>
      <!-- Student Info -->
      <div class="card-box">
        <div class="card-box-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Student Billing Details
        </div>
        <div class="info-row">
          <div class="info-group">
            <label>Student Name</label>
            <div class="info-value"><?php echo htmlspecialchars($fullName); ?></div>
          </div>
          <div class="info-group">
            <label>Email Address</label>
            <div class="info-value"><?php echo htmlspecialchars($email); ?></div>
          </div>
        </div>
        <div class="info-row">
          <div class="info-group">
            <label>Phone Number</label>
            <div class="info-value"><?php echo htmlspecialchars($mobile ?: 'Not provided'); ?></div>
          </div>
          <div class="info-group">
            <label>Student ID</label>
            <div class="info-value">SC-STU-<?php echo sprintf('%05d', $studentId); ?></div>
          </div>
        </div>
      </div>

      <!-- Payment Method -->
      <div class="card-box">
        <div class="card-box-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          Select Payment Method
        </div>

        <div class="payment-option selected" id="optCard" onclick="selectMethod('card')">
          <div class="payment-radio"></div>
          <div class="payment-info">
            <h4>💳 Online Credit / Debit Card (DFCC Bank)</h4>
            <p>Pay instantly with Visa, MasterCard, or AMEX via DFCC's 256-bit SSL encrypted payment portal.</p>
            <div class="card-badges">
              <span class="badge-img">💳 VISA</span>
              <span class="badge-img">💳 Mastercard</span>
              <span class="badge-img">💳 AMEX</span>
            </div>
          </div>
        </div>

        <div class="payment-option" id="optBank" onclick="selectMethod('bank')">
          <div class="payment-radio"></div>
          <div class="payment-info">
            <h4>🏦 Bank Transfer / Deposit Receipt</h4>
            <p>Pay directly to our bank account and upload your deposit slip for admin verification.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Summary Column -->
    <div>
      <div class="summary-card">
        <div class="package-preview">
          <div class="pkg-tag"><?php echo htmlspecialchars($pkgTagLabel); ?></div>
          <div class="pkg-title"><?php echo htmlspecialchars($package['package_name']); ?></div>
          <div class="pkg-sub">
            <?php if ($package['total_sessions'] > 0): ?>
              <?php echo (int)$package['total_sessions']; ?> Sessions Included &bull; <?php echo htmlspecialchars($package['duration_label']); ?>
            <?php else: ?>
              Full Access to Vocabulary Modules
            <?php endif; ?>
          </div>
        </div>

        <div class="summary-row">
          <span>Package Price</span>
          <span style="font-weight: 700;">Rs. <?php echo number_format($package['price'], 2); ?></span>
        </div>
        <div class="summary-row">
          <span>Processing Fee</span>
          <span style="color: var(--success); font-weight:700;">FREE</span>
        </div>
        <div class="summary-row total">
          <span>Total Amount</span>
          <span class="total-amount">Rs. <?php echo number_format($package['price'], 2); ?></span>
        </div>

        <form id="checkoutForm" method="GET" action="<?php echo $packageType === 'vocabulary' ? 'initiate-vocab-payment.php' : 'initiate-payment.php'; ?>">
          <input type="hidden" name="package_id" value="<?php echo (int)$package['id']; ?>">
          <input type="hidden" name="type" value="<?php echo htmlspecialchars($packageType); ?>">
          
          <button type="submit" class="pay-btn" id="paySubmitBtn">
            <span>Proceed to Secure Payment &rarr;</span>
          </button>
        </form>

        <div class="security-footer">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <span>256-Bit SSL Encrypted &bull; PCI DSS Compliant</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let currentMethod = 'card';

function selectMethod(method) {
  currentMethod = method;
  document.getElementById('optCard').classList.toggle('selected', method === 'card');
  document.getElementById('optBank').classList.toggle('selected', method === 'bank');
  
  const submitBtn = document.getElementById('paySubmitBtn');
  const form = document.getElementById('checkoutForm');
  
  if (method === 'bank') {
    submitBtn.innerHTML = '<span>Upload Bank Deposit Slip &rarr;</span>';
    form.action = '<?php echo $packageType === "vocabulary" ? "upload-vocab-bank-receipt.php" : "upload-bank-receipt.php"; ?>';
  } else {
    submitBtn.innerHTML = '<span>Proceed to Secure Payment &rarr;</span>';
    form.action = '<?php echo $packageType === "vocabulary" ? "initiate-vocab-payment.php" : "initiate-payment.php"; ?>';
  }
}
</script>

</body>
</html>