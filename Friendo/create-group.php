<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/friendship.php";
requireLogin();
$uid = currentUserId();

$name = trim($_POST['name'] ?? '');
$membersRaw = trim($_POST['members'] ?? '');

if ($name === '') {
    jsonOut(["ok" => false, "message" => "Please give the group a name."]);
}

$memberIds = [];
if ($membersRaw !== '') {
    foreach (explode(',', $membersRaw) as $part) {
        $id = (int)trim($part);
        if ($id > 0 && $id !== $uid && !in_array($id, $memberIds, true)) {
            $memberIds[] = $id;
        }
    }
}

// Only accepted friends can be added — same trust rule as 1:1 chat.
$validMembers = [];
foreach ($memberIds as $mid) {
    if (areFriends($mysqli, $uid, $mid)) {
        $validMembers[] = $mid;
    }
}

if (count($validMembers) === 0) {
    jsonOut(["ok" => false, "message" => "Pick at least one friend to add to the group."]);
}

mysqli_begin_transaction($mysqli);
try {
    $stmt = mysqli_prepare($mysqli, "INSERT INTO chat_groups (name, created_by) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "si", $name, $uid);
    mysqli_stmt_execute($stmt);
    $groupId = mysqli_insert_id($mysqli);

    $addMember = mysqli_prepare($mysqli, "INSERT INTO chat_group_members (group_id, user_id, role) VALUES (?, ?, ?)");
    $role = 'admin';
    mysqli_stmt_bind_param($addMember, "iis", $groupId, $uid, $role);
    mysqli_stmt_execute($addMember);

    $role = 'member';
    foreach ($validMembers as $mid) {
        mysqli_stmt_bind_param($addMember, "iis", $groupId, $mid, $role);
        mysqli_stmt_execute($addMember);
    }

    mysqli_commit($mysqli);
} catch (Throwable $e) {
    mysqli_rollback($mysqli);
    jsonOut(["ok" => false, "message" => "Could not create the group. Try again."]);
}

$me = currentUser();
$meName = $me ? ($me['nickname'] ?: $me['username']) : 'Someone';
foreach ($validMembers as $mid) {
    createNotification($mid, 'group_invite', $uid, $groupId, $meName . ' added you to "' . $name . '"');
}

jsonOut(["ok" => true, "id" => $groupId]);
