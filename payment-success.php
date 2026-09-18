<?php
session_start();
$orderId = isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : '';
?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Sipway Campus</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f4f0ff;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0;
        }
        .card {
            background: #fff; border-radius: 20px; padding: 40px;
            max-width: 420px; width: 90%; text-align: center;
            box-shadow: 0 20px 50px rgba(124,58,237,0.12);
        }
        .icon { font-size: 64px; margin-bottom: 16px; }
        h1 { color: #1e1b4b; margin: 0 0 12px; font-size: 22px; }
        p { color: #6b7280; line-height: 1.6; margin: 0 0 8px; }
        .order { font-size: 13px; color: #9ca3af; margin-top: 12px; }
        a {
            display: inline-block; margin-top: 24px; padding: 14px 28px;
            background: linear-gradient(135deg,#a855f7,#ec4899);
            color: #fff; text-decoration: none; border-radius: 12px; font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>Payment Successful</h1>
        <p>ඔබගේ ගෙවීම සාර්ථකයි. Admin අනුමත කළ පසු package එක activate වේ.</p>
        <?php if ($orderId): ?>
            <p class="order">Order: <?php echo $orderId; ?></p>
        <?php endif; ?>
        <a href="packages.php">Back to Packages</a>
    </div>
</body>
</html>