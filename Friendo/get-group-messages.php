<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/groupchat.php";
requireLogin();
$uid = currentUserId();
$gid = (int)($_GET['id'] ?? 0);

if (!isGroupMember($mysqli, $gid, $uid)) {
    jsonOut(["ok" => false, "message" => "You're not a member of this group."]);
}

$stmt = mysqli_prepare($mysqli, "SELECT gm.sender_id, gm.message, gm.created_at, u.username, u.nickname
    FROM chat_group_messages gm JOIN users u ON u.id = gm.sender_id
    WHERE gm.group_id = ?
    ORDER BY gm.id ASC");
mysqli_stmt_bind_param($stmt, "i", $gid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$out = [];
while ($row = mysqli_fetch_assoc($res)) {
    $out[] = [
        "mine" => (int)$row['sender_id'] === $uid,
        "senderName" => $row['nickname'] ?: $row['username'],
        "message" => $row['message'],
        "time" => date("g:i A", strtotime($row['created_at'])),
    ];
}

// Viewing the messages clears this group's unread notifications.
mysqli_query($mysqli, "UPDATE notifications SET is_read = 1 WHERE user_id = $uid AND type IN ('group_message','group_invite') AND ref_id = $gid");

jsonOut(["ok" => true, "messages" => $out]);
