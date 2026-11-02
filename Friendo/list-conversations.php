<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

$res = mysqli_query($mysqli, "SELECT sender_id, receiver_id FROM friend_requests WHERE status='accepted' AND (sender_id=$uid OR receiver_id=$uid)");
$friendIds = [];
while ($r = mysqli_fetch_assoc($res)) {
    $friendIds[] = $r['sender_id'] == $uid ? $r['receiver_id'] : $r['sender_id'];
}

$rooms = [];
foreach ($friendIds as $fid) {
    $u = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT id, username, nickname FROM users WHERE id=" . (int)$fid));
    if (!$u) continue;
    $last = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT message, created_at FROM messages
         WHERE (sender_id=$uid AND receiver_id=$fid) OR (sender_id=$fid AND receiver_id=$uid)
         ORDER BY id DESC LIMIT 1"));
    $unreadRow = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND type='message' AND actor_id=$fid AND is_read=0"));

    $rooms[] = [
        "id" => $u['id'],
        "name" => $u['nickname'] ?: $u['username'],
        "lastMessage" => $last ? $last['message'] : "Say hello!",
        "time" => $last ? date("g:i A", strtotime($last['created_at'])) : "",
        "unread" => $unreadRow ? (int)$unreadRow['c'] > 0 : false
    ];
}
jsonOut(["ok" => true, "rooms" => $rooms]);
