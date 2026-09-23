<?php
/**
 * Dedicated Ministries Page — Beacon Gospel Centre
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

ensure_ministries_table();
$ministries = db_fetch_all("SELECT * FROM ministries ORDER BY sort_order ASC, id ASC");

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <div class="shead">
    <p class="idx">The Church in Action</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Our <span class="ser">Ministries &amp; Fellowship</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:640px;">From discipleship and mothers' prayer bands to street rescue and pastoral leadership training across the North Rift.</p>
  </div>

  <div>
    <?php foreach ($ministries as $idx => $m): ?>
      <div class="mrow">
        <div class="num"><?= str_pad($idx + 1, 2, '0', STR_PAD_LEFT) ?></div>
        <div>
          <h3><?= esc($m['name']) ?> <span><?= esc($m['age_span']) ?></span></h3>
          <p style="font-size:1.05rem;line-height:1.7;"><?= esc($m['description']) ?></p>
          <?php if (!empty($m['stats'])): ?>
            <span class="mstat"><?= esc($m['stats']) ?></span>
          <?php endif; ?>
          <div style="margin-top:18px;">
            <a href="<?= BASE_URL ?>prayer.php" class="btn sm ghost">Connect with Leaders</a>
          </div>
        </div>
        <div class="mimg">
          <img src="<?= esc(img_src($m['image_url'])) ?>" alt="<?= esc($m['name']) ?>">
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="verseband">
  <blockquote>“How beautiful are the feet of those who bring good news!”
    <cite>— Romans 10:15</cite>
  </blockquote>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
