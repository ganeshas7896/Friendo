-- ===========================================================
-- FRIENDO — database schema (verified against every query
-- used in api/*.php and the root PHP files)
-- Import this once in phpMyAdmin / mysql CLI before first run:
--   mysql -u root -p < database.sql
-- ===========================================================



-- ---------------------------------------------------------
-- users
-- Accounts + profile fields in one table (keeps the app simple).
-- Columns checked against: signup.php, signin.php, auth.php,
-- save-profile.php, update-profile.php, save-settings.php,
-- send-request.php, list-people.php, list-friends.php,
-- list-conversations.php, conversation.php, verify-payment.php,
-- delete-account.php
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    username          VARCHAR(50)  NOT NULL UNIQUE,
    email             VARCHAR(150) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,
    nickname          VARCHAR(100) DEFAULT '',
    gender            VARCHAR(30)  DEFAULT '',
    birth_year        VARCHAR(10)  DEFAULT '',
    nationality       VARCHAR(100) DEFAULT '',
    region            VARCHAR(100) DEFAULT '',
    self_intro        TEXT,
    extra_info        TEXT,
    privacy           VARCHAR(20)  DEFAULT 'Public',
    theme_mode        VARCHAR(10)  DEFAULT 'light',
    theme_accent      VARCHAR(10)  DEFAULT 'blue',
    is_subscribed     TINYINT(1)   DEFAULT 0,
    receive_requests  TINYINT(1)   DEFAULT 1,
    notif_requests    TINYINT(1)   DEFAULT 1,
    notif_chat        TINYINT(1)   DEFAULT 1,
    min_age           INT DEFAULT 18,
    max_age           INT DEFAULT 99,
    preferred_gender  VARCHAR(20) DEFAULT 'Both',
    interest_to_connect VARCHAR(255) DEFAULT '',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_created_at (created_at)   -- list-people.php: ORDER BY created_at DESC
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- friend_requests
-- One row per friend request. status moves pending -> accepted/declined.
-- Once accepted, the pair is "friends" and may chat.
-- Columns checked against: send-request.php, respond-request.php,
-- friendship.php (areFriends), list-people.php, list-friends.php,
-- list-conversations.php
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS friend_requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    sender_id    INT NOT NULL,
    receiver_id  INT NOT NULL,
    status       ENUM('pending','accepted','declined') DEFAULT 'pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pair (sender_id, receiver_id),
    KEY idx_receiver_status (receiver_id, status),   -- incoming requests / respond-request.php
    KEY idx_sender_status (sender_id, status),        -- outgoing requests / send-request.php reverse-lookup
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- messages
-- Chat messages. A message may only be inserted (enforced in PHP,
-- see friendship.php::areFriends) once the two users have an
-- 'accepted' row in friend_requests.
-- Columns checked against: send-message.php, get-messages.php,
-- list-conversations.php
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    sender_id    INT NOT NULL,
    receiver_id  INT NOT NULL,
    message      TEXT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sender_receiver (sender_id, receiver_id),   -- WHERE sender_id=? AND receiver_id=?
    KEY idx_receiver_sender (receiver_id, sender_id),   -- WHERE receiver_id=? AND sender_id=? (reverse direction)
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- payments
-- Razorpay one-time purchases + subscription attempts.
-- Columns checked against: create-order.php, verify-payment.php
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    order_id     VARCHAR(100) NOT NULL,
    payment_id   VARCHAR(100) DEFAULT NULL,
    amount_paise INT NOT NULL,
    plan         VARCHAR(50) NOT NULL,
    status       VARCHAR(30) DEFAULT 'created',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_order_id (order_id),   -- verify-payment.php looks up WHERE order_id = ?
    KEY idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- chat_groups / chat_group_members / chat_group_messages
-- Group chat feature (separate tab). These are also created
-- automatically at runtime by config.php::ensureExtraTables(),
-- so importing this section manually is optional.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_groups (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    created_by  INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_group_members (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    group_id   INT NOT NULL,
    user_id    INT NOT NULL,
    role       VARCHAR(10) DEFAULT 'member',
    joined_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_member (group_id, user_id),
    KEY idx_user (user_id),
    FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_group_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    group_id    INT NOT NULL,
    sender_id   INT NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_group_id (group_id, id),
    FOREIGN KEY (group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- notifications
-- Persistent notification feed: friend requests/accepts,
-- direct messages, group messages, group invites. Also
-- auto-created by config.php::ensureExtraTables().
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
