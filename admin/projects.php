<?php
/**
 * Admin Projects & Proposals Manager (Full CRUD)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../config/functions.php';

ensure_project_images_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = sanitize_input($_POST['name'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'ongoing');
        $raised = (float)($_POST['raised_amount'] ?? 0);
        $goal = (float)($_POST['goal_amount'] ?? 0);
        $img = sanitize_input($_POST['image_url'] ?? '');

        if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['project_file'], 'projects');
            if ($uploaded) {
                $img = $uploaded;
            }
        }

        $summary = trim($_POST['summary'] ?? '');
        $budget = trim($_POST['budget_text'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!empty($name)) {
            if ($action === 'create') {
                $sql = "INSERT INTO projects (name, status, raised_amount, goal_amount, image_url, summary, budget_text, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                db_query($sql, "ssddsssi", [$name, $status, $raised, $goal, $img, $summary, $budget, $sortOrder]);
                $projectId = (int)mysqli_insert_id(get_db_connection());
                log_audit($adminUser['id'], $adminUser['username'], "Added capital project: '$name'");
                set_flash('success', "Project proposal created.");
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE projects SET name = ?, status = ?, raised_amount = ?, goal_amount = ?, image_url = ?, summary = ?, budget_text = ?, sort_order = ? WHERE id = ?";
                db_query($sql, "ssddsssii", [$name, $status, $raised, $goal, $img, $summary, $budget, $sortOrder, $id]);
                $projectId = $id;
                log_audit($adminUser['id'], $adminUser['username'], "Updated project (ID #$id): '$name'");
                set_flash('success', "Project proposal updated.");
            }
            if ($projectId > 0 && !empty($_FILES['project_images']['name'])) {
              foreach ($_FILES['project_images']['name'] as $key => $unused) {
                if ($_FILES['project_images']['error'][$key] !== UPLOAD_ERR_OK) continue;
                $uploaded = upload_image([
                  'name' => $_FILES['project_images']['name'][$key],
                  'type' => $_FILES['project_images']['type'][$key],
                  'tmp_name' => $_FILES['project_images']['tmp_name'][$key],
                  'error' => $_FILES['project_images']['error'][$key],
                  'size' => $_FILES['project_images']['size'][$key]
                ], 'projects');
                if ($uploaded) db_query("INSERT INTO project_images (project_id, image_url, sort_order) VALUES (?, ?, ?)", "isi", [$projectId, $uploaded, $key]);
              }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM projects WHERE id = ?", "i", [$id]);
        log_audit($adminUser['id'], $adminUser['username'], "Deleted project (ID #$id)");
        set_flash('success', "Project proposal deleted.");
    }

    header('Location: ' . BASE_URL . 'admin/projects.php');
    exit;
}

$editProj = null;
$editImages = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editProj = db_fetch_one("SELECT * FROM projects WHERE id = ?", "i", [$editId]);
  if ($editProj) {
    $editImages = db_fetch_all("SELECT image_url FROM project_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC", "i", [$editId]);
  }
}

$statusFilter = sanitize_input($_GET['status'] ?? 'all');
$where = ($statusFilter !== 'all') ? "WHERE status = '$statusFilter'" : "";
$projects = db_fetch_all("SELECT * FROM projects $where ORDER BY sort_order ASC, id ASC");
?>

<div class="phead">
  <div>
    <h2>Projects <span class="ser">&amp; Capital Proposals</span></h2>
    <p><?= count($projects) ?> capital projects · Generate printable partner proposals and monitor partner commitments</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('addModal')">+ Create New Project</button>
  </div>
</div>

<!-- STATUS FILTER BUTTONS -->
<div style="display:flex;gap:8px;margin-bottom:24px;">
  <a href="projects.php?status=all" class="chip <?= $statusFilter === 'all' ? 'wine' : '' ?>">All Projects</a>
  <a href="projects.php?status=ongoing" class="chip <?= $statusFilter === 'ongoing' ? 'wine' : '' ?>">Ongoing</a>
  <a href="projects.php?status=planning" class="chip <?= $statusFilter === 'planning' ? 'wine' : '' ?>">Planning</a>
  <a href="projects.php?status=completed" class="chip <?= $statusFilter === 'completed' ? 'wine' : '' ?>">Completed</a>
</div>

<!-- PROJECTS GRID -->
<div class="projgrid">
  <?php if (empty($projects)): ?>
    <p class="ser" style="color:var(--ink2);">No projects in this category.</p>
  <?php else: ?>
    <?php foreach ($projects as $p): 
      $pct = $p['goal_amount'] > 0 ? min(100, round(($p['raised_amount'] / $p['goal_amount']) * 100)) : 0;
    ?>
      <div class="pcard">
        <div class="im">
          <img src="<?= esc(img_src($p['image_url'])) ?>" alt="<?= esc($p['name']) ?>">
        </div>
        <div class="bd">
          <div>
            <span class="chip <?= $p['status'] === 'ongoing' ? 'wine' : ($p['status'] === 'completed' ? 'pine' : 'gold') ?>">
              <?= esc($p['status']) ?>
            </span>
          </div>
          <h3><?= esc($p['name']) ?></h3>
          <p><?= esc($p['summary']) ?></p>

          <div class="bar"><i style="width:<?= $pct ?>%"></i></div>

          <div class="nums">
            <span>Raised <b><?= format_ksh($p['raised_amount']) ?></b></span>
            <span>Goal <?= format_compact_ksh($p['goal_amount']) ?> · <?= $pct ?>%</span>
          </div>

          <div style="display:flex;gap:8px;margin-top:8px;">
            <a href="proposal_view.php?id=<?= $p['id'] ?>" class="btn sm gold">View Proposal &rarr;</a>
            <a href="projects.php?edit=<?= $p['id'] ?>#editModal" class="btn sm ghost">Edit Details</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this project?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn sm danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ADD PROJECT MODAL -->
<div class="modal" id="addModal" hidden>
  <div class="mcard-modal" style="width:min(680px,100%);">
    <div class="mcard-head">
      <h3>Create Project Proposal</h3>
      <button type="button" class="modal-cancel-btn" onclick="closeAdminModal('addModal')">✕ Cancel</button>
    </div>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="field">
        <label>Project Name *</label>
        <input type="text" name="name" placeholder="e.g. Auditorium Phase 2 — Roofing &amp; Pews" required>
      </div>

      <div class="mrow2">
        <div class="field">
          <label>Status</label>
          <select name="status">
            <option value="ongoing">Ongoing</option>
            <option value="planning">Planning</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div class="field">
          <label>Project Cover Photo</label>
          <label class="up-btn" style="display:inline-block;padding:10px 14px;border:1px dashed var(--line);border-radius:6px;cursor:pointer;background:var(--bg2);width:100%;text-align:center;">
            <input type="file" name="project_file" id="add_project_file" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewAddImg(event)">
            <span style="font-weight:600;">Choose Project Photo File</span>
            <br><small style="color:var(--ink2);">JPG · PNG · WebP · Max 5 MB</small>
          </label>
          <div class="field"><label>Additional Design / Progress Photos</label><input type="file" name="project_images[]" accept="image/jpeg,image/png,image/webp" multiple></div>
          <div class="up-preview" id="add_preview" style="aspect-ratio:16/9;overflow:hidden;border-radius:6px;margin-top:10px;background:var(--bg2);display:none;align-items:center;justify-content:center;">
            <img id="add_preview_img" src="" alt="Preview" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div style="margin-top:10px;">
            <button type="button" onclick="document.getElementById('add_url_row').style.display = document.getElementById('add_url_row').style.display === 'none' ? 'block' : 'none';" style="background:none;border:none;color:var(--ink2);cursor:pointer;font-size:13px;text-decoration:underline;">+ Or paste image URL instead</button>
          </div>
          <div id="add_url_row" class="field" style="display:none;margin-top:8px;">
            <label style="font-size:13px;">Fallback Image URL</label>
            <input type="text" name="image_url" placeholder="https://picsum.photos/...">
          </div>
        </div>
      </div>

      <div class="mrow2">
        <div class="field">
          <label>Raised Amount (KSh)</label>
          <input type="number" name="raised_amount" placeholder="8200000" step="1000" required>
        </div>
        <div class="field">
          <label>Target Goal (KSh)</label>
          <input type="number" name="goal_amount" placeholder="12000000" step="1000" required>
        </div>
      </div>

      <div class="field">
        <label>Executive Summary</label>
        <textarea name="summary" placeholder="Complete the 2,000-seat sanctuary on the Langas hill so crusade crowds no longer stand in the rain..."></textarea>
      </div>

      <div class="field">
        <label>Budget Lines (Line item|Cost per line, e.g. Roofing sheets|4500000)</label>
        <textarea name="budget_text" rows="4" placeholder="Roofing sheets &amp; trusses|4500000&#10;Pews (600 seats)|3800000&#10;Sound &amp; acoustics|2200000"></textarea>
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Save Project Proposal ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editProj): ?>
  <div class="modal" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editProjectTitle">
    <div class="mcard-modal" style="width:min(680px,100%);">
      <div class="mcard-head">
        <h3 id="editProjectTitle">Edit Project Details: <?= esc($editProj['name']) ?></h3>
        <a href="projects.php" class="modal-cancel-btn">✕ Cancel</a>
      </div>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editProj['id'] ?>">

        <div class="field">
          <label>Project Name *</label>
          <input type="text" name="name" value="<?= esc($editProj['name']) ?>" required>
        </div>

        <div class="mrow2">
          <div class="field">
            <label>Status</label>
            <select name="status">
              <option value="ongoing" <?= $editProj['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
              <option value="planning" <?= $editProj['status'] === 'planning' ? 'selected' : '' ?>>Planning</option>
              <option value="completed" <?= $editProj['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
          </div>
          <div class="field">
            <label>Project Cover Photo</label>
            <?php if (!empty($editProj['image_url'])): ?>
              <div class="current-img-card" style="aspect-ratio:16/9;overflow:hidden;border-radius:6px;margin-bottom:12px;background:var(--bg2);border:1px solid var(--line);">
                <img src="<?= esc(img_src($editProj['image_url'])) ?>" alt="Current" style="width:100%;height:100%;object-fit:cover;">
              </div>
            <?php endif; ?>
            <label class="up-btn" style="display:inline-block;padding:10px 14px;border:1px dashed var(--line);border-radius:6px;cursor:pointer;background:var(--bg2);width:100%;text-align:center;">
              <input type="file" name="project_file" id="edit_project_file" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewEditImg(event)">
              <span style="font-weight:600;">Choose Project Photo File</span>
              <br><small style="color:var(--ink2);">JPG · PNG · WebP · Max 5 MB</small>
            </label>
            <div class="up-preview" id="edit_preview" style="aspect-ratio:16/9;overflow:hidden;border-radius:6px;margin-top:10px;background:var(--bg2);display:none;align-items:center;justify-content:center;">
              <img id="edit_preview_img" src="" alt="Preview" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="margin-top:10px;">
              <button type="button" onclick="document.getElementById('edit_url_row').style.display = document.getElementById('edit_url_row').style.display === 'none' ? 'block' : 'none';" style="background:none;border:none;color:var(--ink2);cursor:pointer;font-size:13px;text-decoration:underline;">+ Or paste image URL instead</button>
            </div>
            <div id="edit_url_row" class="field" style="margin-top:8px;">
              <label style="font-size:13px;">Fallback Image URL</label>
              <input type="text" name="image_url" value="<?= esc($editProj['image_url']) ?>" placeholder="https://picsum.photos/...">
            </div>
            <?php if (!empty($editImages)): ?>
              <div class="field">
                <label>Current Design / Progress Photos</label>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                  <?php foreach ($editImages as $image): ?>
                    <img src="<?= esc(img_src($image['image_url'])) ?>" alt="Current project photo" style="width:100%;aspect-ratio:4/3;object-fit:cover;border:1px solid var(--line);border-radius:4px;">
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
            <div class="field"><label>Additional Design / Progress Photos</label><input type="file" name="project_images[]" accept="image/jpeg,image/png,image/webp" multiple></div>
          </div>
        </div>

        <div class="mrow2">
          <div class="field">
            <label>Raised Amount (KSh)</label>
            <input type="number" name="raised_amount" value="<?= $editProj['raised_amount'] ?>" step="1000" required>
          </div>
          <div class="field">
            <label>Target Goal (KSh)</label>
            <input type="number" name="goal_amount" value="<?= $editProj['goal_amount'] ?>" step="1000" required>
          </div>
        </div>

        <div class="field">
          <label>Executive Summary</label>
          <textarea name="summary"><?= esc($editProj['summary']) ?></textarea>
        </div>

        <div class="field">
          <label>Budget Lines (Line item|Cost per line)</label>
          <textarea name="budget_text" rows="4"><?= esc($editProj['budget_text']) ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="btn" style="flex:1;">Update Project ✦</button>
          <a href="projects.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<script>
function previewAddImg(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('add_preview_img').src = e.target.result;
      document.getElementById('add_preview').style.display = 'flex';
    };
    reader.readAsDataURL(file);
  }
}
function previewEditImg(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('edit_preview_img').src = e.target.result;
      document.getElementById('edit_preview').style.display = 'flex';
    };
    reader.readAsDataURL(file);
  }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
