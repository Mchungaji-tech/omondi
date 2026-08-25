<?php
/**
 * Admin Gallery Photos Manager (Full CRUD + File Upload)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $caption = sanitize_input($_POST['caption'] ?? '');
        $img = sanitize_input($_POST['image_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        // Check if file upload was provided
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['image_file'], 'gallery');
            if ($uploaded) {
                $img = $uploaded;
            }
        }

        if (!empty($caption) && !empty($img)) {
            if ($action === 'create') {
                $sql = "INSERT INTO gallery (caption, image_url, sort_order) VALUES (?, ?, ?)";
                db_query($sql, "ssi", [$caption, $img, $sortOrder]);
                log_audit($adminUser['id'], $adminUser['username'], "Added gallery photo: '$caption'");
                set_flash('success', "Photo added to gallery.");
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE gallery SET caption = ?, image_url = ?, sort_order = ? WHERE id = ?";
                db_query($sql, "ssii", [$caption, $img, $sortOrder, $id]);
                log_audit($adminUser['id'], $adminUser['username'], "Updated gallery photo (ID #$id)");
                set_flash('success', "Photo updated.");
            }
        } else {
            set_flash('error', "Please provide a photo caption and select an image file or URL.");
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM gallery WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Deleted gallery photo (ID #$id)");
        set_flash('success', "Photo deleted.");
    }

    header('Location: ' . BASE_URL . 'admin/gallery.php');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editItem = db_fetch_one("SELECT * FROM gallery WHERE id = ?", "i", [$editId]);
}

$photos = db_fetch_all("SELECT * FROM gallery ORDER BY sort_order ASC, id ASC");
?>

<div class="phead">
  <div>
    <h2>Photo <span class="ser">Gallery</span></h2>
    <p><?= count($photos) ?> photos · Upload field photos with captions</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('addModal')">+ Upload New Photo</button>
  </div>
</div>

<div class="cards4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
  <?php foreach ($photos as $p): ?>
    <?php 
      $imgSrc = (strpos($p['image_url'], 'http') === 0) ? $p['image_url'] : BASE_URL . $p['image_url'];
    ?>
    <div class="statcard" style="padding:12px;display:flex;flex-direction:column;justify-content:space-between;">
      <div>
        <div style="aspect-ratio:4/3;overflow:hidden;border-radius:4px;margin-bottom:10px;background:#000;">
          <img src="<?= esc($imgSrc) ?>" alt="<?= esc($p['caption']) ?>" style="width:100%;height:100%;object-fit:cover;">
        </div>
        <p style="font-size:12px;font-weight:600;"><?= esc($p['caption']) ?></p>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;border-top:1px solid var(--line);padding-top:8px;">
        <a href="gallery.php?edit=<?= $p['id'] ?>" class="btn sm ghost">Edit</a>
        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this photo?');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button type="submit" class="btn sm danger">Delete</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ADD PHOTO MODAL -->
<div class="modal" id="addModal" hidden>
  <div class="mcard-modal">
    <h3>Upload Gallery Photo</h3>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="field">
        <label>Caption *</label>
        <input type="text" name="caption" placeholder="e.g. Open-air crusade in Eldoret" required>
      </div>

      <div class="field">
        <label>Choose Image File (JPG, PNG, WebP)</label>
        <div class="uploader">
          <input type="file" name="image_file" id="add_image_file" accept="image/jpeg,image/png,image/webp" class="up-input">
          <label class="up-btn" for="add_image_file">
            <div class="up-icon">⇪</div>
            <div class="up-text">
              <b>Upload Gallery Photo File</b>
              <small>JPG · PNG · WebP · Max 5 MB</small>
              <div class="up-filename"></div>
            </div>
          </label>
          <div class="up-preview" style="aspect-ratio:4/3;">
            <img src="" alt="Preview">
          </div>
          <div class="up-url-note">
            Or, <a data-toggle-url>paste an external image URL instead →</a>
          </div>
        </div>
        <div class="up-url-row">
          <label>External Image URL</label>
          <input type="text" name="image_url" placeholder="https://picsum.photos/...">
        </div>
      </div>

      <div class="field">
        <label>Display Order</label>
        <input type="number" name="sort_order" value="1">
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Upload Photo ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editItem): ?>
  <div class="modal" id="editModal">
    <div class="mcard-modal">
      <h3>Edit Photo</h3>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">

        <div class="field">
          <label>Caption *</label>
          <input type="text" name="caption" value="<?= esc($editItem['caption']) ?>" required>
        </div>

        <?php if (!empty($editItem['image_url'])): ?>
          <?php
            $editImgSrc = (strpos($editItem['image_url'], 'http') === 0) ? $editItem['image_url'] : BASE_URL . $editItem['image_url'];
          ?>
          <div class="current-img-card">
            <span class="lbl">Current Image</span>
            <div class="cur" style="aspect-ratio:4/3;">
              <img src="<?= esc($editImgSrc) ?>" alt="<?= esc($editItem['caption']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
            </div>
          </div>
        <?php endif; ?>

        <div class="field">
          <label>Replace with Image File</label>
          <div class="uploader">
            <input type="file" name="image_file" id="edit_image_file" accept="image/jpeg,image/png,image/webp" class="up-input">
            <label class="up-btn" for="edit_image_file">
              <div class="up-icon">⇪</div>
              <div class="up-text">
                <b>Upload Gallery Photo File</b>
                <small>JPG · PNG · WebP · Max 5 MB</small>
                <div class="up-filename"></div>
              </div>
            </label>
            <div class="up-preview" style="aspect-ratio:4/3;">
              <img src="" alt="Preview">
            </div>
            <div class="up-url-note">
              Or, <a data-toggle-url>edit the image URL / path instead →</a>
            </div>
          </div>
          <div class="up-url-row">
            <label>Image URL / Path</label>
            <input type="text" name="image_url" value="<?= esc($editItem['image_url']) ?>">
          </div>
        </div>

        <div class="field">
          <label>Display Order</label>
          <input type="number" name="sort_order" value="<?= $editItem['sort_order'] ?>">
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="btn" style="flex:1;">Save Changes ✦</button>
          <a href="gallery.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
