<?php
/**
 * Admin Partner Commitments & Pledges Manager
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id = (int)$_POST['id'];
        $status = sanitize_input($_POST['status'] ?? 'confirmed');
        db_query("UPDATE project_commitments SET status = ? WHERE id = ?", "si", [$status, $id]);
        log_audit($adminUser['id'], $adminUser['username'], "Updated commitment (ID #$id) status to '$status'");
        set_flash('success', "Commitment status updated.");
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM project_commitments WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Deleted commitment record (ID #$id)");
        set_flash('success', "Commitment record removed.");
    }

    header('Location: ' . BASE_URL . 'admin/commitments.php');
    exit;
}

$commitments = db_fetch_all("
    SELECT c.*, p.name as project_name 
    FROM project_commitments c 
    LEFT JOIN projects p ON c.project_id = p.id 
    ORDER BY c.id DESC
");

$totalPledged = 0;
foreach ($commitments as $c) {
    $totalPledged += (float)$c['amount_pledged'];
}
?>

<div class="phead">
  <div>
    <h2>Partner Commitments <span class="ser">&amp; Pledges</span></h2>
    <p><?= count($commitments) ?> partner pledges · Total Pledged: <b><?= format_ksh($totalPledged) ?></b></p>
  </div>
  <div>
    <a href="proposal_view.php" class="btn sm gold">View Partner Proposals &rarr;</a>
  </div>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>Ref No.</th>
        <th>Partner Name &amp; Contact</th>
        <th>Project</th>
        <th>Amount Pledged</th>
        <th>Notes / Dedication</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($commitments)): ?>
        <tr><td colspan="7" style="text-align:center;padding:30px;">No partner commitments submitted yet.</td></tr>
      <?php else: ?>
        <?php foreach ($commitments as $c): ?>
          <tr>
            <td><span class="chip wine"><?= esc($c['ref_no']) ?></span></td>
            <td>
              <b><?= esc($c['partner_name']) ?></b><br>
              <span class="mono" style="font-size:10.5px;color:var(--ink2);"><?= esc($c['partner_contact']) ?></span>
            </td>
            <td><b><?= esc($c['project_name'] ?: 'General Fund') ?></b></td>
            <td class="mono" style="font-weight:600;color:var(--wine);"><?= format_ksh($c['amount_pledged']) ?></td>
            <td style="font-size:12px;max-width:220px;"><?= esc($c['notes']) ?></td>
            <td>
              <span class="chip <?= $c['status'] === 'confirmed' ? 'pine' : ($c['status'] === 'received' ? 'gold' : '') ?>">
                <?= esc($c['status']) ?>
              </span>
            </td>
            <td>
              <div class="act">
                <form method="post" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <?php if ($c['status'] === 'pending'): ?>
                    <button type="submit" name="status" value="confirmed" class="btn sm success">Confirm</button>
                  <?php elseif ($c['status'] === 'confirmed'): ?>
                    <button type="submit" name="status" value="received" class="btn sm gold">Mark Received</button>
                  <?php endif; ?>
                </form>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this commitment record?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
