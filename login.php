<?php
/**
 * Public Member Sign In
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// If already logged in, redirect to member portal
if (user_auth_check()) {
    header('Location: ' . BASE_URL . 'my_requests.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $user = db_fetch_one("SELECT * FROM users WHERE email = ?", "s", [$email]);

        if ($user) {
            if ($user['status'] === 'suspended') {
                $error = 'This member account is currently suspended. Please contact the church office.';
            } elseif (password_verify($password, $user['password_hash'])) {
                user_auth_login($user);
                set_flash('success', "Welcome back, " . $user['name'] . "!");
                $redirectTo = !empty($_GET['redirect']) ? sanitize_input($_GET['redirect']) : 'my_requests.php';
                header('Location: ' . BASE_URL . $redirectTo);
                exit;
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        } else {
            $error = 'No member account found with that email address.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;max-width:480px;">
  <div class="card" style="border-top:5px solid var(--wine);">
    <div style="text-align:center;margin-bottom:24px;">
      <span class="x" style="color:var(--wine);font-size:24px;">✝</span>
      <h2 style="font-size:1.8rem;margin-top:6px;">Member Sign In</h2>
      <p style="font-size:.95rem;color:var(--ink2);margin-top:4px;">Sign in to view your prayer requests, invitations, and active pledges</p>
    </div>

    <?php if (!empty($error)): ?>
      <div style="background:rgba(140,29,47,.1);color:var(--wine);border:1px solid rgba(140,29,47,.25);padding:10px 14px;border-radius:4px;font-family:var(--mono);font-size:11px;margin-bottom:18px;">
        ✗ <?= esc($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>

      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" value="<?= esc($_POST['email'] ?? '') ?>" placeholder="e.g. john.doe@example.com" required autofocus>
      </div>

      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn" style="width:100%;margin-top:8px;">Sign In ✦</button>

      <div style="text-align:center;margin-top:20px;font-family:var(--mono);font-size:11px;color:var(--ink2);">
        Don't have a member account? <a href="<?= BASE_URL ?>register.php" style="color:var(--wine);font-weight:600;">Create one here &rarr;</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
