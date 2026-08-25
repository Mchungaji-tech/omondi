<?php
/**
 * Admin Login Page with MFA (TOTP / QR) & Lockout Protection
 * Pure Procedural PHP
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// If already logged in, redirect to dashboard
if (auth_check()) {
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

$error = '';
$step = 1; // Step 1: credentials, Step 2: MFA verification
$tempUser = null;

// Check temporary MFA session
if (isset($_SESSION['mfa_user_id'])) {
  if (empty($_SESSION['mfa_started']) || time() - (int)$_SESSION['mfa_started'] > 300) {
    unset($_SESSION['mfa_user_id'], $_SESSION['mfa_started']);
  }
  $tempUser = db_fetch_one("SELECT * FROM admins WHERE id = ?", "i", [$_SESSION['mfa_user_id'] ?? 0]);
    if ($tempUser) {
        $step = 2;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter your username and password.';
        } else {
            $user = db_fetch_one("SELECT * FROM admins WHERE username = ? OR email = ?", "ss", [$username, $username]);

            if ($user) {
                // Check Lockout
                $lockoutEnabled = (bool)get_setting('security_lockout', '1');
                if ($lockoutEnabled && !empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                    $diff = ceil((strtotime($user['locked_until']) - time()) / 60);
                    $error = "Account locked due to consecutive failed attempts. Try again in $diff minutes.";
                } else {
                    if (password_verify($password, $user['password_hash'])) {
                        // Reset failed attempts
                        db_query("UPDATE admins SET failed_attempts = 0, locked_until = NULL WHERE id = ?", "i", [$user['id']]);

                        // Check MFA enforcement
                        $mfaEnforced = (bool)get_setting('security_mfa', '1');
                        if ($user['mfa_enabled'] && $mfaEnforced) {
                            $_SESSION['mfa_user_id'] = $user['id'];
                          $_SESSION['mfa_started'] = time();
                            $step = 2;
                            $tempUser = $user;
                        } else {
                            // Directly log in
                            auth_login($user);
                            header('Location: ' . BASE_URL . 'admin/index.php');
                            exit;
                        }
                    } else {
                        // Increment failed attempts
                        $attempts = (int)$user['failed_attempts'] + 1;
                        $lockSql = ($lockoutEnabled && $attempts >= 5) ? ", locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE)" : "";
                        db_query("UPDATE admins SET failed_attempts = ?$lockSql WHERE id = ?", "ii", [$attempts, $user['id']]);
                        
                        log_audit($user['id'], $username, "Failed password attempt ($attempts)");
                        $error = ($attempts >= 5) ? "Too many failed attempts. Account locked for 5 minutes." : "Invalid password. (Attempt $attempts of 5)";
                    }
                }
            } else {
                $error = 'Admin account not found.';
            }
        }
    } elseif ($action === 'verify_mfa') {
        $totpCode = sanitize_input($_POST['totp_code'] ?? '');
        $userId = $_SESSION['mfa_user_id'] ?? 0;

        if ($userId) {
            $user = db_fetch_one("SELECT * FROM admins WHERE id = ?", "i", [$userId]);
            if ($user) {
                $secret = $user['totp_secret'] ?? '';
                $validTotp = verify_totp_code($totpCode, $secret);
                $validRecovery = !$validTotp && consume_recovery_code($user, $totpCode);
                if ($validTotp || $validRecovery) {
                    unset($_SESSION['mfa_user_id'], $_SESSION['mfa_started']);
                    auth_login($user);
                    header('Location: ' . BASE_URL . 'admin/index.php');
                    exit;
                } else {
                    $error = 'Invalid 6-digit authenticator code. Check your authenticator timer.';
                    $step = 2;
                    $tempUser = $user;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Sign In — Redeemed Gospel Church Eldoret</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Newsreader:ital,opsz,wght@0,6..72,300..700;1,6..72,300..700&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
</head>
<body>

<div class="login">
  <!-- LEFT BRANDING PANEL -->
  <div class="lleft">
    <div>
      <span class="k">Redeemed Gospel Church Eldoret</span>
      <h1>Admin Console <span class="ser">&amp; Management</span></h1>
      <ul>
        <li>Pulpit sermon archive and category taxonomy</li>
        <li>Live stream status, viewer simulator &amp; chat moderation</li>
        <li>Confidential intercession prayer requests inbox</li>
        <li>Partner capital proposals &amp; commitments registry</li>
      </ul>
    </div>
    <div class="verse">
      “Let all things be done decently and in order.” — 1 Corinthians 14:40
    </div>
  </div>

  <!-- RIGHT AUTHENTICATION FORM -->
  <div class="lright">
    <div class="lcard">
      <h2><?= $step === 1 ? 'Sign In to Console' : 'Two-Factor Authentication' ?></h2>
      <p class="sub"><?= $step === 1 ? 'Enter your administrative credentials' : 'Step 2: Authenticator verification' ?></p>

      <div class="steps">
        <span id="st1"><?= $step === 1 ? '<b>1</b> Credentials' : '1 Credentials ✓' ?></span>
        <span>&rarr;</span>
        <span id="st2"><?= $step === 2 ? '<b>2</b> Verification' : '2 Verification' ?></span>
      </div>

      <?php if (!empty($error)): ?>
        <div style="background:rgba(140,29,47,.1);color:var(--wine);border:1px solid rgba(140,29,47,.3);padding:10px 14px;border-radius:4px;font-family:var(--mono);font-size:11px;margin-bottom:18px;">
          ✗ <?= esc($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($step === 1): ?>
        <!-- STEP 1: CREDENTIALS -->
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="login">

          <div class="field">
            <label>Username or Email</label>
            <input type="text" name="username" value="<?= esc($_POST['username'] ?? '') ?>" required autofocus>
          </div>

          <div class="field">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••••••" required>
          </div>

          <button type="submit" class="btn" style="width:100%">Continue &rarr;</button>

        </form>
      <?php else: ?>
        <!-- STEP 2: MFA VERIFICATION -->
        <div class="tabs">
          <button type="button" class="on" id="tabTotp">Code (TOTP)</button>
          <button type="button" id="tabQr">QR Enrolment</button>
        </div>

        <!-- TOTP PANE -->
        <div id="paneTotp">
          <div class="authwidget">
            <div>
              <small>Enter the current code from your authenticator app</small>
              <div class="code">••••••</div>
            </div>
            <div class="ring" id="authRing">30s</div>
          </div>

          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="verify_mfa">
            
            <div class="field">
              <label>Authenticator or Recovery Code</label>
              <input type="text" name="totp_code" id="totpIn" placeholder="6-digit code or XXXXX-XXXXX" maxlength="11" autofocus required>
            </div>

            <button type="submit" class="btn" style="width:100%">Verify &amp; Enter Console ✦</button>
          </form>
        </div>

        <!-- QR CODE PANE -->
        <div id="paneQr" hidden>
          <div class="qrbox">
            <?php $qrUri = totp_uri($tempUser['totp_secret'] ?? '', $tempUser['email'] ?? $tempUser['username']); ?>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=168x168&data=<?= rawurlencode($qrUri) ?>" width="168" height="168" alt="Authenticator QR code">
            <p>Scan with Google Authenticator or Microsoft Authenticator</p>
          </div>
          <p class="hint">After scanning, switch to Code (TOTP) and enter the six-digit code shown in your app.</p>
          <button type="button" class="btn gold" style="width:100%;margin-top:12px;" onclick="document.getElementById('tabTotp').click()">Use Code from Authenticator</button>
        </div>

        <div style="margin-top:16px;text-align:center;">
          <a href="<?= BASE_URL ?>admin/login.php?reset=1" style="font-family:var(--mono);font-size:12px;color:var(--ink2);">&larr; Back to login</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>assets/js/admin.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const tabTotp = document.getElementById("tabTotp");
  const tabQr = document.getElementById("tabQr");
  const paneTotp = document.getElementById("paneTotp");
  const paneQr = document.getElementById("paneQr");

  if (tabTotp && tabQr) {
    tabTotp.onclick = () => {
      tabTotp.classList.add("on");
      tabQr.classList.remove("on");
      paneTotp.hidden = false;
      paneQr.hidden = true;
    };
    tabQr.onclick = () => {
      tabQr.classList.add("on");
      tabTotp.classList.remove("on");
      paneQr.hidden = false;
      paneTotp.hidden = true;
      if (window.drawQRCanvas) window.drawQRCanvas("qrCanvas");
    };
  }
});
</script>
</body>
</html>
