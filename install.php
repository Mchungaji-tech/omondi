<?php
/**
 * Automated Installer & Database Setup Script
 * Redeemed Gospel Church Eldoret — Procedural PHP + MySQL
 * Compatible with Local (XAMPP) and Live Hosting (cPanel / LiteSpeed / Apache)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Read existing .env if present
$envPath = __DIR__ . '/.env';
$defaultHost = 'localhost';
$defaultUser = 'root';
$defaultPass = '';
$defaultDb   = 'bgc_church';
$defaultPort = 3306;

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        switch ($key) {
            case 'DB_HOST': $defaultHost = $val; break;
            case 'DB_USER': $defaultUser = $val; break;
            case 'DB_PASS': $defaultPass = $val; break;
            case 'DB_NAME': $defaultDb   = $val; break;
            case 'DB_PORT': $defaultPort = (int)$val; break;
        }
    }
}

$status = [];
$success = false;

function run_installer($host, $user, $pass, $db_name, $port, &$status, $adminUser = 'admin', $adminPass = 'Admin@2026!') {
    // Step 1: Connect to MySQL Server
    $conn = @mysqli_connect($host, $user, $pass, null, (int)$port);
    if (!$conn) {
        $status[] = ['type' => 'error', 'msg' => 'Could not connect to MySQL server (' . htmlspecialchars($host) . '): ' . mysqli_connect_error()];
        return false;
    }
    $status[] = ['type' => 'success', 'msg' => "Connected to MySQL server ({$host})."];

    // Step 2: Create or Select Database
    $sqlCreate = "CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    @mysqli_query($conn, $sqlCreate); // May fail if user lacks CREATE DB privilege (common on shared cPanel), which is fine if DB already created

    if (!@mysqli_select_db($conn, $db_name)) {
        $status[] = ['type' => 'error', 'msg' => "Database `{$db_name}` could not be selected: " . mysqli_error($conn) . ". On cPanel hosting, please create the database first in cPanel MySQL Databases."];
        mysqli_close($conn);
        return false;
    }
    $status[] = ['type' => 'success', 'msg' => "Database `{$db_name}` selected."];
    mysqli_set_charset($conn, 'utf8mb4');

    // Step 3: Read and execute schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        $status[] = ['type' => 'error', 'msg' => 'schema.sql file not found in root directory.'];
        mysqli_close($conn);
        return false;
    }

    $sqlContent = file_get_contents($schemaFile);
    if (mysqli_multi_query($conn, $sqlContent)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        
        $status[] = ['type' => 'success', 'msg' => "Schema tables and initial seed data imported successfully."];
    } else {
        $status[] = ['type' => 'error', 'msg' => 'Error executing schema statements: ' . mysqli_error($conn)];
        mysqli_close($conn);
        return false;
    }

    // Step 4: Ensure default admin has properly hashed password
    $adminEmail = 'office@revlangat.or.ke';
    $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);

    $checkStmt = mysqli_prepare($conn, "SELECT id FROM admins WHERE username = ?");
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "s", $adminUser);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $updateStmt = mysqli_prepare($conn, "UPDATE admins SET password_hash = ?, totp_secret = NULL, mfa_enabled = 0 WHERE username = ?");
            mysqli_stmt_bind_param($updateStmt, "ss", $passwordHash, $adminUser);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
            $status[] = ['type' => 'success', 'msg' => "Admin user credentials synchronized (`{$adminUser}`)."];
        } else {
            $insertStmt = mysqli_prepare($conn, "INSERT INTO admins (username, email, password_hash, role, totp_secret, mfa_enabled) VALUES (?, ?, ?, 'admin', NULL, 0)");
            mysqli_stmt_bind_param($insertStmt, "sss", $adminUser, $adminEmail, $passwordHash);
            mysqli_stmt_execute($insertStmt);
            mysqli_stmt_close($insertStmt);
            $status[] = ['type' => 'success', 'msg' => "Admin account created (`{$adminUser}`)."];
        }
        mysqli_stmt_close($checkStmt);
    }
    mysqli_close($conn);

    // Step 5: Create / Update .env file
    $envPath = __DIR__ . '/.env';
    $envContent = "APP_NAME=\"Redeemed Gospel Church Eldoret\"\n";
    $envContent .= "APP_ENV=production\n";
    $envContent .= "APP_DEBUG=0\n";
    $envContent .= "DB_HOST=\"{$host}\"\n";
    $envContent .= "DB_USER=\"{$user}\"\n";
    $envContent .= "DB_PASS=\"{$pass}\"\n";
    $envContent .= "DB_NAME=\"{$db_name}\"\n";
    $envContent .= "DB_PORT={$port}\n";

    if (@file_put_contents($envPath, $envContent) !== false) {
        $status[] = ['type' => 'success', 'msg' => ".env configuration file created/updated successfully."];
    } else {
        $status[] = ['type' => 'warning', 'msg' => "Could not write .env file. Please ensure folder write permissions are set."];
    }

    return true;
}

// Process Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? $defaultHost);
    $user = trim($_POST['db_user'] ?? $defaultUser);
    $pass = (string)($_POST['db_pass'] ?? $defaultPass);
    $db_name = trim($_POST['db_name'] ?? $defaultDb);
    $port = (int)($_POST['db_port'] ?? $defaultPort);
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = (string)($_POST['admin_pass'] ?? 'Admin@2026!');

    $success = run_installer($host, $user, $pass, $db_name, $port, $status, $adminUser, $adminPass);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup &amp; Installer — Redeemed Gospel Church Eldoret</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Newsreader:ital,opsz,wght@0,6..72,400..700;1,6..72,400..700&display=swap" rel="stylesheet">
<style>
:root{
  --paper:#F7F2E9; --card:#FFFDF8; --ink:#191613; --ink2:#4A4238; --warmdark:#17120E;
  --wine:#8C1D2F; --pine:#274D3D; --gold:#C99B3F; --gold2:#E3BE6E; --line:rgba(25,22,19,.15);
  --mono:"IBM Plex Mono",monospace; --ser:"Newsreader",serif;
}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--paper);color:var(--ink);font-family:var(--ser);font-size:16px;line-height:1.6;padding:40px 20px}
.wrap{max-width:680px;margin:0 auto;background:var(--card);border:1px solid var(--line);border-top:6px solid var(--wine);border-radius:8px;padding:36px;box-shadow:0 12px 40px rgba(0,0,0,.06)}
h1{font-size:1.8rem;margin-bottom:6px;color:var(--wine)}
.sub{font-family:var(--mono);font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink2);margin-bottom:24px;display:block}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:16px 0}
.full{grid-column:1 / -1}
label{display:block;font-family:var(--mono);font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:6px;color:var(--ink2)}
input{width:100%;padding:10px 12px;font-family:var(--mono);font-size:13px;border:1px solid var(--line);border-radius:4px;background:#fff;color:var(--ink)}
input:focus{outline:none;border-color:var(--wine);box-shadow:0 0 0 3px rgba(140,29,47,.1)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--wine);color:#fff;font-family:var(--mono);font-size:12px;letter-spacing:.1em;text-transform:uppercase;font-weight:600;padding:12px 24px;border:none;border-radius:4px;cursor:pointer;text-decoration:none;transition:.2s;width:100%;margin-top:12px}
.btn:hover{background:#701524}
.btn.gold{background:var(--gold);color:var(--warmdark)}
.btn.gold:hover{background:var(--gold2)}
.btn.ghost{background:transparent;color:var(--ink);border:1px solid var(--line);width:auto}
.statlist{list-style:none;margin:20px 0}
.statlist li{font-family:var(--mono);font-size:12px;padding:10px 14px;border-radius:4px;margin-bottom:8px}
.statlist li.success{background:rgba(39,77,61,.1);color:var(--pine);border:1px solid rgba(39,77,61,.25)}
.statlist li.error{background:rgba(140,29,47,.1);color:var(--wine);border:1px solid rgba(140,29,47,.25)}
.statlist li.warning{background:rgba(201,155,63,.1);color:#825a00;border:1px solid rgba(201,155,63,.3)}
.statlist li::before{font-weight:bold;margin-right:8px}
.statlist li.success::before{content:"✓"}
.statlist li.error::before{content:"✗"}
.statlist li.warning::before{content:"⚠"}
.creds{background:var(--warmdark);color:var(--paper);padding:18px;border-radius:6px;margin:20px 0;font-family:var(--mono);font-size:12px;line-height:1.9}
.creds b{color:var(--gold2)}
.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
</style>
</head>
<body>
<div class="wrap">
  <h1>Redeemed Gospel Church Eldoret</h1>
  <span class="sub">Database &amp; System Configuration Installer</span>

  <?php if (!empty($status)): ?>
    <ul class="statlist">
      <?php foreach ($status as $s): ?>
        <li class="<?= $s['type'] ?>"><?= htmlspecialchars($s['msg']) ?></li>
      <?php endforeach; ?>
    </ul>

    <?php if ($success): ?>
      <div class="creds">
        <strong>Default Administrator Credentials:</strong><br>
        URL: <b>admin/login.php</b><br>
        Username: <b><?= htmlspecialchars($adminUser ?? 'admin') ?></b><br>
        Password: <b><?= htmlspecialchars($adminPass ?? 'Admin@2026!') ?></b>
      </div>
      <div class="actions">
        <a href="index.php" class="btn" style="width:auto">View Website &rarr;</a>
        <a href="admin/login.php" class="btn gold" style="width:auto">Admin Console &rarr;</a>
      </div>
    <?php else: ?>
      <div class="actions">
        <a href="install.php" class="btn ghost">← Back &amp; Edit Settings</a>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <p style="margin-bottom:16px;color:var(--ink2);">Enter your database credentials below. On cPanel hosting, make sure you have created the MySQL database and user in your cPanel dashboard first.</p>

    <form method="post">
      <div class="grid">
        <div>
          <label>MySQL Host</label>
          <input type="text" name="db_host" value="<?= htmlspecialchars($defaultHost) ?>" required>
        </div>
        <div>
          <label>Port</label>
          <input type="number" name="db_port" value="<?= htmlspecialchars((string)$defaultPort) ?>" required>
        </div>
        <div class="full">
          <label>Database Name</label>
          <input type="text" name="db_name" value="<?= htmlspecialchars($defaultDb) ?>" placeholder="e.g. tektrend_church" required>
        </div>
        <div>
          <label>Database Username</label>
          <input type="text" name="db_user" value="<?= htmlspecialchars($defaultUser) ?>" placeholder="e.g. tektrend_user" required>
        </div>
        <div>
          <label>Database Password</label>
          <input type="password" name="db_pass" value="<?= htmlspecialchars($defaultPass) ?>" placeholder="Your DB Password">
        </div>
        <div>
          <label>Admin Username</label>
          <input type="text" name="admin_user" value="admin" required>
        </div>
        <div>
          <label>Admin Password</label>
          <input type="text" name="admin_pass" value="Admin@2026!" required>
        </div>
      </div>
      <button type="submit" class="btn">Test Connection &amp; Run Installation ✦</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
