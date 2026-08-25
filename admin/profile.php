<?php
/**
 * Admin Profile & Hero Settings Manager
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

// Handle Form Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!empty($_FILES['portrait_file']) && $_FILES['portrait_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_image($_FILES['portrait_file'], 'profile');
        if ($uploaded !== false) {
            $_POST['portrait_url'] = $uploaded;
        }
    }

    $fields = [
        'pastor_name' => sanitize_input($_POST['pastor_name'] ?? ''),
        'pastor_epithet' => sanitize_input($_POST['pastor_epithet'] ?? ''),
        'tagline' => sanitize_input($_POST['tagline'] ?? ''),
        'hero_verse' => sanitize_input($_POST['hero_verse'] ?? ''),
        'portrait_url' => sanitize_input($_POST['portrait_url'] ?? ''),
        'radio_station' => sanitize_input($_POST['radio_station'] ?? ''),
        'bio1' => trim($_POST['bio1'] ?? ''),
        'bio2' => trim($_POST['bio2'] ?? ''),
        'sun1' => sanitize_input($_POST['sun1'] ?? ''),
        'sun2' => sanitize_input($_POST['sun2'] ?? ''),
        'sun3' => sanitize_input($_POST['sun3'] ?? ''),
        'phone' => sanitize_input($_POST['phone'] ?? ''),
        'email' => sanitize_input($_POST['email'] ?? ''),
        'address' => sanitize_input($_POST['address'] ?? ''),
        'postal_box' => sanitize_input($_POST['postal_box'] ?? ''),
    ];

    foreach ($fields as $key => $val) {
        set_setting($key, $val);
    }

    log_audit($adminUser['id'], $adminUser['username'], "Updated pastor profile and hero settings");
    set_flash('success', 'Profile and hero settings updated successfully.');
    header('Location: ' . BASE_URL . 'admin/profile.php');
    exit;
}

$s = get_all_settings();
?>

<div class="phead">
  <div>
    <h2>Profile <span class="ser">&amp; Hero Settings</span></h2>
    <p>Controls the public masthead, about band, Sunday schedule, and church contacts</p>
  </div>
</div>

<div class="tblwrap" style="padding:28px;">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="mrow2">
      <div class="field">
        <label>Full Pastor Name</label>
        <input type="text" name="pastor_name" value="<?= esc($s['pastor_name'] ?? '') ?>" required>
      </div>

      <div class="field">
        <label>Epithet (e.g. of Eldoret)</label>
        <input type="text" name="pastor_epithet" value="<?= esc($s['pastor_epithet'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Tagline</label>
        <input type="text" name="tagline" value="<?= esc($s['tagline'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Hero Scripture Verse</label>
        <input type="text" name="hero_verse" value="<?= esc($s['hero_verse'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Pastor Portrait Photo</label>
        <?php if (!empty($s['portrait_url'])): ?>
          <div class="current-img-card">
            <span class="lbl">Current Portrait</span>
            <div class="cur" style="aspect-ratio:3/4;max-height:260px;">
              <img src="<?php
                $pv = $s['portrait_url'];
                echo preg_match('#^https?://#i', $pv) ? esc($pv) : BASE_URL . ltrim($pv, '/');
              ?>" alt="Current portrait" style="width:100%;height:100%;object-fit:cover;">
            </div>
          </div>
        <?php endif; ?>
        <div class="uploader">
          <input type="file" name="portrait_file" id="portrait_file" accept="image/*" class="up-input">
          <label for="portrait_file" class="up-btn">
            <span class="up-icon">🖼</span>
            <span class="up-text">
              <b>Choose Portrait Photo File</b>
              <small>JPG · PNG · WebP · Max 5 MB</small>
              <span class="up-filename">No file selected yet</span>
            </span>
          </label>
          <div class="up-preview" style="aspect-ratio:3/4;max-width:220px;" hidden>
            <img alt="Preview">
          </div>
        </div>
        <p class="up-url-note">Need to use a web link? <a data-toggle-url>Paste external URL instead &rarr;</a></p>
        <div class="up-url-row">
          <label style="display:block;margin-bottom:6px;font-family:var(--mono);font-size:11.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink2);">External Image URL</label>
          <input type="text" name="portrait_url" value="<?php echo esc($s['portrait_url'] ?? ''); ?>" placeholder="https://...">
        </div>
      </div>

      <div class="field">
        <label>Radio Broadcast Station</label>
        <input type="text" name="radio_station" value="<?= esc($s['radio_station'] ?? '') ?>">
      </div>

      <div class="field" style="grid-column: 1 / -1;">
        <label>Bio — Paragraph 1 (Story &amp; Calling)</label>
        <textarea name="bio1" rows="3"><?= esc($s['bio1'] ?? '') ?></textarea>
      </div>

      <div class="field" style="grid-column: 1 / -1;">
        <label>Bio — Paragraph 2 (Message &amp; Ministry Philosophy)</label>
        <textarea name="bio2" rows="3"><?= esc($s['bio2'] ?? '') ?></textarea>
      </div>

      <div class="field">
        <label>Sunday Service Line 1</label>
        <input type="text" name="sun1" value="<?= esc($s['sun1'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Sunday Service Line 2 (Main Live)</label>
        <input type="text" name="sun2" value="<?= esc($s['sun2'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Sunday Service Line 3</label>
        <input type="text" name="sun3" value="<?= esc($s['sun3'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Phone / WhatsApp Number</label>
        <input type="text" name="phone" value="<?= esc($s['phone'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Church Office Email</label>
        <input type="email" name="email" value="<?= esc($s['email'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Physical Address</label>
        <input type="text" name="address" value="<?= esc($s['address'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Postal Address</label>
        <input type="text" name="postal_box" value="<?= esc($s['postal_box'] ?? '') ?>">
      </div>
    </div>

    <div style="margin-top:20px;">
      <button type="submit" class="btn">Save Profile Changes ✦</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
