<?php
/**
 * Dedicated Invite the Pastor Page — Beacon Gospel Centre
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$pastorName = get_setting('pastor_name', '');
$phone = get_setting('phone', '');
$email = get_setting('email', '');
$address = get_setting('address', '');
$member = user_auth_data();

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <div class="shead">
    <p class="idx">Kingdom Ministry</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Invite <span class="ser">the Pastor to Minister</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:650px;"><?= !empty($pastorName) ? esc($pastorName) : 'The Pastor' ?> is available to minister at open-air crusades, conferences, revival weeks, youth camps, weddings and ordinations across Kenya and beyond.</p>
  </div>

  <div class="agrid">
    <div class="note">
      <h3 style="font-size:1.4rem;margin-bottom:14px;">Pastoral Ministry Guidelines</h3>
      <ul style="margin:14px 0 20px 20px;font-size:1rem;color:var(--ink2);line-height:1.9;">
        <li>Preaching in English &amp; Kiswahili (with interpreter for Kalenjin or other local languages)</li>
        <li>Owns his travel logistics within Eldoret; modest hospitality requested for upcountry crusades</li>
        <li>No speaking fee — freewill love offering, as the Lord leads</li>
        <li>Bookings confirmed at least 3 weeks in advance by the secretariat</li>
      </ul>

      <?php if (!empty($phone) || !empty($email) || !empty($address)): ?>
        <div style="background:var(--paper2);padding:18px;border-radius:6px;font-family:var(--mono);font-size:11px;line-height:1.9;margin-top:20px;">
          <?php if (!empty($phone)): ?>
            <b>Office Phone / WhatsApp:</b> <?= esc($phone) ?><br>
          <?php endif; ?>
          <?php if (!empty($email)): ?>
            <b>Email:</b> <?= esc($email) ?><br>
          <?php endif; ?>
          <?php if (!empty($address)): ?>
            <b>Church Sanctuary:</b> <?= esc($address) ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <form class="card" id="inviteForm" novalidate>
        <div class="f2">
          <div class="field">
            <label for="fname">Your Name / Contact Person *</label>
            <input id="fname" type="text" value="<?= esc($member['name'] ?? '') ?>" placeholder="e.g. Pastor John Kamau" required>
          </div>
          <div class="field">
            <label for="fchurch">Church / Host Organisation *</label>
            <input id="fchurch" type="text" placeholder="e.g. ACK St. Luke's, Kitale" required>
          </div>
        </div>

        <div class="f2">
          <div class="field">
            <label for="fphone">Phone Number / Email *</label>
            <input id="fphone" type="text" value="<?= esc($member['phone'] ?? ($member['email'] ?? '')) ?>" placeholder="+254 7XX XXX XXX" required>
          </div>
          <div class="field">
            <label for="fcity">Town / County</label>
            <input id="fcity" type="text" value="<?= esc($member['location'] ?? '') ?>" placeholder="e.g. Kitale, Trans-Nzoia">
          </div>
        </div>

        <div class="f2">
          <div class="field">
            <label for="fdate">Preferred Date / Period</label>
            <input id="fdate" type="date">
          </div>
          <div class="field">
            <label for="ftype">Service / Event Type *</label>
            <select id="ftype" required>
              <option value="">— Choose Event Type —</option>
              <option value="sunday">Sunday Pulpit Service</option>
              <option value="crusade">Open-Air Town Crusade</option>
              <option value="revival">Revival / Church Conference</option>
              <option value="camp">Youth Camp / Summit</option>
              <option value="fundraiser">Church Dedication / Fundraiser</option>
              <option value="concert">Praise Concert &amp; Word</option>
              <option value="wedding">Wedding / Ordination</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="fmsg">Message / Theme Request &amp; Congregation Context</label>
          <textarea id="fmsg" placeholder="Tell us about your congregation and what you sense the Lord is speaking..." rows="4"></textarea>
        </div>

        <button class="btn" type="submit" style="width:100%">Send Ministry Invitation ✦</button>
      </form>

      <div class="card success" id="successBox">
        <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="36"/><path d="M24 41 L36 53 L58 29"/></svg>
        <h3>Thank You!</h3>
        <p class="ref">INVITATION REF: <span id="refNo">INV-2026-0000</span></p>
        <p>Your ministry invitation has been received by the office of <?= esc($pastorName) ?>. The secretariat will connect back within <b>5 working days</b>.</p>
        <?php if ($member): ?>
          <a href="<?= BASE_URL ?>my_requests.php" class="btn sm gold" style="margin-top:16px;">View In My Requests &rarr;</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
