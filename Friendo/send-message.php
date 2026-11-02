<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/friendship.php";
requireLogin();
$uid = currentUserId();
$to = (int)($_POST['to'] ?? 0);
$text = trim($_POST['message'] ?? '');

if ($to <= 0 || $text === '') {
    jsonOut(["ok" => false, "message" => "Nothing to send."]);
}
if (!areFriends($mysqli, $uid, $to)) {
    jsonOut(["ok" => false, "message" => "You can only chat with accepted friends. Send and accept a friend request first."]);
}

$stmt = mysqli_prepare($mysqli, "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iis", $uid, $to, $text);
if (mysqli_stmt_execute($stmt)) {
    $me = currentUser();
    $senderName = $me ? ($me['nickname'] ?: $me['username']) : 'Someone';
    $preview = mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : $text;
    createNotification($to, 'message', $uid, null, $senderName . ': ' . $preview);
    jsonOut(["ok" => true]);
} else {
    jsonOut(["ok" => false, "message" => "Message could not be sent."]);
}
