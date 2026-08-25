<?php
/**
 * Dedicated Giving & Support Page — Beacon Gospel Centre
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$fundGoals = db_fetch_all("SELECT * FROM fund_goals ORDER BY sort_order ASC, id ASC");
$supportPrograms = db_fetch_all("SELECT * FROM support_programs ORDER BY sort_order ASC, id ASC");

$mpesaPaybill = get_setting('mpesa_paybill', '453 210');
$bankName = get_setting('bank_name', 'Equity Bank · Eldoret Branch');
$bankAccount = get_setting('bank_account', '04501234567890');
$bankAccountName = get_setting('bank_account_name', 'Redeemed Gospel Church Eldoret');
$swiftCode = get_setting('swift_code', 'EQBLKENA');

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <div class="shead">
    <p class="idx">Partner with the Vineyard</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Giving &amp; <span class="ser">Community Support</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:680px;">“Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver.” — 2 Corinthians 9:7. Every shilling is prayed over and reported quarterly.</p>
  </div>

  <div class="spgrid">
    <div>
      <h3 style="font-size:1.6rem;margin-bottom:24px;">Active Fund Progress</h3>
      <?php foreach ($fundGoals as $fg): 
        $pct = $fg['goal_amount'] > 0 ? min(100, round(($fg['raised_amount'] / $fg['goal_amount']) * 100)) : 0;
      ?>
        <div class="fund" data-pct="<?= $pct ?>">
          <div class="ftop">
            <span><?= esc($fg['name']) ?></span>
            <b><?= format_compact_ksh($fg['raised_amount']) ?></b>
          </div>
          <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
          <div class="goal"><?= $pct ?>% of <?= format_compact_ksh($fg['goal_amount']) ?> goal · <?= esc($fg['note']) ?></div>
        </div>
      <?php endforeach; ?>
      <p style="margin-top:24px;font-family:var(--mono);font-size:11px;letter-spacing:.1em;color:var(--ink2);">✦ AUDITED ANNUALLY · GIVING REPORTS DISTRIBUTED QUARTERLY ✦</p>
    </div>

    <div class="ways">
      <div class="way">
        <h3>M-Pesa <span class="pill">Kenya · Fastest</span></h3>
        <div class="wrow">
          <span>Paybill</span>
          <b><?= esc($mpesaPaybill) ?></b>
          <button class="copy" data-copy="<?= esc(str_replace(' ', '', $mpesaPaybill)) ?>">Copy</button>
        </div>
        <div class="wrow">
          <span>Account No.</span>
          <b>Designated Programme Code</b>
        </div>
        <p style="font-family:var(--mono);font-size:12px;color:var(--ink2);margin-top:12px;letter-spacing:.06em;">LIPA NA M-PESA &rarr; PAYBILL <?= esc($mpesaPaybill) ?> &rarr; ACCOUNT = PROGRAMME CODE</p>
      </div>

      <div class="way">
        <h3>Bank Transfer <span class="pill">Kenya &amp; Diaspora</span></h3>
        <div class="wrow"><span>Bank</span><b><?= esc($bankName) ?></b></div>
        <div class="wrow"><span>Account Name</span><b><?= esc($bankAccountName) ?></b></div>
        <div class="wrow"><span>Account No.</span><b><?= esc($bankAccount) ?></b><button class="copy" data-copy="<?= esc($bankAccount) ?>">Copy</button></div>
        <div class="wrow"><span>SWIFT</span><b><?= esc($swiftCode) ?></b><button class="copy" data-copy="<?= esc($swiftCode) ?>">Copy</button></div>
      </div>
    </div>
  </div>

  <!-- SUPPORT PROGRAMMES LIST -->
  <div class="shead" style="margin-top:60px;">
    <p class="idx">Designated Programmes</p>
    <h2 class="big">Where You Can <span class="ser">Co-Labour</span></h2>
    <p style="margin-top:8px;color:var(--ink2);">Choose any programme code below to designate your offering via M-Pesa or Bank wire.</p>
  </div>
  <div class="programs">
    <?php foreach ($supportPrograms as $prog): ?>
      <div class="prog">
        <div class="ptop">
          <h4><?= esc($prog['name']) ?></h4>
          <span class="acc">ACC · <?= esc($prog['account_code']) ?></span>
        </div>
        <p><?= esc($prog['description']) ?></p>
        <div class="pfoot">
          <span class="goal"><?= esc($prog['goal_text']) ?></span>
          <button class="copy" data-copy="Paybill <?= esc(str_replace(' ', '', $mpesaPaybill)) ?> · Acc <?= esc($prog['account_code']) ?>">Copy details</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
