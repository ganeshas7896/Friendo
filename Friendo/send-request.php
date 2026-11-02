<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();
$to = (int)($_POST['to'] ?? 0);

if ($to <= 0 || $to === $uid) {
    jsonOut(["ok" => false, "message" => "Invalid recipient."]);
}

// Does the other person accept friend requests?
$check = mysqli_prepare($mysqli, "SELECT receive_requests FROM users WHERE id = ?");
mysqli_stmt_bind_param($check, "i", $to);
mysqli_stmt_execute($check);
$target = mysqli_stmt_get_result($check)->fetch_assoc();
if (!$target) jsonOut(["ok" => false, "message" => "User not found."]);
if (!$target['receive_requests']) jsonOut(["ok" => false, "message" => "This user isn't accepting friend requests right now."]);

// If they already sent US a pending request, sending one back just accepts theirs.
$rev = mysqli_prepare($mysqli, "SELECT id, status FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
mysqli_stmt_bind_param($rev, "ii", $to, $uid);
mysqli_stmt_execute($rev);
$reverse = mysqli_stmt_get_result($rev)->fetch_assoc();

$me = currentUser();
$meName = $me ? ($me['nickname'] ?: $me['username']) : 'Someone';

if ($reverse) {
    if ($reverse['status'] === 'pending') {
        $upd = mysqli_prepare($mysqli, "UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
        mysqli_stmt_bind_param($upd, "i", $reverse['id']);
        mysqli_stmt_execute($upd);
        createNotification($to, 'friend_accept', $uid, null, $meName . ' accepted your friend request.');
        jsonOut(["ok" => true, "message" => "You're now friends!", "status" => "accepted"]);
    }
    jsonOut(["ok" => true, "message" => "Request already exists.", "status" => $reverse['status']]);
}

$stmt = mysqli_prepare($mysqli, "INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')
    ON DUPLICATE KEY UPDATE status = IF(status = 'declined', 'pending', status)");
mysqli_stmt_bind_param($stmt, "ii", $uid, $to);
if (mysqli_stmt_execute($stmt)) {
    createNotification($to, 'friend_request', $uid, null, $meName . ' sent you a friend request.');
    jsonOut(["ok" => true, "message" => "Friend request sent.", "status" => "pending"]);
} else {
    jsonOut(["ok" => false, "message" => "Could not send the request. Try again."]);
}
