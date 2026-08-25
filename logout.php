<?php
/**
 * Public Member Logout
 * Pure Procedural PHP
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

user_auth_logout();
set_flash('info', 'You have been signed out.');
header('Location: ' . BASE_URL . 'index.php');
exit;
