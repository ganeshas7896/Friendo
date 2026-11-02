<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();
$me = currentUser();

$minAge = max(18, (int)($me['min_age'] ?? 18));
$maxAge = max($minAge, (int)($me['max_age'] ?? 99));
$preferredGender = strtoupper(trim((string)($me['preferred_gender'] ?? 'Both')));
$interestFilter = trim(strtolower((string)($me['interest_to_connect'] ?? '')));
$interestTerms = $interestFilter !== '' ? preg_split('/[\s,]+/', $interestFilter, -1, PREG_SPLIT_NO_EMPTY) : [];

function normalizeGender($value) {
    $g = strtolower(trim((string)$value));
    if ($g === 'male' || $g === 'm') return 'male';
    if ($g === 'female' || $g === 'f') return 'female';
    return 'other';
}

function matchesGenderPreference($viewerPref, $targetGender) {
    $pref = strtoupper(trim((string)$viewerPref));
    $target = normalizeGender($targetGender);
    if ($pref === 'BOTH' || $pref === 'ANY') return true;
    if ($pref === 'MALE') return $target === 'male';
    if ($pref === 'FEMALE') return $target === 'female';
    return true;
}

function personMatchesAgeRange($birthYear, $minAge, $maxAge) {
    if ($birthYear === null || $birthYear === '' || !is_numeric($birthYear)) {
        return true;
    }
    $age = (int)date('Y') - (int)$birthYear;
    return $age >= $minAge && $age <= $maxAge;
}

function personMatchesInterest($profileInterest, $terms) {
    if (empty($terms)) return true;
    $profileInterest = strtolower(trim((string)$profileInterest));
    if ($profileInterest === '') return false;
    $values = preg_split('/[\s,]+/', $profileInterest, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($terms as $term) {
        foreach ($values as $value) {
            if (strpos($value, $term) !== false || strpos($term, $value) !== false) {
                return true;
            }
        }
    }
    return false;
}

$people = [];
$res = mysqli_query($mysqli, "SELECT id, username, nickname, gender, birth_year, nationality, self_intro, interest_to_connect, preferred_gender FROM users WHERE id != $uid ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $people[$row['id']] = $row;
}

$rel = mysqli_query($mysqli, "SELECT sender_id, receiver_id, status FROM friend_requests WHERE sender_id = $uid OR receiver_id = $uid");
$status = [];
while ($r = mysqli_fetch_assoc($rel)) {
    $otherId = $r['sender_id'] == $uid ? $r['receiver_id'] : $r['sender_id'];
    $direction = $r['sender_id'] == $uid ? 'outgoing' : 'incoming';
    $status[$otherId] = ["status" => $r['status'], "direction" => $direction];
}

$out = [];
foreach ($people as $id => $p) {
    if (!personMatchesAgeRange($p['birth_year'] ?? '', $minAge, $maxAge)) {
        continue;
    }
    if (!matchesGenderPreference($preferredGender, $p['gender'] ?? '')) {
        continue;
    }
    if (!matchesGenderPreference($p['preferred_gender'] ?? 'Both', $me['gender'] ?? '')) {
        continue;
    }
    if (!personMatchesInterest($p['interest_to_connect'] ?? '', $interestTerms)) {
        continue;
    }
    $p['relationship'] = $status[$id]['status'] ?? 'none';
    $p['direction'] = $status[$id]['direction'] ?? null;
    $out[] = $p;
}
jsonOut(["ok" => true, "people" => $out]);
