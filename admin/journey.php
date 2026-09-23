<?php
/**
 * Admin Journey Milestones Manager (Full CRUD)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

ensure_journey_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $yearLabel = sanitize_input($_POST['year_label'] ?? '');
        $title = sanitize_input($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $tag = sanitize_input($_POST['tag'] ?? '');
        $img = sanitize_input($_POST['image_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        $uploadErr = null;
        if (isset($_FILES['journey_file']) && $_FILES['journey_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploaded = upload_image($_FILES['journey_file'], 'journey', $uploadErr);
            if ($uploaded) {
                $img = $uploaded;
            } else {
                set_flash('error', "Image upload failed: " . ($uploadErr ?: "Unknown error."));
            }
        }

        if (!empty($yearLabel) && !empty($title)) {
            if ($action === 'create') {
                $sql = "INSERT INTO journey_milestones (year_label, title, description, tag, image_url, sort_order) VALUES (?, ?, ?, ?, ?, ?)";
                db_query($sql, "sssssi", [$yearLabel, $title, $desc, $tag, $img, $sortOrder]);
                log_audit($adminUser['id'], $adminUser['username'], "Added journey milestone: '{$yearLabel} - {$title}'");
                set_flash('success', "Stage '{$title}' added successfully.");
            } else {
                $id = (int)$_POST['id'];
                // If user didn't upload a new file and didn't provide external URL, preserve existing image
                if (empty($img)) {
                    $existing = db_fetch_one("SELECT image_url FROM journey_milestones WHERE id = ?", "i", [$id]);
                    if ($existing && !empty($existing['image_url'])) {
                        $img = $existing['image_url'];
                    }
                }
                $sql = "UPDATE journey_milestones SET year_label = ?, title = ?, description = ?, tag = ?, image_url = ?, sort_order = ? WHERE id = ?";
                db_query($sql, "sssssii", [$yearLabel, $title, $desc, $tag, $img, $sortOrder, $id]);
                log_audit($adminUser['id'], $adminUser['username'], "Updated journey milestone #{$id}: '{$title}'");
                set_flash('success', "Stage '{$title}' updated successfully.");
            }
        } else {
            set_flash('error', "Please provide both a year/code label and stage title.");
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM journey_milestones WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Deleted journey milestone (ID #$id)");
        set_flash('success', "Journey stage deleted.");
    }
    header('Location: ' . BASE_URL . 'admin/journey.php');
    exit;
}

$editStage = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editStage = db_fetch_one("SELECT * FROM journey_milestones WHERE id = ?", "i", [$editId]);
}

$milestones = db_fetch_all("SELECT * FROM journey_milestones ORDER BY sort_order ASC, id ASC");
?>

<div class="phead">
  <div>
    <h2>The Journey <span class="ser">&amp; Faith Milestones</span></h2>
    <p><?= count($milestones) ?> historical stages · Each stage features a photo displayed on the homepage scroll slider</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('addModal')">+ Add Journey Stage</button>
  </div>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th style="width:70px;">Order</th>
        <th style="width:110px;">Photo</th>
        <th style="width:90px;">Year</th>
        <th>Stage Title &amp; Description</th>
        <th>Tag</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($milestones)): ?>
        <tr>
          <td colspan="6" style="text-align:center;padding:32px;color:var(--ink2);">
            No journey milestones found. Click "+ Add Journey Stage" above to create one.
          </td>
        </tr>
      <?php endif; ?>
      <?php foreach ($milestones as $m): ?>
        <tr>
          <td style="font-family:var(--mono);font-weight:700;color:var(--wine);"><?= (int)$m['sort_order'] ?></td>
          <td>
            <div style="width:80px;height:55px;border-radius:4px;overflow:hidden;background:#191613;border:1px solid var(--line);">
              <?php if (!empty($m['image_url'])): ?>
                <img src="<?= esc(img_src($m['image_url'])) ?>" alt="<?= esc($m['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <span style="display:flex;align-items:center;justify-content:center;height:100%;font-size:10px;color:var(--ink2);">No photo</span>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <span class="chip gold" style="font-family:var(--disp);font-size:1.05rem;"><?= esc($m['year_label']) ?></span>
          </td>
          <td>
            <b><?= esc($m['title']) ?></b>
            <p style="font-size:12.5px;color:var(--ink2);margin-top:4px;max-width:440px;"><?= esc(mb_strimwidth($m['description'], 0, 110, '...')) ?></p>
          </td>
          <td>
            <span class="chip" style="font-size:11px;"><?= esc($m['tag']) ?></span>
          </td>
          <td style="text-align:right;">
            <a href="journey.php?edit=<?= $m['id'] ?>" class="btn sm">Edit</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete milestone stage \'<?= esc(addslashes($m['title'])) ?>\'?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn sm danger">Delete</button>
            </form>
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
      <h3>Add New Journey Stage</h3>
      <button type="button" class="modal-cancel-btn" onclick="closeAdminModal('addModal')">✕ Cancel</button>
    </div>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="mrow2">
        <div class="field">
          <label>Year / Code Label *</label>
          <input type="text" name="year_label" placeholder="e.g. 1995, 2000, Ministry, Family, Today" required>
        </div>
        <div class="field">
          <label>Sort Order (Number) *</label>
          <input type="number" name="sort_order" value="<?= count($milestones) + 1 ?>" required>
        </div>
      </div>

      <div class="field">
        <label>Stage Title *</label>
        <input type="text" name="title" placeholder="e.g. Founded in January" required>
      </div>

      <div class="field">
        <label>Category Tag</label>
        <input type="text" name="tag" placeholder="e.g. Our Beginning, Leadership, Church Planting">
      </div>

      <div class="field">
        <label>Stage Description *</label>
        <textarea name="description" placeholder="Historical context, achievements, or calling for this era..." rows="3" required></textarea>
      </div>

      <div class="field">
        <label>Stage Photo (JPG, PNG, WebP · Max 15 MB)</label>
        <div class="uploader">
          <input type="file" name="journey_file" id="add_journey_file" accept="image/*" class="up-input">
          <label for="add_journey_file" class="up-btn">
            <span class="up-icon">🖼</span>
            <span class="up-text">
              <b>Choose Milestone Photo File</b>
              <small>JPG · PNG · WebP · Max 15 MB</small>
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
        <button type="submit" class="btn" style="flex:1;">Save Stage ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editStage): ?>
  <div class="modal" id="editModal">
    <div class="mcard-modal">
      <div class="mcard-head">
        <h3>Edit Stage: <?= esc($editStage['title']) ?></h3>
        <a href="journey.php" class="modal-cancel-btn">✕ Cancel</a>
      </div>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editStage['id'] ?>">

        <div class="mrow2">
          <div class="field">
            <label>Year / Code Label *</label>
            <input type="text" name="year_label" value="<?= esc($editStage['year_label']) ?>" required>
          </div>
          <div class="field">
            <label>Sort Order *</label>
            <input type="number" name="sort_order" value="<?= (int)$editStage['sort_order'] ?>" required>
          </div>
        </div>

        <div class="field">
          <label>Stage Title *</label>
          <input type="text" name="title" value="<?= esc($editStage['title']) ?>" required>
        </div>

        <div class="field">
          <label>Category Tag</label>
          <input type="text" name="tag" value="<?= esc($editStage['tag']) ?>">
        </div>

        <div class="field">
          <label>Stage Description *</label>
          <textarea name="description" rows="3" required><?= esc($editStage['description']) ?></textarea>
        </div>

        <div class="field">
          <label>Stage Photo</label>
          <?php if (!empty($editStage['image_url'])): ?>
            <div class="current-img-card" style="margin-bottom:12px;">
              <span class="lbl">Current Stage Photo</span>
              <div class="cur" style="max-width:240px;aspect-ratio:3/2;border-radius:4px;overflow:hidden;border:1px solid var(--line);">
                <img src="<?= esc(img_src($editStage['image_url'])) ?>" alt="Current stage photo" style="width:100%;height:100%;object-fit:cover;">
              </div>
            </div>
          <?php endif; ?>
          <div class="uploader">
            <input type="file" name="journey_file" id="edit_journey_file" accept="image/*" class="up-input">
            <label for="edit_journey_file" class="up-btn">
              <span class="up-icon">🖼</span>
              <span class="up-text">
                <b>Choose New Photo to Replace</b>
                <small>JPG · PNG · WebP · Max 15 MB</small>
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
            <input type="text" name="image_url" value="<?= esc($editStage['image_url']) ?>" placeholder="https://...">
          </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="btn" style="flex:1;">Update Stage ✦</button>
          <a href="journey.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
