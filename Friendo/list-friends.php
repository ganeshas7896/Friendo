<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

function fetchUsers($mysqli, $ids) {
    if (empty($ids)) return [];
    $ids = array_map('intval', $ids);
    $in = implode(',', $ids);
    $res = mysqli_query($mysqli, "SELECT id, username, nickname, gender, birth_year FROM users WHERE id IN ($in)");
    $out = [];
    while ($row = mysqli_fetch_assoc($res)) $out[$row['id']] = $row;
    return $out;
}

$friends = []; $sent = []; $received = [];

$res = mysqli_query($mysqli, "SELECT sender_id, receiver_id, status FROM friend_requests WHERE sender_id = $uid OR receiver_id = $uid");
$rows = [];
while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;

$ids = [];
foreach ($rows as $r) $ids[] = $r['sender_id'] == $uid ? $r['receiver_id'] : $r['sender_id'];
$users = fetchUsers($mysqli, $ids);

foreach ($rows as $r) {
    $otherId = $r['sender_id'] == $uid ? $r['receiver_id'] : $r['sender_id'];
    $u = $users[$otherId] ?? null;
    if (!$u) continue;
    if ($r['status'] === 'accepted') {
        $friends[] = $u;
    } elseif ($r['status'] === 'pending' && $r['sender_id'] == $uid) {
        $sent[] = $u;
    } elseif ($r['status'] === 'pending' && $r['receiver_id'] == $uid) {
        $received[] = $u;
    }
}

jsonOut(["ok" => true, "friends" => array_values($friends), "sent" => array_values($sent), "received" => array_values($received)]);
