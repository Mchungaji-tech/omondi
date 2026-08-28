<?php
/**
 * Hosting-Agnostic Deployment Script — Beacon Gospel Centre
 * Works on cPanel, Namecheap, Plesk, or any standard PHP host.
 * Upload to your web root and visit once in browser.
 */

// Auto-detect paths — no hardcoded cPanel paths
$basePath = realpath(__DIR__);
if (!$basePath) $basePath = __DIR__;

$envPath = $basePath . '/.env';
$htaccessPath = $basePath . '/.htaccess';
$schemaPath = $basePath . '/schema.sql';

// Detect public URL from request
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$rootDir = preg_replace('#/(admin|api|includes|config|assets.*)$#', '', $scriptDir);
$baseUrl = rtrim($proto . $host . $rootDir, '/') . '/';

// Read DB creds from existing .env if present, otherwise use defaults / env vars
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bgc_church';
$dbPort = 3306;

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        switch ($key) {
            case 'DB_HOST': $dbHost = $value; break;
            case 'DB_USER': $dbUser = $value; break;
            case 'DB_PASS': $dbPass = $value; break;
            case 'DB_NAME': $dbName = $value; break;
            case 'DB_PORT': $dbPort = (int)$value; break;
        }
    }
} else {
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: '';
    $dbName = getenv('DB_NAME') ?: 'bgc_church';
    $dbPort = (int)(getenv('DB_PORT') ?: 3306);
}

$status = [];
$success = false;

// Step 1: Create .env
$envContent = "APP_NAME=\"Redeemed Gospel Church Eldoret\"\n";
$envContent .= "APP_ENV=production\n";
$envContent .= "APP_DEBUG=0\n";
$envContent .= "DB_HOST=\"$dbHost\"\n";
$envContent .= "DB_USER=\"$dbUser\"\n";
$envContent .= "DB_PASS=\"$dbPass\"\n";
$envContent .= "DB_NAME=\"$dbName\"\n";
$envContent .= "DB_PORT=$dbPort\n";
$envContent .= "BASE_PATH=\"$basePath\"\n";
$envContent .= "BASE_URL=\"$baseUrl\"\n";

if (file_put_contents($envPath, $envContent) !== false) {
    $status[] = ['type' => 'success', 'msg' => ".env created at $envPath"];
} else {
    $status[] = ['type' => 'error', 'msg' => "Failed to create .env. Check folder permissions."];
}

// Step 2: Create .htaccess
$htaccessContent = "DirectoryIndex index.php index.html index.htm default.php\n\n";
$htaccessContent .= "Options -Indexes\n\n";
$htaccessContent .= "<IfModule mod_rewrite.c>\n";
$htaccessContent .= "  RewriteEngine On\n";
$htaccessContent .= "  RewriteRule ^(schema\\.sql|install\\.php|deploy\\.php|\\.env)$ - [F,L,NC]\n";
$htaccessContent .= "  RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccessContent .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccessContent .= "  RewriteCond %{REQUEST_FILENAME}.php -f\n";
$htaccessContent .= "  RewriteRule ^([^\\.]+)$ $1.php [NC,L]\n";
$htaccessContent .= "</IfModule>\n\n";
$htaccessContent .= "<IfModule mod_mime.c>\n";
$htaccessContent .= "  AddType text/css .css\n";
$htaccessContent .= "  AddType application/javascript .js\n";
$htaccessContent .= "  AddType image/svg+xml .svg\n";
$htaccessContent .= "</IfModule>\n";

if (file_put_contents($htaccessPath, $htaccessContent) !== false) {
    $status[] = ['type' => 'success', 'msg' => ".htaccess created at $htaccessPath"];
} else {
    $status[] = ['type' => 'error', 'msg' => "Failed to create .htaccess. Check folder permissions."];
}

// Step 3: Import database schema
if (file_exists($schemaPath)) {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, null, $dbPort);
    if ($conn) {
        mysqli_select_db($conn, $dbName);
        mysqli_set_charset($conn, 'utf8mb4');
        $sqlContent = file_get_contents($schemaPath);
        if (mysqli_multi_query($conn, $sqlContent)) {
            do {
                if ($result = mysqli_store_result($conn)) mysqli_free_result($result);
            } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            $status[] = ['type' => 'success', 'msg' => "Database schema imported successfully."];
        } else {
            $status[] = ['type' => 'error', 'msg' => "Schema import failed: " . mysqli_error($conn)];
        }
        mysqli_close($conn);
    } else {
        $status[] = ['type' => 'error', 'msg' => "MySQL connection failed: " . mysqli_connect_error()];
    }
} else {
    $status[] = ['type' => 'warning', 'msg' => "schema.sql not found at $schemaPath"];
}

// Step 4: Set admin password
$adminUser = 'admin';
$adminPass = 'Admin@2026!';
$adminEmail = 'office@revlangat.or.ke';
$passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);

$conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($conn) {
    $checkStmt = mysqli_prepare($conn, "SELECT id FROM admins WHERE username = ?");
    mysqli_stmt_bind_param($checkStmt, "s", $adminUser);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);
    if (mysqli_stmt_num_rows($checkStmt) > 0) {
        $updateStmt = mysqli_prepare($conn, "UPDATE admins SET password_hash = ?, totp_secret = NULL, mfa_enabled = 0 WHERE username = ?");
        mysqli_stmt_bind_param($updateStmt, "ss", $passwordHash, $adminUser);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
        $status[] = ['type' => 'success', 'msg' => "Admin password synchronized."];
    } else {
        $insertStmt = mysqli_prepare($conn, "INSERT INTO admins (username, email, password_hash, role, totp_secret, mfa_enabled) VALUES (?, ?, ?, 'admin', NULL, 0)");
        mysqli_stmt_bind_param($insertStmt, "sss", $adminUser, $adminEmail, $passwordHash);
        mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);
        $status[] = ['type' => 'success', 'msg' => "Admin account created."];
    }
    mysqli_stmt_close($checkStmt);
    mysqli_close($conn);
}

// Step 5: Self-destruct
@unlink(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deployed — Beacon Gospel Centre</title>
<style>
:root{--paper:#F7F2E9;--wine:#8C1D2F;--pine:#274D3D;--gold:#C99B3F;--line:rgba(25,22,19,.18);--ink:#191613;--ink2:#4A4238;--warmdark:#17120E;--gold2:#E3BE6E}
body{background:var(--paper);color:var(--ink);font-family:system-ui,sans-serif;padding:40px 20px}
.wrap{max-width:680px;margin:0 auto;background:#fff;border:1px solid var(--line);border-top:6px solid var(--wine);border-radius:8px;padding:36px}
h1{font-size:1.6rem;margin-bottom:8px;color:var(--wine)}
.statlist{list-style:none;margin:20px 0}
.statlist li{font-size:13px;padding:10px 14px;border-radius:4px;margin-bottom:8px}
.statlist li.success{background:rgba(39,77,61,.1);color:var(--pine);border:1px solid rgba(39,77,61,.25)}
.statlist li.error{background:rgba(140,29,47,.1);color:var(--wine);border:1px solid rgba(140,29,47,.25)}
.statlist li.warning{background:rgba(201,155,63,.1);color:#96701e;border:1px solid rgba(201,155,63,.3)}
.statlist li::before{font-weight:bold;margin-right:8px}
.statlist li.success::before{content:"✓"}
.statlist li.error::before{content:"✗"}
.statlist li.warning::before{content:"⚠"}
.creds{background:var(--warmdark);color:var(--paper);padding:20px;border-radius:6px;margin:24px 0;font-size:13px;line-height:1.9}
.creds b{color:var(--gold2)}
.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
.btn{display:inline-flex;align-items:center;justify-content:center;background:var(--wine);color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase}
.btn.gold{background:var(--gold);color:var(--warmdark)}
</style>
</head>
<body>
<div class="wrap">
  <h1>Deployment Complete</h1>
  <p style="color:var(--ink2);margin-bottom:20px;">Beacon Gospel Centre has been deployed.</p>
  <ul class="statlist">
    <?php foreach ($status as $s): ?>
      <li class="<?= $s['type'] ?>"><?= htmlspecialchars($s['msg']) ?></li>
    <?php endforeach; ?>
  </ul>
  <div class="creds">
    <strong>Default Admin Credentials:</strong><br>
    URL: <b><?= htmlspecialchars($baseUrl . 'admin/') ?></b><br>
    Username: <b>admin</b><br>
    Password: <b>Admin@2026!</b>
    <br><br>
    <small>Change this password immediately after first login.</small>
  </div>
  <div class="actions">
    <a href="<?= htmlspecialchars($baseUrl) ?>" class="btn">View Website</a>
    <a href="<?= htmlspecialchars($baseUrl . 'admin/') ?>" class="btn gold">Admin Console</a>
  </div>
</div>
</body>
</html>
