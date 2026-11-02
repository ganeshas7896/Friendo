<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

$orderId   = $_POST['razorpay_order_id'] ?? '';
$paymentId = $_POST['razorpay_payment_id'] ?? '';
$signature = $_POST['razorpay_signature'] ?? '';

if (!$orderId || !$paymentId || !$signature) {
    jsonOut(["ok" => false, "message" => "Missing payment details."]);
}

$expected = hash_hmac('sha256', $orderId . "|" . $paymentId, RAZORPAY_KEY_SECRET);
if (!hash_equals($expected, $signature)) {
    jsonOut(["ok" => false, "message" => "Payment signature could not be verified."]);
}

$stmt = mysqli_prepare($mysqli, "UPDATE payments SET payment_id=?, status='paid' WHERE order_id=? AND user_id=?");
mysqli_stmt_bind_param($stmt, "ssi", $paymentId, $orderId, $uid);
mysqli_stmt_execute($stmt);

$plan = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT plan FROM payments WHERE order_id='" . mysqli_real_escape_string($mysqli, $orderId) . "'"));
if ($plan && $plan['plan'] === 'monthly') {
    $up = mysqli_prepare($mysqli, "UPDATE users SET is_subscribed = 1 WHERE id = ?");
    mysqli_stmt_bind_param($up, "i", $uid);
    mysqli_stmt_execute($up);
}

jsonOut(["ok" => true]);
