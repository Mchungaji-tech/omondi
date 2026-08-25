<?php
/**
 * Automated Installer & Database Setup Script
 * Beacon Gospel Centre — Procedural PHP + MySQL
 */

// Basic error display for installation
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$user = 'root';
$pass = '';
$db_name = 'bgc_church';
$port = 3306;

$status = [];
$success = false;

// Function to run installation
function run_installer($host, $user, $pass, $db_name, $port, &$status) {
    // Step 1: Connect to MySQL Server (without selecting DB first)
    $conn = @mysqli_connect($host, $user, $pass, null, $port);
    if (!$conn) {
        $status[] = ['type' => 'error', 'msg' => 'Could not connect to MySQL server: ' . mysqli_connect_error()];
        return false;
    }
    $status[] = ['type' => 'success', 'msg' => "Connected to MySQL server ($host)."];

    // Step 2: Create Database if not exists
    $sqlCreate = "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!mysqli_query($conn, $sqlCreate)) {
        $status[] = ['type' => 'error', 'msg' => 'Failed to create database: ' . mysqli_error($conn)];
        mysqli_close($conn);
        return false;
    }
    $status[] = ['type' => 'success', 'msg' => "Database `$db_name` verified/created successfully."];

    // Select database
    mysqli_select_db($conn, $db_name);
    mysqli_set_charset($conn, 'utf8mb4');

    // Step 3: Read and execute schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        $status[] = ['type' => 'error', 'msg' => 'schema.sql file not found in root directory.'];
        mysqli_close($conn);
        return false;
    }

    $sqlContent = file_get_contents($schemaFile);
    
    // Execute multi query
    if (mysqli_multi_query($conn, $sqlContent)) {
        do {
            // Store / free results to proceed to next statement
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
    $adminUser = 'admin';
    $adminPass = 'Admin@2026!';
    $adminEmail = 'office@revlangat.or.ke';
    $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);
    $totpSecret = null;

    $checkStmt = mysqli_prepare($conn, "SELECT id FROM admins WHERE username = ?");
    mysqli_stmt_bind_param($checkStmt, "s", $adminUser);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);

    if (mysqli_stmt_num_rows($checkStmt) > 0) {
        $updateStmt = mysqli_prepare($conn, "UPDATE admins SET password_hash = ?, totp_secret = ?, mfa_enabled = 0 WHERE username = ?");
        mysqli_stmt_bind_param($updateStmt, "sss", $passwordHash, $totpSecret, $adminUser);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
        $status[] = ['type' => 'success', 'msg' => "Default admin user password synchronized (`admin` / `Admin@2026!`)."];
    } else {
        $insertStmt = mysqli_prepare($conn, "INSERT INTO admins (username, email, password_hash, role, totp_secret, mfa_enabled) VALUES (?, ?, ?, 'admin', ?, 1)");
        mysqli_stmt_bind_param($insertStmt, "ssss", $adminUser, $adminEmail, $passwordHash, $totpSecret);
        mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);
        $status[] = ['type' => 'success', 'msg' => "Default admin account created (`admin` / `Admin@2026!`)."];
    }
    mysqli_stmt_close($checkStmt);

    mysqli_close($conn);

    // Step 5: Create .env file
    $envPath = __DIR__ . '/.env';
    $envContent = "APP_NAME=\"Redeemed Gospel Church Eldoret\"\n";
    $envContent .= "APP_ENV=production\n";
    $envContent .= "APP_DEBUG=0\n";
    $envContent .= "DB_HOST=\"localhost\"\n";
    $envContent .= "DB_USER=\"root\"\n";
    $envContent .= "DB_PASS=\"\"\n";
    $envContent .= "DB_NAME=\"$db_name\"\n";
    $envContent .= "DB_PORT=3306\n";
    if (file_put_contents($envPath, $envContent) !== false) {
        $status[] = ['type' => 'success', 'msg' => ".env configuration file created successfully."];
    } else {
        $status[] = ['type' => 'warning', 'msg' => "Could not create .env file. Please create it manually with your database credentials."];
    }

    // Step 6: Create root .htaccess with minimal security rules
    $htaccessPath = __DIR__ . '/.htaccess';
    $htaccessContent = "<IfModule mod_rewrite.c>\n";
    $htaccessContent .= "  RewriteEngine On\n";
    $htaccessContent .= "  RewriteRule ^(schema\.sql|install\.php|\.env)$ - [F,L,NC]\n";
    $htaccessContent .= "</IfModule>\n";
    $htaccessContent .= "<IfModule mod_autoindex.c>\n";
    $htaccessContent .= "  Options -Indexes\n";
    $htaccessContent .= "</IfModule>\n";
    if (file_put_contents($htaccessPath, $htaccessContent) !== false) {
        $status[] = ['type' => 'success', 'msg' => "Root .htaccess created with minimal security rules."];
    } else {
        $status[] = ['type' => 'warning', 'msg' => "Could not create .htaccess. Please create it manually."];
    }

    return true;
}

// Auto-run if requested via POST, GET ?run=1 or CLI
$isCli = (php_sapi_name() === 'cli');
if ($isCli || isset($_GET['run']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = run_installer($host, $user, $pass, $db_name, $port, $status);
    if ($isCli) {
        echo "=== Redeemed Gospel Church Eldoret Installer ===\n";
        foreach ($status as $s) {
            echo "[" . strtoupper($s['type']) . "] " . $s['msg'] . "\n";
        }
        if ($success) {
            echo "\n✓ Installation completed successfully!\n";
            echo "Admin Login: admin\nPassword: Admin@2026!\nSet up MFA in Admin Security after signing in.\n";
        } else {
            echo "\n✗ Installation encountered errors.\n";
        }
        exit($success ? 0 : 1);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Installer — Redeemed Gospel Church Eldoret (Procedural PHP + MySQL)</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Newsreader:ital,opsz,wght@0,6..72,300..700;1,6..72,300..700&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
:root{
  --paper:#F7F2E9; --paper2:#EFE6D6; --card:#FFFDF8;
  --ink:#191613; --ink2:#4A4238; --warmdark:#17120E;
  --wine:#8C1D2F; --pine:#274D3D; --gold:#C99B3F; --gold2:#E3BE6E;
  --line:rgba(25,22,19,.18);
  --disp:"Anton",sans-serif; --ser:"Newsreader",serif; --mono:"IBM Plex Mono",monospace;
}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--paper);color:var(--ink);font-family:var(--ser);font-size:16px;line-height:1.6;padding:40px 20px}
.wrap{max-width:680px;margin:0 auto;background:var(--card);border:1px solid var(--line);border-top:6px solid var(--wine);border-radius:8px;padding:36px;box-shadow:0 20px 50px rgba(23,18,14,.1)}
h1{font-family:var(--disp);font-size:2.2rem;text-transform:uppercase;margin-bottom:8px;color:var(--wine);letter-spacing:.02em}
.sub{font-family:var(--mono);font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--ink2);margin-bottom:24px;display:block}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--wine);color:var(--paper);font-family:var(--mono);font-size:11.5px;letter-spacing:.14em;text-transform:uppercase;font-weight:600;padding:12px 24px;border:1px solid var(--wine);border-radius:4px;cursor:pointer;text-decoration:none;transition:.2s}
.btn:hover{background:var(--ink);border-color:var(--ink);color:var(--paper)}
.btn.gold{background:var(--gold);border-color:var(--gold);color:var(--warmdark)}
.btn.gold:hover{background:var(--gold2);border-color:var(--gold2)}
.btn.ghost{background:transparent;color:var(--ink);border-color:var(--ink)}
.btn.ghost:hover{background:var(--ink);color:var(--paper)}
.statlist{list-style:none;margin:20px 0}
.statlist li{font-family:var(--mono);font-size:12px;padding:10px 14px;border-radius:4px;margin-bottom:8px;display:flex;align-items:center;gap:10px}
.statlist li.success{background:rgba(39,77,61,.1);color:var(--pine);border:1px solid rgba(39,77,61,.25)}
.statlist li.error{background:rgba(140,29,47,.1);color:var(--wine);border:1px solid rgba(140,29,47,.25)}
.statlist li::before{font-weight:bold}
.statlist li.success::before{content:"✓ "}
.statlist li.error::before{content:"✗ "}
.creds{background:var(--warmdark);color:var(--paper);padding:20px;border-radius:6px;margin:24px 0;font-family:var(--mono);font-size:12px;line-height:1.9}
.creds b{color:var(--gold2)}
.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
</style>
</head>
<body>
<div class="wrap">
  <h1>Redeemed Gospel Church Eldoret</h1>
  <span class="sub">Database & System Installer (Procedural PHP + MariaDB/MySQL)</span>

  <?php if (empty($status)): ?>
    <p>Welcome to the automated setup for <strong>Redeemed Gospel Church Eldoret</strong>. Clicking the button below will:</p>
    <ul style="margin:16px 0 24px 20px;font-size:1rem;color:var(--ink2)">
      <li>Create the MySQL database <code><?= htmlspecialchars($db_name) ?></code></li>
      <li>Create all schema tables (Admins, Sermons, Ministries, Schedule, Projects, Prayers, etc.)</li>
      <li>Populate pre-configured seed data (sermons, live broadcasts, gallery, pledges)</li>
      <li>Create the default administrative credentials (<code>admin</code> / <code>Admin@2026!</code>)</li>
      <li>Create <code>.env</code> configuration file with database credentials</li>
      <li>Create root <code>.htaccess</code> with minimal security rules</li>
    </ul>
    <form method="post">
      <button type="submit" class="btn">Initialize Database &amp; Seed Data ✦</button>
    </form>
  <?php else: ?>
    <ul class="statlist">
      <?php foreach ($status as $s): ?>
        <li class="<?= $s['type'] ?>"><?= htmlspecialchars($s['msg']) ?></li>
      <?php endforeach; ?>
    </ul>

    <?php if ($success): ?>
      <div class="creds">
        <strong>Default Administrator Credentials:</strong><br>
        Username: <b>admin</b><br>
        Password: <b>Admin@2026!</b><br>
        MFA TOTP: Set up an authenticator app in the Admin Security Centre after installation.
      </div>
      <div class="actions">
        <a href="index.php" class="btn">View Public Website &rarr;</a>
        <a href="admin/login.php" class="btn gold">Access Admin Console &rarr;</a>
        <a href="proposal.php" class="btn ghost">View Partner Proposal &rarr;</a>
      </div>
    <?php else: ?>
      <p style="color:var(--wine);font-weight:600;margin-top:16px;">Installation could not be completed. Please review the errors above and ensure MySQL is running in XAMPP.</p>
      <div class="actions">
        <a href="install.php" class="btn">Retry Installation</a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
