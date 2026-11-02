<?php
/* Include this AFTER setting:
   $active   = "people" | "friends" | "messages" | "groups" | "profile" | "settings" | "payment" | "contact" | "home"
   $backHref = optional string — if set, mobile topbar shows a Back link instead of the brand
   $me       = optional current user row (array) from currentUser()
   Usage:
     $active = "people";
     require __DIR__ . "/nav.php";
*/
if (!isset($active)) $active = "";
$meName = isset($me) && $me ? ($me['nickname'] ?: $me['username']) : "";
?>
<div class="topnav">
  <div class="brand"><img src="logo.png" alt="Friendo"><span>Friendo</span></div>
  <div class="topnav-links">
    
    <a href="people.php" data-nav="people" class="<?php echo $active === 'people' ? 'active' : ''; ?>">People</a>
    <a href="friends.php" data-nav="friends" class="<?php echo $active === 'friends' ? 'active' : ''; ?>">Friends<span class="nav-badge" data-nav-badge="social"></span></a>
    <a href="messages.php" data-nav="messages" class="<?php echo $active === 'messages' ? 'active' : ''; ?>">Chatting<span class="nav-badge" data-nav-badge="dm"></span></a>
    <a href="groups.php" data-nav="groups" class="<?php echo $active === 'groups' ? 'active' : ''; ?>">Groups<span class="nav-badge" data-nav-badge="group"></span></a>
    <a href="profile.php" data-nav="profile" class="<?php echo $active === 'profile' ? 'active' : ''; ?>">Profile</a>
    <a href="settings.php" data-nav="settings" class="<?php echo $active === 'settings' ? 'active' : ''; ?>">Settings</a>
  </div>
  <div class="topnav-right">
    <div class="notif-wrap">
      <button type="button" class="notif-bell" onclick="toggleNotifPanel(this)" title="Notifications" aria-label="Notifications">
        <span>&#128276;</span><span class="notif-badge" data-notif-badge></span>
      </button>
      <div class="notif-panel" data-notif-panel>
        
        <div class="notif-list" data-notif-list><p class="muted" style="padding:14px 16px;">Loading...</p></div>
      </div>
    </div>
    <button type="button" class="theme-toggle" onclick="toggleDarkMode()" title="Toggle light / dark" aria-label="Toggle theme">&#9788;</button>
    <a class="btn btn-primary btn-sm" href="payment.php">Buy</a>
    <?php if ($meName): ?><span class="support" style="font-size:13px;"><?php echo htmlspecialchars($meName); ?></span><?php endif; ?>
    <a class="support" href="logout.php">Logout</a>
  </div>
</div>

<div class="mobile-topbar">
  <?php if (!empty($backHref)): ?>
    <a class="back" href="<?php echo htmlspecialchars($backHref); ?>">&larr; Back</a>
  <?php else: ?>
    <div class="brand"><img src="logo.png" alt="Friendo"><span>Friendo</span></div>
  <?php endif; ?>
  <div style="display:flex;align-items:center;gap:10px;">
    <div class="notif-wrap">
      <button type="button" class="notif-bell" onclick="toggleNotifPanel(this)" title="Notifications" aria-label="Notifications">
        <span>&#128276;</span><span class="notif-badge" data-notif-badge></span>
      </button>
      <div class="notif-panel" data-notif-panel>
        
        <div class="notif-list" data-notif-list><p class="muted" style="padding:14px 16px;">Loading...</p></div>
      </div>
    </div>
    <button type="button" class="theme-toggle" onclick="toggleDarkMode()" title="Toggle light / dark" aria-label="Toggle theme">&#9788;</button>
    <a href="payment.php" style="font-size:13px;color:var(--primary-dark);font-weight:600;">Buy</a>
  </div>
</div>

<div class="bottomnav">
  <a href="people.php" data-nav="people" class="<?php echo $active === 'people' ? 'active' : ''; ?>"><span class="icon">&#128101;</span>People</a>
  <a href="friends.php" data-nav="friends" class="<?php echo $active === 'friends' ? 'active' : ''; ?>"><span class="icon">&#129309;</span>Friends<span class="nav-badge" data-nav-badge="social"></span></a>
  <a href="messages.php" data-nav="messages" class="<?php echo $active === 'messages' ? 'active' : ''; ?>"><span class="icon">&#128172;</span>Chatting<span class="nav-badge" data-nav-badge="dm"></span></a>
  <a href="groups.php" data-nav="groups" class="<?php echo $active === 'groups' ? 'active' : ''; ?>"><span class="icon">&#128101;&#8205;&#128101;</span>Groups<span class="nav-badge" data-nav-badge="group"></span></a>
  <a href="profile.php" data-nav="profile" class="<?php echo $active === 'profile' ? 'active' : ''; ?>"><span class="icon">&#128578;</span>Profile</a>
  <a href="settings.php" data-nav="settings" class="<?php echo $active === 'settings' ? 'active' : ''; ?>"><span class="icon">&#9881;&#65039;</span>Settings</a>
</div>
