<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/friendship.php";
requireLogin();
$uid = currentUserId();
$withId = (int)($_GET['with'] ?? 0);

if (!areFriends($mysqli, $uid, $withId)) {
    jsonOut(["ok" => false, "message" => "You're not friends with this user yet."]);
}

$stmt = mysqli_prepare($mysqli,
    "SELECT sender_id, message, created_at FROM messages
     WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
     ORDER BY id ASC");
mysqli_stmt_bind_param($stmt, "iiii", $uid, $withId, $withId, $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$out = [];
while ($row = mysqli_fetch_assoc($res)) {
    $out[] = [
        "mine" => (int)$row['sender_id'] === $uid,
        "message" => $row['message'],
        "time" => date("g:i A", strtotime($row['created_at']))
    ];
}
jsonOut(["ok" => true, "messages" => $out]);
