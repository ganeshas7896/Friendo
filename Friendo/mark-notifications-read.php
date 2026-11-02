<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

$scope = $_POST['scope'] ?? 'all'; // all | conversation | group | social

if ($scope === 'conversation') {
    $withId = (int)($_POST['with'] ?? 0);
    $stmt = mysqli_prepare($mysqli, "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = 'message' AND actor_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $uid, $withId);
    mysqli_stmt_execute($stmt);
} elseif ($scope === 'group') {
    $gid = (int)($_POST['group'] ?? 0);
    $stmt = mysqli_prepare($mysqli, "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type IN ('group_message','group_invite') AND ref_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $uid, $gid);
    mysqli_stmt_execute($stmt);
} elseif ($scope === 'social') {
    $stmt = mysqli_prepare($mysqli, "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type IN ('friend_request','friend_accept')");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
} else {
    $stmt = mysqli_prepare($mysqli, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
}

jsonOut(["ok" => true]);
