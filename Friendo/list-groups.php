<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/groupchat.php";
requireLogin();
$uid = currentUserId();

$stmt = mysqli_prepare($mysqli, "SELECT g.id, g.name
    FROM chat_groups g JOIN chat_group_members m ON m.group_id = g.id
    WHERE m.user_id = ?
    ORDER BY g.id DESC");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$groups = [];
while ($g = mysqli_fetch_assoc($res)) {
    $gid = (int)$g['id'];

    $memberCountRow = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) AS c FROM chat_group_members WHERE group_id = $gid"));
    $memberCount = $memberCountRow ? (int)$memberCountRow['c'] : 0;

    $last = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT gm.message, gm.created_at, u.username, u.nickname
         FROM chat_group_messages gm JOIN users u ON u.id = gm.sender_id
         WHERE gm.group_id = $gid ORDER BY gm.id DESC LIMIT 1"));

    $lastMessage = "No messages yet.";
    if ($last) {
        $lastSenderName = $last['nickname'] ?: $last['username'];
        $lastMessage = $lastSenderName . ': ' . $last['message'];
    }

    $unreadRow = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT COUNT(*) AS c FROM notifications
         WHERE user_id = $uid AND type = 'group_message' AND ref_id = $gid AND is_read = 0"));

    $groups[] = [
        "id" => $gid,
        "name" => $g['name'],
        "memberCount" => $memberCount,
        "lastMessage" => $lastMessage,
        "time" => $last ? date("g:i A", strtotime($last['created_at'])) : "",
        "unread" => $unreadRow ? (int)$unreadRow['c'] > 0 : false,
    ];
}

jsonOut(["ok" => true, "groups" => $groups]);
