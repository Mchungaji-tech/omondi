<?php
/**
 * Dedicated Live Broadcast Page — Beacon Gospel Centre
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$streamIsLive = (bool)get_setting('stream_is_live', '1');
$streamViewers = (int)get_setting('stream_viewers', '1243');
$streamYoutube = (string)get_setting('stream_youtube_url', '');
$streamFacebook = (string)get_setting('stream_facebook_url', '');
$liveTitle = (string)get_setting('live_title', 'Sunday Main Service — Live from Eldoret');
$liveSchedules = db_fetch_all("SELECT * FROM live_schedule WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
$member = user_auth_data();

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <div class="shead">
    <p class="idx">Worship in Real Time</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Live <span class="ser">Sermons &amp; Broadcast</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:640px;">Join the congregation in real time. Every service streamed live on YouTube and Facebook from Redeemed Gospel Church Eldoret.</p>
  </div>

  <div class="livegrid">
    <div>
      <div class="liveframe" id="liveframe" style="position:relative;">
        <?php if ($streamIsLive): ?>
          <div class="livebadge"><i></i>LIVE NOW</div>
        <?php else: ?>
          <div class="livebadge" style="background:#7f8c8d;"><i></i>OFFLINE</div>
        <?php endif; ?>
        <div class="viewers">👁 <b id="viewers"><?= number_format($streamViewers) ?></b> watching</div>

        <div class="liveiframes" style="position:absolute;inset:0;">
          <div data-plat="yt" style="position:absolute;inset:0;">
            <?php if ($streamIsLive && !empty($streamYoutube)): ?>
              <?= video_iframe($streamYoutube, '16:9', ['autoplay' => '1']) ?>
            <?php else: ?>
              <div class="offline-note" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:8px;text-align:center;padding:30px;">
                <h2 style="font-size:clamp(2rem,5vw,3.2rem);margin:0 0 10px;letter-spacing:3px;opacity:.95;">STANDING BY</h2>
                <h3 style="font-size:clamp(1.1rem,2.5vw,1.5rem);margin:0 0 14px;opacity:.8;">OFFLINE</h3>
                <p style="max-width:440px;opacity:.75;line-height:1.6;margin:0;">
                  YouTube stream is not live right now. Check the weekly schedule below for upcoming broadcast times, or tune in during Sunday Main Service.
                </p>
              </div>
            <?php endif; ?>
          </div>
          <div data-plat="fb" style="position:absolute;inset:0;display:none;">
            <?php if ($streamIsLive && !empty($streamFacebook)): ?>
              <?= video_iframe($streamFacebook, '16:9', ['autoplay' => '1']) ?>
            <?php else: ?>
              <div class="offline-note" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:8px;text-align:center;padding:30px;">
                <h2 style="font-size:clamp(2rem,5vw,3.2rem);margin:0 0 10px;letter-spacing:3px;opacity:.95;">STANDING BY</h2>
                <h3 style="font-size:clamp(1.1rem,2.5vw,1.5rem);margin:0 0 14px;opacity:.8;">OFFLINE</h3>
                <p style="max-width:440px;opacity:.75;line-height:1.6;margin:0;">
                  Facebook stream is not live right now. Check the weekly schedule below for upcoming broadcast times, or tune in during Sunday Main Service.
                </p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="stream-tabs" style="position:absolute;bottom:12px;left:12px;display:flex;gap:6px;z-index:5;">
          <button class="stream-tab on" data-tab="yt" style="padding:7px 14px;border:0;border-radius:6px;background:#fff;color:#222;font-weight:600;font-size:.84rem;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.2);" <?= empty($streamYoutube) ? 'disabled style="opacity:.5;cursor:not-allowed;padding:7px 14px;border:0;border-radius:6px;background:#fff;color:#222;font-weight:600;font-size:.84rem;box-shadow:0 2px 8px rgba(0,0,0,.2);"' : '' ?>>YouTube</button>
          <button class="stream-tab" data-tab="fb" style="padding:7px 14px;border:0;border-radius:6px;background:rgba(255,255,255,.2);color:#fff;font-weight:600;font-size:.84rem;cursor:pointer;backdrop-filter:blur(6px);" <?= empty($streamFacebook) ? 'disabled style="opacity:.5;cursor:not-allowed;padding:7px 14px;border:0;border-radius:6px;background:rgba(255,255,255,.2);color:#fff;font-weight:600;font-size:.84rem;backdrop-filter:blur(6px);"' : '' ?>>Facebook</button>
        </div>
      </div>

      <div class="livemeta">
        <h3><?= esc($liveTitle) ?> — <span>Live from Eldoret</span></h3>
        <p>Simulcast on YouTube, Facebook &amp; FM 94.6 — Worship, Word &amp; Prayer for the Sick</p>
      </div>
    </div>

    <!-- LIVE CHAT -->
    <div class="chat">
      <header>
        <span>Live Congregation Chat</span>
        <span id="chatCount">Online</span>
      </header>
      <div class="chatlog" id="chatlog" aria-live="polite"></div>
      
      <form class="chatform" id="chatform">
        <?php if ($member): ?>
          <span class="avatar-circle" style="width:28px;height:28px;font-size:11px;"><?= esc($member['initials']) ?></span>
        <?php endif; ?>
        <input id="chatinput" type="text" placeholder="Say amen or share a prayer..." maxlength="120" autocomplete="off" required>
        <button type="submit" aria-label="Send message">➤</button>
      </form>
    </div>
  </div>

  <!-- UPCOMING BROADCAST TIMETABLE -->
  <div class="sched" style="margin-top:40px;">
    <h3 style="font-size:1.4rem;margin-bottom:18px;">Weekly Broadcast Schedule</h3>
    <?php foreach ($liveSchedules as $ls): ?>
      <div class="srow2">
        <span class="tm"><?= esc(strtoupper($ls['day_code'])) ?> · <?= esc($ls['stream_time']) ?></span>
        <b><?= esc($ls['title']) ?></b>
        <span class="cd" data-cd="<?= esc(strtolower($ls['day_code'])) ?>">—</span>
        <button class="remind">Remind me</button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
