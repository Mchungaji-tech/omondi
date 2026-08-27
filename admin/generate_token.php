<?php
/**
 * One-Time Admin Registration Token Generator
 *
 * Visit this page to generate a single-use registration link.
 * The generated link expires in 24 hours and can only be used once.
 *
 * SECURITY: Delete or restrict access to this file after generating your link.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

$generatedLink = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $rawToken = strtoupper(bin2hex(random_bytes(16)));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60));

    $ok = db_query(
        "INSERT INTO admin_registration_tokens (token_hash, expires_at) VALUES (?, ?)",
        "ss",
        [$tokenHash, $expiresAt]
    );

    if ($ok) {
        $generatedLink = BASE_URL . 'admin/register.php?token=' . $rawToken;
        $message = 'Registration link generated successfully. Copy the URL below and send it to the new administrator.';
        log_audit(null, 'System', "Generated one-time admin registration link");
    } else {
        $message = 'Failed to generate registration link. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Admin Registration Link — Redeemed Gospel Church Eldoret</title>
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
    max-width: 560px;
    width: 100%;
    box-shadow: 0 2px 12px rgba(0,0,0,.04);
  }
  .brand { text-align: center; margin-bottom: 24px; }
  .brand span { color: var(--wine); font-size: 28px; }
  .brand h1 {
    font-family: var(--disp);
    font-size: 1.5rem;
    margin: 6px 0 4px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
  }
  .brand p { margin: 0; font-size: .9rem; color: var(--ink2); }
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
  .linkbox {
    background: #f7f5f0;
    border: 1px dashed var(--ink2);
    border-radius: 4px;
    padding: 14px;
    font-family: var(--mono);
    font-size: 12px;
    word-break: break-all;
    color: var(--ink);
    margin-bottom: 18px;
  }
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
  }
  .btn:hover { filter: brightness(1.1); }
  .hint {
    font-size: .8rem;
    color: var(--ink2);
    margin-top: 14px;
    text-align: center;
    line-height: 1.6;
  }
  .hint strong { color: var(--wine); }
</style>
</head>
<body>

<div class="card">
  <div class="brand">
    <span>✝</span>
    <h1>Generate Registration Link</h1>
    <p>Create a one-time link for a new administrator</p>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert <?= !empty($generatedLink) ? 'success' : 'error' ?>">
      <?= esc($message) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($generatedLink)): ?>
    <div class="linkbox"><?= esc($generatedLink) ?></div>
    <form method="post" onsubmit="return confirm('Generate a new link? Any previously generated unused link will become invalid.');">
      <?= csrf_field() ?>
      <button type="submit" class="btn">Generate New Link</button>
    </form>
  <?php else: ?>
    <form method="post" onsubmit="return confirm('Generate a one-time registration link?');">
      <?= csrf_field() ?>
      <button type="submit" class="btn">Generate One-Time Registration Link</button>
    </form>
  <?php endif; ?>

  <p class="hint">
    <strong>Security:</strong> This link expires in 24 hours and can be used only once.<br>
    After sending the link, you may delete or restrict this page.
  </p>
</div>

</body>
</html>
