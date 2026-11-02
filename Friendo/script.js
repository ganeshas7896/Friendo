/* ===========================================================
   FRIENDO — shared script
   Theme (light/dark + accent), dialog helpers, country/region
   data for profile setup, and a tiny fetch helper.
   =========================================================== */

/* ---------------- Theme (light/dark + blue/red/gold) ---------------- */
/* NOTE: a tiny inline script in each page's <head> already applies the
   saved theme before paint, to avoid a flash. These functions handle
   changing it afterwards (from the nav button or Settings page). */

function applyTheme(mode, accent) {
  document.documentElement.setAttribute("data-theme", mode);
  document.documentElement.setAttribute("data-accent", accent);
  try {
    localStorage.setItem("friendo_theme_mode", mode);
    localStorage.setItem("friendo_theme_accent", accent);
  } catch (e) {}
}

function toggleDarkMode() {
  const current =
    document.documentElement.getAttribute("data-theme") || "light";
  const next = current === "dark" ? "light" : "dark";
  const accent = document.documentElement.getAttribute("data-accent") || "blue";
  applyTheme(next, accent);
  syncThemeToServer("theme_mode", next);
}

function setAccent(accent) {
  const mode = document.documentElement.getAttribute("data-theme") || "light";
  applyTheme(mode, accent);
  syncThemeToServer("theme_accent", accent);
  document.querySelectorAll(".swatch").forEach(function (el) {
    el.classList.toggle("selected", el.getAttribute("data-swatch") === accent);
  });
}

function syncThemeToServer(field, value) {
  // Best-effort — only works on pages where the user is signed in.
  postForm("save-settings.php", { field: field, value: value }).catch(
    function () {},
  );
}

/* ---------------- Fetch helpers ---------------- */

function postForm(url, data) {
  const body = new URLSearchParams(data || {});
  return fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: body.toString(),
  }).then(async function (r) {
    const text = await r.text();
    if (!text) {
      return { ok: false, message: "No response from the server." };
    }

    try {
      return JSON.parse(text);
    } catch (error) {
      throw new Error(text || "Server returned an invalid response.");
    }
  });
}

function getJSON(url) {
  return fetch(url).then(function (r) {
    return r.json();
  });
}

function escapeHtml(value) {
  return String(value == null ? "" : value).replace(
    /[&<>"']/g,
    function (character) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[character];
    },
  );
}

/* ---------------- Data: countries & regions (used in profile setup) ---------------- */

const REGIONS = {
  India: [
    "Andhra Pradesh",
    "Arunachal Pradesh",
    "Assam",
    "Bihar",
    "Chhattisgarh",
    "Goa",
    "Gujarat",
    "Haryana",
    "Himachal Pradesh",
    "Jharkhand",
    "Karnataka",
    "Kerala",
    "Madhya Pradesh",
    "Maharashtra",
    "Manipur",
    "Meghalaya",
    "Mizoram",
    "Nagaland",
    "Odisha",
    "Punjab",
    "Rajasthan",
    "Sikkim",
    "Tamil Nadu",
    "Telangana",
    "Tripura",
    "Uttar Pradesh",
    "Uttarakhand",
    "West Bengal",
    "Andaman and Nicobar Islands",
    "Chandigarh",
    "Dadra and Nagar Haveli and Daman and Diu",
    "Delhi",
    "Jammu and Kashmir",
    "Ladakh",
    "Lakshadweep",
    "Puducherry",
  ],
  "United States": [
    "California",
    "Texas",
    "New York",
    "Florida",
    "Illinois",
    "Washington",
    "Georgia",
    "Massachusetts",
    "Ohio",
    "Pennsylvania",
    "Other US State",
  ],
  Japan: [
    "Tokyo",
    "Osaka",
    "Kyoto",
    "Hokkaido",
    "Fukuoka",
    "Aichi",
    "Kanagawa",
    "Other Prefecture",
  ],
  Korea: [
    "Seoul",
    "Busan",
    "Incheon",
    "Daegu",
    "Gwangju",
    "Daejeon",
    "Jeju",
    "Other Region",
  ],
  China: [
    "Beijing",
    "Shanghai",
    "Guangdong",
    "Sichuan",
    "Zhejiang",
    "Jiangsu",
    "Other Province",
  ],
  Australia: [
    "New South Wales",
    "Victoria",
    "Queensland",
    "Western Australia",
    "South Australia",
    "Other Territory",
  ],
  Canada: [
    "Ontario",
    "Quebec",
    "British Columbia",
    "Alberta",
    "Manitoba",
    "Other Province",
  ],
  "United Kingdom": ["England", "Scotland", "Wales", "Northern Ireland"],
  Indonesia: [
    "Jakarta",
    "West Java",
    "East Java",
    "Bali",
    "Central Java",
    "Other Province",
  ],
  Taiwan: [
    "Taipei",
    "New Taipei",
    "Taichung",
    "Kaohsiung",
    "Tainan",
    "Other Region",
  ],
  Global: ["Not specified"],
};

const COUNTRIES = [
  { name: "Global", flag: "🌐" },
  { name: "Korea", flag: "🇰🇷" },
  { name: "Japan", flag: "🇯🇵" },
  { name: "United States", flag: "🇺🇸" },
  { name: "Australia", flag: "🇦🇺" },
  { name: "Canada", flag: "🇨🇦" },
  { name: "China", flag: "🇨🇳" },
  { name: "India", flag: "🇮🇳" },
  { name: "Indonesia", flag: "🇮🇩" },
  { name: "Taiwan", flag: "🇹🇼" },
  { name: "United Kingdom", flag: "🇬🇧" },
];

/* ---------------- Local draft store (profile-setup wizard only) ----------
   This only holds the in-progress answers while the multi-step dialogs on
   profile-setup.html are being filled in. The final "Start" button submits
   everything to save-profile.php — this is not used to persist real
   account data anywhere else in the app. ------------------------------- */

const Store = {
  get(key, fallback) {
    try {
      const raw = localStorage.getItem("friendo_draft_" + key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  },
  set(key, value) {
    try {
      localStorage.setItem("friendo_draft_" + key, JSON.stringify(value));
    } catch (e) {}
  },
};

function getProfile() {
  return Store.get("profile", {
    nickname: "",
    gender: "",
    birthYear: "",
    nationality: "",
    region: "",
  });
}
function saveProfile(p) {
  Store.set("profile", p);
}

/* ---------------- Get the app ---------------- */

const PLAY_STORE_LINK =
  "https://play.google.com/store/apps/details?id=com.friendo.app";
function openPlayStore() {
  window.open(PLAY_STORE_LINK, "_blank", "noopener");
}

/* ---------------- Navigation active-state (fallback for static pages) ---------------- */

document.addEventListener("DOMContentLoaded", function () {
  const current = (document.body.getAttribute("data-page") || "").toLowerCase();
  document.querySelectorAll("[data-nav]").forEach(function (el) {
    if (el.getAttribute("data-nav").toLowerCase() === current) {
      el.classList.add("active");
    }
  });
});

/* ---------------- Generic dialog helpers ---------------- */

function openDialog(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add("open");
}
function closeDialog(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove("open");
}

/* ---------------- Toasts (instant feedback for actions you take) ---------------- */
/* Used for things like "Message sent" / "Friend request sent" — the
   persistent bell + badges (below) cover things other people send you. */

let toastTimer = null;
function showToast(message) {
  let el = document.getElementById("friendoToast");
  if (!el) {
    el = document.createElement("div");
    el.id = "friendoToast";
    el.className = "friendo-toast";
    document.body.appendChild(el);
  }
  el.textContent = message;
  el.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(function () {
    el.classList.remove("show");
  }, 2600);
}

/* ---------------- Notification center (bell + badges) ----------------
   Included on every logged-in page via nav.php. Polls list-notifications.php
   so friend requests, friend acceptances, direct messages and group
   messages all show up as: (1) a red dot on the relevant nav tab,
   (2) a count on the bell, (3) an entry in the bell dropdown, and
   (4) a browser notification popup when permission has been granted
   and the item is new since the last poll. */

let notifPollTimer = null;
let notifLastSeenId = 0;
let notifFirstLoad = true;
let notifPanelOpen = false;

function toggleNotifPanel(btn) {
  notifPanelOpen = !notifPanelOpen;
  document.querySelectorAll("[data-notif-panel]").forEach(function (panel) {
    panel.classList.toggle("open", notifPanelOpen);
  });
  if (notifPanelOpen) {
    loadNotifications();
    if (window.Notification && Notification.permission === "default") {
      Notification.requestPermission();
    }
  }
}

function setBadge(el, count) {
  if (!el) return;
  if (count > 0) {
    el.textContent = count > 9 ? "9+" : String(count);
    el.classList.add("show");
  } else {
    el.textContent = "";
    el.classList.remove("show");
  }
}

function toggleUnreadBadge(selector, visible) {
  document.querySelectorAll(selector).forEach(function (el) {
    el.classList.toggle("show", !!visible);
  });
}

function renderNotifBadges(unread) {
  document.querySelectorAll("[data-notif-badge]").forEach(function (el) {
    setBadge(el, unread.total || 0);
  });
  toggleUnreadBadge('[data-nav-badge="dm"]', (unread.dm || 0) > 0);
  toggleUnreadBadge('[data-page-badge="dm"]', (unread.dm || 0) > 0);
  toggleUnreadBadge('[data-tab-badge="dm"]', (unread.dm || 0) > 0);
  toggleUnreadBadge('[data-nav-badge="group"]', (unread.group || 0) > 0);
  toggleUnreadBadge('[data-page-badge="group"]', (unread.group || 0) > 0);
  toggleUnreadBadge('[data-tab-badge="group"]', (unread.group || 0) > 0);
  toggleUnreadBadge('[data-nav-badge="social"]', (unread.social || 0) > 0);
  toggleUnreadBadge('[data-page-badge="social"]', (unread.social || 0) > 0);
  toggleUnreadBadge('[data-tab-badge="social"]', (unread.social || 0) > 0);
}

function iconForNotifType(type) {
  if (type === "friend_request") return "&#129309;";
  if (type === "friend_accept") return "&#127881;";
  if (type === "message") return "&#128172;";
  if (type === "group_message") return "&#128101;";
  if (type === "group_invite") return "&#128229;";
  return "&#128276;";
}

function renderNotifList(items) {
  document.querySelectorAll("[data-notif-list]").forEach(function (el) {
    if (items.length === 0) {
      el.innerHTML =
        '<p class="muted" style="padding:14px 16px;">You\'re all caught up.</p>';
      return;
    }
    el.innerHTML = items
      .map(function (n) {
        return (
          '<a class="notif-item' +
          (n.isRead ? "" : " unread") +
          '" href="' +
          n.link +
          '">' +
          '<span class="notif-item-icon">' +
          iconForNotifType(n.type) +
          "</span>" +
          '<span class="notif-item-body"><span class="notif-item-text">' +
          escapeHtml(n.message) +
          "</span>" +
          '<span class="notif-item-time">' +
          n.time +
          "</span></span></a>"
        );
      })
      .join("");
  });
}

async function loadNotifications(isBackgroundPoll) {
  let result;
  try {
    result = await getJSON("list-notifications.php");
  } catch (e) {
    return;
  }
  if (!result || !result.ok) return;

  renderNotifBadges(result.unread || {});
  renderNotifList(result.items || []);

  const newest = result.items && result.items.length ? result.items[0] : null;
  if (
    isBackgroundPoll &&
    newest &&
    newest.id > notifLastSeenId &&
    !notifFirstLoad
  ) {
    if (!newest.isRead) {
      showToast(newest.message);
      if (window.Notification && Notification.permission === "granted") {
        try {
          new Notification("Friendo", { body: newest.message });
        } catch (e) {}
      }
    }
  }
  if (newest) notifLastSeenId = Math.max(notifLastSeenId, newest.id);
  notifFirstLoad = false;
}

function markAllNotificationsRead() {
  postForm("mark-notifications-read.php", { scope: "all" }).then(function () {
    loadNotifications(false);
  });
}

function initNotificationCenter() {
  if (notifPollTimer) return; // already running on this page
  loadNotifications(false);
  notifPollTimer = setInterval(function () {
    loadNotifications(true);
  }, 7000);

  document.addEventListener("click", function (e) {
    if (!notifPanelOpen) return;
    if (e.target.closest && e.target.closest(".notif-wrap")) return;
    notifPanelOpen = false;
    document.querySelectorAll("[data-notif-panel]").forEach(function (panel) {
      panel.classList.remove("open");
    });
  });
}

/* ---------------- Simple list filter for dialog search ---------------- */

function filterOptionRows(inputEl, listSelector) {
  const q = inputEl.value.trim().toLowerCase();
  document
    .querySelectorAll(listSelector + " .option-row")
    .forEach(function (row) {
      const label = (row.getAttribute("data-label") || "").toLowerCase();
      row.style.display = label.indexOf(q) === -1 ? "none" : "flex";
    });
}

/* ---------------- Auto-start the notification center ----------------
   Only on pages that actually include nav.php (i.e. the bell markup is
   present in the DOM). This script tag always loads after nav.php's
   markup, since every page requires nav.php before the <script src=
   "script.js"> tag near the end of <body>, so the elements already
   exist by the time this runs. Public/static pages (index.php, signin.html,
   etc.) don't have the markup, so nothing runs there. */
if (document.querySelector("[data-notif-panel]")) {
  initNotificationCenter();
}
