<?php
/**
 * Admin Ministries Manager (Full CRUD)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

ensure_ministries_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = sanitize_input($_POST['name'] ?? '');
        $span = sanitize_input($_POST['age_span'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $stats = sanitize_input($_POST['stats'] ?? '');
        $img = sanitize_input($_POST['image_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        $uploadErr = null;
        if (isset($_FILES['ministry_file']) && $_FILES['ministry_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploaded = upload_image($_FILES['ministry_file'], 'ministries', $uploadErr);
            if ($uploaded) {
                $img = $uploaded;
            } else {
                set_flash('error', "Ministry photo upload failed: " . ($uploadErr ?: "Unknown error."));
            }
        }

        if (!empty($name)) {
            if ($action === 'create') {
                $sql = "INSERT INTO ministries (name, age_span, description, stats, image_url, sort_order) VALUES (?, ?, ?, ?, ?, ?)";
                db_query($sql, "sssssi", [$name, $span, $desc, $stats, $img, $sortOrder]);
                log_audit($adminUser['id'], $adminUser['username'], "Added new ministry: '$name'");
                set_flash('success', "Ministry '$name' added.");
            } else {
                $id = (int)$_POST['id'];
                if (empty($img)) {
                    $existing = db_fetch_one("SELECT image_url FROM ministries WHERE id = ?", "i", [$id]);
                    if ($existing && !empty($existing['image_url'])) {
                        $img = $existing['image_url'];
                    }
                }
                $sql = "UPDATE ministries SET name = ?, age_span = ?, description = ?, stats = ?, image_url = ?, sort_order = ? WHERE id = ?";
                db_query($sql, "sssssii", [$name, $span, $desc, $stats, $img, $sortOrder, $id]);
                log_audit($adminUser['id'], $adminUser['username'], "Updated ministry: '$name'");
                set_flash('success', "Ministry '$name' updated.");
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM ministries WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Deleted ministry (ID #$id)");
        set_flash('success', "Ministry deleted.");
    }
    header('Location: ' . BASE_URL . 'admin/ministries.php');
    exit;
}

$editMinistry = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editMinistry = db_fetch_one("SELECT * FROM ministries WHERE id = ?", "i", [$editId]);
}

$ministries = db_fetch_all("SELECT * FROM ministries ORDER BY sort_order ASC, id ASC");
?>

<div class="phead">
  <div>
    <h2>Ministries <span class="ser">Manager</span></h2>
    <p><?= count($ministries) ?> active ministries · Edits display on the public ministry showcase</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('addModal')">+ Add Ministry</button>
  </div>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>Ministry</th>
        <th>Target Group</th>
        <th>Key Statistics</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ministries as $m): ?>
        <tr>
          <td>
            <b><?= esc($m['name']) ?></b><br>
            <span style="font-size:12px;color:var(--ink2);"><?= esc(substr($m['description'], 0, 90)) ?>...</span>
          </td>
          <td><span class="chip wine"><?= esc($m['age_span']) ?></span></td>
          <td><span class="chip gold"><?= esc($m['stats']) ?></span></td>
          <td>
            <div class="act">
              <a href="ministries.php?edit=<?= $m['id'] ?>" class="btn sm">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this ministry?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
    <div class="mcard-head">
      <h3>Add New Ministry</h3>
      <button type="button" class="modal-cancel-btn" onclick="closeAdminModal('addModal')">✕ Cancel</button>
    </div>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="mrow2">
        <div class="field">
          <label>Ministry Name *</label>
          <input type="text" name="name" placeholder="e.g. Youth Ministry" required>
        </div>
        <div class="field">
          <label>Sort Order (Number)</label>
          <input type="number" name="sort_order" value="<?= count($ministries) + 1 ?>">
        </div>
      </div>

      <div class="field">
        <label>Age Group / Tag</label>
        <input type="text" name="age_span" placeholder="e.g. Ages 13–30">
      </div>

      <div class="field">
        <label>Description</label>
        <textarea name="description" placeholder="Discipleship cells, worship nights..."></textarea>
      </div>

      <div class="field">
        <label>Key Stats / Highlights</label>
        <input type="text" name="stats" placeholder="e.g. 400+ youth · 18 cell groups">
      </div>

      <div class="field">
        <label>Ministry Photo</label>
        <div class="uploader">
          <input type="file" name="ministry_file" id="add_ministry_file" accept="image/*" class="up-input">
          <label for="add_ministry_file" class="up-btn">
            <span class="up-icon">🖼</span>
            <span class="up-text">
              <b>Choose Ministry Photo File</b>
              <small>JPG · PNG · WebP · Max 5 MB</small>
              <span class="up-filename">No file selected yet</span>
            </span>
          </label>
          <div class="up-preview">
            <img alt="Preview">
          </div>
        </div>
        <p class="up-url-note">Need to use a web link? <a data-toggle-url>Paste external URL instead &rarr;</a></p>
        <div class="up-url-row">
          <label style="display:block;margin-bottom:6px;font-family:var(--mono);font-size:11.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink2);">External Image URL</label>
          <input type="text" name="image_url" placeholder="https://...">
        </div>
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Save Ministry ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editMinistry): ?>
  <div class="modal" id="editModal">
    <div class="mcard-modal">
      <div class="mcard-head">
        <h3>Edit Ministry: <?= esc($editMinistry['name']) ?></h3>
        <a href="ministries.php" class="modal-cancel-btn">✕ Cancel</a>
      </div>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editMinistry['id'] ?>">

        <div class="mrow2">
          <div class="field">
            <label>Ministry Name *</label>
            <input type="text" name="name" value="<?= esc($editMinistry['name']) ?>" required>
          </div>
          <div class="field">
            <label>Sort Order</label>
            <input type="number" name="sort_order" value="<?= (int)$editMinistry['sort_order'] ?>">
          </div>
        </div>

        <div class="field">
          <label>Age Group / Tag</label>
          <input type="text" name="age_span" value="<?= esc($editMinistry['age_span']) ?>">
        </div>

        <div class="field">
          <label>Description</label>
          <textarea name="description"><?= esc($editMinistry['description']) ?></textarea>
        </div>

        <div class="field">
          <label>Key Stats / Highlights</label>
          <input type="text" name="stats" value="<?= esc($editMinistry['stats']) ?>">
        </div>

        <div class="field">
          <label>Ministry Photo</label>
          <?php if (!empty($editMinistry['image_url'])): ?>
            <div class="current-img-card">
              <span class="lbl">Current Photo</span>
              <div class="cur">
                <img src="<?php
                  $pv = $editMinistry['image_url'];
                  echo preg_match('#^https?://#i', $pv) ? esc($pv) : BASE_URL . ltrim($pv, '/');
                ?>" alt="Current ministry photo" style="width:100%;height:100%;object-fit:cover;">
              </div>
            </div>
          <?php endif; ?>
          <div class="uploader">
            <input type="file" name="ministry_file" id="edit_ministry_file" accept="image/*" class="up-input">
            <label for="edit_ministry_file" class="up-btn">
              <span class="up-icon">🖼</span>
              <span class="up-text">
                <b>Choose Ministry Photo File</b>
                <small>JPG · PNG · WebP · Max 5 MB</small>
                <span class="up-filename">No file selected yet</span>
              </span>
            </label>
            <div class="up-preview">
              <img alt="Preview">
            </div>
          </div>
          <p class="up-url-note">Need to use a web link? <a data-toggle-url>Paste external URL instead &rarr;</a></p>
          <div class="up-url-row">
          <label style="display:block;margin-bottom:6px;font-family:var(--mono);font-size:11.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink2);">External Image URL</label>
            <input type="text" name="image_url" value="<?= esc($editMinistry['image_url']) ?>" placeholder="https://...">
          </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="btn" style="flex:1;">Update Ministry ✦</button>
          <a href="ministries.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
