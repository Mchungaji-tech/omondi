<?php
/**
 * Admin Preaching Invitations Manager
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm') {
        $id = (int)$_POST['id'];
        db_query("UPDATE invitations SET status = 'confirmed' WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Confirmed invitation (ID #$id)");
        set_flash('success', "Invitation confirmed.");
    } elseif ($action === 'decline') {
        $id = (int)$_POST['id'];
        db_query("UPDATE invitations SET status = 'declined' WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Declined invitation (ID #$id)");
        set_flash('success', "Invitation declined.");
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM invitations WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Deleted invitation record (ID #$id)");
        set_flash('success', "Invitation record removed.");
    }

    header('Location: ' . BASE_URL . 'admin/invites.php');
    exit;
}

$statusFilter = sanitize_input($_GET['status'] ?? 'all');
$where = ($statusFilter !== 'all') ? "WHERE status = '$statusFilter'" : "";
$invitations = db_fetch_all("SELECT * FROM invitations $where ORDER BY id DESC");
$pastorName = get_setting('pastor_name', '');
?>

<div class="phead">
  <div>
    <h2>Invitation <span class="ser">Inbox</span></h2>
    <p>Official requests for <?= !empty($pastorName) ? esc($pastorName) : 'the Pastor' ?> to minister at crusades, conferences, and revival services</p>
  </div>
</div>

<!-- FILTER CHIPS -->
<div style="display:flex;gap:8px;margin-bottom:20px;">
  <a href="invites.php?status=all" class="chip <?= $statusFilter === 'all' ? 'wine' : '' ?>">All Invitations</a>
  <a href="invites.php?status=new" class="chip <?= $statusFilter === 'new' ? 'wine' : '' ?>">New</a>
  <a href="invites.php?status=confirmed" class="chip <?= $statusFilter === 'confirmed' ? 'wine' : '' ?>">Confirmed</a>
  <a href="invites.php?status=declined" class="chip <?= $statusFilter === 'declined' ? 'wine' : '' ?>">Declined</a>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>From &amp; Ref</th>
        <th>Church / Org</th>
        <th>Service Type</th>
        <th>Preferred Date</th>
        <th>Town / County</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($invitations)): ?>
        <tr><td colspan="7" style="text-align:center;padding:30px;">No invitations in this folder.</td></tr>
      <?php else: ?>
        <?php foreach ($invitations as $inv): ?>
          <tr>
            <td>
              <b><?= esc($inv['contact_name']) ?></b><br>
              <span class="mono" style="font-size:12px;color:var(--ink2);"><?= esc($inv['phone_email']) ?></span><br>
              <span class="chip" style="font-size:8.5px;padding:2px 6px;margin-top:4px;"><?= esc($inv['ref_no']) ?></span>
            </td>
            <td>
              <b><?= esc($inv['church_org']) ?></b>
              <?php if (!empty($inv['message'])): ?>
                <p style="font-size:11px;color:var(--ink2);margin-top:4px;"><?= esc($inv['message']) ?></p>
              <?php endif; ?>
            </td>
            <td><span class="chip wine"><?= esc($inv['service_type']) ?></span></td>
            <td class="mono" style="font-size:11px;"><?= esc($inv['preferred_date']) ?></td>
            <td style="font-size:12px;"><?= esc($inv['town_county']) ?></td>
            <td>
              <span class="chip <?= $inv['status'] === 'new' ? 'gold' : ($inv['status'] === 'confirmed' ? 'pine' : '') ?>">
                <?= esc($inv['status']) ?>
              </span>
            </td>
            <td>
              <div class="act">
                <?php if ($inv['status'] !== 'confirmed'): ?>
                  <form method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="confirm">
                    <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                    <button type="submit" class="btn sm success">Confirm</button>
                  </form>
                <?php endif; ?>

                <?php if ($inv['status'] !== 'declined'): ?>
                  <form method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="decline">
                    <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                    <button type="submit" class="btn sm ghost">Decline</button>
                  </form>
                <?php endif; ?>

                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this invitation record?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                  <button type="submit" class="btn sm danger">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
