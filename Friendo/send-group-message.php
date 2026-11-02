<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/groupchat.php";
requireLogin();
$uid = currentUserId();
$gid = (int)($_POST['group'] ?? 0);
$text = trim($_POST['message'] ?? '');

if ($gid <= 0 || $text === '') {
    jsonOut(["ok" => false, "message" => "Nothing to send."]);
}
if (!isGroupMember($mysqli, $gid, $uid)) {
    jsonOut(["ok" => false, "message" => "You're not a member of this group."]);
}

$stmt = mysqli_prepare($mysqli, "INSERT INTO chat_group_messages (group_id, sender_id, message) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iis", $gid, $uid, $text);

if (mysqli_stmt_execute($stmt)) {
    $me = currentUser();
    $group = groupInfo($mysqli, $gid);
    $senderName = $me ? ($me['nickname'] ?: $me['username']) : 'Someone';
    $preview = mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : $text;
    $groupName = $group ? $group['name'] : 'the group';

    foreach (groupMemberIds($mysqli, $gid) as $memberId) {
        if ($memberId === $uid) continue;
        createNotification($memberId, 'group_message', $uid, $gid, $senderName . ' in ' . $groupName . ': ' . $preview);
    }

    jsonOut(["ok" => true]);
} else {
    jsonOut(["ok" => false, "message" => "Message could not be sent."]);
}
