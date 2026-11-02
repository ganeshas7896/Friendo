<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

if (RAZORPAY_KEY_ID === "rzp_test_YOUR_KEY_ID") {
    jsonOut(["ok" => false, "message" => "Payments aren't configured yet — add your Razorpay keys to config.php."]);
}

$plan = ($_POST['plan'] ?? 'monthly') === 'items' ? 'items' : 'monthly';
$amountPaise = $plan === 'monthly'
    ? RAZORPAY_MONTHLY_AMOUNT_PAISE
    : max(100, (int)($_POST['amount_paise'] ?? 10000)); // custom item amount, min ₹1

$receipt = "friendo_" . $uid . "_" . time();

$ch = curl_init("https://api.razorpay.com/v1/orders");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "amount" => $amountPaise,
        "currency" => "INR",
        "receipt" => $receipt,
    ]),
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err || !$response) {
    jsonOut(["ok" => false, "message" => "Could not reach Razorpay: $err"]);
}

$order = json_decode($response, true);
if (empty($order['id'])) {
    jsonOut(["ok" => false, "message" => $order['error']['description'] ?? "Razorpay rejected the order."]);
}

$stmt = mysqli_prepare($mysqli, "INSERT INTO payments (user_id, order_id, amount_paise, plan, status) VALUES (?, ?, ?, ?, 'created')");
mysqli_stmt_bind_param($stmt, "isis", $uid, $order['id'], $amountPaise, $plan);
mysqli_stmt_execute($stmt);

jsonOut(["ok" => true, "order_id" => $order['id'], "amount" => $amountPaise, "key" => RAZORPAY_KEY_ID]);
