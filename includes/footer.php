<?php
/**
 * Public Footer Component - Beacon Gospel Centre
 * Pure Procedural PHP
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$pastorName = get_setting('pastor_name', 'Bishop Morris Omondi');
$phone = get_setting('phone', '+254 712 000 000');
$email = get_setting('email', 'office@revlangat.or.ke');
$address = get_setting('address', 'Redeemed Gospel Church Eldoret');
$paybill = get_setting('mpesa_paybill', '453 210');
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
      <h4>Explore Beacon</h4>
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
        <li><?= esc($address) ?></li>
      </ul>
    </div>
    <div>
      <h4>Contact &amp; Giving</h4>
      <ul>
        <li><?= esc($phone) ?></li>
        <li><?= esc($email) ?></li>
        <li>M-Pesa Paybill <b><?= esc($paybill) ?></b></li>
        <?php
          $_last = trim($pastorName);
          $_last = preg_split('/\s+/', $_last);
          $_last = end($_last);
        ?>
        <li><a href="<?= BASE_URL ?>live.php">YouTube — Rev. <?= esc($_last) ?> TV</a></li>
        <li><a href="<?= BASE_URL ?>live.php">Facebook — Beacon Gospel</a></li>
        <li><a href="<?= BASE_URL ?>invite.php" style="color:var(--gold2);">✉ Invite Pastor to Minister</a></li>
      </ul>
    </div>
  </div>
  <div class="fbottom">
    <span>© <?= date('Y') ?> <?= esc(strtoupper($pastorName)) ?> · REDEEMED GOSPEL CHURCH ELDORET</span>
    <span>SOLI DEO GLORIA ✦ ELDORET, KENYA</span>
  </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
