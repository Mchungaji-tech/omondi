<?php
/**
 * Admin Sermons Manager (Full CRUD)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../config/functions.php';

ensure_sermon_views_table();

// Handle Add / Edit / Delete POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title = sanitize_input($_POST['title'] ?? '');
        $series = sanitize_input($_POST['series'] ?? '');
        $ref = '';
        $date = '';
        $dur = sanitize_input($_POST['duration'] ?? '45:00');
        $cat = sanitize_input($_POST['category'] ?? 'General');
        $audioUrl = sanitize_input($_POST['audio_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!empty($title)) {
            if ($action === 'create') {
                $sql = "INSERT INTO sermons (title, series, scripture_ref, sermon_date, duration, category, audio_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                db_query($sql, "sssssssi", [$title, $series, $ref, $date, $dur, $cat, $audioUrl, $sortOrder]);
                log_audit($adminUser['id'], $adminUser['username'], "Added new sermon: '$title'");
                set_flash('success', "Sermon '$title' added to library.");
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE sermons SET title = ?, series = ?, scripture_ref = ?, sermon_date = ?, duration = ?, category = ?, audio_url = ?, sort_order = ? WHERE id = ?";
                db_query($sql, "sssssssii", [$title, $series, $ref, $date, $dur, $cat, $audioUrl, $sortOrder, $id]);
                log_audit($adminUser['id'], $adminUser['username'], "Updated sermon (ID #$id): '$title'");
                set_flash('success', "Sermon '$title' updated.");
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            db_query("DELETE FROM sermons WHERE id = ?", "i", [$id]);
            log_audit($adminUser['id'], $adminUser['username'], "Deleted sermon (ID #$id)");
            set_flash('success', "Sermon record deleted.");
        }
    }
    header('Location: ' . BASE_URL . 'admin/sermons.php');
    exit;
}

// Edit prefill
$editSermon = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editSermon = db_fetch_one("SELECT * FROM sermons WHERE id = ?", "i", [$editId]);
}

$sermons = db_fetch_all("SELECT * FROM sermons ORDER BY sort_order ASC, id DESC");
$sermonIds = array_column($sermons, 'id');
$viewCounts = [];
if (!empty($sermonIds)) {
    $ids = implode(',', array_map('intval', $sermonIds));
    $viewRows = db_fetch_all("SELECT sermon_id, COUNT(*) as cnt FROM sermon_views WHERE sermon_id IN ($ids) GROUP BY sermon_id");
    foreach ($viewRows as $vr) {
        $viewCounts[(int)$vr['sermon_id']] = (int)$vr['cnt'];
    }
}
?>

<div class="phead">
  <div>
    <h2>Sermons <span class="ser">Library Manager</span></h2>
    <p><?= count($sermons) ?> sermons in archive · Edits publish immediately to the public video library</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('addModal')">+ Add New Sermon</button>
  </div>
</div>

<!-- SERMONS TABLE -->
<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>Title &amp; Series</th>
        <th>Category</th>
        <th>Runtime</th>
        <th>Video Source</th>
        <th>Views</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($sermons)): ?>
        <tr><td colspan="6" style="text-align:center;padding:30px;">No sermons in library yet.</td></tr>
      <?php else: ?>
        <?php foreach ($sermons as $s): ?>
          <tr>
            <td>
              <b><?= esc($s['title']) ?></b><br>
              <span class="ser" style="color:var(--ink2);"><?= esc($s['series']) ?></span>
            </td>
            <td><span class="chip wine"><?= esc($s['category']) ?></span></td>
            <td class="mono" style="font-size:11px;"><?= esc($s['duration'] ?: '—') ?></td>
             <td>
               <?php if (stripos($s['audio_url'], 'youtu') !== false): ?>
                 <span class="chip" style="background:rgba(255,0,0,.08);color:#c00;border-color:rgba(255,0,0,.25);">▶ YouTube</span>
               <?php elseif (stripos($s['audio_url'], 'facebook') !== false || stripos($s['audio_url'], 'fb.') !== false): ?>
                 <span class="chip" style="background:rgba(24,119,242,.08);color:#1877f2;border-color:rgba(24,119,242,.25);">▶ Facebook</span>
               <?php elseif (stripos($s['audio_url'], 'vimeo') !== false): ?>
                 <span class="chip">▶ Vimeo</span>
               <?php elseif (!empty($s['audio_url'])): ?>
                 <span class="chip">▶ Video</span>
               <?php else: ?>
                 <span class="chip" style="background:#f1f1f1;color:#888;">No video URL</span>
               <?php endif; ?>
             </td>
             <td style="font-family:var(--mono);font-size:12px;text-align:center;">
               <b><?= number_format($viewCounts[(int)$s['id']] ?? 0) ?></b>
             </td>
            <td>
              <div class="act">
                <a href="sermons.php?edit=<?= $s['id'] ?>" class="btn sm">Edit</a>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this sermon record?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
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

<!-- ADD MODAL -->
<div class="modal" id="addModal" hidden>
  <div class="mcard-modal">
    <h3>Add New Sermon to Library</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="field">
        <label>Sermon Title *</label>
        <input type="text" name="title" placeholder="e.g. The God Who Runs to You" required>
      </div>

      <div class="field">
        <label>Series Name</label>
        <input type="text" name="series" placeholder="e.g. Grace Without Borders">
      </div>

      <div class="mrow2">
        <div class="field">
          <label>Category (Manual Entry) *</label>
          <input type="text" name="category" placeholder="e.g. Grace, Sunday Service, Revival..." value="Grace" required>
        </div>
        <div class="field">
          <label>Runtime Label (optional, display only e.g. 45:00)</label>
          <input type="text" name="duration" value="45:00">
        </div>
      </div>

      <div class="field">
        <label>YouTube / Facebook Video URL</label>
        <input type="text" name="audio_url" placeholder="https://www.youtube.com/watch?v=... OR https://www.facebook.com/watch/?v=...">
        <small style="font-family:var(--mono);font-size:11.5px;letter-spacing:0.08em;color:var(--ink2);text-transform:uppercase;">Share link from YouTube app / Facebook Watch — will auto-convert to embed</small>
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Save Sermon ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL (IF TRIGGERED VIA GET) -->
<?php if ($editSermon): ?>
  <div class="modal" id="editModal">
    <div class="mcard-modal">
      <h3>Edit Sermon: <?= esc($editSermon['title']) ?></h3>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editSermon['id'] ?>">

        <div class="field">
          <label>Sermon Title *</label>
          <input type="text" name="title" value="<?= esc($editSermon['title']) ?>" required>
        </div>

        <div class="field">
          <label>Series Name</label>
          <input type="text" name="series" value="<?= esc($editSermon['series']) ?>">
        </div>

        <div class="mrow2">
          <div class="field">
            <label>Category (Manual Entry) *</label>
            <input type="text" name="category" value="<?= esc($editSermon['category'] ?? '') ?>" placeholder="e.g. Grace, Revival, Sunday Service..." required>
          </div>
          <div class="field">
            <label>Runtime Label (optional, display only e.g. 45:00)</label>
            <input type="text" name="duration" value="<?= esc($editSermon['duration']) ?>">
          </div>
        </div>

        <div class="field">
          <label>YouTube / Facebook Video URL</label>
          <input type="text" name="audio_url" value="<?= esc($editSermon['audio_url']) ?>" placeholder="https://www.youtube.com/watch?v=... OR https://www.facebook.com/watch/?v=...">
        <small style="font-family:var(--mono);font-size:11.5px;letter-spacing:0.08em;color:var(--ink2);text-transform:uppercase;">Share link from YouTube app / Facebook Watch — will auto-convert to embed</small>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="btn" style="flex:1;">Update Sermon ✦</button>
          <a href="sermons.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
