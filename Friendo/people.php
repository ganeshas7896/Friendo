<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$me = currentUser();
$active = "people";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>

<link rel="stylesheet" href="style.css">
</head>
<body data-page="people">
<div class="app has-desktop-nav">
  <?php require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <h1 class="page-title">People</h1>
    <div class="list" id="peopleList"><p class="muted">Loading people...</p></div>
  </div>
</div>

<!-- Person detail dialog -->
<div class="overlay" id="personDialog">
  <div class="dialog">
    <div class="dialog-header"><h3 id="personName">Name</h3><button class="close" onclick="closeDialog('personDialog')">&times;</button></div>
    <div class="dialog-footer">
      <p class="muted" id="personSub" style="margin-bottom:16px;"></p>
      <button class="btn btn-primary" id="personActionBtn" onclick="actOnSelected()">Add Friend</button>
    </div>
  </div>
</div>

<script src="script.js"></script>
<script>
  let people = [];
  let selected = null;

  function statusLabel(p) {
    if (p.relationship === 'accepted') return { text: 'Friends', cls: 'accepted', action: null };
    if (p.relationship === 'pending' && p.direction === 'outgoing') return { text: 'Requested', cls: '', action: null };
    if (p.relationship === 'pending' && p.direction === 'incoming') return { text: 'Respond in Friends', cls: '', action: null };
    return { text: '', cls: '', action: 'request' };
  }

  function render() {
    const listEl = document.getElementById('peopleList');
    if (people.length === 0) {
      listEl.innerHTML = '<div class="empty-state"><div class="emoji">&#128101;</div><p>No one else has joined yet.<br>Invite a friend to get started!</p></div>';
      return;
    }
    listEl.innerHTML = people.map(function (p, i) {
      const s = statusLabel(p);
      const name = p.nickname || p.username;
      const sub = [p.gender, p.birth_year].filter(Boolean).join(' \u00b7 ') || 'Friendo member';
      return '<div class="list-row" onclick="showPerson(' + i + ')">' +
        '<div class="avatar">' + escapeHtml(name.charAt(0).toUpperCase()) + '</div>' +
        '<div class="info"><div class="name">' + escapeHtml(name) + '</div><div class="sub">' + escapeHtml(sub) + '</div></div>' +
        (s.text ? '<span class="pill-status ' + s.cls + '">' + s.text + '</span>' : '') +
        '</div>';
    }).join('');
  }

  function showPerson(i) {
    selected = people[i];
    const name = selected.nickname || selected.username;
    document.getElementById('personName').textContent = name;
    document.getElementById('personSub').textContent = (selected.self_intro || 'No self-introduction yet.');
    const s = statusLabel(selected);
    const btn = document.getElementById('personActionBtn');
    if (s.action === 'request') {
      btn.style.display = 'block';
      btn.textContent = 'Send Friend Request';
    } else {
      btn.style.display = 'none';
    }
    openDialog('personDialog');
  }

  async function actOnSelected() {
    if (!selected) return;
    const result = await postForm('send-request.php', { to: selected.id });
    if (result.ok) {
      showToast(result.message || 'Friend request sent.');
    } else {
      alert(result.message || 'Something went wrong.');
    }
    closeDialog('personDialog');
    loadPeople();
  }

  async function loadPeople() {
    const result = await getJSON('list-people.php');
    people = result.ok ? result.people : [];
    render();
  }
  loadPeople();
</script>
</body>
</html>
