<?php
/**
 * Admin Member & User Management (Full CRUD)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $newStatus = sanitize_input($_POST['status'] ?? 'active');
        db_query("UPDATE users SET status = ? WHERE id = ?", "si", [$newStatus, $id]);
        log_audit($adminUser['id'], $adminUser['username'], "Updated member (ID #$id) status to '$newStatus'");
        set_flash('success', "Member status updated to $newStatus.");
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM users WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Deleted member account (ID #$id)");
        set_flash('success', "Member account removed.");
    }

    header('Location: ' . BASE_URL . 'admin/users.php');
    exit;
}

$search = sanitize_input($_GET['q'] ?? '');
$where = "";
$params = [];
$types = "";

if (!empty($search)) {
    $where = "WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR location LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
    $types = "ssss";
}

$users = db_fetch_all("
    SELECT u.*,
      (SELECT COUNT(*) FROM prayer_requests p WHERE p.user_id = u.id) as prayer_count,
      (SELECT COUNT(*) FROM invitations i WHERE i.user_id = u.id) as invite_count,
      (SELECT COUNT(*) FROM project_commitments c WHERE c.user_id = u.id) as pledge_count
    FROM users u
    $where
    ORDER BY u.id DESC
", $types, $params);
?>

<div class="phead">
  <div>
    <h2>Member <span class="ser">Accounts</span></h2>
    <p><?= count($users) ?> registered public members · Manage user access, activity, and submissions</p>
  </div>
  <div>
    <form method="get" action="users.php" style="display:flex;gap:8px;">
      <input type="text" name="q" value="<?= esc($search) ?>" placeholder="Search member name, email..." style="padding:7px 12px;border:1px solid var(--line);border-radius:4px;font-family:var(--ser);font-size:.92rem;">
      <button type="submit" class="btn sm">Search</button>
      <?php if (!empty($search)): ?>
        <a href="users.php" class="btn sm ghost">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>Member</th>
        <th>Contact &amp; Location</th>
        <th>Initials</th>
        <th>Activity</th>
        <th>Status</th>
        <th>Joined</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
        <tr><td colspan="7" style="text-align:center;padding:30px;">No registered members found.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <b><?= esc($u['name']) ?></b><br>
              <span class="mono" style="font-size:10.5px;color:var(--ink2);"><?= esc($u['email']) ?></span>
            </td>
            <td>
              <span class="mono" style="font-size:11px;"><?= esc($u['phone'] ?: '—') ?></span><br>
              <span style="font-size:11.5px;color:var(--ink2);"><?= esc($u['location'] ?: 'Kenya') ?></span>
            </td>
            <td>
              <span class="avatar" style="width:30px;height:30px;font-size:11px;background:var(--wine);color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
                <?= esc($u['avatar_initials'] ?: calculate_initials($u['name'])) ?>
              </span>
            </td>
            <td style="font-size:11.5px;">
              🙏 <?= $u['prayer_count'] ?> Prayers · ✉ <?= $u['invite_count'] ?> Invites · 🤝 <?= $u['pledge_count'] ?> Pledges
            </td>
            <td>
              <span class="chip <?= $u['status'] === 'active' ? 'pine' : 'danger' ?>">
                <?= esc($u['status']) ?>
              </span>
            </td>
            <td class="mono" style="font-size:10.5px;"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <div class="act">
                <form method="post" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <?php if ($u['status'] === 'active'): ?>
                    <input type="hidden" name="status" value="suspended">
                    <button type="submit" class="btn sm danger" onclick="return confirm('Suspend this member account?');">Suspend</button>
                  <?php else: ?>
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn sm success">Activate</button>
                  <?php endif; ?>
                </form>

                <form method="post" style="display:inline;" onsubmit="return confirm('Permanently delete this member account?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
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
