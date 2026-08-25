<?php
/**
 * Database Connection - Procedural mysqli
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
        $conn = mysqli_init();
        
        // Set connection timeout
        mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

        // Attempt connection with error suppression to handle custom message
        $connected = @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if (!$connected) {
            // Check if database needs to be created / initialized
            $err = mysqli_connect_error();
            $errno = mysqli_connect_errno();

            // If database does not exist (error 1049), prompt to run installer
            if ($errno === 1049) {
                die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:2px solid #8C1D2F;border-radius:8px;background:#FFFDF8;color:#191613;'>
                    <h2 style='color:#8C1D2F;margin-top:0;'>Database Not Initialized</h2>
                    <p>The database <code>" . DB_NAME . "</code> has not been created yet.</p>
                    <p><a href='" . BASE_URL . "install.php' style='display:inline-block;background:#8C1D2F;color:#fff;padding:10px 18px;text-decoration:none;border-radius:4px;font-weight:bold;'>Run Automated Installer &rarr;</a></p>
                </div>");
            }

            die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:2px solid #8C1D2F;border-radius:8px;background:#FFFDF8;color:#191613;'>
                <h2 style='color:#8C1D2F;margin-top:0;'>Database Connection Error</h2>
                <p>Unable to connect to MySQL database server at <code>" . DB_HOST . "</code>.</p>
                <p><strong>Details:</strong> " . htmlspecialchars($err) . "</p>
                <p>Please make sure MySQL is running in your XAMPP Control Panel.</p>
            </div>");
        }

        // Set utf8mb4 encoding
        mysqli_set_charset($conn, 'utf8mb4');
    }

    return $conn;
}

// Global procedural DB handle
$db = get_db_connection();
