<?php
/**
 * Public Footer Component - Beacon Gospel Centre
 * Pure Procedural PHP
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$pastorName = get_setting('pastor_name', '');
$phone = get_setting('phone', '');
$email = get_setting('email', '');
$address = get_setting('address', '');
$paybill = get_setting('mpesa_paybill', '');
$sun1 = get_setting('sun1', '6:30 AM — Dawn Service');
$sun2 = get_setting('sun2', '9:00 AM — Main Service · Live');
$sun3 = get_setting('sun3', '2:00 PM — New Believers\' Class');
?>
<footer>
  <div class="fgrid">
    <div>
      <div class="big">To God alone<br>be the glory.</div>
      <p style="margin-top:16px;max-width:320px;font-size:1rem">“I have fought the good fight, I have finished the race, I have kept the faith.” — 2 Timothy 4:7</p>
    </div>
    <div>
      <h4>Explore</h4>
      <ul>
        <li><a href="<?= BASE_URL ?>about.php">Pastor's Journey</a></li>
        <li><a href="<?= BASE_URL ?>sermons.php">Sermons Library</a></li>
        <li><a href="<?= BASE_URL ?>live.php">Live Broadcast</a></li>
        <li><a href="<?= BASE_URL ?>ministries.php">Ministries</a></li>
        <li><a href="<?= BASE_URL ?>proposals.php">Capital Proposals</a></li>
        <li><a href="<?= BASE_URL ?>giving.php">Giving &amp; Support</a></li>
        <li><a href="<?= BASE_URL ?>prayer.php">Prayer Requests</a></li>
        <li><a href="<?= BASE_URL ?>events.php">Upcoming Events</a></li>
      </ul>
    </div>
    <div>
      <h4>Join Us in Church</h4>
      <ul>
        <li>Sun · <?= esc($sun1) ?></li>
        <li>Sun · <?= esc($sun2) ?></li>
        <li>Sun · <?= esc($sun3) ?></li>
        <li>Wed · Bible Study 6:00 PM</li>
        <li>Fri · Youth Night 7:00 PM</li>
        <?php if (!empty($address)): ?>
          <li><?= esc($address) ?></li>
        <?php endif; ?>
      </ul>
    </div>
    <div>
      <h4>Contact &amp; Giving</h4>
      <ul>
        <?php if (!empty($phone)): ?>
          <li><?= esc($phone) ?></li>
        <?php endif; ?>
        <?php if (!empty($email)): ?>
          <li><?= esc($email) ?></li>
        <?php endif; ?>
        <?php if (!empty($paybill)): ?>
          <li>M-Pesa Paybill <b><?= esc($paybill) ?></b></li>
        <?php endif; ?>
        <li><a href="<?= BASE_URL ?>live.php">Watch Live Services</a></li>
        <li><a href="<?= BASE_URL ?>invite.php" style="color:var(--gold2);">✉ Invite Pastor to Minister</a></li>
      </ul>
    </div>
  </div>
  <div class="fbottom">
    <span>© <?= date('Y') ?> <?= !empty($pastorName) ? esc(strtoupper($pastorName)) . ' · ' : '' ?>REDEEMED GOSPEL CHURCH ELDORET</span>
    <span>SOLI DEO GLORIA ✦ ELDORET, KENYA</span>
  </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
