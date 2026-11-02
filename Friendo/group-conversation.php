<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/groupchat.php";
requireLogin();
$me = currentUser();
$uid = currentUserId();
$active = "groups";
$gid = (int)($_GET['id'] ?? 0);

$group = $gid > 0 ? groupInfo($mysqli, $gid) : null;
$isMember = $group ? isGroupMember($mysqli, $gid, $uid) : false;
$members = $isMember ? groupMembers($mysqli, $gid) : [];
$groupName = $group ? $group['name'] : 'Unknown group';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>
<title><?php echo htmlspecialchars($groupName); ?> </title>
<link rel="stylesheet" href="style.css">
</head>
<body data-page="groups">
<div class="app has-desktop-nav">
  <?php $backHref = "groups.php"; require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <h1 class="page-title" style="margin-bottom:8px;"><?php echo htmlspecialchars($groupName); ?></h1>
      <?php if ($isMember): ?>
      <button type="button" class="btn btn-outline btn-sm" style="margin-bottom:8px;" onclick="openDialog('groupInfoDialog')">Members</button>
      <?php endif; ?>
    </div>

    <?php if (!$group): ?>
      <div class="chat-locked">That group could not be found.</div>
    <?php elseif (!$isMember): ?>
      <div class="chat-locked">You're not a member of this group.</div>
    <?php else: ?>
      <p class="muted" style="margin-top:-14px;"><?php echo count($members); ?> member<?php echo count($members) === 1 ? '' : 's'; ?></p>
      <div class="chat-window" id="chatWindow"><p class="muted">Loading messages...</p></div>
    <?php endif; ?>
  </div>

  <?php if ($group && $isMember): ?>
  <div class="page-content" style="padding-top:0;">
    <div class="chat-input-row">
      <div class="chat-composer">
        <textarea id="msgInput" rows="1" placeholder="Message the group..." onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault(); sendMsg();}"></textarea>
        <button type="button" onclick="sendMsg()" aria-label="Send message">
          <span>➤</span>
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if ($group && $isMember): ?>
<!-- Members / add-friends dialog -->
<div class="overlay" id="groupInfoDialog">
  <div class="dialog">
    <div class="dialog-header"><h3>Group members</h3><button class="close" onclick="closeDialog('groupInfoDialog')">&times;</button></div>
    <div class="dialog-body">
      <?php foreach ($members as $m): $n = $m['nickname'] ?: $m['username']; ?>
        <div class="option-row" data-label="<?php echo htmlspecialchars($n); ?>">
          <span><?php echo htmlspecialchars($n); ?><?php echo $m['role'] === 'admin' ? ' &middot; admin' : ''; ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="dialog-footer" style="display:flex; gap:10px;">
      <button class="btn btn-secondary" style="flex:1;" onclick="openAddMembers()">Add friends</button>
      <button class="btn btn-outline" style="flex:1;" onclick="doLeaveGroup()">Leave group</button>
    </div>
  </div>
</div>

<!-- Add members dialog -->
<div class="overlay" id="addMembersDialog">
  <div class="dialog">
    <div class="dialog-header"><h3>Add friends</h3><button class="close" onclick="closeDialog('addMembersDialog')">&times;</button></div>
    <div class="dialog-search"><input type="text" placeholder="Search friends" oninput="filterOptionRows(this, '#addMembersList')"></div>
    <div class="dialog-body" id="addMembersList"><p class="muted" style="padding:14px 16px;">Loading friends...</p></div>
    <div class="dialog-footer">
      <button class="btn btn-primary" style="width:100%;" onclick="submitAddMembers()">Add to group</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="script.js"></script>
<?php if ($group && $isMember): ?>
<script>
  const groupId = <?php echo (int)$gid; ?>;
  const existingMemberIds = <?php echo json_encode(array_map(function ($m) { return (int)$m['id']; }, $members)); ?>;
  let pollTimer = null;
  let addSelectedIds = new Set();

  function renderMessages(messages) {
    const el = document.getElementById('chatWindow');
    if (messages.length === 0) {
      el.innerHTML = '<div class="empty-state"><div class="emoji">&#128075;</div><p>Say hello to start the group conversation!</p></div>';
      return;
    }
    el.innerHTML = messages.map(function (m) {
      const label = m.mine ? '' : '<div style="font-size:11.5px;color:var(--text-soft);margin:0 0 2px 2px;">' + escapeHtml(m.senderName) + '</div>';
      return label + '<div class="bubble ' + (m.mine ? 'mine' : 'theirs') + '">' + escapeHtml(m.message) +
        '<span class="bubble-time">' + m.time + '</span></div>';
    }).join('');
    el.scrollTop = el.scrollHeight;
  }

  async function loadMessages() {
    const result = await getJSON('get-group-messages.php?id=' + groupId);
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
    const result = await postForm('send-group-message.php', { group: groupId, message: text });
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

  async function openAddMembers() {
    closeDialog('groupInfoDialog');
    addSelectedIds = new Set();
    openDialog('addMembersDialog');
    const result = await getJSON('list-friends.php');
    const friends = (result.ok ? result.friends : []).filter(function (f) { return existingMemberIds.indexOf(f.id) === -1; });
    const el = document.getElementById('addMembersList');
    if (friends.length === 0) {
      el.innerHTML = '<p class="muted" style="padding:14px 16px;">All your friends are already in this group.</p>';
      return;
    }
    el.innerHTML = friends.map(function (f) {
      const name = f.nickname || f.username;
      return '<label class="option-row" data-label="' + escapeHtml(name) + '">' +
        '<span>' + escapeHtml(name) + '</span>' +
        '<input type="checkbox" value="' + f.id + '" onchange="toggleAddSelect(' + f.id + ', this.checked)"></label>';
    }).join('');
  }

  function toggleAddSelect(id, checked) {
    if (checked) addSelectedIds.add(id);
    else addSelectedIds.delete(id);
  }

  async function submitAddMembers() {
    if (addSelectedIds.size === 0) { alert('Pick at least one friend.'); return; }
    const result = await postForm('add-group-members.php', { group: groupId, members: Array.from(addSelectedIds) });
    if (result.ok) {
      closeDialog('addMembersDialog');
      window.location.reload();
    } else {
      alert(result.message || 'Could not add members.');
    }
  }

  async function doLeaveGroup() {
    if (!confirm('Leave this group?')) return;
    const result = await postForm('leave-group.php', { group: groupId });
    if (result.ok) {
      window.location.href = 'groups.php';
    } else {
      alert(result.message || 'Could not leave the group.');
    }
  }
</script>
<?php endif; ?>
</body>
</html>
