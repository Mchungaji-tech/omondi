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

// 6. Synchronize Updated Capital Project Proposals & AI-Generated Plans
$newProjects = [
    1 => [
        'name' => 'Sanctuary Upgrade — 200 Chairs, Gypsum Altar & Big Screens',
        'status' => 'ongoing',
        'raised_amount' => 350000.00,
        'goal_amount' => 920000.00,
        'image_url' => 'uploads/projects/sanctuary_interior_plan.jpg',
        'summary' => 'Comprehensive interior sanctuary revitalization to expand seating capacity and elevate the worship and media broadcast experience at Redeemed Gospel Church Eldoret. Scope includes procuring 200 high-density cushioned sanctuary chairs (200 units @ KSh 2,100 each = KSh 420,000), fabricating a bespoke stepped altar stage with custom architectural gypsum ceiling and warm ambient LED cove lighting (KSh 200,000), and installing two ultra-bright large LED video screens and digital AV signal distribution (KSh 300,000).',
        'budget_text' => "200 New Cushioned Sanctuary Chairs (200 @ KSh 2,100)|420000\nAltar Architectural Gypsum Design & Ambient Lighting|200000\nHigh-Definition Sanctuary Big Display Screens & AV System|300000",
        'sort_order' => 1
    ],
    2 => [
        'name' => 'Front Porch Terrazzo & Exterior Wall Plastering',
        'status' => 'ongoing',
        'raised_amount' => 45000.00,
        'goal_amount' => 150000.00,
        'image_url' => 'uploads/projects/porch_terrazzo_exterior.jpg',
        'summary' => 'Exterior rehabilitation and entrance enhancement project for the church sanctuary and auditorium. Scope entails casting and polishing high-durability speckled terrazzo flooring across the front porch and entrance stairs (KSh 50,000), complete external wall plastering to repair, seal, and smooth all exterior outside walls (KSh 80,000), and applying weather-resistant protective coatings and finishing paint (KSh 20,000).',
        'budget_text' => "Front Porch Terrazzo Floor Finishing (Cast-in-place & Polished)|50000\nExterior Outside Wall Plastering & Surface Preparation|80000\nWeather-Resistant Exterior Finishing Coat & Paint|20000",
        'sort_order' => 2
    ],
    3 => [
        'name' => 'Integrated Multi-Storey Complex — Church Offices & School',
        'status' => 'planning',
        'raised_amount' => 5200000.00,
        'goal_amount' => 30000000.00,
        'image_url' => 'uploads/projects/multistorey_school_office_complex.jpg',
        'summary' => 'A flagship landmark multi-storey facility combining central church administration with an integrated Christian primary and secondary academy in Eldoret. The ground floor accommodates pastoral executive suites, central church administration, counseling rooms, and boardroom. The first and second storeys feature modern classrooms, dedicated science laboratories, computer ICT lab, and library with bursary opportunities for underprivileged children from Langas and Huruma.',
        'budget_text' => "Substructure & Multi-Storey Reinforced Foundations|6500000\nGround Floor: Church Administration Offices & Pastoral Suites|7500000\nFirst Floor: Primary & Junior Academy Classrooms & Staff Room|6500000\nSecond Floor: Senior Classrooms, ICT Computer Lab & Science Labs|5500000\nCommercial Roofing, Rooftop Solar Power Array & Water Harvesting|2500000\nInternal & External Plastering, Acoustic Ceilings, Glazing & Cabling|1500000",
        'sort_order' => 3
    ],
    4 => [
        'name' => 'Ministry Fleet — 4 Vehicles for Outreach',
        'status' => 'ongoing',
        'raised_amount' => 2100000.00,
        'goal_amount' => 7000000.00,
        'image_url' => 'https://picsum.photos/seed/ministry-fleet-vehicles/800/400',
        'summary' => 'Four dedicated ministry vehicles to serve the North Rift: a 14-seater for crusades and hospital visits, a 9-seater for youth camps, a pickup for field logistics and widows outreach, and a sedan for pastoral administration and elder meetings.',
        'budget_text' => "14-seater Toyota Hiace (crusades & hospital)|2200000\n9-seater Toyota Hiace (youth camps & transport)|1500000\nPick-up truck (field logistics, widows outreach)|1800000\nExecutive sedan (pastoral visits & admin)|1200000\nInsurance & branding (all 4 vehicles, 1 year)|200000",
        'sort_order' => 4
    ],
    5 => [
        'name' => 'Widows, Orphans & Bursary Fund — Annual Operation',
        'status' => 'ongoing',
        'raised_amount' => 800000.00,
        'goal_amount' => 5000000.00,
        'image_url' => 'https://picsum.photos/seed/widows-orphans-bursary/800/400',
        'summary' => 'Sustained financial support for 120 widows households and 200 vulnerable students across Uasin Gishu. Covers school fees, termly bursaries, monthly food parcels, basic medical support, and emergency relief for the most vulnerable.',
        'budget_text' => "School fees bursary (200 students @ KSh 8,000/term)|1600000\nFood parcels for 120 widows (monthly @ KSh 2,000)|720000\nBasic medical & health support|480000\nEmergency relief (funerals, fire, sickness)|200000\nAdministration & field verification|200000\nBursary review & report printing|100000\nContingency buffer|200000",
        'sort_order' => 5
    ]
];

foreach ($newProjects as $id => $p) {
    $exists = mysqli_query($conn, "SELECT id FROM projects WHERE id = " . (int)$id);
    if ($exists && mysqli_num_rows($exists) > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE projects SET name = ?, status = ?, raised_amount = ?, goal_amount = ?, image_url = ?, summary = ?, budget_text = ?, sort_order = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssddsssii", $p['name'], $p['status'], $p['raised_amount'], $p['goal_amount'], $p['image_url'], $p['summary'], $p['budget_text'], $p['sort_order'], $id);
        mysqli_stmt_execute($stmt);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO projects (id, name, status, raised_amount, goal_amount, image_url, summary, budget_text, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issddsssi", $id, $p['name'], $p['status'], $p['raised_amount'], $p['goal_amount'], $p['image_url'], $p['summary'], $p['budget_text'], $p['sort_order']);
        mysqli_stmt_execute($stmt);
    }
}

// 7. Seed Project Gallery Images
$galleryImages = [
    [1, 'uploads/projects/altar_gypsum_screens_closeup.jpg', 1],
    [1, 'uploads/projects/sanctuary_interior_plan.jpg', 2],
    [2, 'uploads/projects/porch_terrazzo_exterior.jpg', 1],
    [3, 'uploads/projects/multistorey_school_office_complex.jpg', 1]
];

foreach ($galleryImages as [$pId, $imgUrl, $order]) {
    $imgExists = mysqli_query($conn, "SELECT id FROM project_images WHERE project_id = $pId AND image_url = '" . mysqli_real_escape_string($conn, $imgUrl) . "'");
    if ($imgExists && mysqli_num_rows($imgExists) === 0) {
        mysqli_query($conn, "INSERT INTO project_images (project_id, image_url, sort_order) VALUES ($pId, '" . mysqli_real_escape_string($conn, $imgUrl) . "', $order)");
    }
}

// 8. Update Support Programs & Fund Goals
$supportProgs = [
    ['Sanctuary Seating & Media Upgrade', 'SANCTUARY', '200 new cushioned chairs, custom architectural gypsum altar with cove lighting, and dual large LED visual screens.', 'GOAL: KSh 920K', 1],
    ['Front Porch Terrazzo & Exterior Plastering', 'PORCH', 'Cast-in-place polished terrazzo for front entrance porch and complete plastering/waterproofing of outside walls.', 'GOAL: KSh 150K', 2],
    ['Integrated Multi-Storey Complex', 'COMPLEX', 'Three-storey landmark complex integrating church pastoral/admin offices on ground floor and primary/secondary school on upper floors.', 'GOAL: KSh 30M', 3],
    ['Ministry Vehicle Fleet', 'FLEET', 'Four vehicles for crusades, hospital visits, youth camps, and pastoral outreach across the North Rift.', 'GOAL: KSh 7M', 4],
    ['Widows, Orphans & Bursary Fund', 'WIDOWS', 'Annual school fees, food parcels, medical support and emergency relief for 120 widows households and 200 students.', 'GOAL: KSh 5M / YEAR', 5]
];

$hasProgs = mysqli_query($conn, "SELECT COUNT(*) FROM support_programs");
$progCount = $hasProgs ? (int)mysqli_fetch_row($hasProgs)[0] : 0;
if ($progCount <= 4) {
    mysqli_query($conn, "DELETE FROM support_programs");
    foreach ($supportProgs as $sp) {
        $stmt = mysqli_prepare($conn, "INSERT INTO support_programs (name, account_code, description, goal_text, sort_order) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssi", $sp[0], $sp[1], $sp[2], $sp[3], $sp[4]);
        mysqli_stmt_execute($stmt);
    }
}

$fundGoals = [
    ['Sanctuary Upgrade — 200 Chairs, Gypsum Altar & Screens', 350000.00, 920000.00, '200 chairs @ 2,100, gypsum altar, 2 big screens', 1],
    ['Front Porch Terrazzo & Exterior Wall Plastering', 45000.00, 150000.00, 'Polished terrazzo porch (50k) & wall plastering', 2],
    ['Integrated Multi-Storey Complex — Offices & School', 5200000.00, 30000000.00, '3-storey facility integrating admin offices & school', 3],
    ['Ministry Fleet — 4 Vehicles', 2100000.00, 7000000.00, '14-seater, 9-seater, pickup & sedan', 4],
    ['Widows, Orphans & Bursary Fund', 800000.00, 5000000.00, 'Annual fees, food parcels & relief', 5]
];

$hasGoals = mysqli_query($conn, "SELECT COUNT(*) FROM fund_goals");
$goalCount = $hasGoals ? (int)mysqli_fetch_row($hasGoals)[0] : 0;
if ($goalCount <= 4) {
    mysqli_query($conn, "DELETE FROM fund_goals");
    foreach ($fundGoals as $fg) {
        $stmt = mysqli_prepare($conn, "INSERT INTO fund_goals (name, raised_amount, goal_amount, note, sort_order) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sddsi", $fg[0], $fg[1], $fg[2], $fg[3], $fg[4]);
        mysqli_stmt_execute($stmt);
    }
}

echo "Migration applied successfully with updated project proposals and AI visual plans.\n";

