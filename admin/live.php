<?php
/**
 * Admin Live Stream & Broadcast Controller
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

// Handle POST updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_stream') {
        $isLive = !empty($_POST['stream_is_live']) ? '1' : '0';
        $viewers = (int)($_POST['stream_viewers'] ?? 1243);

        set_setting('stream_is_live', $isLive);
        set_setting('stream_viewers', (string)$viewers);

        $youtubeUrl = sanitize_input($_POST['stream_youtube_url'] ?? '');
        $facebookUrl = sanitize_input($_POST['stream_facebook_url'] ?? '');
        $liveTitle = sanitize_input($_POST['live_title'] ?? 'Sunday Main Service');
        set_setting('stream_youtube_url', $youtubeUrl);
        set_setting('stream_facebook_url', $facebookUrl);
        set_setting('live_title', $liveTitle);

        log_audit($adminUser['id'], $adminUser['username'], "Updated stream status: " . ($isLive === '1' ? 'LIVE' : 'OFFLINE'));
        set_flash('success', 'Stream settings saved.');
    } elseif ($action === 'add_schedule') {
        $day = sanitize_input($_POST['day_code'] ?? 'Sun');
        $time = sanitize_input($_POST['stream_time'] ?? '9:00 AM');
        $title = sanitize_input($_POST['title'] ?? '');

        if (!empty($title)) {
            db_query("INSERT INTO live_schedule (day_code, stream_time, title) VALUES (?, ?, ?)", "sss", [$day, $time, $title]);
            log_audit($adminUser['id'], $adminUser['username'], "Added live schedule: '$title'");
            set_flash('success', 'Live schedule added.');
        }
    } elseif ($action === 'delete_schedule') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM live_schedule WHERE id = ?", "i", [$id]);
        set_flash('success', 'Schedule item deleted.');
    } elseif ($action === 'delete_chat') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM live_chat_messages WHERE id = ?", "i", [$id]);
        set_flash('success', 'Chat message removed.');
    }

    header('Location: ' . BASE_URL . 'admin/live.php');
    exit;
}

$isLive = (bool)get_setting('stream_is_live', '1');
$streamViewers = (int)get_setting('stream_viewers', '1243');
$streamYoutube = (string)get_setting('stream_youtube_url', '');
$streamFacebook = (string)get_setting('stream_facebook_url', '');
$liveTitle = (string)get_setting('live_title', 'Sunday Main Service — Live from Eldoret');
$schedules = db_fetch_all("SELECT * FROM live_schedule ORDER BY sort_order ASC, id ASC");
$chatMessages = db_fetch_all("SELECT * FROM live_chat_messages ORDER BY id DESC LIMIT 20");
?>

<div class="phead">
  <div>
    <h2>Live Broadcast <span class="ser">&amp; Streams Manager</span></h2>
    <p>Controls live streaming status, YouTube/Facebook live URLs, recurring broadcast schedule, and chat moderation</p>
  </div>
</div>

<div class="cards4" style="grid-template-columns: 1fr 1fr; margin-bottom: 30px;">
  <!-- STREAM STATUS & VIEWERS -->
  <div class="tblwrap" style="padding:26px;">
    <h4 style="font-size:1.2rem;margin-bottom:16px;">Broadcast Status &amp; YouTube/Facebook URLs</h4>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_stream">

      <div class="field">
        <label>Live Stream Broadcast State</label>
        <select name="stream_is_live">
          <option value="1" <?= $isLive ? 'selected' : '' ?>>● LIVE (Active Broadcast on Frontend)</option>
          <option value="0" <?= !$isLive ? 'selected' : '' ?>>○ OFFLINE (Standby Mode)</option>
        </select>
      </div>

      <div class="field">
        <label>Viewer Baseline Count (Fluctuates on frontend)</label>
        <input type="number" name="stream_viewers" value="<?= $streamViewers ?>" required>
      </div>

      <div class="field">
        <label>Live Broadcast Title (Shown to viewers)</label>
        <input type="text" name="live_title" value="<?=esc($liveTitle)?>" placeholder="Sunday Main Service — Live from Eldoret">
      </div>

      <div class="field">
        <label>YouTube Live Embed URL</label>
        <input type="text" name="stream_youtube_url" value="<?=esc($streamYoutube)?>" placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/...">
        <small>Paste the Watch page URL of your YouTube live stream. Will auto-convert to an iframe embed.</small>
      </div>

      <div class="field">
        <label>Facebook Live Video URL</label>
        <input type="text" name="stream_facebook_url" value="<?=esc($streamFacebook)?>" placeholder="https://www.facebook.com/watch/?v=... or /YourPage/videos/...">
        <small>Paste a Facebook Watch / video URL. Leave empty to only show YouTube.</small>
      </div>

      <button type="submit" class="btn" style="margin-top:10px;">Update Broadcast Status &amp; URLs ✦</button>
    </form>
  </div>

  <!-- ADD LIVE SCHEDULE -->
  <div class="tblwrap" style="padding:26px;">
    <h4 style="font-size:1.2rem;margin-bottom:16px;">Add Recurring Stream Schedule</h4>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_schedule">

      <div class="mrow2">
        <div class="field">
          <label>Day of Week</label>
          <select name="day_code">
            <option value="Sun">Sunday (Sun)</option>
            <option value="Wed">Wednesday (Wed)</option>
            <option value="Fri">Friday (Fri)</option>
            <option value="Mon">Monday (Mon)</option>
            <option value="Tue">Tuesday (Tue)</option>
            <option value="Thu">Thursday (Thu)</option>
            <option value="Sat">Saturday (Sat)</option>
          </select>
        </div>
        <div class="field">
          <label>Time</label>
          <input type="text" name="stream_time" placeholder="e.g. 9:00 AM" required>
        </div>
      </div>

      <div class="field">
        <label>Broadcast Title</label>
        <input type="text" name="title" placeholder="e.g. Midweek Bible Exposition" required>
      </div>

      <button type="submit" class="btn sm gold" style="margin-top:8px;">+ Add Schedule Line</button>
    </form>
  </div>
</div>

<!-- SCHEDULES TABLE -->
<div class="tblwrap" style="margin-bottom:30px;">
  <div style="padding:18px 20px;border-bottom:1px solid var(--line);">
    <h4>Configured Stream Schedules</h4>
  </div>
  <table>
    <thead>
      <tr>
        <th>Day</th>
        <th>Time</th>
        <th>Title</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($schedules as $sch): ?>
        <tr>
          <td><span class="chip wine"><?= esc($sch['day_code']) ?></span></td>
          <td class="mono"><?= esc($sch['stream_time']) ?></td>
          <td><b><?= esc($sch['title']) ?></b></td>
          <td>
            <form method="post" style="display:inline;" onsubmit="return confirm('Remove this schedule item?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_schedule">
              <input type="hidden" name="id" value="<?= $sch['id'] ?>">
              <button type="submit" class="btn sm danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- RECENT CHAT MODERATION -->
<div class="tblwrap">
  <div style="padding:18px 20px;border-bottom:1px solid var(--line);">
    <h4>Live Chat Moderation (Latest Messages)</h4>
  </div>
  <table>
    <thead>
      <tr>
        <th>Sender</th>
        <th>Location</th>
        <th>Message Content</th>
        <th>Time</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($chatMessages)): ?>
        <tr><td colspan="5" style="text-align:center;padding:20px;">No chat messages.</td></tr>
      <?php else: ?>
        <?php foreach ($chatMessages as $msg): ?>
          <tr>
            <td><b><?= esc($msg['sender_name']) ?></b></td>
            <td><?= esc($msg['location']) ?></td>
            <td><?= esc($msg['message']) ?></td>
            <td class="mono" style="font-size:12px;"><?= date('M d, H:i', strtotime($msg['created_at'])) ?></td>
            <td>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_chat">
                <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                <button type="submit" class="btn sm danger">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>