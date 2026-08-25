<?php
/**
 * Admin Dashboard Overview
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

ensure_sermon_views_table();

// Handle Live Stream Toggle
if (isset($_GET['toggle_live'])) {
    $currentLive = (bool)get_setting('stream_is_live', '1');
    $newLive = $currentLive ? '0' : '1';
    set_setting('stream_is_live', $newLive);
    log_audit($adminUser['id'], $adminUser['username'], $newLive === '1' ? 'Live stream broadcast started' : 'Live stream ended');
    set_flash('success', $newLive === '1' ? 'Stream status set to LIVE on public website.' : 'Stream broadcast ended.');
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

// Fetch KPIs
$totalSermons = (int)db_fetch_one("SELECT COUNT(*) as cnt FROM sermons")['cnt'];
$newPrayers = (int)db_fetch_one("SELECT COUNT(*) as cnt FROM prayer_requests WHERE status = 'new'")['cnt'];
$newInvites = (int)db_fetch_one("SELECT COUNT(*) as cnt FROM invitations WHERE status = 'new'")['cnt'];
$streamViewers = (int)get_setting('stream_viewers', '1243');
$isLive = (bool)get_setting('stream_is_live', '1');
$totalViews = (int)db_fetch_one("SELECT COUNT(*) as cnt FROM sermon_views")['cnt'];
$mostViewed = db_fetch_one("SELECT s.title, COUNT(v.id) as cnt FROM sermons s LEFT JOIN sermon_views v ON s.id = v.sermon_id GROUP BY s.id ORDER BY cnt DESC LIMIT 1");
$mostViewedTitle = $mostViewed ? esc($mostViewed['title']) : '—';
$mostViewedCount = $mostViewed ? (int)$mostViewed['cnt'] : 0;

// Fetch Audit Logs & Funds
$recentLogs = db_fetch_all("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 10");
$funds = db_fetch_all("SELECT * FROM fund_goals ORDER BY sort_order ASC, id ASC");
?>

<div class="phead">
  <div>
    <h2>Overview <span class="ser">— karibu, <?= esc($adminUser['username']) ?></span></h2>
    <p>Church activity, pastoral inbox, and public portal status at a glance</p>
  </div>
  <div>
    <a href="index.php?toggle_live=1" class="btn sm <?= $isLive ? 'gold' : '' ?>">
      <?= $isLive ? '● Broadcast is LIVE — Click to End' : '○ Broadcast OFFLINE — Go Live' ?>
    </a>
  </div>
</div>

<!-- 4 KPI STATS CARDS -->
<div class="cards4">
  <div class="statcard">
    <b><?= $totalSermons ?></b>
    <small>Sermons in Library</small>
  </div>
  <div class="statcard" style="<?= $newPrayers > 0 ? 'border-top-color:var(--wine);' : '' ?>">
    <b style="<?= $newPrayers > 0 ? 'color:var(--wine);' : '' ?>"><?= $newPrayers ?></b>
    <small>New Prayer Requests</small>
  </div>
  <div class="statcard">
    <b><?= $newInvites ?></b>
    <small>New Invitations</small>
  </div>
  <div class="statcard">
    <b><?= number_format($streamViewers) ?></b>
    <small>Live Viewers Base</small>
  </div>
  <div class="statcard" style="grid-column: span 2;">
    <b><?= number_format($totalViews) ?></b>
    <small>Total Sermon Views (all time)</small>
  </div>
  <div class="statcard">
    <b style="font-size:0.9rem;line-height:1.3;"><?= $mostViewedTitle ?></b>
    <small>Most Viewed Sermon (<?= number_format($mostViewedCount) ?> views)</small>
  </div>
</div>

<!-- 2 COLUMN SPLIT: AUDIT LOGS & FUND PROGRESS -->
<div class="cards4" style="grid-template-columns: 1fr 1fr; margin-bottom: 30px;">
  <!-- RECENT ACTIVITY AUDIT LOG -->
  <div class="tblwrap" style="padding:22px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h4 style="font-size:1.15rem;">Recent Activity &amp; Audit Trail</h4>
      <a href="security.php" style="font-family:var(--mono);font-size:12px;color:var(--wine);">View All &rarr;</a>
    </div>
    <ul class="loglist">
      <?php if (empty($recentLogs)): ?>
        <li>No recent activity recorded.</li>
      <?php else: ?>
        <?php foreach ($recentLogs as $log): ?>
          <li>
            <b><?= date('M d, H:i', strtotime($log['created_at'])) ?></b> — <?= esc($log['action']) ?>
            <span style="font-size:11.5px;color:rgba(25,22,19,.5);">(<?= esc($log['username']) ?>)</span>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>

  <!-- FUND GOALS PROGRESS -->
  <div class="tblwrap" style="padding:22px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h4 style="font-size:1.15rem;">Fund Campaigns Progress</h4>
      <a href="programs.php" style="font-family:var(--mono);font-size:12px;color:var(--wine);">Manage &rarr;</a>
    </div>
    <?php foreach ($funds as $f): 
      $pct = $f['goal_amount'] > 0 ? min(100, round(($f['raised_amount'] / $f['goal_amount']) * 100)) : 0;
    ?>
      <div style="margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;font-family:var(--mono);font-size:12px;margin-bottom:6px;">
          <span><?= esc($f['name']) ?></span>
          <b><?= format_ksh($f['raised_amount']) ?> / <?= format_compact_ksh($f['goal_amount']) ?> (<?= $pct ?>%)</b>
        </div>
        <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- QUICK ACTION SHORTCUTS -->
<div class="tblwrap" style="padding:24px;">
  <h4 style="font-size:1.15rem;margin-bottom:16px;">Pastoral &amp; Administration Quick Shortcuts</h4>
  <div style="display:flex;gap:12px;flex-wrap:wrap;">
    <a href="sermons.php" class="btn sm">+ Add New Sermon</a>
    <a href="prayers.php" class="btn sm gold">Review Prayer Inbox (<?= $newPrayers ?>)</a>
    <a href="invites.php" class="btn sm ghost">Manage Preaching Invitations (<?= $newInvites ?>)</a>
    <a href="live.php" class="btn sm ghost">Broadcast &amp; Live Chat Controls</a>
    <a href="projects.php" class="btn sm">Manage Capital Proposals</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
