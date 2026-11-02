<?php
/**
 * Small helpers shared by the group-chat endpoints.
 * Mirrors the style of friendship.php (single choke-point functions).
 */

function isGroupMember($mysqli, $groupId, $userId) {
    $stmt = mysqli_prepare($mysqli, "SELECT id FROM chat_group_members WHERE group_id = ? AND user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $groupId, $userId);
    mysqli_stmt_execute($stmt);
    return (bool) mysqli_stmt_get_result($stmt)->fetch_assoc();
}

function groupInfo($mysqli, $groupId) {
    $stmt = mysqli_prepare($mysqli, "SELECT id, name, created_by FROM chat_groups WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $groupId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt)->fetch_assoc();
}

/** All member user ids for a group. */
function groupMemberIds($mysqli, $groupId) {
    $stmt = mysqli_prepare($mysqli, "SELECT user_id FROM chat_group_members WHERE group_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $groupId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($row = mysqli_fetch_assoc($res)) $ids[] = (int)$row['user_id'];
    return $ids;
}

/** Member rows (id, username, nickname, role) for a group. */
function groupMembers($mysqli, $groupId) {
    $stmt = mysqli_prepare($mysqli, "SELECT u.id, u.username, u.nickname, m.role
        FROM chat_group_members m JOIN users u ON u.id = m.user_id
        WHERE m.group_id = ? ORDER BY (m.role = 'admin') DESC, u.nickname, u.username");
    mysqli_stmt_bind_param($stmt, "i", $groupId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $out = [];
    while ($row = mysqli_fetch_assoc($res)) $out[] = $row;
    return $out;
}
