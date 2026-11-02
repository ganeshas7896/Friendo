<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

$nickname    = trim($_POST['nName'] ?? '');
$gender      = trim($_POST['gender'] ?? '');
$doby        = trim($_POST['doby'] ?? '');
$nationality = trim($_POST['national'] ?? '');
$region      = trim($_POST['region'] ?? '');

if ($nickname === '' || $gender === '' || $doby === '' || $nationality === '' || $region === '') {
    echo "Please complete every field before continuing.";
    exit;
}

$stmt = mysqli_prepare($mysqli,
    "UPDATE users SET nickname=?, gender=?, birth_year=?, nationality=?, region=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "sssssi", $nickname, $gender, $doby, $nationality, $region, $uid);

if (mysqli_stmt_execute($stmt)) {
    echo "success";
} else {
    echo "Something went wrong, your profile was not saved.";
}
