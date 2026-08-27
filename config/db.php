<?php
/**
 * Database Connection - Procedural MySQLi
 * Hardened for PHP 7.4 - PHP 8.3+ Compatibility
 */

require_once __DIR__ . '/config.php';

/**
 * Returns a procedural MySQLi connection link
 *
 * @return mysqli
 */
function get_db_connection() {
    static $conn = null;

    if ($conn === null) {
        // Disable throwing mysqli exceptions to handle connection failures cleanly across PHP 8.1+
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }

        $err = '';
        try {
            $conn = mysqli_init();
            if ($conn) {
                mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
                $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
            } else {
                $connected = false;
            }
        } catch (Throwable $e) {
            $connected = false;
            $err = $e->getMessage();
        }

        if (!$connected || empty($conn)) {
            $err = !empty($err) ? $err : (mysqli_connect_error() ?: 'Connection failed');
            $errno = mysqli_connect_errno();

            // Provide a user-friendly setup screen instead of a raw 500 error
            $installUrl = BASE_URL . 'install.php';
            $dbName = htmlspecialchars((string)DB_NAME, ENT_QUOTES, 'UTF-8');
            $dbHost = htmlspecialchars((string)DB_HOST, ENT_QUOTES, 'UTF-8');
            $dbUser = htmlspecialchars((string)DB_USER, ENT_QUOTES, 'UTF-8');
            $appName = htmlspecialchars((string)APP_NAME, ENT_QUOTES, 'UTF-8');
            $errEscaped = htmlspecialchars((string)$err, ENT_QUOTES, 'UTF-8');

            echo "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>Database Setup Required — {$appName}</title>
<style>
  :root { --paper: #F7F2E9; --wine: #8C1D2F; --ink: #191613; --gold: #C99B3F; }
  body { background: var(--paper); color: var(--ink); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px 20px; line-height: 1.6; }
  .box { max-width: 620px; margin: 40px auto; background: #fff; border: 1px solid rgba(0,0,0,0.1); border-top: 5px solid var(--wine); border-radius: 8px; padding: 32px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
  h2 { color: var(--wine); margin-top: 0; font-size: 1.4rem; }
  p { margin: 12px 0; color: #333; }
  .details { background: #fdf6f7; border: 1px solid rgba(140,29,47,0.2); padding: 12px 16px; border-radius: 4px; font-family: monospace; font-size: 13px; color: var(--wine); margin: 16px 0; word-break: break-all; }
  .btn { display: inline-block; background: var(--wine); color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px; margin-top: 12px; }
  .btn:hover { background: #701524; }
  .hint { font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 14px; }
</style>
</head>
<body>
  <div class='box'>
    <h2>Database Connection Setup Required</h2>
    <p>The application could not connect to the MySQL database <code>{$dbName}</code> on host <code>{$dbHost}</code> with user <code>{$dbUser}</code>.</p>
    <div class='details'>{$errEscaped}</div>
    <p>If you have just uploaded the website, you can run the automated installer to configure your database and create all necessary tables:</p>
    <a href='{$installUrl}' class='btn'>Run Automated Installer &rarr;</a>
    <div class='hint'>
      <strong>Tip for cPanel Hosting:</strong> Make sure you have created a MySQL Database and User in cPanel, assigned the user with ALL PRIVILEGES, and added those credentials into your <code>.env</code> file or through the installer.
    </div>
  </div>
</body>
</html>";
            exit;
        }

        // Set utf8mb4 encoding
        mysqli_set_charset($conn, 'utf8mb4');
    }

    return $conn;
}

// Global procedural DB handle
$db = get_db_connection();
