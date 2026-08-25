<?php
/**
 * About & Journey Page — Beacon Gospel Centre
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$pastorName = get_setting('pastor_name', 'Bishop Morris Omondi');
$pastorEpithet = get_setting('pastor_epithet', 'of Eldoret');
$tagline = get_setting('tagline', 'Preacher of the Gospel · 23 Years in the Vineyard');
$heroVerse = get_setting('hero_verse', '“Your word is a lamp to my feet and a light to my path.” — Psalm 119:105');
$portraitUrl = get_setting('portrait_url', 'https://picsum.photos/seed/eldoret-pastor-portrait/900/1125');
$bio1 = get_setting('bio1');
$bio2 = get_setting('bio2');
$scheduleDays = db_fetch_all("SELECT * FROM weekly_schedule ORDER BY sort_order ASC, id ASC");
$galleryItems = db_fetch_all("SELECT * FROM gallery ORDER BY sort_order ASC, id ASC LIMIT 4");

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <!-- PAGE HEADER -->
  <div class="shead">
    <p class="idx">The Shepherd &amp; The Field</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Pastor's <span class="ser">Journey &amp; Calling</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:640px;"><?= esc($tagline) ?> — Redeemed Gospel Church Eldoret.</p>
  </div>

  <!-- HERO PROFILE SPLIT -->
  <div class="hero" style="margin-top:0;margin-bottom:60px;">
    <div>
      <div style="background:var(--card);border:1px solid var(--line);border-left:5px solid var(--wine);padding:32px;border-radius:8px;">
        <span class="chip gold" style="margin-bottom:16px;">Pastoral Bio</span>
        <h2 style="font-size:1.8rem;margin-bottom:16px;"><?= esc($pastorName) ?> <span class="ser"><?= esc($pastorEpithet) ?></span></h2>
        <p style="font-size:1.1rem;line-height:1.75;color:var(--ink2);margin-bottom:16px;">Founded in January 1995 by Pastor Morris Omondi and Grace Olweny, Redeemed Gospel Church Eldoret grew through evangelistic crusades, becoming a beacon of spiritual influence in the region.</p>
        <p style="font-size:1.1rem;line-height:1.75;color:var(--ink2);margin-bottom:16px;"><b>Our Ministry.</b> We have planted churches across various locations and authored books on spiritual growth. Our upcoming book, "Kingdom Living," aims to inspire and equip believers.</p>
        <p style="font-size:1.1rem;line-height:1.75;color:var(--ink2);margin-bottom:16px;"><b>Leadership Journey.</b> Pastor Morris Omondi was ordained as a pastor in 2000, an overseer in 2002, and a bishop in 2012, now overseeing Western Region churches.</p>
        <p style="font-size:1.1rem;line-height:1.75;color:var(--ink2);margin-bottom:16px;"><b>Our Family.</b> Bishop Morris and Rev. Grace Omondi have three children - Joan, Eunice, and Pastor Timothy Omondi - who are all devoted to the Lord and active in ministry.</p>
        <p style="font-size:1.1rem;line-height:1.75;color:var(--ink2);margin-bottom:16px;"><b>Our Present.</b> Our sanctuary, built for Christ's glory, continues to grow as we pray for more souls to join God's kingdom. The church has a capacity of 1,200 members.</p>
        <p style="font-size:1.1rem;line-height:1.75;color:var(--ink2);margin-bottom:20px;"><b>Global Impact.</b> Bishop Omondi, a prolific author and sought-after preacher, has shared the Gospel across Africa, Europe, and North America, offering wisdom and encouragement.</p>
        <p style="font-size:1.1rem;line-height:1.75;color:var(--ink2);margin-bottom:20px;">We believe in the Gospel's power to redeem, restore, and renew individuals, families, and communities.</p>
        
        <div style="padding:16px;background:var(--paper);border-radius:6px;font-style:italic;font-size:1.1rem;color:var(--wine);">
          <?= esc($heroVerse) ?>
        </div>

        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
          <a href="<?= BASE_URL ?>invite.php" class="btn sm">Invite Pastor to Minister ✦</a>
          <a href="<?= BASE_URL ?>sermons.php" class="btn sm ghost">Listen to Sermons</a>
        </div>
      </div>
    </div>

    <div>
      <div class="bigphoto">
        <span class="plabel">Pastor &amp; Evangelist</span>
        <div class="ph">
          <img src="<?= esc(img_src($portraitUrl)) ?>" alt="<?= esc($pastorName) ?>">
        </div>
        <div class="pcapbar">
          <b><?= esc($pastorName) ?></b>
          <span>Bishop Morris Omondi · Redeemed Gospel Church Eldoret</span>
        </div>
      </div>
    </div>
  </div>

  <!-- WEEKLY RHYTHM SECTION -->
  <div class="shead" style="margin-top:50px;">
    <p class="idx">Weekly Rhythm</p>
    <h2 class="big">The Pastor's <span class="ser">Week</span></h2>
    <p style="margin-top:8px;color:var(--ink2);">The rhythm of prayer, counseling, radio, and church services</p>
  </div>
  <div class="tblwrap" style="margin-bottom:60px;">
    <?php foreach ($scheduleDays as $day): ?>
      <div class="day <?= $day['is_sunday'] ? 'sunday' : '' ?>">
        <div class="d"><?= esc($day['day_code']) ?></div>
        <div class="a">
          <b><?= esc($day['title']) ?></b>
          <span><?= esc($day['details']) ?></span>
        </div>
        <div class="tm"><?= esc($day['time_chip']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- MOMENTS GALLERY -->
  <div class="shead" style="text-align:center;">
    <p class="idx" style="justify-content:center">Moments from the Field</p>
    <h2 class="big">Ministry in <span class="ser">Photos</span></h2>
  </div>
  <div class="cards">
    <?php foreach ($galleryItems as $photo): ?>
      <figure class="postcard">
        <div class="ph">
          <img src="<?= esc(img_src($photo['image_url'])) ?>" alt="<?= esc($photo['caption']) ?>">
        </div>
        <figcaption><?= esc($photo['caption']) ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
