<?php
/**
 * Public Member Registration
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// If already logged in, redirect to home
if (user_auth_check()) {
    header('Location: ' . BASE_URL . 'my_requests.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $location = sanitize_input($_POST['location'] ?? 'Eldoret');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields (Name, Email, Password).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email exists
        $existing = db_fetch_one("SELECT id FROM users WHERE email = ?", "s", [$email]);
        if ($existing) {
            $error = 'An account with this email address already exists. Please sign in instead.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $initials = calculate_initials($name);

            $sql = "INSERT INTO users (name, email, phone, location, avatar_initials, password_hash, status) VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $newId = db_query($sql, "ssssss", [$name, $email, $phone, $location, $initials, $hash]);

            if ($newId) {
                $user = db_fetch_one("SELECT * FROM users WHERE id = ?", "i", [$newId]);
                user_auth_login($user);
                set_flash('success', "Welcome to Redeemed Gospel Church Eldoret, $name!");
                header('Location: ' . BASE_URL . 'my_requests.php');
                exit;
            } else {
                $error = 'Could not create account. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;max-width:540px;">
  <div class="card" style="border-top:5px solid var(--wine);">
    <div style="text-align:center;margin-bottom:24px;">
      <span class="x" style="color:var(--wine);font-size:24px;">✝</span>
      <h2 style="font-size:1.8rem;margin-top:6px;">Join the Beacon Community</h2>
      <p style="font-size:.95rem;color:var(--ink2);margin-top:4px;">Create your member account to connect with pastor, track prayer requests, and join live worship</p>
    </div>

    <?php if (!empty($error)): ?>
      <div style="background:rgba(140,29,47,.1);color:var(--wine);border:1px solid rgba(140,29,47,.25);padding:10px 14px;border-radius:4px;font-family:var(--mono);font-size:11px;margin-bottom:18px;">
        ✗ <?= esc($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>

      <div class="field">
        <label>Full Name *</label>
        <input type="text" name="name" value="<?= esc($_POST['name'] ?? '') ?>" placeholder="e.g. Grace Wanjiru" required autofocus>
      </div>

      <div class="field">
        <label>Email Address *</label>
        <input type="email" name="email" value="<?= esc($_POST['email'] ?? '') ?>" placeholder="e.g. grace@example.com" required>
      </div>

      <div class="mrow2">
        <div class="field">
          <label>Phone / WhatsApp</label>
          <input type="text" name="phone" value="<?= esc($_POST['phone'] ?? '') ?>" placeholder="+254 7XX XXX XXX">
        </div>
        <div class="field">
          <label>Town / Location</label>
          <input type="text" name="location" value="<?= esc($_POST['location'] ?? 'Eldoret') ?>" placeholder="e.g. Eldoret">
        </div>
      </div>

      <div class="mrow2">
        <div class="field">
          <label>Password *</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="field">
          <label>Confirm Password *</label>
          <input type="password" name="confirm_password" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn" style="width:100%;margin-top:8px;">Create My Account ✦</button>

      <div style="text-align:center;margin-top:20px;font-family:var(--mono);font-size:11px;color:var(--ink2);">
        Already have an account? <a href="<?= BASE_URL ?>login.php" style="color:var(--wine);font-weight:600;">Sign in here &rarr;</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
