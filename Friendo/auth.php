<?php
require_once __DIR__ . "/config.php";

/**
 * Call this at the very top of any page that requires a signed-in user.
 * If nobody is signed in, it sends them to signin.html.
 */
function requireLogin() {
    if (!currentUserId()) {
        header("Location: signin.html?next=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Fetch the full row for the signed-in user.
 */
function currentUser() {
    global $mysqli;
    $uid = currentUserId();
    if (!$uid) return null;
    $stmt = mysqli_prepare($mysqli, "SELECT * FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res);
}
