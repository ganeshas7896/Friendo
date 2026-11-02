# Friendo — setup & hosting guide

## What this is

A multi-page PHP + MySQL friend-chat app:

- Sign up / Sign in with hashed passwords (real accounts, not localStorage)
- Browse People → send a friend request
- Friends page → Accept or Decline. **Chat only unlocks once a request is accepted** (enforced server-side in `includes/friendship.php`, not just hidden in the UI)
- Chatting page → one room per accepted friend, polls for new messages
- Profile, Settings (search prefs, notifications, light/dark mode, 3 accent colors: blue / red / gold)
- Razorpay payment integration (one-time item purchase + ₹29/month subscription) with server-side order creation and signature verification

## 1. Local setup (XAMPP / WAMP / MAMP)

1. Copy the `Friendo` folder into your server's web root (e.g. `htdocs/Friendo`).
2. Create the database: open phpMyAdmin (or `mysql -u root -p`) and import `database.sql`.
3. Open `config.php` and check the DB settings at the top — the defaults (`root` / empty password / `localhost`) match a fresh XAMPP/WAMP install.
4. Visit `http://localhost/Friendo/index.html` in your browser.

## 2. Going live on shared/public hosting

1. Upload the contents of the `Friendo` folder into the hosting document root. On InfinityFree this is normally the `htdocs` folder. The document root must contain `index.html`, `config.php`, `api/`, `includes/`, `assets/`, and `style.css` directly; do not leave them inside an extra `Friendo/Friendo` folder.
2. In your host's control panel (cPanel, Plesk, etc.), create a MySQL database and a database user, and import `database.sql` into it via phpMyAdmin.
3. Edit `config.php`:
   - `$DB_HOST`, `$DB_USER`, `$DB_PASS`, `$DB_NAME` — from your host's database page
   - `SITE_URL` — your live domain, e.g. `https://yourdomain.com/friendo`
   - Alternatively, set `FRIENDO_DB_HOST`, `FRIENDO_DB_USER`, `FRIENDO_DB_PASS`, `FRIENDO_DB_NAME`, and `FRIENDO_SITE_URL` as server environment variables. These override the values in `config.php`.
4. Make sure the site is served over PHP 7.4+ with the `mysqli` and `curl` extensions enabled (virtually all shared hosts have these by default).
5. If the files are directly in the document root, visit `https://yourdomain.com/index.html` (or `https://yourdomain.com/`). If you uploaded the whole folder as `htdocs/Friendo`, visit `https://yourdomain.com/Friendo/index.html` instead.

That's the only file you should need to touch (`config.php`) when moving between local and live hosting.

## 3. Enabling real payments (Razorpay)

1. Create a Razorpay account and grab your **Key ID** and **Key Secret** from https://dashboard.razorpay.com/app/keys
2. Paste them into `config.php`:
   ```php
   define("RAZORPAY_KEY_ID", "rzp_live_xxxxxxxx");
   define("RAZORPAY_KEY_SECRET", "your_secret_here");
   ```
3. Until you do this, the Payment page shows a friendly "not configured yet" message instead of failing.
4. Razorpay requires **HTTPS** in live mode — test mode keys (`rzp_test_...`) work over plain HTTP for local testing.

## 4. How the friend-request gate works

- `friend_requests` table holds every request with a status: `pending → accepted` or `declined`.
- `includes/friendship.php` has one function, `areFriends()`, checked by **both** `api/send-message.php` and `api/get-messages.php` before any message is read or written.
- This means even a modified frontend can't bypass it — the block is enforced by the server, not just hidden with CSS/JS.

## 5. Theme system

- Toggle button (☀/🌙 icon) in the top nav / mobile bar flips light ↔ dark instantly, no reload.
- Settings → Appearance lets a signed-in user pick an accent: **light blue** (default), **light red**, or **light gold**.
- Preference is saved both to `localStorage` (instant, no flash on next page load) and to the `users` table (so it also loads correctly on a new device once you're signed in there).

## 6. File map

```
Friendo/
  config.php              ← edit this when hosting
  database.sql            ← import this once
  index.html              landing page
  signin.html / signup.html
  terms.html → privacy.html → profile-setup.html → profile-confirm.html   (onboarding)
  people.php / friends.php / messages.php / conversation.php / profile.php / settings.php / payment.php / contact.php
  logout.php
  includes/  (auth.php, friendship.php, nav.php — shared PHP partials)
  api/       (all backend endpoints, called via fetch())
  assets/logo.png
  style.css / script.js
```
