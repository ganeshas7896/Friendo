<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/groupchat.php";
requireLogin();
$uid = currentUserId();
$gid = (int)($_POST['group'] ?? 0);

$stmt = mysqli_prepare($mysqli, "DELETE FROM chat_group_members WHERE group_id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $gid, $uid);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    // If the group is now empty, remove it entirely.
    $left = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) AS c FROM chat_group_members WHERE group_id = " . (int)$gid));
    if ($left && (int)$left['c'] === 0) {
        mysqli_query($mysqli, "DELETE FROM chat_groups WHERE id = " . (int)$gid);
    }
    jsonOut(["ok" => true]);
} else {
    jsonOut(["ok" => false, "message" => "You're not in that group."]);
}
