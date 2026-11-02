<?php
require_once __DIR__ . "/config.php";

$identity = trim($_POST['identity'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($identity === '' || $password === '') {
    jsonOut(["ok" => false, "message" => "Enter your username/email and password."]);
}

$stmt = mysqli_prepare($mysqli, "SELECT id, password, nickname FROM users WHERE username = ? OR email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ss", $identity, $identity);
mysqli_stmt_execute($stmt);
$row = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$row || !password_verify($password, $row['password'])) {
    jsonOut(["ok" => false, "message" => "Incorrect username/email or password."]);
}

$_SESSION['user_id'] = $row['id'];
$next = $row['nickname'] ? "people.php" : "profile-setup.html";
jsonOut(["ok" => true, "next" => $next]);
