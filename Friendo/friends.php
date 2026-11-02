<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$me = currentUser();
$active = "friends";

// Visiting Friends clears the friend-request / friend-accept notifications.
mysqli_query($mysqli, "UPDATE notifications SET is_read = 1 WHERE user_id = " . currentUserId() . " AND type IN ('friend_request','friend_accept')");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>

<link rel="stylesheet" href="style.css">
</head>
<body data-page="friends">
<div class="app has-desktop-nav">
  <?php require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <h1 class="page-title">Friends<span class="page-badge" data-page-badge="social"></span></h1>
    <div class="tabs">
      <div class="tab active" id="tab-friends" onclick="showTab('friends')">Friends<span class="tab-badge" data-tab-badge="social"></span></div>
      <div class="tab" id="tab-sent" onclick="showTab('sent')">Sent</div>
      <div class="tab" id="tab-received" onclick="showTab('received')">Received</div>
    </div>
    <div id="friendsContent"><p class="muted">Loading...</p></div>
  </div>
</div>

<script src="script.js"></script>
<script>
  let currentTab = 'friends';
  let data = { friends: [], sent: [], received: [] };

  function showTab(tab) {
    currentTab = tab;
    ['friends', 'sent', 'received'].forEach(function (t) {
      document.getElementById('tab-' + t).classList.toggle('active', t === tab);
    });
    render();
  }

  function render() {
    const list = data[currentTab] || [];
    const el = document.getElementById('friendsContent');
    if (list.length === 0) {
      const msg = currentTab === 'friends' ? 'No friends yet.<br>Add people in People, then wait for them to accept.'
        : currentTab === 'sent' ? 'No pending sent requests.'
        : 'No incoming requests right now.';
      el.innerHTML = '<div class="empty-state"><div class="emoji">&#128100;</div><p>' + msg + '</p></div>';
      return;
    }
    el.innerHTML = '<div class="list">' + list.map(function (f) {
      const name = f.nickname || f.username;
      const sub = [f.gender, f.birth_year].filter(Boolean).join(' \u00b7 ') || 'Friendo member';
      let actions = '';
      if (currentTab === 'received') {
        actions = '<div class="actions">' +
          '<button class="btn btn-primary btn-sm" onclick="respond(' + f.id + ',\'accept\')">Accept</button>' +
          '<button class="btn btn-outline btn-sm" onclick="respond(' + f.id + ',\'decline\')">Decline</button></div>';
      } else if (currentTab === 'friends') {
        actions = '<a class="btn btn-secondary btn-sm" href="conversation.php?with=' + f.id + '">Chat</a>';
      } else {
        actions = '<span class="pill-status">Pending</span>';
      }
      return '<div class="list-row"><div class="avatar">' + escapeHtml(name.charAt(0).toUpperCase()) + '</div>' +
        '<div class="info"><div class="name">' + escapeHtml(name) + '</div><div class="sub">' + escapeHtml(sub) + '</div></div>' + actions + '</div>';
    }).join('') + '</div>';
  }

  async function respond(fromId, action) {
    const result = await postForm('respond-request.php', { from: fromId, action: action });
    if (!result.ok) alert(result.message || 'Could not update that request.');
    load();
  }

  async function load() {
    const result = await getJSON('list-friends.php');
    if (result.ok) data = result;
    render();
  }
  load();
</script>
</body>
</html>
