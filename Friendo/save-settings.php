<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$uid = currentUserId();

$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

$allowed = [
    'receive_requests'   => 'i',
    'notif_requests'     => 'i',
    'notif_chat'         => 'i',
    'theme_mode'         => 's',
    'theme_accent'       => 's',
    'min_age'            => 'i',
    'max_age'            => 'i',
    'preferred_gender'   => 's',
    'interest_to_connect' => 's',
];
if (!array_key_exists($field, $allowed)) {
    jsonOut(["ok" => false, "message" => "Unknown setting."]);
}

$type = $allowed[$field];
if ($type === 'i') {
    if ($field === 'min_age' || $field === 'max_age') {
        $value = (int) $value;
        $value = max(18, min(80, $value));
    } else {
        $value = $value ? 1 : 0;
    }
} elseif ($field === 'theme_mode') {
    $value = in_array($value, ['light', 'dark'], true) ? $value : 'light';
} elseif ($field === 'theme_accent') {
    $value = in_array($value, ['blue', 'red', 'gold'], true) ? $value : 'blue';
} elseif ($field === 'preferred_gender') {
    $value = in_array($value, ['Male', 'Female', 'Both'], true) ? $value : 'Both';
} elseif ($field === 'interest_to_connect') {
    $value = trim((string) $value);
}

if ($field === 'min_age' || $field === 'max_age') {
    $current = currentUser();
    $currentMin = max(18, (int)($current['min_age'] ?? 18));
    $currentMax = max(18, (int)($current['max_age'] ?? 99));
    if ($field === 'min_age' && $value > $currentMax) {
        $value = $currentMax;
    }
    if ($field === 'max_age' && $value < $currentMin) {
        $value = $currentMin;
    }
    if ($field === 'min_age' && $value > (int)($current['max_age'] ?? 99)) {
        $value = (int)($current['max_age'] ?? 99);
    }
    if ($field === 'max_age' && $value < (int)($current['min_age'] ?? 18)) {
        $value = (int)($current['min_age'] ?? 18);
    }
    if ($field === 'min_age' && $value > $value) {
        $value = $value;
    }
}

$stmt = mysqli_prepare($mysqli, "UPDATE users SET `$field` = ? WHERE id = ?");
if ($type === 'i') {
    mysqli_stmt_bind_param($stmt, "ii", $value, $uid);
} else {
    mysqli_stmt_bind_param($stmt, "si", $value, $uid);
}
jsonOut(["ok" => (bool)mysqli_stmt_execute($stmt)]);
