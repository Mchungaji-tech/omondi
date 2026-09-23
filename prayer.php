<?php
/**
 * Dedicated Prayer Request Page — Beacon Gospel Centre
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$phone = get_setting('phone', '');
$pastorName = get_setting('pastor_name', '');
$member = user_auth_data();

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <div class="shead">
    <p class="idx">Intercession &amp; Standing in Faith</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Request <span class="ser">Pastoral Prayer</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:650px;">“Confess your sins to each other and pray for each other so that you may be healed. The prayer of a righteous person is powerful and effective.” — James 5:16</p>
  </div>

  <div class="prgrid">
    <div class="prnote">
      <p class="verse">You are not carrying this burden alone.</p>
      <p style="font-size:1.05rem;line-height:1.75;color:var(--ink2);margin-bottom:16px;">
        Send your prayer request below. It is read only by <?= !empty($pastorName) ? esc($pastorName) : 'the Pastor' ?> and the consecrated intercession team, held in strict confidence, and lifted every Tuesday on Kaptagat Prayer Mountain.
      </p>
      <?php if (!empty($phone)): ?>
        <p style="font-size:1.05rem;line-height:1.75;color:var(--ink2);margin-bottom:20px;">
          For urgent pastoral care or hospital visitation at MTRH Eldoret, call the office directly: <b style="color:var(--wine)"><?= esc($phone) ?></b>.
        </p>
      <?php endif; ?>
      
      <div class="prstats" style="grid-template-columns:repeat(3,1fr);display:grid;gap:16px;border-top:1px solid var(--line);padding-top:20px;">
        <div><b>1,240+</b><small>Prayers Answered</small></div>
        <div><b>36</b><small>Intercessors</small></div>
        <div><b>Every Tue</b><small>5:30 AM Ridge</small></div>
      </div>
    </div>

    <div>
      <form class="card" id="prayerForm" novalidate>
        <div class="f2">
          <div class="field">
            <label for="prName">Your Name</label>
            <input id="prName" type="text" value="<?= esc($member['name'] ?? '') ?>" placeholder="e.g. Mary Chepkoech">
          </div>
          <div class="field">
            <label for="prContact">Phone / Email (optional)</label>
            <input id="prContact" type="text" value="<?= esc($member['phone'] ?? ($member['email'] ?? '')) ?>" placeholder="If you'd like us to follow up">
          </div>
        </div>

        <div class="field">
          <label for="prCat">Category</label>
          <select id="prCat">
            <option value="general">General Intercession</option>
            <option value="healing">Healing &amp; Sickness</option>
            <option value="family">Family &amp; Marriage</option>
            <option value="salvation">Salvation of a Loved One</option>
            <option value="guidance">Guidance &amp; Decisions</option>
            <option value="finances">Finances &amp; Provision</option>
            <option value="grief">Grief &amp; Comfort</option>
          </select>
        </div>

        <div class="field">
          <label for="prText">Your Prayer Request Details *</label>
          <textarea id="prText" placeholder="Share what is on your heart — as much or as little as you like..." rows="4" required></textarea>
        </div>

        <label class="anon">
          <input type="checkbox" id="prAnon"> Submit anonymously (your name will not be shared, even with the team)
        </label>

        <button class="btn" type="submit" style="width:100%">Send Prayer Request to Pastor ✦</button>
      </form>

      <div class="card success" id="prSuccess">
        <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="36"/><path d="M24 41 L36 53 L58 29"/></svg>
        <h3>We Are Standing With You</h3>
        <p class="ref">REQUEST REF: <span id="prRef">PRAY-2026-0000</span></p>
        <p>Your request has been received in confidence and will be prayed over at this Tuesday's intercession meeting. Keep your reference number — “Cast all your anxiety on Him, because He cares for you.” — 1 Peter 5:7</p>
        <?php if ($member): ?>
          <a href="<?= BASE_URL ?>my_requests.php" class="btn sm gold" style="margin-top:16px;">View In My Requests &rarr;</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
