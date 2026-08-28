<?php
/**
 * Beacon Gospel Centre — Live Diagnostic & Health Status JSON API
 * Designed for morrisomondi.com (cPanel Production)
 */

header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

$envFile = __DIR__ . '/.env';
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l === '' || strpos($l, '#') === 0 || strpos($l, '=') === false) continue;
        list($k, $v) = explode('=', $l, 2);
        $envVars[trim($k)] = trim(trim($v), "\"'\t\r\n ");
    }
}

$dbHost = $envVars['DB_HOST'] ?? 'localhost';
$dbName = $envVars['DB_NAME'] ?? 'tektxbzg_omondi';
$dbUser = $envVars['DB_USER'] ?? 'tektxbzg_omondi';
$dbPass = $envVars['DB_PASS'] ?? '';
$dbPort = (int)($envVars['DB_PORT'] ?? 3306);

$response = [
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
    'script_path' => __DIR__,
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'env_exists' => file_exists($envFile),
    'htaccess_exists' => file_exists(__DIR__ . '/.htaccess'),
    'database' => [
        'connected' => false,
        'error' => null,
        'version' => null,
        'tables_count' => 0,
        'tables' => []
    ],
    'directories' => []
];

try {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($conn) {
        $response['database']['connected'] = true;
        $response['database']['version'] = mysqli_get_server_info($conn);
        $res = mysqli_query($conn, "SHOW TABLES");
        if ($res) {
            $response['database']['tables_count'] = mysqli_num_rows($res);
            while ($row = mysqli_fetch_array($res)) {
                $response['database']['tables'][] = $row[0];
            }
        }
        mysqli_close($conn);
    } else {
        $response['database']['error'] = mysqli_connect_error();
    }
} catch (Throwable $e) {
    $response['database']['error'] = $e->getMessage();
}

$dirs = [
    'uploads',
    'uploads/gallery',
    'uploads/projects',
    'uploads/profile',
    'uploads/sermons',
    'uploads/events',
    'uploads/testimonies',
    'uploads/ministries'
];
foreach ($dirs as $d) {
    $p = __DIR__ . '/' . $d;
    $response['directories'][$d] = [
        'exists' => is_dir($p),
        'writable' => is_dir($p) && is_writable($p)
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
