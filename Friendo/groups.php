<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$me = currentUser();
$active = "groups";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>

<link rel="stylesheet" href="style.css">
</head>
<body data-page="groups">
<div class="app has-desktop-nav">
  <?php require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <h1 class="page-title" style="margin-bottom:18px;">Groups<span class="page-badge" data-page-badge="group"></span></h1>
      <button type="button" class="btn btn-primary btn-sm" style="margin-bottom:18px;" onclick="openNewGroup()">+ New Group</button>
    </div>
    <div class="tabs" style="margin-top: -8px; margin-bottom: 10px;">
      <div class="tab active" style="cursor:default;">Groups<span class="tab-badge" data-tab-badge="group"></span></div>
    </div>
    <div id="groupsContent"><p class="muted">Loading...</p></div>
  </div>
</div>

<!-- New group dialog -->
<div class="overlay" id="newGroupDialog">
  <div class="dialog">
    <div class="dialog-header"><h3>New group</h3><button class="close" onclick="closeDialog('newGroupDialog')">&times;</button></div>
    <div style="padding:14px 16px 0 16px;">
      <div class="field" style="margin-bottom:10px;">
        <label>Group name</label>
        <input type="text" id="groupNameInput" placeholder="e.g. Weekend Trip">
      </div>
      <p class="muted" style="margin:0 0 4px 0;">Add friends (only accepted friends can be added):</p>
    </div>
    <div class="dialog-search"><input type="text" placeholder="Search friends" oninput="filterOptionRows(this, '#groupFriendList')"></div>
    <div class="dialog-body" id="groupFriendList"><p class="muted" style="padding:14px 16px;">Loading friends...</p></div>
    <div class="dialog-footer">
      <button class="btn btn-primary" style="width:100%;" onclick="submitNewGroup()">Create Group</button>
    </div>
  </div>
</div>

<script src="script.js"></script>
<script>
  let groups = [];
  let friendsForGroup = [];
  let selectedFriendIds = new Set();

  function renderGroups() {
    const el = document.getElementById('groupsContent');
    if (groups.length === 0) {
      el.innerHTML = '<div class="empty-state"><div class="emoji">&#128101;</div>' +
        '<p>No group chats yet.<br>Tap "New Group" and pick a few friends to start one.</p></div>';
      return;
    }
    el.innerHTML = '<div class="list">' + groups.map(function (g) {
      return '<a class="list-row" href="group-conversation.php?id=' + g.id + '" style="text-decoration:none;">' +
        '<div class="avatar">' + escapeHtml(g.name.charAt(0).toUpperCase()) + '</div>' +
        '<div class="info"><div class="name">' + escapeHtml(g.name) + (g.unread ? ' <span class="unread-dot"></span>' : '') + '</div>' +
        '<div class="sub">' + escapeHtml(g.lastMessage) + '</div></div>' +
        '<div class="time">' + escapeHtml(g.time) + '</div></a>';
    }).join('') + '</div>';
  }

  async function loadGroups() {
    const result = await getJSON('list-groups.php');
    groups = result.ok ? result.groups : [];
    renderGroups();
  }

  function renderFriendPicker() {
    const el = document.getElementById('groupFriendList');
    if (friendsForGroup.length === 0) {
      el.innerHTML = '<p class="muted" style="padding:14px 16px;">You need at least one accepted friend before you can start a group.</p>';
      return;
    }
    el.innerHTML = friendsForGroup.map(function (f) {
      const name = f.nickname || f.username;
      return '<label class="option-row" data-label="' + escapeHtml(name) + '">' +
        '<span>' + escapeHtml(name) + '</span>' +
        '<input type="checkbox" value="' + f.id + '" onchange="toggleFriendSelect(' + f.id + ', this.checked)"></label>';
    }).join('');
  }

  function toggleFriendSelect(id, checked) {
    if (checked) selectedFriendIds.add(id);
    else selectedFriendIds.delete(id);
  }

  async function openNewGroup() {
    document.getElementById('groupNameInput').value = '';
    selectedFriendIds = new Set();
    openDialog('newGroupDialog');
    const result = await getJSON('list-friends.php');
    friendsForGroup = result.ok ? result.friends : [];
    renderFriendPicker();
  }

  async function submitNewGroup() {
    const name = document.getElementById('groupNameInput').value.trim();
    if (!name) { alert('Please give the group a name.'); return; }
    if (selectedFriendIds.size === 0) { alert('Pick at least one friend to add.'); return; }
    const result = await postForm('create-group.php', { name: name, members: Array.from(selectedFriendIds) });
    if (result.ok) {
      closeDialog('newGroupDialog');
      window.location.href = 'group-conversation.php?id=' + result.id;
    } else {
      alert(result.message || 'Could not create the group.');
    }
  }

  loadGroups();
</script>
</body>
</html>
