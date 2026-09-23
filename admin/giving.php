<?php
/**
 * Admin Giving Details Manager
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fields = [
        'mpesa_paybill' => sanitize_input($_POST['mpesa_paybill'] ?? ''),
        'bank_name' => sanitize_input($_POST['bank_name'] ?? ''),
        'bank_account' => sanitize_input($_POST['bank_account'] ?? ''),
        'bank_account_name' => sanitize_input($_POST['bank_account_name'] ?? ''),
        'swift_code' => sanitize_input($_POST['swift_code'] ?? ''),
        'bank_code' => sanitize_input($_POST['bank_code'] ?? ''),
        'branch_code' => sanitize_input($_POST['branch_code'] ?? ''),
        'bank_address' => sanitize_input($_POST['bank_address'] ?? ''),
    ];

    foreach ($fields as $key => $val) {
        set_setting($key, $val);
    }

    log_audit($adminUser['id'], $adminUser['username'], "Updated official church giving and banking details");
    set_flash('success', 'Official giving and banking channels updated successfully.');
    header('Location: ' . BASE_URL . 'admin/giving.php');
    exit;
}

$s = get_all_settings();
?>

<div class="phead">
  <div>
    <h2>Giving <span class="ser">&amp; Payment Channels</span></h2>
    <p>Controls the official M-Pesa Paybill, bank transfer, SWIFT, bank/branch codes, and banking address on the public site</p>
  </div>
</div>

<div class="tblwrap" style="padding:28px;">
  <form method="post">
    <?= csrf_field() ?>

    <div class="mrow2">
      <div class="field">
        <label>M-Pesa Paybill Number *</label>
        <input type="text" name="mpesa_paybill" value="<?= esc($s['mpesa_paybill'] ?? '') ?>" required>
      </div>

      <div class="field">
        <label>Bank Name &amp; Branch *</label>
        <input type="text" name="bank_name" value="<?= esc($s['bank_name'] ?? '') ?>" required>
      </div>

      <div class="field">
        <label>Bank Account Number *</label>
        <input type="text" name="bank_account" value="<?= esc($s['bank_account'] ?? '') ?>" required>
      </div>

      <div class="field">
        <label>Bank Account Name *</label>
        <input type="text" name="bank_account_name" value="<?= esc($s['bank_account_name'] ?? '') ?>" required>
      </div>

      <div class="field">
        <label>SWIFT / BIC Code (Diaspora Giving)</label>
        <input type="text" name="swift_code" value="<?= esc($s['swift_code'] ?? '') ?>" placeholder="e.g. EQBLKENA">
      </div>

      <div class="field">
        <label>Bank Code</label>
        <input type="text" name="bank_code" value="<?= esc($s['bank_code'] ?? '') ?>" placeholder="e.g. 68">
      </div>

      <div class="field">
        <label>Branch Code</label>
        <input type="text" name="branch_code" value="<?= esc($s['branch_code'] ?? '') ?>" placeholder="e.g. 045">
      </div>

      <div class="field">
        <label>Bank Branch Physical Address</label>
        <input type="text" name="bank_address" value="<?= esc($s['bank_address'] ?? '') ?>" placeholder="e.g. Uganda Road, Eldoret Branch, P.O. Box 1234, Eldoret">
      </div>
    </div>

    <div style="margin-top:20px;">
      <button type="submit" class="btn">Update Giving Channels ✦</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
