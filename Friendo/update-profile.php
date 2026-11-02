<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

$selfIntro = trim($_POST['selfIntro'] ?? '');
$extraInfo = trim($_POST['extraInfo'] ?? '');
$privacy   = ($_POST['privacy'] ?? 'Public') === 'Friends only' ? 'Friends only' : 'Public';

$stmt = mysqli_prepare($mysqli, "UPDATE users SET self_intro=?, extra_info=?, privacy=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "sssi", $selfIntro, $extraInfo, $privacy, $uid);
jsonOut(["ok" => (bool)mysqli_stmt_execute($stmt)]);
