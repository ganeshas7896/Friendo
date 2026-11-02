<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();
$fromId = (int)($_POST['from'] ?? 0);
$action = $_POST['action'] ?? '';  // 'accept' or 'decline'

if (!in_array($action, ['accept', 'decline'], true) || $fromId <= 0) {
    jsonOut(["ok" => false, "message" => "Invalid request."]);
}

$status = $action === 'accept' ? 'accepted' : 'declined';
$stmt = mysqli_prepare($mysqli, "UPDATE friend_requests SET status = ? WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, "sii", $status, $fromId, $uid);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    if ($status === 'accepted') {
        $me = currentUser();
        $meName = $me ? ($me['nickname'] ?: $me['username']) : 'Someone';
        createNotification($fromId, 'friend_accept', $uid, null, $meName . ' accepted your friend request.');
    }
    jsonOut(["ok" => true, "status" => $status]);
} else {
    jsonOut(["ok" => false, "message" => "That request is no longer pending."]);
}
