<?php
/**
 * Admin Security Center & Audit Trail
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_policy') {
        $mfa = !empty($_POST['security_mfa']) ? '1' : '0';
        $lock = !empty($_POST['security_lockout']) ? '1' : '0';
        $audit = !empty($_POST['security_audit']) ? '1' : '0';

        set_setting('security_mfa', $mfa);
        set_setting('security_lockout', $lock);
        set_setting('security_audit', $audit);

        log_audit($adminUser['id'], $adminUser['username'], "Updated security enforcement policy");
        set_flash('success', "Security policy settings saved.");
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $admin = db_fetch_one("SELECT * FROM admins WHERE id = ?", "i", [$adminUser['id']]);

        if (!$admin || !password_verify($currentPass, $admin['password_hash'])) {
            set_flash('error', 'Current password is incorrect.');
        } elseif (strlen($newPass) < 6) {
            set_flash('error', 'New password must be at least 6 characters long.');
        } elseif ($newPass !== $confirmPass) {
            set_flash('error', 'New password and confirmation do not match.');
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            db_query("UPDATE admins SET password_hash = ? WHERE id = ?", "si", [$newHash, $adminUser['id']]);
            log_audit($adminUser['id'], $adminUser['username'], "Administrator changed their password");
            set_flash('success', 'Password changed successfully.');
        }
        } elseif ($action === 'rotate_totp') {
          $secret = generate_totp_secret();
          db_query("UPDATE admins SET totp_secret = ?, mfa_enabled = 1 WHERE id = ?", "si", [$secret, $adminUser['id']]);
          log_audit($adminUser['id'], $adminUser['username'], "Generated a new authenticator secret");
          set_flash('success', 'New authenticator secret generated. Scan the QR code below before your next login.');
        } elseif ($action === 'generate_recovery_codes') {
          $recovery = generate_recovery_codes();
          db_query("UPDATE admins SET recovery_codes = ?, recovery_codes_generated_at = CURRENT_TIMESTAMP WHERE id = ?", "si", [$recovery['stored'], $adminUser['id']]);
          $_SESSION['new_recovery_codes'] = $recovery['plain'];
          log_audit($adminUser['id'], $adminUser['username'], "Generated new one-time recovery codes");
          set_flash('success', 'Recovery codes generated. Save or print them now; they will not be shown again.');
        } elseif ($action === 'generate_setup_token') {
          $_SESSION['new_mfa_setup_token'] = create_mfa_setup_token($adminUser['id']);
          log_audit($adminUser['id'], $adminUser['username'], "Generated one-time MFA registration code");
          set_flash('success', 'One-time registration code generated and expires in 15 minutes.');
        } elseif ($action === 'use_setup_token') {
          if (consume_mfa_setup_token($adminUser['id'], $_POST['setup_token'] ?? '')) {
            $secret = generate_totp_secret();
            db_query("UPDATE admins SET totp_secret = ?, mfa_enabled = 1 WHERE id = ?", "si", [$secret, $adminUser['id']]);
            log_audit($adminUser['id'], $adminUser['username'], "Completed one-time MFA registration");
            set_flash('success', 'Authenticator registration completed. Scan the new QR code before signing out.');
          } else {
            set_flash('error', 'That registration code is invalid, expired, or already used.');
          }
    } elseif ($action === 'clear_audit') {
        db_query("DELETE FROM audit_logs WHERE id NOT IN (SELECT id FROM (SELECT id FROM audit_logs ORDER BY id DESC LIMIT 5) as t)");
        log_audit($adminUser['id'], $adminUser['username'], "Cleared older audit logs");
        set_flash('success', "Audit history trimmed.");
    }

    header('Location: ' . BASE_URL . 'admin/security.php');
    exit;
}

$mfaOn = (bool)get_setting('security_mfa', '1');
$lockOn = (bool)get_setting('security_lockout', '1');
$auditOn = (bool)get_setting('security_audit', '1');
$currentAdmin = db_fetch_one("SELECT username, email, totp_secret, mfa_enabled FROM admins WHERE id = ?", "i", [$adminUser['id']]);
$totpSecret = $currentAdmin['totp_secret'] ?? '';
$totpUri = $totpSecret !== '' ? totp_uri($totpSecret, $currentAdmin['email'] ?: $currentAdmin['username']) : '';
$newRecoveryCodes = $_SESSION['new_recovery_codes'] ?? [];
$newMfaSetupToken = $_SESSION['new_mfa_setup_token'] ?? '';
unset($_SESSION['new_recovery_codes'], $_SESSION['new_mfa_setup_token']);

$fromDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '';
$toDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '';
$logWhere = [];
$logParams = [];
$logTypes = '';
if ($fromDate !== '') { $logWhere[] = 'created_at >= ?'; $logParams[] = $fromDate . ' 00:00:00'; $logTypes .= 's'; }
if ($toDate !== '') { $logWhere[] = 'created_at <= ?'; $logParams[] = $toDate . ' 23:59:59'; $logTypes .= 's'; }
$logs = db_fetch_all('SELECT * FROM audit_logs' . ($logWhere ? ' WHERE ' . implode(' AND ', $logWhere) : '') . ' ORDER BY id DESC LIMIT 200', $logTypes, $logParams);
?>

<div class="phead">
  <div>
    <h2>Security <span class="ser">Centre &amp; Audit Trail</span></h2>
    <p>Authentication policies, password controls, and system access logs</p>
  </div>
</div>

<div class="cards4" style="grid-template-columns: 1fr 1fr; margin-bottom: 30px;">
  <!-- POLICY SETTINGS -->
  <div class="tblwrap" style="padding:26px;">
    <h4 style="font-size:1.2rem;margin-bottom:16px;">Security Policies</h4>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_policy">

      <div class="field">
        <label>Multi-Factor Authentication (TOTP)</label>
        <select name="security_mfa">
          <option value="1" <?= $mfaOn ? 'selected' : '' ?>>ENFORCED (Require TOTP code or QR approval)</option>
          <option value="0" <?= !$mfaOn ? 'selected' : '' ?>>OPTIONAL / OFF</option>
        </select>
      </div>

      <div class="field">
        <label>Brute-Force Lockout (5 Failed Attempts = 5 min lock)</label>
        <select name="security_lockout">
          <option value="1" <?= $lockOn ? 'selected' : '' ?>>ON (Enforce brute-force lockout)</option>
          <option value="0" <?= !$lockOn ? 'selected' : '' ?>>OFF</option>
        </select>
      </div>

      <div class="field">
        <label>Audit Trail Logging</label>
        <select name="security_audit">
          <option value="1" <?= $auditOn ? 'selected' : '' ?>>ACTIVE (Log all admin &amp; public actions)</option>
          <option value="0" <?= !$auditOn ? 'selected' : '' ?>>OFF</option>
        </select>
      </div>

      <button type="submit" class="btn" style="margin-top:10px;">Save Security Policy ✦</button>
    </form>
  </div>

  <!-- PASSWORD CHANGER -->
  <div class="tblwrap" style="padding:26px;">
    <h4 style="font-size:1.2rem;margin-bottom:16px;">Change Administrator Password</h4>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">

      <div class="field">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
      </div>

      <div class="mrow2">
        <div class="field">
          <label>New Password</label>
          <input type="password" name="new_password" required>
        </div>
        <div class="field">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" required>
        </div>
      </div>

      <button type="submit" class="btn gold" style="margin-top:10px;">Update Password ✦</button>
    </form>
  </div>

  <div class="tblwrap" style="padding:26px;">
    <h4 style="font-size:1.2rem;margin-bottom:10px;">Authenticator App</h4>
    <p style="color:var(--ink2);font-size:.95rem;margin-bottom:14px;">Scan this QR code with Google Authenticator or Microsoft Authenticator. Codes change every 30 seconds.</p>
    <?php if ($totpUri): ?>
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=168x168&data=<?= rawurlencode($totpUri) ?>" width="168" height="168" alt="Authenticator QR code" style="display:block;margin-bottom:12px;">
      <code style="display:block;word-break:break-all;font-size:11px;margin-bottom:14px;"><?= esc($totpSecret) ?></code>
    <?php endif; ?>
    <form method="post" onsubmit="return confirm('Generate a new secret? Existing authenticator apps will stop working.');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="rotate_totp">
      <button type="submit" class="btn gold">Generate New QR Secret</button>
    </form>
  </div>

  <div class="tblwrap" style="padding:26px;">
    <h4 style="font-size:1.2rem;margin-bottom:10px;">Recovery Codes</h4>
    <p style="color:var(--ink2);font-size:.95rem;margin-bottom:14px;">Each code works once if your authenticator is unavailable. Generating new codes invalidates the old set.</p>
    <?php if (!empty($newRecoveryCodes)): ?>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;padding:14px;background:var(--paper2);border:1px solid var(--line);margin-bottom:14px;">
        <?php foreach ($newRecoveryCodes as $code): ?><code><?= esc($code) ?></code><?php endforeach; ?>
      </div>
      <p class="hint">This is the only time these codes are displayed. Print or store them securely now.</p>
    <?php endif; ?>
    <form method="post" onsubmit="return confirm('Generate a new recovery-code set? Old codes will stop working.');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="generate_recovery_codes">
      <button type="submit" class="btn gold">Generate Recovery Codes</button>
    </form>
  </div>

  <div class="tblwrap" style="padding:26px;">
    <h4 style="font-size:1.2rem;margin-bottom:10px;">One-Time MFA Registration</h4>
    <p style="color:var(--ink2);font-size:.95rem;margin-bottom:14px;">Generate a short-lived registration code for a controlled authenticator setup. It expires after 15 minutes and can be used once.</p>
    <?php if ($newMfaSetupToken): ?><code style="display:block;font-size:1.4rem;letter-spacing:.14em;padding:14px;background:var(--paper2);margin-bottom:14px;"><?= esc($newMfaSetupToken) ?></code><?php endif; ?>
    <form method="post" onsubmit="return confirm('Generate a new one-time registration code?');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="generate_setup_token">
      <button type="submit" class="btn">Generate Registration Code</button>
    </form>
    <form method="post" style="margin-top:12px;display:flex;gap:8px;align-items:end;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="use_setup_token">
      <div class="field" style="flex:1;margin:0;"><label>Registration code</label><input type="text" name="setup_token" maxlength="16" required></div>
      <button type="submit" class="btn gold">Use Once</button>
    </form>
  </div>
</div>

<!-- AUDIT TRAIL LOGS -->
<div class="tblwrap">
  <div style="padding:18px 22px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;">
    <h4 style="font-size:1.15rem;">System Audit Trail (Latest <?= count($logs) ?> Events)</h4>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <form method="get" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;">
        <div class="field" style="margin:0;"><label>From</label><input type="date" name="from" value="<?= esc($fromDate) ?>"></div>
        <div class="field" style="margin:0;"><label>To</label><input type="date" name="to" value="<?= esc($toDate) ?>"></div>
        <button type="submit" class="btn sm">Filter</button>
        <a href="security.php" class="btn sm ghost">Clear</a>
      </form>
      <form method="post" onsubmit="return confirm('Trim older logs?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="clear_audit">
        <button type="submit" class="btn sm danger">Trim Older Logs</button>
      </form>
    </div>
  </div>
  <table>
    <thead>
      <tr>
        <th>Timestamp (EAT)</th>
        <th>User</th>
        <th>Action Performed</th>
        <th>IP Address</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="4" style="text-align:center;padding:24px;">No audit records.</td></tr>
      <?php else: ?>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td class="mono" style="font-size:11px;"><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></td>
            <td><b><?= esc($l['username']) ?></b></td>
            <td><?= esc($l['action']) ?></td>
            <td class="mono" style="font-size:11px;color:var(--ink2);"><?= esc($l['ip_address']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
