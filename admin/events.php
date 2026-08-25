<?php
/**
 * Admin Events Calendar Manager (Full CRUD)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';
ensure_event_image_column();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $day = sanitize_input($_POST['day_num'] ?? '01');
        $mon = sanitize_input($_POST['month_label'] ?? date('M Y'));
        $title = sanitize_input($_POST['title'] ?? '');
        $loc = sanitize_input($_POST['location'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $type = sanitize_input($_POST['event_type'] ?? 'general');
        $eventDate = !empty($_POST['event_date']) ? sanitize_input($_POST['event_date']) : null;
        $imageUrl = sanitize_input($_POST['existing_image_url'] ?? '');
        if (!empty($_FILES['event_image']['name'])) {
          $uploaded = upload_image($_FILES['event_image'], 'events');
          if ($uploaded) $imageUrl = $uploaded;
        }
        $active = !empty($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!empty($title)) {
            if ($action === 'create') {
                db_query("INSERT INTO events (day_num, month_label, event_date, title, location, description, image_url, event_type, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", "ssssssssii", [$day, $mon, $eventDate, $title, $loc, $desc, $imageUrl ?: null, $type, $active, $sortOrder]);
                log_audit($adminUser['id'], $adminUser['username'], "Added event: '$title'");
                set_flash('success', "Event added.");
            } else {
                $id = (int)$_POST['id'];
                db_query("UPDATE events SET day_num = ?, month_label = ?, event_date = ?, title = ?, location = ?, description = ?, image_url = ?, event_type = ?, is_active = ?, sort_order = ? WHERE id = ?", "sssssssssii", [$day, $mon, $eventDate, $title, $loc, $desc, $imageUrl ?: null, $type, $active, $sortOrder, $id]);
                log_audit($adminUser['id'], $adminUser['username'], "Updated event: '$title'");
                set_flash('success', "Event updated.");
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM events WHERE id = ?", "i", [$id]);
        set_flash('success', "Event deleted.");
    }
    header('Location: ' . BASE_URL . 'admin/events.php');
    exit;
}

$editEvent = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editEvent = db_fetch_one("SELECT * FROM events WHERE id = ?", "i", [$editId]);
}

$events = db_fetch_all("SELECT * FROM events ORDER BY sort_order ASC, id ASC");
?>

<div class="phead">
  <div>
    <h2>Events <span class="ser">Calendar Manager</span></h2>
    <p><?= count($events) ?> events scheduled · Links to public RSVP booking button</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('addModal')">+ Add Event</button>
  </div>
</div>

<div class="tblwrap">
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Poster</th>
        <th>Event Title</th>
        <th>Location</th>
        <th>Type</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($events as $e): ?>
        <tr>
          <td>
            <div class="chip wine" style="text-align:center;">
              <b><?= esc($e['day_num']) ?></b><br>
              <small><?= esc($e['month_label']) ?></small>
            </div>
          </td>
          <td><?php if (!empty($e['image_url'])): ?><img src="<?= esc(img_src($e['image_url'])) ?>" alt="" style="width:64px;height:42px;object-fit:cover;border-radius:4px;"><?php else: ?><span style="font-size:11px;color:var(--ink2);">No image</span><?php endif; ?></td>
          <td>
            <b><?= esc($e['title']) ?></b><br>
            <span style="font-size:12px;color:var(--ink2);"><?= esc(substr($e['description'], 0, 80)) ?>...</span>
          </td>
          <td style="font-size:12px;"><?= esc($e['location']) ?></td>
          <td><span class="chip"><?= esc($e['event_type']) ?></span></td>
          <td>
            <div class="act">
              <a href="events.php?edit=<?= $e['id'] ?>" class="btn sm">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this event?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $e['id'] ?>">
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
    <h3>Add Upcoming Event</h3>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="mrow2">
        <div class="field">
          <label>Day Number (e.g. 16)</label>
          <input type="text" name="day_num" placeholder="16" required>
        </div>
        <div class="field">
          <label>Month Label (e.g. Aug 2026)</label>
          <input type="text" name="month_label" placeholder="Aug 2026" required>
        </div>
      </div>

      <div class="field">
        <label>Event Date (for countdown)</label>
        <input type="date" name="event_date">
      </div>

      <div class="field">
        <label>Event Title *</label>
        <input type="text" name="title" placeholder="e.g. Glory Open-Air Crusade" required>
      </div>

      <div class="field">
        <label>Location Line</label>
        <input type="text" name="location" placeholder="✦ Eldoret Showgrounds · 2:00 PM · Free entry" required>
      </div>

      <div class="mrow2">
        <div class="field">
          <label>Event Type</label>
          <select name="event_type">
            <option value="crusade">Crusade</option>
            <option value="camp">Youth Camp</option>
            <option value="fundraiser">Fundraiser</option>
            <option value="concert">Concert</option>
            <option value="revival">Revival</option>
            <option value="sunday">Sunday Service</option>
          </select>
        </div>
        <div class="field">
          <label>Sort Order</label>
          <input type="number" name="sort_order" value="1">
        </div>
      </div>

      <div class="field">
        <label>Description</label>
        <textarea name="description" placeholder="One-day town crusade with worship, the Word and prayer for the sick..."></textarea>
      </div>

      <div class="field">
        <label>Event Poster Image</label>
        <input type="file" name="event_image" accept="image/jpeg,image/png,image/webp,image/gif">
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Save Event ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editEvent): ?>
  <div class="modal" id="editModal">
    <div class="mcard-modal">
      <h3>Edit Event</h3>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editEvent['id'] ?>">

        <div class="mrow2">
          <div class="field">
            <label>Day Number</label>
            <input type="text" name="day_num" value="<?= esc($editEvent['day_num']) ?>" required>
          </div>
          <div class="field">
            <label>Month Label</label>
            <input type="text" name="month_label" value="<?= esc($editEvent['month_label']) ?>" required>
          </div>
        </div>

        <div class="field">
          <label>Event Date (for countdown)</label>
          <input type="date" name="event_date" value="<?= esc($editEvent['event_date'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Event Title *</label>
          <input type="text" name="title" value="<?= esc($editEvent['title']) ?>" required>
        </div>

        <div class="field">
          <label>Location Line</label>
          <input type="text" name="location" value="<?= esc($editEvent['location']) ?>" required>
        </div>

        <div class="mrow2">
          <div class="field">
            <label>Event Type</label>
            <select name="event_type">
              <?php foreach (['crusade', 'camp', 'fundraiser', 'concert', 'revival', 'sunday'] as $t): ?>
                <option value="<?= $t ?>" <?= $editEvent['event_type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Sort Order</label>
            <input type="number" name="sort_order" value="<?= $editEvent['sort_order'] ?>">
          </div>
        </div>

        <div class="field">
          <label>Description</label>
          <textarea name="description"><?= esc($editEvent['description']) ?></textarea>
        </div>

        <div class="field">
          <label>Event Poster Image</label>
          <?php if (!empty($editEvent['image_url'])): ?><img src="<?= esc(img_src($editEvent['image_url'])) ?>" alt="Current event poster" style="width:120px;height:75px;object-fit:cover;border-radius:4px;margin-bottom:8px;"><?php endif; ?>
          <input type="hidden" name="existing_image_url" value="<?= esc($editEvent['image_url'] ?? '') ?>">
          <input type="file" name="event_image" accept="image/jpeg,image/png,image/webp,image/gif">
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="btn" style="flex:1;">Update Event ✦</button>
          <a href="events.php" class="btn ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
