<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$me = currentUser();
$active = "messages";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>
<title>Chatting — Friendo</title>
<link rel="stylesheet" href="style.css">
</head>
<body data-page="messages">
<div class="app has-desktop-nav">
  <?php require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <h1 class="page-title">Chatting<span class="page-badge" data-page-badge="dm"></span></h1>
    <div class="tabs" style="margin-top: 0;">
      <div class="tab active" style="cursor:default;">Chatting<span class="tab-badge" data-tab-badge="dm"></span></div>
    </div>
    <div id="chatContent"><p class="muted">Loading...</p></div>
  </div>
</div>

<script src="script.js"></script>
<script>
  function render(rooms) {
    const el = document.getElementById('chatContent');
    if (rooms.length === 0) {
      el.innerHTML = '<div class="empty-state"><div class="emoji">&#128172;</div>' +
        '<p>You don\'t have a chat room yet.<br>Accept a friend request in Friends to start one.</p></div>';
    } else {
      el.innerHTML = '<div class="list">' + rooms.map(function (r) {
        return '<a class="list-row" href="conversation.php?with=' + r.id + '" style="text-decoration:none;">' +
          '<div class="avatar">' + escapeHtml(r.name.charAt(0).toUpperCase()) + '</div>' +
          '<div class="info"><div class="name">' + escapeHtml(r.name) + (r.unread ? ' <span class="unread-dot"></span>' : '') + '</div><div class="sub">' + escapeHtml(r.lastMessage) + '</div></div>' +
          '<div class="time">' + escapeHtml(r.time) + '</div></a>';
      }).join('') + '</div>';
    }
  }
  async function load() {
    const result = await getJSON('list-conversations.php');
    render(result.ok ? result.rooms : []);
  }
  load();
</script>
</body>
</html>
