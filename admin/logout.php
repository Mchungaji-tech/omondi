<?php
/**
 * Admin Logout Controller
 * Pure Procedural PHP
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

auth_logout();
set_flash('info', 'You have been signed out safely.');
header('Location: ' . BASE_URL . 'admin/login.php');
exit;
