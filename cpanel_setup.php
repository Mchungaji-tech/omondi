<?php
/**
 * Beacon Gospel Centre — cPanel Production Diagnostics & 1-Click Repair Tool
 * Designed for: morrisomondi.com
 * Path: /home/tektxbzg/morris/ (or public_html/morris)
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

$baseDir = __DIR__;
$envFile = $baseDir . '/.env';
$htaccessFile = $baseDir . '/.htaccess';
$schemaFile = $baseDir . '/schema.sql';

// Handle JSON format request
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    $apiResponse = [
        'status' => 'online',
        'timestamp' => date('Y-m-d H:i:s'),
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
        'script_path' => __DIR__,
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'env_exists' => file_exists($envFile),
        'htaccess_exists' => file_exists($htaccessFile),
    ];
    echo json_encode($apiResponse, JSON_PRETTY_PRINT);
    exit;
}

// Read current .env
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $envVars[trim($k)] = trim(trim($v), "\"'\t\r\n ");
    }
}

$dbHost = $envVars['DB_HOST'] ?? 'localhost';
$dbUser = $envVars['DB_USER'] ?? 'tektxbzg_omondi';
$dbPass = $envVars['DB_PASS'] ?? '';
$dbName = $envVars['DB_NAME'] ?? 'tektxbzg_omondi';
$dbPort = (int)($envVars['DB_PORT'] ?? 3306);
$appName = $envVars['APP_NAME'] ?? 'Redeemed Gospel Church Eldoret';

$notices = [];

// =========================================================================
// ACTION HANDLERS
// =========================================================================
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// 1. SAVE .ENV CONFIGURATION
if ($action === 'save_env' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nHost = trim($_POST['db_host'] ?? 'localhost');
    $nUser = trim($_POST['db_user'] ?? '');
    $nPass = (string)($_POST['db_pass'] ?? '');
    $nName = trim($_POST['db_name'] ?? '');
    $nPort = (int)($_POST['db_port'] ?? 3306);

    $envContent = "APP_NAME=\"Redeemed Gospel Church Eldoret\"\n";
    $envContent .= "APP_ENV=production\n";
    $envContent .= "APP_DEBUG=0\n";
    $envContent .= "DB_HOST=\"{$nHost}\"\n";
    $envContent .= "DB_USER=\"{$nUser}\"\n";
    $envContent .= "DB_PASS=\"{$nPass}\"\n";
    $envContent .= "DB_NAME=\"{$nName}\"\n";
    $envContent .= "DB_PORT={$nPort}\n";

    if (@file_put_contents($envFile, $envContent) !== false) {
        $notices[] = ['type' => 'success', 'msg' => '✅ .env file saved successfully!'];
        $dbHost = $nHost; $dbUser = $nUser; $dbPass = $nPass; $dbName = $nName; $dbPort = $nPort;
    } else {
        $notices[] = ['type' => 'error', 'msg' => '❌ Failed to write .env file. Please check folder permissions.'];
    }
}

// 2. REPAIR / IMPORT DATABASE SCHEMA
if ($action === 'import_schema') {
    if (!file_exists($schemaFile)) {
        $notices[] = ['type' => 'error', 'msg' => '❌ schema.sql file not found in root folder.'];
    } else {
        $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, null, $dbPort);
        if (!$conn) {
            $notices[] = ['type' => 'error', 'msg' => '❌ MySQL Connection Failed: ' . mysqli_connect_error()];
        } else {
            @mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            if (!@mysqli_select_db($conn, $dbName)) {
                $notices[] = ['type' => 'error', 'msg' => "❌ Could not select database `{$dbName}`: " . mysqli_error($conn) . ". Please make sure the database is created in cPanel MySQL Databases."];
            } else {
                mysqli_set_charset($conn, 'utf8mb4');
                $sqlContent = file_get_contents($schemaFile);
                if (mysqli_multi_query($conn, $sqlContent)) {
                    do {
                        if ($result = mysqli_store_result($conn)) {
                            mysqli_free_result($result);
                        }
                    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
                    $notices[] = ['type' => 'success', 'msg' => '✅ Database schema and default seed data imported successfully!'];
                } else {
                    $notices[] = ['type' => 'error', 'msg' => '❌ Schema execution error: ' . mysqli_error($conn)];
                }
            }
            mysqli_close($conn);
        }
    }
}

// 3. SYNC / RESET ADMIN ACCOUNT
if ($action === 'sync_admin') {
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = (string)($_POST['admin_pass'] ?? 'Admin@2026!');
    $adminEmail = 'office@revlangat.or.ke';
    $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);

    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if (!$conn) {
        $notices[] = ['type' => 'error', 'msg' => '❌ Cannot connect to database to sync admin: ' . mysqli_connect_error()];
    } else {
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM admins WHERE username = ?");
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "s", $adminUser);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $updateStmt = mysqli_prepare($conn, "UPDATE admins SET password_hash = ?, totp_secret = NULL, mfa_enabled = 0, failed_attempts = 0, locked_until = NULL WHERE username = ?");
                mysqli_stmt_bind_param($updateStmt, "ss", $passwordHash, $adminUser);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
                $notices[] = ['type' => 'success', 'msg' => "✅ Admin user `{$adminUser}` credentials reset successfully (Password: {$adminPass})."];
            } else {
                $insertStmt = mysqli_prepare($conn, "INSERT INTO admins (username, email, password_hash, role, totp_secret, mfa_enabled) VALUES (?, ?, ?, 'admin', NULL, 0)");
                mysqli_stmt_bind_param($insertStmt, "sss", $adminUser, $adminEmail, $passwordHash);
                mysqli_stmt_execute($insertStmt);
                mysqli_stmt_close($insertStmt);
                $notices[] = ['type' => 'success', 'msg' => "✅ New admin account `{$adminUser}` created successfully (Password: {$adminPass})."];
            }
            mysqli_stmt_close($checkStmt);
        }
        mysqli_close($conn);
    }
}

// 4. AUTO-CREATE AND FIX DIRECTORIES
if ($action === 'fix_dirs') {
    $dirsToCreate = [
        'uploads',
        'uploads/gallery',
        'uploads/projects',
        'uploads/profile',
        'uploads/sermons',
        'uploads/events',
        'uploads/testimonies',
        'uploads/ministries'
    ];
    $createdCount = 0;
    foreach ($dirsToCreate as $d) {
        $target = $baseDir . '/' . $d;
        if (!is_dir($target)) {
            @mkdir($target, 0755, true);
            $createdCount++;
        }
        @chmod($target, 0755);
    }
    $notices[] = ['type' => 'success', 'msg' => "✅ Storage folders verified and permissions set to 0755 ({$createdCount} new folders created)."];
}

// 5. RECREATE .HTACCESS
if ($action === 'fix_htaccess') {
    $htaccessCode = "DirectoryIndex index.php index.html index.htm default.php\n\n";
    $htaccessCode .= "Options -Indexes\n";
    $htaccessCode .= "<IfModule mod_autoindex.c>\n";
    $htaccessCode .= "    Options -Indexes\n";
    $htaccessCode .= "</IfModule>\n\n";
    $htaccessCode .= "<IfModule mod_rewrite.c>\n";
    $htaccessCode .= "    RewriteEngine On\n";
    $htaccessCode .= "    RewriteRule ^(\\.env|\\.git|\\.cpanel\\.ya?ml|schema\\.sql)$ - [F,L,NC]\n";
    $htaccessCode .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
    $htaccessCode .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
    $htaccessCode .= "    RewriteCond %{REQUEST_FILENAME}.php -f\n";
    $htaccessCode .= "    RewriteRule ^([^\\.]+)$ $1.php [NC,L]\n";
    $htaccessCode .= "</IfModule>\n\n";
    $htaccessCode .= "<IfModule mod_mime.c>\n";
    $htaccessCode .= "    AddType text/css .css\n";
    $htaccessCode .= "    AddType application/javascript .js\n";
    $htaccessCode .= "    AddType image/svg+xml .svg\n";
    $htaccessCode .= "</IfModule>\n";

    if (@file_put_contents($htaccessFile, $htaccessCode) !== false) {
        $notices[] = ['type' => 'success', 'msg' => '✅ .htaccess file recreated with DirectoryIndex index.php and Options -Indexes.'];
    } else {
        $notices[] = ['type' => 'error', 'msg' => '❌ Failed to write .htaccess. Check folder permissions.'];
    }
}

// =========================================================================
// RUN DIAGNOSTICS
// =========================================================================

// 1. PHP & Server
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$exts = ['mysqli', 'mbstring', 'openssl', 'json', 'fileinfo', 'gd', 'session'];
$missingExts = [];
foreach ($exts as $e) {
    if (!extension_loaded($e)) $missingExts[] = $e;
}

// 2. Database Connection Test
$dbConnected = false;
$dbError = null;
$dbVersion = 'Unknown';
$existingTables = [];
$tableCounts = [];

$expectedTables = [
    'admins', 'admin_mfa_setup_tokens', 'admin_registration_tokens', 'users',
    'site_settings', 'sermons', 'sermon_views', 'sermon_comments', 'live_schedule',
    'live_chat_messages', 'ministries', 'pastor_schedule', 'gallery', 'testimonies',
    'events', 'fund_goals', 'projects', 'project_images', 'pledges', 'prayers',
    'invites', 'audit_logs'
];

try {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($conn) {
        $dbConnected = true;
        $dbVersion = mysqli_get_server_info($conn);
        $res = mysqli_query($conn, "SHOW TABLES");
        if ($res) {
            while ($row = mysqli_fetch_array($res)) {
                $tName = $row[0];
                $existingTables[] = $tName;
                $cRes = @mysqli_query($conn, "SELECT COUNT(*) FROM `{$tName}`");
                $cnt = $cRes ? mysqli_fetch_row($cRes)[0] : 0;
                $tableCounts[$tName] = (int)$cnt;
            }
        }
        mysqli_close($conn);
    } else {
        $dbError = mysqli_connect_error();
    }
} catch (Throwable $t) {
    $dbError = $t->getMessage();
}

// 3. Storage Folders Status
$dirList = [
    'uploads',
    'uploads/gallery',
    'uploads/projects',
    'uploads/profile',
    'uploads/sermons',
    'uploads/events',
    'uploads/testimonies',
    'uploads/ministries'
];
$dirStats = [];
$allDirsOk = true;
foreach ($dirList as $d) {
    $fullP = $baseDir . '/' . $d;
    $exists = is_dir($fullP);
    $writable = $exists && is_writable($fullP);
    if (!$exists || !$writable) $allDirsOk = false;
    $dirStats[$d] = ['exists' => $exists, 'writable' => $writable];
}

// 4. .htaccess check
$htaccessOk = file_exists($htaccessFile);
$hasDirectoryIndex = false;
$hasOptionsIndexes = false;
if ($htaccessOk) {
    $htContent = file_get_contents($htaccessFile);
    if (stripos($htContent, 'DirectoryIndex index.php') !== false) $hasDirectoryIndex = true;
    if (stripos($htContent, 'Options -Indexes') !== false) $hasOptionsIndexes = true;
}

// 5. Admin account check
$adminFound = false;
if ($dbConnected && in_array('admins', $existingTables)) {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($conn) {
        $aRes = @mysqli_query($conn, "SELECT username, email, mfa_enabled FROM admins LIMIT 1");
        if ($aRes && mysqli_num_rows($aRes) > 0) {
            $adminFound = mysqli_fetch_assoc($aRes);
        }
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>cPanel System Diagnostics & Setup — morrisomondi.com</title>
<style>
:root {
  --wine: #8C1D2F;
  --wine-dark: #681422;
  --gold: #C99B3F;
  --ink: #0F172A;
  --paper: #F8FAFC;
  --card-bg: #FFFFFF;
  --border: #E2E8F0;
  --muted: #64748B;
  --success: #16A34A;
  --danger: #DC2626;
  --warning: #D97706;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: var(--paper);
  color: var(--ink);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  line-height: 1.5;
  padding: 30px 15px;
}
.container { max-width: 960px; margin: 0 auto; }
.header {
  background: linear-gradient(135deg, var(--wine), var(--wine-dark));
  color: #fff;
  padding: 28px 32px;
  border-radius: 12px;
  margin-bottom: 24px;
  box-shadow: 0 4px 14px rgba(140,29,47,0.25);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}
.header h1 { font-size: 24px; font-weight: 700; }
.header p { font-size: 14px; opacity: 0.9; margin-top: 4px; }
.header .badge-domain {
  background: rgba(255,255,255,0.18);
  border: 1px solid rgba(255,255,255,0.3);
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-family: monospace;
}

.notice {
  padding: 14px 18px;
  border-radius: 8px;
  margin-bottom: 18px;
  font-size: 14px;
  font-weight: 500;
}
.notice.success { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
.notice.error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(440px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}
@media (max-width: 600px) {
  .grid { grid-template-columns: 1fr; }
}

.card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 22px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.card h2 {
  font-size: 17px;
  margin-bottom: 16px;
  color: var(--ink);
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border);
  padding-bottom: 10px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  text-transform: uppercase;
}
.status-badge.ok { background: #DCFCE7; color: var(--success); }
.status-badge.fail { background: #FEE2E2; color: var(--danger); }
.status-badge.warn { background: #FEF3C7; color: var(--warning); }

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px dashed var(--border);
  font-size: 13.5px;
  align-items: center;
}
.info-row:last-child { border-bottom: none; }
.info-label { color: var(--muted); font-weight: 500; }
.info-val { font-weight: 600; font-family: monospace; }

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: var(--wine);
  color: #fff;
  padding: 10px 18px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: background 0.15s ease;
}
.btn:hover { background: var(--wine-dark); }
.btn.secondary { background: #334155; }
.btn.secondary:hover { background: #1E293B; }
.btn.gold { background: var(--gold); color: #000; }
.btn.gold:hover { background: #B3862E; }
.btn.sm { padding: 6px 12px; font-size: 12px; }

form.form-compact { margin-top: 15px; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 4px; }
.form-group input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: 13.5px;
  font-family: monospace;
}
.form-group input:focus { outline: none; border-color: var(--wine); }

.table-pill-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 12px;
}
.table-pill {
  font-size: 11.5px;
  font-family: monospace;
  padding: 3px 8px;
  border-radius: 4px;
  background: #F1F5F9;
  border: 1px solid var(--border);
}
.table-pill.exists { background: #ECFDF5; border-color: #A7F3D0; color: #065F46; }
.table-pill.missing { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }

.quick-bar {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
}
</style>
</head>
<body>

<div class="container">

  <!-- Header -->
  <div class="header">
    <div>
      <h1>🛠️ Live Diagnostic & Setup Console</h1>
      <p>Beacon Gospel Centre / Redeemed Gospel Church Eldoret</p>
    </div>
    <div class="badge-domain">
      🌐 <?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'morrisomondi.com') ?>
    </div>
  </div>

  <!-- Notices -->
  <?php foreach ($notices as $n): ?>
    <div class="notice <?= $n['type'] ?>"><?= htmlspecialchars($n['msg']) ?></div>
  <?php endforeach; ?>

  <div class="grid">

    <!-- 1. SERVER & PATH DIAGNOSTICS -->
    <div class="card">
      <h2>
        <span>🖥️ 1. Server & Paths</span>
        <?php if ($phpOk && empty($missingExts)): ?>
          <span class="status-badge ok">Healthy</span>
        <?php else: ?>
          <span class="status-badge fail">Check PHP</span>
        <?php endif; ?>
      </h2>
      <div class="info-row">
        <span class="info-label">Domain Name</span>
        <span class="info-val"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown') ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Active Script Path</span>
        <span class="info-val"><?= htmlspecialchars(__DIR__) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">cPanel Document Root</span>
        <span class="info-val"><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">PHP Version</span>
        <span class="info-val"><?= htmlspecialchars(PHP_VERSION) ?> (<?= $phpOk ? '✅ PHP 7.4+' : '❌ Upgrade to 8.1+' ?>)</span>
      </div>
      <div class="info-row">
        <span class="info-label">Web Server</span>
        <span class="info-val"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/LiteSpeed') ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">HTTPS Protocol</span>
        <span class="info-val"><?= (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '✅ Enabled (Secure)' : '⚠️ HTTP (No SSL)' ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Required Extensions</span>
        <span class="info-val"><?= empty($missingExts) ? '✅ All 7 Loaded' : '❌ Missing: ' . implode(', ', $missingExts) ?></span>
      </div>
    </div>

    <!-- 2. .HTACCESS & ROUTING -->
    <div class="card">
      <h2>
        <span>📜 2. .htaccess & Routing</span>
        <?php if ($htaccessOk && $hasDirectoryIndex && $hasOptionsIndexes): ?>
          <span class="status-badge ok">Configured</span>
        <?php else: ?>
          <span class="status-badge warn">Needs Fix</span>
        <?php endif; ?>
      </h2>
      <div class="info-row">
        <span class="info-label">.htaccess File</span>
        <span class="info-val"><?= $htaccessOk ? '✅ Present in Root' : '❌ Missing' ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">DirectoryIndex Rule</span>
        <span class="info-val"><?= $hasDirectoryIndex ? '✅ index.php First' : '⚠️ Missing Rule' ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Directory Browsing</span>
        <span class="info-val"><?= $hasOptionsIndexes ? '✅ Options -Indexes' : '⚠️ Allowed' ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Clean URL Rewriting</span>
        <span class="info-val">✅ /about &rarr; about.php</span>
      </div>
      <div style="margin-top: 15px;">
        <a href="?action=fix_htaccess" class="btn sm secondary">🔄 Recreate Optimal .htaccess</a>
      </div>
    </div>

    <!-- 3. DATABASE CONNECTION -->
    <div class="card">
      <h2>
        <span>🗄️ 3. Database Connection</span>
        <?php if ($dbConnected): ?>
          <span class="status-badge ok">Connected</span>
        <?php else: ?>
          <span class="status-badge fail">Disconnected</span>
        <?php endif; ?>
      </h2>

      <?php if ($dbConnected): ?>
        <div class="info-row">
          <span class="info-label">MySQL Server Version</span>
          <span class="info-val"><?= htmlspecialchars($dbVersion) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Connected Database</span>
          <span class="info-val"><?= htmlspecialchars($dbName) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">DB Host / User</span>
          <span class="info-val"><?= htmlspecialchars($dbHost) ?> / <?= htmlspecialchars($dbUser) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Tables Count</span>
          <span class="info-val"><?= count($existingTables) ?> / <?= count($expectedTables) ?> Tables</span>
        </div>
      <?php else: ?>
        <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 12px; font-family: monospace;">
          <strong>Error:</strong> <?= htmlspecialchars($dbError ?: 'Could not connect') ?>
        </div>
        <p style="font-size: 12.5px; color: var(--muted); margin-bottom: 10px;">
          👉 Ensure the database name and user exist in cPanel &rarr; <strong>MySQL Databases</strong> with <strong>ALL PRIVILEGES</strong>.
        </p>
      <?php endif; ?>

      <!-- Quick .env update form -->
      <form method="POST" action="?action=save_env" class="form-compact">
        <input type="hidden" name="action" value="save_env">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
          <div class="form-group">
            <label>DB Host</label>
            <input type="text" name="db_host" value="<?= htmlspecialchars($dbHost) ?>">
          </div>
          <div class="form-group">
            <label>DB Port</label>
            <input type="number" name="db_port" value="<?= htmlspecialchars((string)$dbPort) ?>">
          </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
          <div class="form-group">
            <label>DB User (e.g. tektxbzg_omondi)</label>
            <input type="text" name="db_user" value="<?= htmlspecialchars($dbUser) ?>">
          </div>
          <div class="form-group">
            <label>DB Name (e.g. tektxbzg_omondi)</label>
            <input type="text" name="db_name" value="<?= htmlspecialchars($dbName) ?>">
          </div>
        </div>
        <div class="form-group">
          <label>DB Password</label>
          <input type="password" name="db_pass" value="<?= htmlspecialchars($dbPass) ?>" placeholder="Enter MySQL password">
        </div>
        <button type="submit" class="btn sm">💾 Save .env Credentials</button>
      </form>
    </div>

    <!-- 4. DATABASE TABLES & SCHEMA -->
    <div class="card">
      <h2>
        <span>📊 4. Database Schema Tables</span>
        <?php if ($dbConnected && count($existingTables) >= count($expectedTables)): ?>
          <span class="status-badge ok"><?= count($existingTables) ?> Tables Ready</span>
        <?php else: ?>
          <span class="status-badge warn"><?= count($existingTables) ?> / <?= count($expectedTables) ?> Tables</span>
        <?php endif; ?>
      </h2>

      <p style="font-size: 13px; color: var(--muted); margin-bottom: 8px;">
        Expected schema tables:
      </p>

      <div class="table-pill-grid">
        <?php foreach ($expectedTables as $t): ?>
          <?php $exists = in_array($t, $existingTables); ?>
          <span class="table-pill <?= $exists ? 'exists' : 'missing' ?>">
            <?= $exists ? '✓' : '✗' ?> <?= htmlspecialchars($t) ?><?= $exists ? ' (' . ($tableCounts[$t] ?? 0) . ')' : '' ?>
          </span>
        <?php endforeach; ?>
      </div>

      <div style="margin-top: 18px; display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="?action=import_schema" class="btn sm gold">📥 1-Click Import / Repair Schema.sql</a>
      </div>
    </div>

    <!-- 5. UPLOAD DIRECTORIES & STORAGE -->
    <div class="card">
      <h2>
        <span>📁 5. Upload Directories</span>
        <?php if ($allDirsOk): ?>
          <span class="status-badge ok">Writable</span>
        <?php else: ?>
          <span class="status-badge warn">Permissions Needed</span>
        <?php endif; ?>
      </h2>

      <?php foreach ($dirStats as $d => $st): ?>
        <div class="info-row">
          <span class="info-label"><?= htmlspecialchars($d) ?>/</span>
          <span class="info-val">
            <?php if ($st['exists'] && $st['writable']): ?>
              <span style="color: var(--success);">✅ Writable (0755)</span>
            <?php elseif ($st['exists']): ?>
              <span style="color: var(--warning);">⚠️ Not Writable (chmod 755)</span>
            <?php else: ?>
              <span style="color: var(--danger);">❌ Missing Folder</span>
            <?php endif; ?>
          </span>
        </div>
      <?php endforeach; ?>

      <div style="margin-top: 15px;">
        <a href="?action=fix_dirs" class="btn sm secondary">📁 Auto-Create & Fix Folder Permissions</a>
      </div>
    </div>

    <!-- 6. ADMIN ACCOUNT SETUP -->
    <div class="card">
      <h2>
        <span>🔑 6. Administrator Account</span>
        <?php if ($adminFound): ?>
          <span class="status-badge ok">Active</span>
        <?php else: ?>
          <span class="status-badge warn">Not Configured</span>
        <?php endif; ?>
      </h2>

      <?php if ($adminFound): ?>
        <div class="info-row">
          <span class="info-label">Admin Username</span>
          <span class="info-val"><?= htmlspecialchars($adminFound['username']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Admin Email</span>
          <span class="info-val"><?= htmlspecialchars($adminFound['email']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">MFA Status</span>
          <span class="info-val"><?= $adminFound['mfa_enabled'] ? '🔐 MFA Enabled' : '🔓 Password Only' ?></span>
        </div>
      <?php else: ?>
        <p style="font-size: 13px; color: var(--muted); margin-bottom: 12px;">
          No administrator user found in the database. Use the quick tool below to synchronize credentials.
        </p>
      <?php endif; ?>

      <form method="POST" action="?action=sync_admin" class="form-compact">
        <input type="hidden" name="action" value="sync_admin">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
          <div class="form-group">
            <label>Admin Username</label>
            <input type="text" name="admin_user" value="admin">
          </div>
          <div class="form-group">
            <label>Admin Password</label>
            <input type="text" name="admin_pass" value="Admin@2026!">
          </div>
        </div>
        <button type="submit" class="btn sm secondary">🔑 Sync / Reset Admin Account</button>
      </form>
    </div>

  </div>

  <!-- Quick Launch Navigation Bar -->
  <div class="quick-bar">
    <div>
      <strong style="font-size: 15px;">🚀 Ready to Launch?</strong>
      <p style="font-size: 13px; color: var(--muted);">Once all badges are green, your site is fully operational.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="index.php" class="btn">🌐 View Website (index.php)</a>
      <a href="admin/login.php" class="btn secondary">🛡️ Admin Sign In</a>
      <a href="install.php" class="btn gold">⚙️ Full Installer</a>
    </div>
  </div>

</div>

</body>
</html>
