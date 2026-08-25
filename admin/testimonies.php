<?php
/**
 * Admin Testimonies Manager (Full CRUD)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $quote = trim($_POST['quote'] ?? '');
        $who = sanitize_input($_POST['attribution'] ?? '');
        $approved = !empty($_POST['is_approved']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!empty($quote)) {
            if ($action === 'create') {
                db_query("INSERT INTO testimonies (quote, attribution, is_approved, sort_order) VALUES (?, ?, ?, ?)", "ssii", [$quote, $who, $approved, $sortOrder]);
                log_audit($adminUser['id'], $adminUser['username'], "Added testimony from: '$who'");
                set_flash('success', "Testimony added.");
            } else {
                $id = (int)$_POST['id'];
                db_query("UPDATE testimonies SET quote = ?, attribution = ?, is_approved = ?, sort_order = ? WHERE id = ?", "ssiii", [$quote, $who, $approved, $sortOrder, $id]);
                log_audit($adminUser['id'], $adminUser['username'], "Updated testimony (ID #$id)");
                set_flash('success', "Testimony updated.");
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM testimonies WHERE id = ?", "i", [$id]);
        set_flash('success', "Testimony removed.");
    }
    header('Location: ' . BASE_URL . 'admin/testimonies.php');
    exit;
}

$editTestimony = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editTestimony = db_fetch_one("SELECT * FROM testimonies WHERE id = ?", "i", [$editId]);
}

$testimonies = db_fetch_all("SELECT * FROM testimonies ORDER BY sort_order ASC, id ASC");
?>

<div class="phead">
  <div>
    <h2>Flock Testimonies <span class="ser">Manager</span></h2>
    <p><?= count($testimonies) ?> testimonies · Displays in the animated testimony stack on public site</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('addModal')">+ Add Testimony</button>
  </div>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>Testimony Quote</th>
        <th>Attribution</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($testimonies as $t): ?>
        <tr>
          <td>“<?= esc($t['quote']) ?>”</td>
          <td><b style="color:var(--wine);"><?= esc($t['attribution']) ?></b></td>
          <td><span class="chip <?= $t['is_approved'] ? 'pine' : 'gold' ?>"><?= $t['is_approved'] ? 'Approved' : 'Pending' ?></span></td>
          <td>
            <div class="act">
              <a href="testimonies.php?edit=<?= $t['id'] ?>" class="btn sm">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this testimony?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn sm danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ADD MODAL -->
<div class="modal" id="addModal" hidden>
  <div class="mcard-modal">
    <h3>Add Testimony</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="field">
        <label>Testimony Quote *</label>
        <textarea name="quote" placeholder="Share how God moved in this person's life..." required></textarea>
      </div>

      <div class="field">
        <label>Attribution (Name &amp; Details) *</label>
        <input type="text" name="attribution" placeholder="e.g. Mama Grace W. · Church Mother since 2009" required>
      </div>

      <div class="field">
        <label>
          <input type="checkbox" name="is_approved" value="1" checked> Approved to publish on homepage
        </label>
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Save Testimony ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editTestimony): ?>
  <div class="modal" id="editModal">
    <div class="mcard-modal">
      <h3>Edit Testimony</h3>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editTestimony['id'] ?>">

        <div class="field">
          <label>Testimony Quote *</label>
          <textarea name="quote" required><?= esc($editTestimony['quote']) ?></textarea>
        </div>

        <div class="field">
          <label>Attribution *</label>
          <input type="text" name="attribution" value="<?= esc($editTestimony['attribution']) ?>" required>
        </div>

        <div class="field">
          <label>
            <input type="checkbox" name="is_approved" value="1" <?= $editTestimony['is_approved'] ? 'checked' : '' ?>> Approved to publish on homepage
          </label>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="btn" style="flex:1;">Update Testimony ✦</button>
          <a href="testimonies.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
