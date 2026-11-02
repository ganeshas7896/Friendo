<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

$stmt = mysqli_prepare($mysqli, "SELECT n.id, n.type, n.actor_id, n.ref_id, n.message, n.is_read, n.created_at,
        u.username AS actor_username, u.nickname AS actor_nickname
    FROM notifications n
    LEFT JOIN users u ON u.id = n.actor_id
    WHERE n.user_id = ?
    ORDER BY n.id DESC
    LIMIT 40");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$items = [];
$unreadTotal = 0;
$unreadDm = 0;
$unreadGroup = 0;
$unreadSocial = 0;

while ($row = mysqli_fetch_assoc($res)) {
    $link = '#';
    if ($row['type'] === 'message') {
        $link = 'conversation.php?with=' . (int)$row['actor_id'];
    } elseif ($row['type'] === 'friend_request' || $row['type'] === 'friend_accept') {
        $link = 'friends.php';
    } elseif ($row['type'] === 'group_message' || $row['type'] === 'group_invite') {
        $link = 'group-conversation.php?id=' . (int)$row['ref_id'];
    }

    $items[] = [
        "id" => (int)$row['id'],
        "type" => $row['type'],
        "actorId" => $row['actor_id'] !== null ? (int)$row['actor_id'] : null,
        "actorName" => $row['actor_nickname'] ?: ($row['actor_username'] ?: 'Someone'),
        "refId" => $row['ref_id'] !== null ? (int)$row['ref_id'] : null,
        "message" => $row['message'],
        "isRead" => (bool)$row['is_read'],
        "time" => date("g:i A", strtotime($row['created_at'])),
        "link" => $link,
    ];

    if (!$row['is_read']) {
        $unreadTotal++;
        if ($row['type'] === 'message') {
            $unreadDm++;
        } elseif ($row['type'] === 'group_message' || $row['type'] === 'group_invite') {
            $unreadGroup++;
        } else {
            $unreadSocial++;
        }
    }
}

jsonOut([
    "ok" => true,
    "items" => $items,
    "unread" => ["total" => $unreadTotal, "dm" => $unreadDm, "group" => $unreadGroup, "social" => $unreadSocial],
]);
