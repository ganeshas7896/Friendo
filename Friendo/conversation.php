<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/friendship.php";
requireLogin();
$me = currentUser();
$uid = currentUserId();
$active = "messages";
$withId = (int)($_GET['with'] ?? 0);

$other = null;
if ($withId > 0) {
    $stmt = mysqli_prepare($mysqli, "SELECT id, username, nickname FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $withId);
    mysqli_stmt_execute($stmt);
    $other = mysqli_stmt_get_result($stmt)->fetch_assoc();
}
$isFriend = $other ? areFriends($mysqli, $uid, $withId) : false;
$otherName = $other ? ($other['nickname'] ?: $other['username']) : 'Unknown';

// Opening this conversation clears its unread message notifications.
if ($isFriend) {
    mysqli_query($mysqli, "UPDATE notifications SET is_read = 1 WHERE user_id = $uid AND type = 'message' AND actor_id = $withId");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>
<title><?php echo htmlspecialchars($otherName); ?> — Friendo</title>
<link rel="stylesheet" href="style.css">
</head>
<body data-page="messages">
<div class="app has-desktop-nav">
  <?php $backHref = "messages.php"; require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <h1 class="page-title"><?php echo htmlspecialchars($otherName); ?></h1>

    <?php if (!$other): ?>
      <div class="chat-locked">That user could not be found.</div>
    <?php elseif (!$isFriend): ?>
      <div class="chat-locked">
        You're not friends with <?php echo htmlspecialchars($otherName); ?> yet.<br>
        Send a friend request from People and wait for it to be accepted — chat unlocks automatically once it is.
      </div>
    <?php else: ?>
      <div class="chat-window" id="chatWindow"><p class="muted">Loading messages...</p></div>
    <?php endif; ?>
  </div>

  <?php if ($other && $isFriend): ?>
  <div class="page-content" style="padding-top:0;">
    <div class="chat-input-row">
      <div class="chat-composer">
        <textarea id="msgInput" rows="1" placeholder="Type a message..." onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault(); sendMsg();}"></textarea>
        <button type="button" onclick="sendMsg()" aria-label="Send message">
          <span>➤</span>
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script src="script.js"></script>
<?php if ($other && $isFriend): ?>
<script>
  const withId = <?php echo (int)$withId; ?>;
  let pollTimer = null;

  function renderMessages(messages) {
    const el = document.getElementById('chatWindow');
    if (messages.length === 0) {
      el.innerHTML = '<div class="empty-state"><div class="emoji">&#128075;</div><p>Say hello to start the conversation!</p></div>';
      return;
    }
    el.innerHTML = messages.map(function (m) {
      return '<div class="bubble ' + (m.mine ? 'mine' : 'theirs') + '">' + escapeHtml(m.message) +
        '<span class="bubble-time">' + m.time + '</span></div>';
    }).join('');
    el.scrollTop = el.scrollHeight;
  }

  function escapeHtml(s) {
    return s.replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
    });
  }

  async function loadMessages() {
    const result = await getJSON('get-messages.php?with=' + withId);
    if (result.ok) renderMessages(result.messages);
  }

  function autoResizeInput() {
    const input = document.getElementById('msgInput');
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 140) + 'px';
  }

  async function sendMsg() {
    const input = document.getElementById('msgInput');
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    autoResizeInput();
    const result = await postForm('send-message.php', { to: withId, message: text });
    if (!result.ok) {
      alert(result.message || 'Message could not be sent.');
    } else {
      showToast('Message sent.');
    }
    loadMessages();
  }

  document.getElementById('msgInput').addEventListener('input', autoResizeInput);

  loadMessages();
  autoResizeInput();
  pollTimer = setInterval(loadMessages, 4000);
</script>
<?php endif; ?>
</body>
</html>
