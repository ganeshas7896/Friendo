<?php
require_once __DIR__ . "/auth.php";
requireLogin();
$me = currentUser();
$active = "payment";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var m=localStorage.getItem('friendo_theme_mode')||<?php echo json_encode($me['theme_mode'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;var a=localStorage.getItem('friendo_theme_accent')||<?php echo json_encode($me['theme_accent'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;document.documentElement.setAttribute('data-theme',m);document.documentElement.setAttribute('data-accent',a);}catch(e){}})();</script>
<title>Support Friendo — Subscription</title>
<link rel="stylesheet" href="style.css">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body data-page="payment">
<div class="app has-desktop-nav">
  <?php $backHref = "settings.php"; require __DIR__ . "/nav.php"; ?>

  <div class="page-content">
    <h1 class="page-title">Support Friendo</h1>
    <p class="muted" style="margin-top:-10px;">Purchase Friendo items, or subscribe for uninterrupted access.</p>

    <h2 class="section-title">One-time purchase</h2>
    <div class="plan-card">
      <div class="plan-name">Friendo Items</div>
      <div class="plan-price">&#8377;<span id="itemAmountLabel">100</span> <span>one-time</span></div>
      <div class="field" style="margin-top:12px;">
        <label>Amount (&#8377;)</label>
        <input type="text" id="itemAmount" value="100" oninput="document.getElementById('itemAmountLabel').textContent = this.value || 0;">
      </div>
      <ul>
        <li>Boost your profile visibility</li>
        <li>Unlock extra profile customization</li>
      </ul>
      <div class="btn-row">
        <button class="btn btn-primary" onclick="pay('items')">Proceed to Payment</button>
      </div>
    </div>

    <h2 class="section-title">Subscription</h2>
    <div class="plan-card">
      <div class="plan-name">Friendo Monthly</div>
      <div class="plan-price">&#8377;29 <span>/ month</span></div>
      <ul>
        <li>Unlimited friend requests</li>
        <li>Priority placement in People</li>
        <li>Ad-free experience</li>
        <li>Cancel anytime</li>
      </ul>
      <div class="btn-row">
        <button class="btn btn-primary" onclick="pay('monthly')">Pay with Razorpay</button>
      </div>
      <p class="muted" style="margin-top:10px;margin-bottom:0;">You'll be taken to Razorpay's secure checkout. Friendo never sees or stores your card details.</p>
    </div>
    <p class="muted" id="subStatus" style="<?php echo $me['is_subscribed'] ? '' : 'display:none;'; ?>">You're on the Friendo Monthly plan.</p>

    <h2 class="section-title">Get the app</h2>
    <a class="store-badge" href="javascript:void(0)" onclick="openPlayStore()">
      <span class="store-icon">&#9654;</span>
      <span class="store-text">GET IT ON<strong>Google Play</strong></span>
    </a>
    <p class="muted" style="margin-top:8px;">Your profile, friends and chats stay in sync when you use the Friendo app from Google Play.</p>
  </div>
</div>

<script src="script.js"></script>
<script>
  const RAZORPAY_KEY_HINT = "<?php echo RAZORPAY_KEY_ID === 'rzp_test_YOUR_KEY_ID' ? '0' : '1'; ?>";

  async function pay(plan) {
    if (RAZORPAY_KEY_HINT === '0') {
      alert("Payments aren't configured yet. Add your Razorpay Key ID and Secret to config.php to enable checkout.");
      return;
    }
    const amountRupees = plan === 'items' ? (parseInt(document.getElementById('itemAmount').value, 10) || 100) : 29;
    const order = await postForm('create-order.php', { plan: plan, amount_paise: amountRupees * 100 });
    if (!order.ok) { alert(order.message || 'Could not start payment.'); return; }

    const rzp = new Razorpay({
      key: order.key,
      amount: order.amount,
      currency: "INR",
      name: "Friendo",
      description: plan === 'monthly' ? 'Friendo Monthly Subscription' : 'Friendo Items',
      order_id: order.order_id,
      handler: async function (response) {
        const verify = await postForm('verify-payment.php', response);
        if (verify.ok) {
          alert('Payment successful — thank you for supporting Friendo!');
          if (plan === 'monthly') document.getElementById('subStatus').style.display = 'block';
        } else {
          alert(verify.message || 'Payment could not be verified.');
        }
      },
      theme: { color: "#5CB0D6" }
    });
    rzp.open();
  }
</script>
</body>
</html>
