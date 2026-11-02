<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$me = currentUser();
$active = "profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>

<link rel="stylesheet" href="style.css">
</head>
<body data-page="profile">
<div class="app has-desktop-nav">
  <?php require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <h1 class="page-title">Profile</h1>

    <div style="display:flex;flex-direction:column;align-items:center;margin-bottom:8px;">
      <div class="avatar" style="width:72px;height:72px;font-size:24px;">
        <?php echo htmlspecialchars(strtoupper(substr($me['nickname'] ?: $me['username'], 0, 1))); ?>
      </div>
    </div>

    <h2 class="section-title">Basic Information</h2>
    <div class="row-list">
      <div class="row"><span class="row-label">Nickname</span><span class="row-value"><?php echo htmlspecialchars($me['nickname'] ?: '-'); ?></span></div>
      <div class="row"><span class="row-label">Gender</span><span class="row-value"><?php echo htmlspecialchars($me['gender'] ?: '-'); ?></span></div>
      <div class="row"><span class="row-label">Birth Year</span><span class="row-value"><?php echo htmlspecialchars($me['birth_year'] ?: '-'); ?></span></div>
      <div class="row"><span class="row-label">Country</span><span class="row-value"><?php echo htmlspecialchars($me['nationality'] ?: '-'); ?></span></div>
      <div class="row"><span class="row-label">Region</span><span class="row-value"><?php echo htmlspecialchars($me['region'] ?: '-'); ?></span></div>
    </div>
    <p class="muted" style="margin-top:8px;"><a href="profile-setup.html">Edit basic info &rsaquo;</a></p>

    <h2 class="section-title">Connection Preferences</h2>
    <div class="row-list">
      <div class="row"><span class="row-label">Min age</span><span class="row-value"><?php echo htmlspecialchars((string)($me['min_age'] ?? 18)); ?></span></div>
      <div class="row"><span class="row-label">Max age</span><span class="row-value"><?php echo htmlspecialchars((string)($me['max_age'] ?? 99)); ?></span></div>
      <div class="row"><span class="row-label">Chat with</span><span class="row-value"><?php echo htmlspecialchars(($me['preferred_gender'] ?? 'Both') ?: 'Both'); ?></span></div>
      <div class="row"><span class="row-label">Interest to connect</span><span class="row-value"><?php echo htmlspecialchars($me['interest_to_connect'] ?: 'Not set'); ?></span></div>
    </div>

    <h2 class="section-title">Self-introduction</h2>
    <div class="field"><textarea id="selfIntro" placeholder="Enter your self-introduction."><?php echo htmlspecialchars($me['self_intro']); ?></textarea></div>

    <h2 class="section-title">Extra Information</h2>
    <div class="field"><textarea id="extraInfo" placeholder="Anything else you'd like people to know."><?php echo htmlspecialchars($me['extra_info']); ?></textarea></div>

    <h2 class="section-title">Profile Privacy</h2>
    <div class="row-list">
      <label class="row clickable"><span class="row-label">Public</span><input type="radio" name="priv" id="privPublic" <?php echo $me['privacy'] === 'Public' ? 'checked' : ''; ?>></label>
      <label class="row clickable"><span class="row-label">Friends only</span><input type="radio" name="priv" id="privFriends" <?php echo $me['privacy'] === 'Friends only' ? 'checked' : ''; ?>></label>
    </div>

    <div class="btn-row" style="margin-top:20px;">
      <button class="btn btn-primary" onclick="saveAll()">Save Changes</button>
    </div>
    <p class="muted" id="savedNote" style="display:none;text-align:center;margin-top:10px;">Saved.</p>
  </div>
</div>

<script src="script.js"></script>
<script>
  async function saveAll() {
    const result = await postForm('update-profile.php', {
      selfIntro: document.getElementById('selfIntro').value,
      extraInfo: document.getElementById('extraInfo').value,
      privacy: document.getElementById('privFriends').checked ? 'Friends only' : 'Public'
    });
    const note = document.getElementById('savedNote');
    note.textContent = result.ok ? 'Saved.' : 'Could not save — try again.';
    note.style.display = 'block';
  }
</script>
</body>
</html>
