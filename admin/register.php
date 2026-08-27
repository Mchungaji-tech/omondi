<?php
/**
 * One-Time Admin Registration
 * Pure Procedural PHP + MySQL
 *
 * Access via: admin/register.php?token=XXXXX
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

$token = $_GET['token'] ?? '';
$token = strtoupper(trim((string)$token));

$error = '';
$success = '';
$validToken = false;

if (empty($token)) {
    $error = 'Missing registration token. Please use the link sent to you.';
} else {
    $tokenRow = db_fetch_one(
        "SELECT * FROM admin_registration_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()",
        "s",
        [hash('sha256', $token)]
    );

    if (!$tokenRow) {
        $error = 'This registration link is invalid, expired, or has already been used.';
    } else {
        $validToken = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    csrf_verify();

    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $existing = db_fetch_one("SELECT id FROM admins WHERE username = ? OR email = ?", "ss", [$username, $email]);
        if ($existing) {
            $error = 'An administrator with this username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $newId = db_query(
                "INSERT INTO admins (username, email, password_hash, role, mfa_enabled) VALUES (?, ?, ?, 'admin', 0)",
                "sss",
                [$username, $email, $hash]
            );

            if ($newId) {
                db_query("UPDATE admin_registration_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = ?", "i", [$tokenRow['id']]);
                log_audit($newId, $username, "New administrator account created via one-time registration link");
                set_flash('success', 'Administrator account created successfully. You may now sign in.');
                header('Location: ' . BASE_URL . 'admin/login.php');
                exit;
            } else {
                $error = 'Could not create administrator account. Please try again.';
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
<title>Admin Registration — Redeemed Gospel Church Eldoret</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Newsreader:ital,opsz,wght@0,6..72,300..700;1,6..72,300..700&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
  :root {
    --wine: #8C1D2F;
    --paper: #FFFDF8;
    --ink: #191613;
    --ink2: #6b6560;
    --line: rgba(25,22,19,.12);
    --mono: 'IBM Plex Mono', monospace;
    --disp: 'Anton', sans-serif;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    font-family: 'Newsreader', serif;
    background: var(--paper);
    color: var(--ink);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
  }
  .card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 36px 32px;
    max-width: 480px;
    width: 100%;
    box-shadow: 0 2px 12px rgba(0,0,0,.04);
  }
  .brand { text-align: center; margin-bottom: 24px; }
  .brand span { color: var(--wine); font-size: 28px; }
  .brand h1 {
    font-family: var(--disp);
    font-size: 1.6rem;
    margin: 6px 0 4px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
  }
  .brand p { margin: 0; font-size: .9rem; color: var(--ink2); }
  .field { margin-bottom: 14px; }
  .field label {
    display: block;
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ink2);
    margin-bottom: 6px;
  }
  .field input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--line);
    border-radius: 4px;
    font-family: var(--mono);
    font-size: 14px;
    color: var(--ink);
    background: #fff;
    outline: none;
    transition: border-color .15s;
  }
  .field input:focus { border-color: var(--wine); }
  .mrow2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border: none;
    border-radius: 4px;
    font-family: var(--mono);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.06em;
    cursor: pointer;
    text-decoration: none;
    background: var(--wine);
    color: #fff;
    width: 100%;
    margin-top: 6px;
  }
  .btn:hover { filter: brightness(1.1); }
  .alert {
    padding: 10px 14px;
    border-radius: 4px;
    font-family: var(--mono);
    font-size: 11px;
    margin-bottom: 18px;
    line-height: 1.5;
  }
  .alert.error {
    background: rgba(140,29,47,.08);
    color: var(--wine);
    border: 1px solid rgba(140,29,47,.25);
  }
  .alert.success {
    background: rgba(34,120,60,.08);
    color: #1a5c32;
    border: 1px solid rgba(34,120,60,.25);
  }
  .hint {
    font-size: .8rem;
    color: var(--ink2);
    margin-top: 14px;
    text-align: center;
  }
  .hint a { color: var(--wine); text-decoration: none; font-weight: 600; }
  .hint a:hover { text-decoration: underline; }
  @media (max-width: 520px) {
    .mrow2 { grid-template-columns: 1fr; }
    .card { padding: 24px 18px; }
  }
</style>
</head>
<body>

<div class="card">
  <div class="brand">
    <span>✝</span>
    <h1>Administrator Registration</h1>
    <p>Redeemed Gospel Church Eldoret · Beacon Admin</p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert error">✗ <?= esc($error) ?></div>
  <?php endif; ?>

  <?php if (!$validToken && empty($success)): ?>
    <div class="alert error">✗ <?= esc($error) ?></div>
    <p class="hint">Please request a valid registration link from an existing administrator.</p>
  <?php elseif ($validToken): ?>
    <form method="post">
      <?= csrf_field() ?>

      <div class="field">
        <label>Username *</label>
        <input type="text" name="username" value="<?= esc($_POST['username'] ?? '') ?>" placeholder="e.g. john_admin" required autofocus>
      </div>

      <div class="field">
        <label>Email Address *</label>
        <input type="email" name="email" value="<?= esc($_POST['email'] ?? '') ?>" placeholder="e.g. john@example.com" required>
      </div>

      <div class="mrow2">
        <div class="field">
          <label>Password *</label>
          <input type="password" name="password" placeholder="Min. 8 characters" required>
        </div>
        <div class="field">
          <label>Confirm Password *</label>
          <input type="password" name="confirm_password" placeholder="Repeat password" required>
        </div>
      </div>

      <button type="submit" class="btn">Create Administrator Account ✦</button>
    </form>

    <p class="hint">
      This link is one-time use and will expire after first successful registration.<br>
      Already have an account? <a href="<?= BASE_URL ?>admin/login.php">Sign in here &rarr;</a>
    </p>
  <?php endif; ?>
</div>

</body>
</html>
