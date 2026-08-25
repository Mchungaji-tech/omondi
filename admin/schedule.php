<?php
/**
 * Admin Weekly Schedule Manager
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $day = sanitize_input($_POST['day_code'] ?? 'Mon');
        $title = sanitize_input($_POST['title'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $time = sanitize_input($_POST['time_chip'] ?? '');
        $isSun = (!empty($_POST['is_sunday']) || $day === 'Sun') ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!empty($title)) {
            if ($action === 'create') {
                $sql = "INSERT INTO weekly_schedule (day_code, title, details, time_chip, is_sunday, sort_order) VALUES (?, ?, ?, ?, ?, ?)";
                db_query($sql, "ssssii", [$day, $title, $details, $time, $isSun, $sortOrder]);
                log_audit($adminUser['id'], $adminUser['username'], "Added schedule day: '$day - $title'");
                set_flash('success', "Schedule day added.");
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE weekly_schedule SET day_code = ?, title = ?, details = ?, time_chip = ?, is_sunday = ?, sort_order = ? WHERE id = ?";
                db_query($sql, "ssssiii", [$day, $title, $details, $time, $isSun, $sortOrder, $id]);
                log_audit($adminUser['id'], $adminUser['username'], "Updated schedule day: '$day'");
                set_flash('success', "Schedule day updated.");
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM weekly_schedule WHERE id = ?", "i", [$id]);
        set_flash('success', "Schedule item deleted.");
    }
    header('Location: ' . BASE_URL . 'admin/schedule.php');
    exit;
}

$editDay = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editDay = db_fetch_one("SELECT * FROM weekly_schedule WHERE id = ?", "i", [$editId]);
}

$schedule = db_fetch_all("SELECT * FROM weekly_schedule ORDER BY sort_order ASC, id ASC");
?>

<div class="phead">
  <div>
    <h2>Weekly Schedule <span class="ser">Rhythm</span></h2>
    <p>Controls the pastor's public rhythm of prayer, counseling, radio, and church services</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('addModal')">+ Add Schedule Day</button>
  </div>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>Day</th>
        <th>Activity Title</th>
        <th>Details</th>
        <th>Time Chip</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($schedule as $d): ?>
        <tr>
          <td><span class="chip <?= $d['is_sunday'] ? 'wine' : 'pine' ?>"><?= esc($d['day_code']) ?></span></td>
          <td><b><?= esc($d['title']) ?></b></td>
          <td><?= esc($d['details']) ?></td>
          <td class="mono" style="font-size:11px;color:var(--wine);"><?= esc($d['time_chip']) ?></td>
          <td>
            <div class="act">
              <a href="schedule.php?edit=<?= $d['id'] ?>" class="btn sm">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this schedule line?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
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
    <h3>Add Weekly Schedule Day</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="mrow2">
        <div class="field">
          <label>Day Code</label>
          <select name="day_code">
            <option value="Mon">Mon</option>
            <option value="Tue">Tue</option>
            <option value="Wed">Wed</option>
            <option value="Thu">Thu</option>
            <option value="Fri">Fri</option>
            <option value="Sat">Sat</option>
            <option value="Sun">Sun</option>
          </select>
        </div>
        <div class="field">
          <label>Time Chip</label>
          <input type="text" name="time_chip" placeholder="e.g. 5:30 – 7:00 AM" required>
        </div>
      </div>

      <div class="field">
        <label>Activity Title *</label>
        <input type="text" name="title" placeholder="e.g. Prayer Mountain — Kaptagat Ridge" required>
      </div>

      <div class="field">
        <label>Activity Details</label>
        <textarea name="details" placeholder="Corporate intercession for the church and nation..."></textarea>
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Save Schedule ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editDay): ?>
  <div class="modal" id="editModal">
    <div class="mcard-modal">
      <h3>Edit Schedule: <?= esc($editDay['day_code']) ?></h3>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editDay['id'] ?>">

        <div class="mrow2">
          <div class="field">
            <label>Day Code</label>
            <input type="text" name="day_code" value="<?= esc($editDay['day_code']) ?>" required>
          </div>
          <div class="field">
            <label>Time Chip</label>
            <input type="text" name="time_chip" value="<?= esc($editDay['time_chip']) ?>" required>
          </div>
        </div>

        <div class="field">
          <label>Activity Title *</label>
          <input type="text" name="title" value="<?= esc($editDay['title']) ?>" required>
        </div>

        <div class="field">
          <label>Activity Details</label>
          <textarea name="details"><?= esc($editDay['details']) ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="btn" style="flex:1;">Update Schedule ✦</button>
          <a href="schedule.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
