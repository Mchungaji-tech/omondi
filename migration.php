<?php
/**
 * Migration Script - Adds Users Table & User ID Foreign Keys
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$conn = get_db_connection();

// Add production MFA storage to databases created before this release.
$adminColumns = mysqli_query($conn, "SHOW COLUMNS FROM `admins`");
$existingAdminColumns = [];
while ($column = mysqli_fetch_assoc($adminColumns)) $existingAdminColumns[$column['Field']] = true;
if (!isset($existingAdminColumns['recovery_codes'])) mysqli_query($conn, "ALTER TABLE `admins` ADD COLUMN `recovery_codes` TEXT DEFAULT NULL AFTER `mfa_enabled`");
if (!isset($existingAdminColumns['recovery_codes_generated_at'])) mysqli_query($conn, "ALTER TABLE `admins` ADD COLUMN `recovery_codes_generated_at` DATETIME DEFAULT NULL AFTER `recovery_codes`");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `admin_mfa_setup_tokens` (`id` INT AUTO_INCREMENT PRIMARY KEY, `admin_id` INT NOT NULL, `token_hash` CHAR(64) NOT NULL UNIQUE, `expires_at` DATETIME NOT NULL, `used_at` DATETIME DEFAULT NULL, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, KEY `idx_mfa_setup_admin` (`admin_id`), CONSTRAINT `fk_mfa_setup_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Update legacy branding and remove the shared demo MFA secret.
mysqli_query($conn, "UPDATE site_settings SET setting_value = 'Bishop Morris Omondi' WHERE setting_key = 'pastor_name' AND setting_value IN ('Rev. Samuel K. Langat', 'Rev. Samuel Langat')");
mysqli_query($conn, "UPDATE site_settings SET setting_value = 'Redeemed Gospel Church Eldoret' WHERE setting_key IN ('address', 'bank_account_name') AND setting_value LIKE '%Beacon Gospel Centre%'");
mysqli_query($conn, "UPDATE admins SET totp_secret = NULL, mfa_enabled = 0 WHERE totp_secret = 'BGC2K7RDELD2026'");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `project_images` (`id` INT AUTO_INCREMENT PRIMARY KEY, `project_id` INT NOT NULL, `image_url` VARCHAR(500) NOT NULL, `sort_order` INT NOT NULL DEFAULT 0, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, KEY `idx_project_images_project` (`project_id`), CONSTRAINT `fk_project_images_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// 1. Create users table
$sqlUsers = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(50) DEFAULT '',
    `location` VARCHAR(100) DEFAULT '',
    `avatar_initials` VARCHAR(10) DEFAULT '',
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('active','suspended') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

mysqli_query($conn, $sqlUsers);

// 2. Add user_id column to prayer_requests if not exists
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM `prayer_requests` LIKE 'user_id'");
if (mysqli_num_rows($colCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE `prayer_requests` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`");
}

// 3. Add user_id column to invitations if not exists
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM `invitations` LIKE 'user_id'");
if (mysqli_num_rows($colCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE `invitations` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`");
}

// 4. Add user_id column to project_commitments if not exists
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM `project_commitments` LIKE 'user_id'");
if (mysqli_num_rows($colCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE `project_commitments` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`");
}

// 5. Add user_id column to live_chat_messages if not exists
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM `live_chat_messages` LIKE 'user_id'");
if (mysqli_num_rows($colCheck) === 0) {
    mysqli_query($conn, "ALTER TABLE `live_chat_messages` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`");
}

// Seed sample public user if none exists
$userCheck = mysqli_query($conn, "SELECT id FROM `users` WHERE email = 'john.doe@example.com'");
if (mysqli_num_rows($userCheck) === 0) {
    $pass = password_hash('User@2026!', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO `users` (name, email, phone, location, avatar_initials, password_hash, status) VALUES ('John Doe', 'john.doe@example.com', '+254 712 345 678', 'Eldoret', 'JD', '$pass', 'active')");
}

echo "Migration applied successfully.\n";
