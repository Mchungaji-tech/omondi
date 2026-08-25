<?php
/**
 * Beacon Gospel Centre — Streamlined Public Homepage
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Fetch site profile & settings
$pastorName = get_setting('pastor_name', 'Bishop Morris Omondi');
$pastorEpithet = get_setting('pastor_epithet', 'of Eldoret');
$tagline = get_setting('tagline', 'Preacher of the Gospel · 23 Years in the Vineyard');
$heroVerse = get_setting('hero_verse', '“Your word is a lamp to my feet and a light to my path.” — Psalm 119:105');
$portraitUrl = get_setting('portrait_url', 'https://picsum.photos/seed/eldoret-pastor-portrait/900/1125');
$bio1 = get_setting('bio1');

// Split pastor name into 2 lines for the big hero poster (last word = line 2, rest = line 1)
$_nameParts = preg_split('/\s+/', trim($pastorName));
$_line1 = '';
$_line2 = '';
if (count($_nameParts) <= 1) {
    $_line1 = $pastorName;
} else {
    $_line2 = array_pop($_nameParts);
    $_line1 = implode(' ', $_nameParts);
}
$_posterLine1 = strtoupper($_line1);
$_posterLine2 = strtoupper($_line2);
$sun1 = get_setting('sun1', '6:30 AM — Dawn Service');
$sun2 = get_setting('sun2', '9:00 AM — Main Service · Live');
$sun3 = get_setting('sun3', '2:00 PM — New Believers\' Class');
$radioStation = get_setting('radio_station', 'Voice of the Gospel 94.6 FM');
$streamIsLive = (bool)get_setting('stream_is_live', '1');
$streamViewers = (int)get_setting('stream_viewers', '1243');
$streamYoutube = (string)get_setting('stream_youtube_url', '');
$streamFacebook = (string)get_setting('stream_facebook_url', '');
$liveTitle = (string)get_setting('live_title', 'Sunday Main Service — Live from Eldoret');
$mpesaPaybill = get_setting('mpesa_paybill', '453 210');

$ministries = db_fetch_all("SELECT * FROM ministries ORDER BY sort_order ASC, id ASC LIMIT 3");
$fundGoals = db_fetch_all("SELECT * FROM fund_goals ORDER BY sort_order ASC, id ASC LIMIT 3");
$events = db_fetch_all("SELECT * FROM events WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 3");
$testimonies = db_fetch_all("SELECT * FROM testimonies WHERE is_approved = 1 ORDER BY sort_order ASC, id ASC LIMIT 4");
$galleryItems = db_fetch_all("SELECT * FROM gallery ORDER BY sort_order ASC, id ASC LIMIT 4");

$member = user_auth_data();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ============ MASTHEAD & HERO ============ -->
<div class="mast" id="top">
  <div class="topline">
    <span class="topline-loc">Redeemed Gospel Church Eldoret</span>
    <?php if (!$member): ?>
      <a href="<?= BASE_URL ?>register.php" class="topline-slot-cta topline-join">Join</a>
      <a href="<?= BASE_URL ?>login.php" class="topline-slot-cta topline-signin">Sign In</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>invite.php" class="topline-invite btn sm gold">Invite Pastor</a>
  </div>
  <div class="hero">
    <div>
      <h1 class="poster" id="posterName">
        <span class="lm"><span><?= esc($_posterLine1) ?></span></span>
        <span class="lm"><span><?= esc($_posterLine2) ?> <em><?= esc($pastorEpithet) ?></em></span></span>
      </h1>
      <p class="mtag">✦ <?= esc($tagline) ?></p>
      <p class="scr"><?= esc($heroVerse) ?></p>
      <div class="heroctas">
        <a href="<?= BASE_URL ?>live.php" class="btn">Watch Live Broadcast</a>
        <a href="<?= BASE_URL ?>sermons.php" class="btn ghost">Sermon Archive</a>
        <a href="<?= BASE_URL ?>proposals.php" class="btn gold">Capital Proposals</a>
      </div>
    </div>
    <div>
      <div class="bigphoto kb reveal">
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

  <div class="mrule"></div>

  <!-- ABOUT & SUNDAY BAND -->
  <div class="mband">
    <div class="mcard mabout">
      <span class="mc-lab">The Shepherd</span>
      <img class="pola hide-mobile" src="https://picsum.photos/seed/pastor-with-bible/300/340" alt="Rev. Langat with his Bible">
      <p><?= nl2br(esc($bio1)) ?></p>
      <div class="sig">— <?= esc($pastorName) ?></div>
      <p class="mcap"><b>Tip:</b> swap the large frame above for the pastor's official photo (4:5 ratio works best).</p>
      <div style="margin-top:14px;">
        <a href="#journey" class="btn sm ghost">Read the full Journey &rarr;</a>
      </div>
    </div>
    <div class="suncard">
      <span class="mc-lab" style="background:var(--gold);color:var(--warmdark)">This Sunday</span>
      <h3>The Lord's Day</h3>
      <ul>
        <li><b>DAWN</b> <?= esc($sun1) ?></li>
        <li><b>MAIN</b> <?= esc($sun2) ?></li>
        <li><b>CLASS</b> <?= esc($sun3) ?></li>
      </ul>
      <div class="cdw">
        <div><b id="cd-d">0</b><small>DAYS</small></div>
        <div><b id="cd-h">0</b><small>HRS</small></div>
        <div><b id="cd-m">0</b><small>MIN</small></div>
        <div><b id="cd-s">0</b><small>SEC</small></div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>live.php" class="btn gold" style="flex:1;padding:12px;">Watch Live</a>
        <a href="<?= BASE_URL ?>#support" class="btn ghost" style="flex:1;padding:12px;color:var(--paper);border-color:rgba(247,242,233,.4)">Give</a>
      </div>
    </div>
  </div>
</div>

<!-- ============ RUNNING WORDS TICKER ============ -->
<div class="ticker" aria-hidden="true">
  <div class="track">
    <span>Jesus Is Lord&nbsp;&nbsp;✦&nbsp;&nbsp;Welcome Home&nbsp;&nbsp;✦&nbsp;&nbsp;Hallelujah&nbsp;&nbsp;✦&nbsp;&nbsp;Grace Upon Grace&nbsp;&nbsp;✦&nbsp;&nbsp;Eldoret · Kitale · Kisumu · Nakuru&nbsp;&nbsp;✦&nbsp;&nbsp;Praise The Lord&nbsp;&nbsp;✦&nbsp;&nbsp;</span>
    <span>Jesus Is Lord&nbsp;&nbsp;✦&nbsp;&nbsp;Welcome Home&nbsp;&nbsp;✦&nbsp;&nbsp;Hallelujah&nbsp;&nbsp;✦&nbsp;&nbsp;Grace Upon Grace&nbsp;&nbsp;✦&nbsp;&nbsp;Eldoret · Kitale · Kisumu · Nakuru&nbsp;&nbsp;✦&nbsp;&nbsp;Praise The Lord&nbsp;&nbsp;✦&nbsp;&nbsp;</span>
  </div>
</div>

<!-- ============ THE JOURNEY ============ -->
<section id="journey">
  <div class="wrap jgrid">
    <div class="jleft">
      <p class="idx">(01) — The Journey</p>
      <h2 class="big reveal">A Legacy of<br><span class="ser">Faith</span></h2>
      <p style="margin-top:22px;max-width:380px;color:var(--ink2);">Founded in January 1995, Redeemed Gospel Church Eldoret continues to redeem, restore, and renew lives through the power of the Gospel.</p>
      <div class="jphoto kb reveal"><img src="https://picsum.photos/seed/redeemed-gospel-church/660/440" alt="Redeemed Gospel Church Eldoret"></div>
    </div>
    <div class="tline">
      <div class="titem reveal"><div class="yr">1995</div><h3>Founded in January</h3><p>Founded by Pastor Morris Omondi and Grace Olweny, Redeemed Gospel Church Eldoret grew through evangelistic crusades and became a beacon of spiritual influence in the region.</p><span class="tag">Our Beginning</span></div>
      <div class="titem reveal"><div class="yr">Ministry</div><h3>Our Ministry</h3><p>We have planted churches across various locations and authored books on spiritual growth. Our upcoming book, "Kingdom Living," aims to inspire and equip believers.</p><span class="tag">Church Planting</span></div>
      <div class="titem reveal"><div class="yr">2000</div><h3>Leadership Journey</h3><p>Pastor Morris Omondi was ordained as a pastor in 2000, an overseer in 2002, and a bishop in 2012. He now oversees Western Region churches.</p><span class="tag">Leadership</span></div>
      <div class="titem reveal"><div class="yr">Family</div><h3>Our Family</h3><p>Bishop Morris and Rev. Grace Omondi have three children - Joan, Eunice, and Pastor Timothy Omondi - who are devoted to the Lord and active in ministry.</p><span class="tag">Faith at Home</span></div>
      <div class="titem reveal"><div class="yr">Today</div><h3>Our Present</h3><p>Our sanctuary, built for Christ's glory, continues to grow as we pray for more souls to join God's kingdom. The church has a capacity of 1,200 members.</p><span class="tag">Growing Together</span></div>
      <div class="titem reveal"><div class="yr">Global</div><h3>Global Impact</h3><p>Bishop Omondi, a prolific author and sought-after preacher, has shared the Gospel across Africa, Europe, and North America, offering wisdom and encouragement.</p><span class="tag">Beyond Borders</span></div>
    </div>
  </div>
</section>

<!-- ============ STATS BAR ============ -->
<div class="stats">
  <div class="wrap">
    <div class="stat reveal"><b><span data-count="23">0</span>+</b><small>Years in Ministry</small></div>
    <div class="stat reveal"><b><span data-count="4">0</span></b><small>Churches Planted</small></div>
    <div class="stat reveal"><b><span data-count="1800">0</span>+</b><small>Baptisms by Immersion</small></div>
    <div class="stat reveal"><b><span data-count="120">0</span>k</b><small>Weekly Radio Listeners</small></div>
  </div>
</div>

<!-- ============ LIVE STREAM & CHAT PREVIEW ============ -->
<section id="live">
  <div class="wrap">
    <div class="shead" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;">
      <div>
        <p class="idx">(03) — Live Worship</p>
        <h2 class="big reveal">Live <span class="ser">Broadcast</span></h2>
      </div>
      <div>
        <a href="<?= BASE_URL ?>live.php" class="btn sm">Full Live Hub &rarr;</a>
      </div>
    </div>

    <div class="livegrid">
      <div>
        <div class="liveframe" id="liveframe">
          <?php if ($streamIsLive): ?>
            <div class="livebadge"><i></i>LIVE</div>
          <?php else: ?>
            <div class="livebadge" style="background:#7f8c8d;"><i></i>OFFLINE</div>
          <?php endif; ?>
          <div class="viewers">👁 <b id="viewers"><?= number_format($streamViewers) ?></b> watching</div>
          <div class="stream-tabs" style="position:absolute;bottom:14px;left:14px;z-index:3;">
            <button data-plat="yt" class="<?= !empty($streamYoutube) ? 'on' : '' ?>">YouTube</button>
            <button data-plat="fb" class="<?= empty($streamYoutube) && !empty($streamFacebook) ? 'on' : '' ?>" <?= empty($streamFacebook) ? 'disabled style="opacity:.4"' : '' ?>>Facebook</button>
          </div>
          <div class="liveiframes" style="position:absolute;inset:0;">
            <div data-plat="yt" style="position:absolute;inset:0;<?= !empty($streamYoutube) && $streamIsLive ? '' : 'display:none'; ?>">
              <?php if ($streamIsLive && !empty($streamYoutube)): ?>
                <?= video_iframe($streamYoutube, '16:9', ['autoplay'=>'1']) ?>
              <?php endif; ?>
              <?php if (!$streamIsLive || empty($streamYoutube)): ?>
                <div class="offline-note"><div class="big"><?= $streamIsLive ? 'STANDING BY' : 'OFFLINE · NEXT BROADCAST SOON' ?></div><p><?= $streamIsLive ? 'Live stream is starting on YouTube. If video does not auto-load above, tap play or join via your YouTube app.' : 'Watch live on YouTube & Facebook during scheduled service times. Next broadcast: Sunday 9:00 AM EAT.' ?></p></div>
              <?php endif; ?>
            </div>
            <div data-plat="fb" style="position:absolute;inset:0;display:none;">
              <?php if (!empty($streamFacebook)): ?>
                <?= video_iframe($streamFacebook, '16:9', ['autoplay'=>'1']) ?>
              <?php endif; ?>
              <?php if (empty($streamFacebook)): ?>
                <div class="offline-note"><div class="big"><?= $streamIsLive ? 'STANDING BY' : 'OFFLINE · NEXT BROADCAST SOON' ?></div><p><?= $streamIsLive ? 'Live stream is starting on Facebook. If video does not auto-load above, tap play or join via your Facebook app.' : 'Watch live on YouTube & Facebook during scheduled service times. Next broadcast: Sunday 9:00 AM EAT.' ?></p></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="livemeta">
          <h3><?= esc($liveTitle) ?> — <span>Live from Eldoret</span></h3>
          <p>YouTube · Facebook · FM 94.6 — Worship, Word &amp; Prayer for the Sick</p>
        </div>
      </div>

      <div class="chat reveal">
        <header>
          <span>Live Chat</span>
          <span id="chatCount">Online</span>
        </header>
        <div class="chatlog" id="chatlog" aria-live="polite"></div>
        <form class="chatform" id="chatform">
          <?php if ($member): ?>
            <span class="avatar-circle" style="width:28px;height:28px;font-size:13px;"><?= esc($member['initials']) ?></span>
          <?php endif; ?>
          <input id="chatinput" type="text" placeholder="Say amen or share a prayer..." maxlength="120" autocomplete="off" required>
          <button type="submit" aria-label="Send message">➤</button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ============ MINISTRIES HIGHLIGHTS ============ -->
<section id="ministries">
  <div class="wrap">
    <div class="shead" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;">
      <div>
        <p class="idx">(04) — The Work</p>
        <h2 class="big reveal">Our <span class="ser">Ministries</span></h2>
      </div>
      <div>
        <a href="<?= BASE_URL ?>ministries.php" class="btn sm ghost">View All Ministries &rarr;</a>
      </div>
    </div>

    <div>
      <?php foreach ($ministries as $idx => $m): ?>
        <div class="mrow reveal">
          <div class="num"><?= str_pad($idx + 1, 2, '0', STR_PAD_LEFT) ?></div>
          <div>
            <h3><?= esc($m['name']) ?> <span><?= esc($m['age_span']) ?></span></h3>
            <p><?= esc($m['description']) ?></p>
            <?php if (!empty($m['stats'])): ?>
              <span class="mstat"><?= esc($m['stats']) ?></span>
            <?php endif; ?>
          </div>
          <div class="mimg">
            <img src="<?= esc(img_src($m['image_url'])) ?>" alt="<?= esc($m['name']) ?>">
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ GIVING & PROPOSALS PREVIEW ============ -->
<section id="support">
  <div class="wrap">
    <div class="shead" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;">
      <div>
        <p class="idx">(05) — Partner With Us</p>
        <h2 class="big reveal">Community <span class="ser">Support &amp; Proposals</span></h2>
      </div>
      <div style="display:flex;gap:10px;">
        <button type="button" class="currency-toggle" data-currency-toggle aria-label="Switch between Kenyan shillings and US dollars">KSh / USD</button>
        <a href="<?= BASE_URL ?>proposals.php" class="btn sm gold">View Project Proposals &rarr;</a>
        <a href="<?= BASE_URL ?>giving.php" class="btn sm ghost">Giving Channels &rarr;</a>
      </div>
    </div>

    <div class="spgrid">
      <div class="reveal">
         <h3 style="font-size:1.5rem;margin-bottom:20px;">Current Capital Goals</h3>
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
      </div>

      <div class="ways reveal">
        <div class="way">
          <h3>M-Pesa <span class="pill">Kenya · Fastest</span></h3>
          <div class="wrow">
            <span>Paybill</span>
            <b><?= esc($mpesaPaybill) ?></b>
            <button class="copy" data-copy="<?= esc(str_replace(' ', '', $mpesaPaybill)) ?>">Copy</button>
          </div>
          <div class="wrow">
            <span>Account No.</span>
            <b>Programme Code (BUILD, CAMP, WIDOWS)</b>
          </div>
          <a href="<?= BASE_URL ?>giving.php" class="btn sm gold" style="margin-top:14px;width:100%;text-align:center;">All Giving Details &rarr;</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ PRAYER & INVITATION CTAS ============ -->
<section id="connect" style="background:var(--paper2);">
  <div class="wrap">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;">
      <!-- PRAYER CARD -->
      <div class="card" style="border-top:4px solid var(--wine);">
        <h3 style="font-size:1.6rem;margin-bottom:10px;">Need Prayer?</h3>
        <p style="color:var(--ink2);margin-bottom:20px;font-size:1rem;">Send your confidential request to Rev. Langat and the intercession team for Tuesday prayer mountain.</p>
        <a href="<?= BASE_URL ?>prayer.php" class="btn" style="width:100%;">Submit Prayer Request &rarr;</a>
      </div>

      <!-- INVITE PASTOR CARD -->
      <div class="card" style="border-top:4px solid var(--gold);">
        <h3 style="font-size:1.6rem;margin-bottom:10px;">Invite Pastor to Minister</h3>
        <p style="color:var(--ink2);margin-bottom:20px;font-size:1rem;">Hosting a revival, open-air crusade, youth camp, or church conference? Send an invitation.</p>
        <a href="<?= BASE_URL ?>invite.php" class="btn gold" style="width:100%;">Invite the Pastor ✦</a>
      </div>
    </div>
  </div>
</section>

<!-- ============ TESTIMONIES ============ -->
<section id="voices" class="hide-mobile">
  <div class="wrap">
    <div class="shead" style="text-align:center">
      <p class="idx" style="justify-content:center">Voices</p>
      <h2 class="big reveal">The Flock <span class="ser">Speaks</span></h2>
    </div>
    <div class="tstack" id="tstack">
      <?php foreach ($testimonies as $t): ?>
        <div class="tcard">
          <div class="q">“<?= esc($t['quote']) ?>”</div>
          <div class="who"><?= esc($t['attribution']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="tctrl">
      <button id="tPrev" aria-label="Previous testimony">←</button>
      <button id="tNext" aria-label="Next testimony">→</button>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
