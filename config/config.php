<?php
/**
 * Configuration File - Beacon Gospel Centre
 * Pure Procedural PHP
 */

// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!empty($key)) {
            $_ENV[$key] = $value;
        }
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Database Configuration Constants
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'bgc_church');
define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);

// Application Details
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Redeemed Gospel Church Eldoret');
define('APP_TAGLINE', $_ENV['APP_TAGLINE'] ?? 'Bishop Morris Omondi — Eldoret, Kenya');
define('APP_YEAR', date('Y'));
define('KES_PER_USD', $_ENV['KES_PER_USD'] ?? 130.0);

// Determine Base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$rootDir = preg_replace('#/(admin|api|includes|config|assets.*)$#', '', $scriptDir);
$baseUrl = rtrim($protocol . $host . $rootDir, '/') . '/';
define('BASE_URL', $baseUrl);

// Error reporting based on environment/debug flag
$debugMode = (!file_exists($envFile) || ($_ENV['APP_DEBUG'] ?? '0') === '1');
error_reporting(E_ALL);
ini_set('display_errors', $debugMode ? 1 : 0);
ini_set('log_errors', 1);

// Set default timezone for Eldoret, Kenya (East Africa Time - UTC+3)
date_default_timezone_set('Africa/Nairobi');
