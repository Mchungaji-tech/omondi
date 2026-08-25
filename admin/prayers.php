<?php
/**
 * Admin Confidential Prayer Inbox Manager
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_prayed') {
        $id = (int)$_POST['id'];
        db_query("UPDATE prayer_requests SET status = 'prayed' WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Marked prayer request (ID #$id) as prayed");
        set_flash('success', "Prayer request marked as prayed 🙏.");
    } elseif ($action === 'archive') {
        $id = (int)$_POST['id'];
        db_query("UPDATE prayer_requests SET status = 'archived' WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Archived prayer request (ID #$id)");
        set_flash('success', "Prayer request archived.");
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM prayer_requests WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Deleted prayer request (ID #$id)");
        set_flash('success', "Prayer request record removed.");
    }

    header('Location: ' . BASE_URL . 'admin/prayers.php');
    exit;
}

$statusFilter = sanitize_input($_GET['status'] ?? 'all');
$where = ($statusFilter !== 'all') ? "WHERE status = '$statusFilter'" : "";
$prayers = db_fetch_all("SELECT * FROM prayer_requests $where ORDER BY id DESC");
$newCount = (int)db_fetch_one("SELECT COUNT(*) as cnt FROM prayer_requests WHERE status = 'new'")['cnt'];
?>

<div class="phead">
  <div>
    <h2>Prayer <span class="ser">Inbox</span></h2>
    <p>Confidential pastoral intercession team only · Lifted every Tuesday morning on Kaptagat Ridge</p>
  </div>
  <div>
    <div class="chip <?= $newCount > 0 ? 'wine' : 'pine' ?>">
      <?= $newCount ?> New Requests Pending Prayer
    </div>
  </div>
</div>

<!-- FILTER CHIPS -->
<div style="display:flex;gap:8px;margin-bottom:20px;">
  <a href="prayers.php?status=all" class="chip <?= $statusFilter === 'all' ? 'wine' : '' ?>">All Requests</a>
  <a href="prayers.php?status=new" class="chip <?= $statusFilter === 'new' ? 'wine' : '' ?>">New</a>
  <a href="prayers.php?status=prayed" class="chip <?= $statusFilter === 'prayed' ? 'wine' : '' ?>">Prayed 🙏</a>
  <a href="prayers.php?status=archived" class="chip <?= $statusFilter === 'archived' ? 'wine' : '' ?>">Archived</a>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>From &amp; Ref</th>
        <th>Category</th>
        <th>Prayer Request Details</th>
        <th>Status</th>
        <th>Received</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($prayers)): ?>
        <tr><td colspan="6" style="text-align:center;padding:30px;">No prayer requests in this folder.</td></tr>
      <?php else: ?>
        <?php foreach ($prayers as $r): ?>
          <tr>
            <td>
              <?php if ($r['is_anonymous']): ?>
                <i>Anonymous</i>
              <?php else: ?>
                <b><?= esc($r['sender_name'] ?: 'Friend') ?></b><br>
                <span class="mono" style="font-size:12px;color:var(--ink2);"><?= esc($r['contact']) ?></span>
              <?php endif; ?>
              <br><span class="chip" style="font-size:8.5px;padding:2px 6px;margin-top:4px;"><?= esc($r['ref_no']) ?></span>
            </td>
            <td><span class="chip wine"><?= esc($r['category']) ?></span></td>
            <td style="max-width:340px;line-height:1.5;"><?= nl2br(esc($r['request_text'])) ?></td>
            <td>
              <span class="chip <?= $r['status'] === 'new' ? 'gold' : ($r['status'] === 'prayed' ? 'pine' : '') ?>">
                <?= esc($r['status']) ?>
              </span>
            </td>
            <td class="mono" style="font-size:10.5px;white-space:nowrap;">
              <?= date('M d, H:i', strtotime($r['created_at'])) ?>
            </td>
            <td>
              <div class="act">
                <?php if ($r['status'] === 'new'): ?>
                  <form method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mark_prayed">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn sm success">Mark Prayed 🙏</button>
                  </form>
                <?php endif; ?>
                
                <?php if ($r['status'] !== 'archived'): ?>
                  <form method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="archive">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn sm ghost">Archive</button>
                  </form>
                <?php else: ?>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Delete this record permanently?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn sm danger">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
