<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/friendship.php";
require_once __DIR__ . "/groupchat.php";
requireLogin();
$uid = currentUserId();
$gid = (int)($_POST['group'] ?? 0);
$membersRaw = trim($_POST['members'] ?? '');

if (!isGroupMember($mysqli, $gid, $uid)) {
    jsonOut(["ok" => false, "message" => "You're not a member of this group."]);
}

$memberIds = [];
if ($membersRaw !== '') {
    foreach (explode(',', $membersRaw) as $part) {
        $id = (int)trim($part);
        if ($id > 0 && !in_array($id, $memberIds, true)) {
            $memberIds[] = $id;
        }
    }
}

$added = [];
$me = currentUser();
$meName = $me ? ($me['nickname'] ?: $me['username']) : 'Someone';
$group = groupInfo($mysqli, $gid);
$groupName = $group ? $group['name'] : 'a group';

foreach ($memberIds as $mid) {
    if (!areFriends($mysqli, $uid, $mid)) continue;
    if (isGroupMember($mysqli, $gid, $mid)) continue;

    $stmt = mysqli_prepare($mysqli, "INSERT INTO chat_group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
    mysqli_stmt_bind_param($stmt, "ii", $gid, $mid);
    if (mysqli_stmt_execute($stmt)) {
        $added[] = $mid;
        createNotification($mid, 'group_invite', $uid, $gid, $meName . ' added you to "' . $groupName . '"');
    }
}

if (empty($added)) {
    jsonOut(["ok" => false, "message" => "No new friends were added."]);
}

jsonOut(["ok" => true, "added" => count($added)]);
