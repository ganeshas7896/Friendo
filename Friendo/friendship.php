<?php
/**
 * True if $a and $b have an ACCEPTED friend_requests row (either direction).
 * This is the single choke point that enforces "must accept before chat".
 */
function areFriends($mysqli, $a, $b) {
    $stmt = mysqli_prepare($mysqli,
        "SELECT id FROM friend_requests
         WHERE status = 'accepted'
           AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
         LIMIT 1");
    mysqli_stmt_bind_param($stmt, "iiii", $a, $b, $b, $a);
    mysqli_stmt_execute($stmt);
    return (bool) mysqli_stmt_get_result($stmt)->fetch_assoc();
}
