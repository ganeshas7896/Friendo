<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$me = currentUser();
$active = "contact";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>
<title>Contact Friendo</title>
<link rel="stylesheet" href="style.css">
</head>
<body data-page="contact">
<div class="app has-desktop-nav">
  <?php $backHref = "settings.php"; require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <h1 class="page-title">Contact Friendo</h1>

    <div class="tabs">
      <div class="tab active" id="tab-bug" onclick="showForm('bug')">Report Bugs</div>
      <div class="tab" id="tab-support" onclick="showForm('support')">Contact Support</div>
    </div>

    <form id="contactForm" onsubmit="return submitForm(event)">
      <div class="field"><label>Name</label><input type="text" id="cName" value="<?php echo htmlspecialchars($me['nickname'] ?: $me['username']); ?>" required></div>
      <div class="field"><label>Email</label><input type="email" id="cEmail" value="<?php echo htmlspecialchars($me['email']); ?>" required></div>
      <div class="field"><label>Message</label><textarea id="msgField" required></textarea></div>
      <button class="btn btn-primary" type="submit">Send</button>
    </form>
    <p class="muted" id="sentNote" style="display:none;">Thanks — your message has been sent to the Friendo team.</p>
  </div>
</div>

<script src="script.js"></script>
<script>
  function showForm(kind) {
    document.getElementById('tab-bug').classList.toggle('active', kind === 'bug');
    document.getElementById('tab-support').classList.toggle('active', kind === 'support');
    document.getElementById('msgField').placeholder = kind === 'bug' ? 'Describe the bug you found...' : 'How can we help?';
  }
  function submitForm(e) {
    e.preventDefault();
    document.getElementById('contactForm').reset();
    document.getElementById('sentNote').style.display = 'block';
    return false;
  }
  showForm('bug');
</script>
</body>
</html>
