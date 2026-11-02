<?php
/* ===========================================================
   FRIENDO — site configuration
   =========================================================== */

// ---------- Database ----------
// InfinityFree usually uses a host like sql111.infinityfree.com
// and a MySQL username/database name generated in cPanel.
$DB_HOST = getenv("FRIENDO_DB_HOST") ?: "sql111.infinityfree.com";
$DB_USER = getenv("FRIENDO_DB_USER") ?: "if0_42867828";
$DB_PASS = getenv("FRIENDO_DB_PASS") ?: "Asganesh123";
$DB_NAME = getenv("FRIENDO_DB_NAME") ?: "if0_42867828_friendo";

define('DB_PORT', (int) (getenv('FRIENDO_DB_PORT') ?: 3306));

// ---------- Site ----------
// Example: https://yourdomain.com or https://yourdomain.com/Friendo
define(
    "SITE_URL",
    rtrim(
        getenv("FRIENDO_SITE_URL") ?: "https://frindo.gt.tc",
        "/"
    )
);

// ---------- Razorpay ----------
define("RAZORPAY_KEY_ID", "rzp_test_YOUR_KEY_ID");
define("RAZORPAY_KEY_SECRET", "YOUR_KEY_SECRET");

// ₹29 = 2900 paise
define("RAZORPAY_MONTHLY_AMOUNT_PAISE", 2900);

// ===================== Nothing below this line should need editing =====================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = @mysqli_connect(
    $DB_HOST,
    $DB_USER,
    $DB_PASS,
    $DB_NAME,
    $DB_PORT
);

if (!$mysqli) {
    http_response_code(503);
    die(
        "Database connection failed. Check the public hosting database settings and make sure database.sql has been imported to the live database."
    );
}

mysqli_set_charset($mysqli, "utf8mb4");

function ensureUserColumns() {
    global $mysqli;
    $cols = [];
    $res = mysqli_query($mysqli, "SHOW COLUMNS FROM users");
    while ($row = mysqli_fetch_assoc($res)) {
        $cols[] = $row['Field'];
    }

    $add = [
        'min_age' => 'INT DEFAULT 18',
        'max_age' => 'INT DEFAULT 99',
        'preferred_gender' => 'VARCHAR(20) DEFAULT "Both"',
        'interest_to_connect' => 'VARCHAR(255) DEFAULT ""',
    ];

    foreach ($add as $name => $definition) {
        if (!in_array($name, $cols, true)) {
            mysqli_query($mysqli, "ALTER TABLE users ADD COLUMN `$name` $definition");
        }
    }
}

ensureUserColumns();

/* ---------------------------------------------------------
   Extra tables for: Group Chat + Notifications
   Added additively — nothing above this touches existing
   tables/columns, so every original feature keeps working.
   --------------------------------------------------------- */
function ensureExtraTables() {
    global $mysqli;

    mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS chat_groups (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(100) NOT NULL,
        created_by  INT NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS chat_group_members (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        group_id   INT NOT NULL,
        user_id    INT NOT NULL,
        role       VARCHAR(10) DEFAULT 'member',
        joined_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_member (group_id, user_id),
        KEY idx_user (user_id),
        FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS chat_group_messages (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        group_id    INT NOT NULL,
        sender_id   INT NOT NULL,
        message     TEXT NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_group_id (group_id, id),
        FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mysqli_query($mysqli, "CREATE TABLE IF NOT EXISTS notifications (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        type        VARCHAR(30) NOT NULL,
        actor_id    INT DEFAULT NULL,
        ref_id      INT DEFAULT NULL,
        message     VARCHAR(255) NOT NULL,
        is_read     TINYINT(1) DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user_read (user_id, is_read),
        KEY idx_user_type (user_id, type),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
ensureExtraTables();

/**
 * Create a notification row for $userId, unless $userId is muted for that
 * category (reuses the existing notif_requests / notif_chat toggles from
 * Settings, so those switches finally do something) or the actor is the
 * same as the recipient (never notify yourself).
 *
 * $type   one of: friend_request, friend_accept, message, group_message, group_invite
 * $refId  optional secondary id (e.g. the group id for group_* types)
 */
function createNotification($userId, $type, $actorId, $refId, $message) {
    global $mysqli;
    $userId = (int)$userId;
    if ($actorId !== null && (int)$actorId === $userId) return;

    $pref = mysqli_prepare($mysqli, "SELECT notif_requests, notif_chat FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($pref, "i", $userId);
    mysqli_stmt_execute($pref);
    $row = mysqli_stmt_get_result($pref)->fetch_assoc();
    if (!$row) return;

    $isSocial = in_array($type, ['friend_request', 'friend_accept'], true);
    $isChat   = in_array($type, ['message', 'group_message', 'group_invite'], true);
    if ($isSocial && !$row['notif_requests']) return;
    if ($isChat && !$row['notif_chat']) return;

    $stmt = mysqli_prepare($mysqli, "INSERT INTO notifications (user_id, type, actor_id, ref_id, message) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isiis", $userId, $type, $actorId, $refId, $message);
    mysqli_stmt_execute($stmt);
}

function jsonOut($arr) {
    header("Content-Type: application/json");
    echo json_encode($arr);
    exit;
}

function currentUserId() {
    return isset($_SESSION['user_id'])
        ? (int)$_SESSION['user_id']
        : null;
}