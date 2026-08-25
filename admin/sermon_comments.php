<?php
/**
 * Admin: Sermon Comments Moderation
 * Approve / Unapprove / Delete threaded comments on sermons.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

auth_require();
ensure_sermon_comments_table();

$currentPage = 'sermon_comments';
$toast = null;

// ---------- ACTIONS ----------
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$cid = (int)($_POST['cid'] ?? $_GET['cid'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve' && $cid > 0) {
  csrf_verify();
    db_query("UPDATE sermon_comments SET is_approved = 1 WHERE id = ?", "i", [$cid]);
    $toast = ['type' => 'success', 'message' => 'Comment #' . $cid . ' approved ✔'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'unapprove' && $cid > 0) {
  csrf_verify();
    db_query("UPDATE sermon_comments SET is_approved = 0 WHERE id = ?", "i", [$cid]);
    $toast = ['type' => 'success', 'message' => 'Comment #' . $cid . ' unapproved'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && $cid > 0) {
  csrf_verify();
    // Also delete replies (MySQL FK cascade fallback — manual)
    db_query("DELETE FROM sermon_comments WHERE id = ? OR parent_id = ?", "ii", [$cid, $cid]);
    $toast = ['type' => 'success', 'message' => 'Comment #' . $cid . ' (and replies) deleted'];
}
if ($action === 'approve_all_pending') {
  csrf_verify();
    db_query("UPDATE sermon_comments SET is_approved = 1 WHERE is_approved = 0");
    $toast = ['type' => 'success', 'message' => 'All pending comments approved ✔'];
}

// ---------- FILTER ----------
$filter = $_GET['f'] ?? 'all';   // all | pending | approved
$sid = (int)($_GET['sid'] ?? 0);
$where = "WHERE 1=1";
$params = [];
$types = "";
if ($filter === 'pending') { $where .= " AND is_approved = 0"; }
if ($filter === 'approved') { $where .= " AND is_approved = 1"; }
if ($sid > 0) { $where .= " AND sermon_id = ?"; $params[] = $sid; $types .= "i"; }
$orderBy = "ORDER BY is_approved ASC, created_at DESC";
$rows = db_fetch_all("SELECT * FROM sermon_comments $where $orderBy LIMIT 400", $types, $params);

// ---------- STATS ----------
$totalAll = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM sermon_comments")['c'] ?? 0);
$totalPending = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM sermon_comments WHERE is_approved = 0")['c'] ?? 0);
$totalApproved = $totalAll - $totalPending;
$sermonList = db_fetch_all("SELECT id, title FROM sermons ORDER BY id DESC LIMIT 200");
$csrf = csrf_token();

require_once __DIR__ . '/includes/admin_header.php';

function rowClass($r) { return (int)$r['is_approved'] === 1 ? '' : 'row-pend'; }
function cardComment($r, $sermonTitleByID, $csrf, $depth = 0) {
    $sid = (int)$r['sermon_id'];
    $stitle = $sermonTitleByID[$sid] ?? ('Sermon #' . $sid);
    $approved = (int)$r['is_approved'] === 1;
    $init = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $r['author_name'] ?: '?'), 0, 2));
    $color = $r['user_id'] ? '#8C1D2F' : '#' . dechex(crc32($r['author_name']) % 0xFFFFFF + 0x222222);
    $isReply = $depth > 0;
    ?>
    <tr class="<?= rowClass($r) ?>" style="<?= $isReply ? 'background:rgba(196,154,80,.03);' : '' ?>">
      <td style="padding-left:<?= 10 + $depth * 22 ?>px;">
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:30px;height:30px;border-radius:50%;background:<?= $color ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0;"><?= $init ?></div>
          <div>
            <div style="font-weight:600;"><?= esc($r['author_name']) ?>
              <?php if ($r['user_id']): ?><span class="chip" style="background:rgba(140,29,47,.08);color:var(--wine);margin-left:6px;">MEMBER</span><?php endif; ?>
              <?php if (!$approved): ?><span class="chip" style="background:var(--gold);color:var(--warmdark);margin-left:6px;">PENDING</span><?php endif; ?>
              <?php if ($isReply): ?><span class="chip" style="background:var(--paper2);margin-left:6px;">REPLY</span><?php endif; ?>
            </div>
            <div style="font-size:10.5px;color:var(--ink2);font-family:var(--mono);letter-spacing:.05em;margin-top:2px;">
              <?= esc($r['author_email']) ?>
              <?php if (!empty($r['author_location'])): ?> · <?= esc($r['author_location']) ?><?php endif; ?>
            </div>
          </div>
        </div>
      </td>
      <td style="max-width:320px;">
        <a href="sermons.php?edit=<?= $sid ?>" style="text-decoration:none;color:var(--ink);font-weight:500;"><?= esc($stitle) ?></a>
        <div style="font-family:var(--mono);font-size:11.5px;color:var(--ink2);letter-spacing:.08em;text-transform:uppercase;margin-top:3px;">Sermon #<?= $sid ?></div>
      </td>
      <td style="max-width:460px;">
        <div style="font-size:.95rem;line-height:1.55;color:var(--ink);"><?= nl2br(esc($r['comment_text'])) ?></div>
        <div style="display:flex;gap:10px;margin-top:8px;font-family:var(--mono);font-size:11.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--ink2);">
          <span>🕒 <?= date('M j, Y · g:i A', strtotime($r['created_at'])) ?></span>
          <span>❤️ <?= number_format((int)$r['likes']) ?></span>
          <span>ID #<?= (int)$r['id'] ?></span>
          <span>PARENT #<?= (int)($r['parent_id'] ?? 0) ?></span>
        </div>
      </td>
      <td>
        <form method="post" style="display:inline-flex;flex-wrap:wrap;gap:5px;">
          <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
          <input type="hidden" name="cid" value="<?= (int)$r['id'] ?>">
          <?php if (!$approved): ?>
            <button type="submit" name="action" value="approve" class="btn xsm ok">✔ Approve</button>
          <?php else: ?>
            <button type="submit" name="action" value="unapprove" class="btn xsm warn">Unapprove</button>
          <?php endif; ?>
          <button type="submit" name="action" value="delete" class="btn xsm danger" onclick="return confirm('Delete comment #<?= (int)$r['id'] ?>? This removes its replies too.');">🗑 Delete</button>
          <a type="button" class="btn xsm ghost" target="_blank" href="<?= BASE_URL ?>sermons.php?watch=<?= $sid ?>">👁 View</a>
        </form>
      </td>
    </tr>
    <?php
}

// Group rows by parent for flat threaded display
$byParent = [];
$roots = [];
foreach ($rows as $r) {
    $pid = (int)($r['parent_id'] ?? 0);
    if ($pid <= 0) $roots[] = $r;
    else {
        if (!isset($byParent[$pid])) $byParent[$pid] = [];
        $byParent[$pid][] = $r;
    }
}
$sermonTitleByID = [];
foreach ($sermonList as $s) $sermonTitleByID[(int)$s['id']] = $s['title'];
?>

<div class="page-head">
  <div>
    <h1>Sermon <span style="color:var(--wine);">Comments</span></h1>
    <p>Moderate conversations left on sermons. Approve new ones, delete spam, and keep the community kind.</p>
  </div>
  <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
    <button type="submit" name="action" value="approve_all_pending" class="btn" <?= $totalPending === 0 ? 'disabled style="opacity:.6"' : '' ?>>✔ Approve All (<?= number_format($totalPending) ?> pending)</button>
    <a href="<?= BASE_URL ?>sermons.php" class="btn ghost" target="_blank">👁 Open Sermon Library</a>
  </form>
</div>

<!-- STATS ROW -->
<div class="g3" style="margin-bottom:18px;">
  <div class="kpi">
    <span class="v" style="color:var(--wine);"><?= number_format($totalAll) ?></span>
    <span class="l">Total comments</span>
  </div>
  <div class="kpi">
    <span class="v" style="color:var(--gold2);"><?= number_format($totalPending) ?></span>
    <span class="l">Awaiting review</span>
  </div>
  <div class="kpi">
    <span class="v" style="color:#27ae60;"><?= number_format($totalApproved) ?></span>
    <span class="l">Published</span>
  </div>
</div>

<!-- FILTER BAR -->
<form method="get" class="filterbar" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:14px 16px;background:var(--paper2);border-radius:10px;border:1px solid var(--line);margin-bottom:18px;">
  <label style="font-family:var(--mono);font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink2);">Filter:</label>
  <div class="filters">
    <a href="sermon_comments.php?f=all<?= $sid ? '&sid=' . $sid : '' ?>" class="<?= $filter === 'all' ? 'on' : '' ?>">All (<?= number_format($totalAll) ?>)</a>
    <a href="sermon_comments.php?f=pending<?= $sid ? '&sid=' . $sid : '' ?>" class="<?= $filter === 'pending' ? 'on' : '' ?>" style="<?= $totalPending > 0 ? 'color:#a9751e;border-color:var(--gold);' : '' ?>">Pending (<?= number_format($totalPending) ?>)</a>
    <a href="sermon_comments.php?f=approved<?= $sid ? '&sid=' . $sid : '' ?>" class="<?= $filter === 'approved' ? 'on' : '' ?>">Approved (<?= number_format($totalApproved) ?>)</a>
  </div>
  <select name="sid" style="margin-left:auto;padding:7px 10px;border:1px solid var(--line);border-radius:6px;background:#fff;min-width:200px;" onchange="this.form.submit();">
    <option value="0">All Sermons</option>
    <?php foreach ($sermonList as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= $sid === (int)$s['id'] ? 'selected' : '' ?><?php
        $pendingFor = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM sermon_comments WHERE sermon_id = ? AND is_approved = 0", "i", [$s['id']])['c'] ?? 0);
      ?>>#<?= (int)$s['id'] ?> · <?= esc($s['title']) ?><?= $pendingFor > 0 ? ' (' . $pendingFor . ' pending)' : '' ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($filter !== 'all' || $sid > 0): ?>
    <a href="sermon_comments.php" class="btn sm ghost">Reset</a>
  <?php endif; ?>
</form>

<!-- TABLE -->
<div class="tblwrap" style="background:#fff;">
  <table class="dt">
    <thead>
      <tr>
        <th>Author</th>
        <th>Sermon</th>
        <th>Comment</th>
        <th style="width:220px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($roots)): ?>
        <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--ink2);">No comments found for this filter.</td></tr>
      <?php else: foreach ($roots as $r):
        cardComment($r, $sermonTitleByID, $csrf, 0);
        // replies
        $replies = $byParent[(int)$r['id']] ?? [];
        foreach ($replies as $rp) cardComment($rp, $sermonTitleByID, $csrf, 1);
      endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
