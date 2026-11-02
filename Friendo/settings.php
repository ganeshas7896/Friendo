<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$me = currentUser();
$active = "settings";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>

<link rel="stylesheet" href="style.css">
</head>
<body data-page="settings">
<div class="app has-desktop-nav">
  <?php require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <h1 class="page-title">Settings</h1>

    <h2 class="section-title">Appearance</h2>
    <div class="row-list">
      <div class="row"><span class="row-label">Mode</span>
        <label class="switch"><input type="checkbox" id="darkToggle" <?php echo $me['theme_mode'] === 'dark' ? 'checked' : ''; ?>><span class="slider"></span></label>
      </div>
    </div>
    <div class="row-list" style="margin-top:8px;">
      <div class="swatch-row">
        <div class="swatch <?php echo $me['theme_accent'] === 'blue' ? 'selected' : ''; ?>" data-swatch="blue" onclick="setAccent('blue')"><span class="dot"></span>Blue</div>
        <div class="swatch <?php echo $me['theme_accent'] === 'red' ? 'selected' : ''; ?>" data-swatch="red" onclick="setAccent('red')"><span class="dot"></span>Red</div>
        <div class="swatch <?php echo $me['theme_accent'] === 'gold' ? 'selected' : ''; ?>" data-swatch="gold" onclick="setAccent('gold')"><span class="dot"></span>Gold</div>
      </div>
    </div>

    <h2 class="section-title">Allow Setting</h2>
    <div class="row-list">
      <div class="row"><span class="row-label">Receive Friend Requests</span>
        <label class="switch"><input type="checkbox" id="toggleRequests" <?php echo $me['receive_requests'] ? 'checked' : ''; ?>><span class="slider"></span></label>
      </div>
    </div>

    <h2 class="section-title">Connection Filters</h2>
    <div class="row-list">
      
      
      <div class="row"><span class="row-label">Chat with</span>
        <select id="preferredGenderInput" style="padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--text);">
          <option value="Both" <?php echo (($me['preferred_gender'] ?? 'Both') === 'Both' ? 'selected' : ''); ?>>Both</option>
          <option value="Male" <?php echo (($me['preferred_gender'] ?? 'Both') === 'Male' ? 'selected' : ''); ?>>Male</option>
          <option value="Female" <?php echo (($me['preferred_gender'] ?? 'Both') === 'Female' ? 'selected' : ''); ?>>Female</option>
        </select>
      </div>
      <div class="row" style="display:block; padding:12px 14px; flex-direction:column; align-items:flex-start; gap:8px;">
        
        <input type="text" id="interestInput" value="<?php echo htmlspecialchars($me['interest_to_connect'] ?? ''); ?>" placeholder="e.g. travel, music, hiking" style="width:100%;">
      </div>
    </div>

    <h2 class="section-title">Notifications</h2>
    <div class="row-list">
      <div class="row"><span class="row-label">Friend request notification</span>
        <label class="switch"><input type="checkbox" id="toggleFriendNotif" <?php echo $me['notif_requests'] ? 'checked' : ''; ?>><span class="slider"></span></label>
      </div>
      <div class="row"><span class="row-label">Chatting notification</span>
        <label class="switch"><input type="checkbox" id="toggleChatNotif" <?php echo $me['notif_chat'] ? 'checked' : ''; ?>><span class="slider"></span></label>
      </div>
    </div>

    <h2 class="section-title">Buy</h2>
    <div class="row-list">
      <a class="row clickable" href="payment.php"><span class="row-label">Buying items / Subscription</span><span class="row-value"><?php echo $me['is_subscribed'] ? 'Active &rsaquo;' : '&rsaquo;'; ?></span></a>
    </div>

    <h2 class="section-title">Contact</h2>
    <div class="row-list">
      <a class="row clickable" href="contact.php"><span class="row-label">Report bugs / Contact support</span><span class="row-value">&rsaquo;</span></a>
    </div>

    <h2 class="section-title">Account</h2>
    <div class="row-list">
      <a class="row clickable" href="logout.php"><span class="row-label">Log out</span><span class="row-value">&rsaquo;</span></a>
      <div class="row danger clickable" onclick="openDialog('deleteDialog')"><span class="row-label">Delete your account</span><span class="row-value">&rsaquo;</span></div>
    </div>
  </div>
</div>

<div class="overlay center" id="deleteDialog">
  <div class="dialog confirm">
    <h3 style="margin:0 0 6px 0;">Delete your account?</h3>
    <p>This action can't be undone. Your profile, friends and chat data will be permanently removed.</p>
    <div class="btn-row">
      <button class="btn btn-outline" onclick="closeDialog('deleteDialog')">Cancel</button>
      <button class="btn btn-danger" onclick="deleteAccount()">Delete</button>
    </div>
  </div>
</div>

<script src="script.js"></script>
<script>
  document.getElementById('darkToggle').addEventListener('change', function () { toggleDarkMode(); });

  ['toggleRequests', 'toggleFriendNotif', 'toggleChatNotif'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', function () {
      const fieldMap = { toggleRequests: 'receive_requests', toggleFriendNotif: 'notif_requests', toggleChatNotif: 'notif_chat' };
      postForm('save-settings.php', { field: fieldMap[id], value: this.checked ? 1 : 0 });
    });
  });

  function bindSettingNumeric(id, field) {
    const input = document.getElementById(id);
    input.addEventListener('change', function () {
      const mapped = Number(this.value);
      if (!Number.isFinite(mapped)) return;
      postForm('save-settings.php', { field: field, value: mapped });
    });
  }

  
  document.getElementById('preferredGenderInput').addEventListener('change', function () {
    postForm('save-settings.php', { field: 'preferred_gender', value: this.value });
  });
  document.getElementById('interestInput').addEventListener('change', function () {
    postForm('save-settings.php', { field: 'interest_to_connect', value: this.value.trim() });
  });

  async function deleteAccount() {
    const result = await postForm('delete-account.php', {});
    if (result.ok) {
      window.location.href = 'index.html';
    } else {
      alert('Could not delete your account.');
    }
  }
</script>
</body>
</html>
