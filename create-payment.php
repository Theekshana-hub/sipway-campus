<?php
require_once 'db.php';

function createPaymentRecord(int $studentId, int $packageId, float $amount): array
{
    global $conn;
    $config = require 'payment-config.php';

    // Unique order_id (invoice)
    $orderId = 'SW-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    $stmt = $conn->prepare("
        INSERT INTO payments 
            (student_id, package_id, order_id, amount, currency, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $currency = $config['currency'];
    $stmt->bind_param('iisds', $studentId, $packageId, $orderId, $amount, $currency);
    $stmt->execute();
    $paymentId = $stmt->insert_id;
    $stmt->close();

    return [
        'id'         => $paymentId,
        'order_id'   => $orderId,
        'amount'     => $amount,
        'currency'   => $currency,
        'student_id' => $studentId,
        'package_id' => $packageId,
    ];
}