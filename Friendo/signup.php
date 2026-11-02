<?php
require_once __DIR__ . "/config.php";

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    jsonOut(["ok" => false, "message" => "Please fill in username, email and password."]);
}
if (strlen($password) < 6) {
    jsonOut(["ok" => false, "message" => "Password must be at least 6 characters."]);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonOut(["ok" => false, "message" => "Please enter a valid email address."]);
}

$check = mysqli_prepare($mysqli, "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
mysqli_stmt_bind_param($check, "ss", $username, $email);
mysqli_stmt_execute($check);
if (mysqli_stmt_get_result($check)->fetch_assoc()) {
    jsonOut(["ok" => false, "message" => "That username or email is already registered — try signing in instead."]);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($mysqli, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hash);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['user_id'] = mysqli_insert_id($mysqli);
    jsonOut(["ok" => true, "next" => "terms.html"]);
} else {
    jsonOut(["ok" => false, "message" => "Could not create your account. Please try again."]);
}
