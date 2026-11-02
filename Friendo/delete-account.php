<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

$stmt = mysqli_prepare($mysqli, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);

$_SESSION = [];
session_destroy();
jsonOut(["ok" => true]);
