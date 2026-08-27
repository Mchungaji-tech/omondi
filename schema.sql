-- ====================================================================
-- Database Schema for Beacon Gospel Centre
-- Pure Procedural PHP + MariaDB / MySQL
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `bgc_church` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bgc_church`;

-- --------------------------------------------------------------------
-- 1. Admins Table (Back-office Administration & MFA)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
    `totp_secret` VARCHAR(64) DEFAULT NULL,
    `mfa_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `recovery_codes` TEXT DEFAULT NULL,
    `recovery_codes_generated_at` DATETIME DEFAULT NULL,
    `failed_attempts` INT NOT NULL DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_mfa_setup_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `token_hash` CHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_mfa_setup_admin` (`admin_id`),
    CONSTRAINT `fk_mfa_setup_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_registration_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `token_hash` CHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_reg_token_hash` (`token_hash`),
    KEY `idx_reg_token_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 2. Public Users / Members Table (Separate Member Accounts)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(50) DEFAULT '',
    `location` VARCHAR(100) DEFAULT '',
    `avatar_initials` VARCHAR(10) DEFAULT '',
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('active','suspended') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. Site Settings & Profile Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(64) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. Sermons Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sermons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `series` VARCHAR(255) NOT NULL,
    `scripture_ref` VARCHAR(100) NOT NULL,
    `sermon_date` VARCHAR(50) NOT NULL,
    `duration` VARCHAR(20) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'grace',
    `audio_url` VARCHAR(255) DEFAULT '',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sermon_views` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sermon_id` INT NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT '',
    `user_agent` VARCHAR(255) DEFAULT '',
    `user_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sermon_views_sermon` (`sermon_id`),
    KEY `idx_sermon_views_created` (`created_at`),
    CONSTRAINT `fk_sermon_views_sermon` FOREIGN KEY (`sermon_id`) REFERENCES `sermons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 5. Live Schedule Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `live_schedule` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `day_code` VARCHAR(10) NOT NULL,
    `stream_time` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 6. Live Stream Chat Messages Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `live_chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `sender_name` VARCHAR(100) NOT NULL,
    `location` VARCHAR(100) DEFAULT '',
    `message` VARCHAR(255) NOT NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 7. Ministries Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ministries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `age_span` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `stats` VARCHAR(255) DEFAULT '',
    `image_url` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 8. Pastor's Weekly Rhythm Schedule Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `weekly_schedule` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `day_code` VARCHAR(10) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `details` TEXT NOT NULL,
    `time_chip` VARCHAR(100) NOT NULL,
    `is_sunday` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 9. Field Photo Gallery Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `image_url` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 10. Testimonies Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `testimonies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quote` TEXT NOT NULL,
    `attribution` VARCHAR(200) NOT NULL,
    `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 11. Events Calendar Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `day_num` VARCHAR(5) NOT NULL,
    `month_label` VARCHAR(30) NOT NULL,
    `event_date` DATE DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `event_type` VARCHAR(50) DEFAULT 'general',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 12. Support Programmes Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `support_programs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `account_code` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `goal_text` VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 13. Fund Goals Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fund_goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `raised_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `goal_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `note` VARCHAR(255) DEFAULT '',
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 14. Projects & Proposals Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `status` ENUM('planning','ongoing','completed') NOT NULL DEFAULT 'ongoing',
    `raised_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `goal_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `image_url` VARCHAR(255) NOT NULL,
    `summary` TEXT NOT NULL,
    `budget_text` TEXT NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_project_images_project` (`project_id`),
    CONSTRAINT `fk_project_images_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 15. Project Partner Commitments Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_commitments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `project_id` INT DEFAULT NULL,
    `ref_no` VARCHAR(50) NOT NULL UNIQUE,
    `partner_name` VARCHAR(150) NOT NULL,
    `partner_contact` VARCHAR(150) NOT NULL,
    `amount_pledged` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 16. Prayer Requests Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prayer_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `ref_no` VARCHAR(50) NOT NULL UNIQUE,
    `sender_name` VARCHAR(150) DEFAULT '',
    `contact` VARCHAR(150) DEFAULT '',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `request_text` TEXT NOT NULL,
    `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('new','prayed','archived') NOT NULL DEFAULT 'new',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 17. Preaching / Ministry Invitations Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invitations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `ref_no` VARCHAR(50) NOT NULL UNIQUE,
    `contact_name` VARCHAR(150) NOT NULL,
    `church_org` VARCHAR(200) NOT NULL,
    `phone_email` VARCHAR(150) NOT NULL,
    `town_county` VARCHAR(100) DEFAULT '',
    `preferred_date` VARCHAR(50) DEFAULT '',
    `service_type` VARCHAR(50) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('new','confirmed','declined') NOT NULL DEFAULT 'new',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 18. Audit Logs Table
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `username` VARCHAR(50) DEFAULT 'System',
    `action` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT '',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SEED DATA INITIALIZATION
-- ====================================================================

-- Default Admin Account
INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `role`, `totp_secret`, `mfa_enabled`)
VALUES (1, 'admin', 'office@revlangat.or.ke', '$2y$10$w09aJjGszcI1g88yE3e2j.3O5gT7aF6Q.sSgJ7Zl4Q18W6rE345lW', 'admin', NULL, 0)
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- Sample Public User
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `location`, `avatar_initials`, `password_hash`, `status`)
VALUES (1, 'John Doe', 'john.doe@example.com', '+254 712 345 678', 'Eldoret', 'JD', '$2y$10$VbLqG1qjSsqzC.0o0F57V.pGg9h/4H.i2L7O5m8B1w4E9Z1Z5J8eG', 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Profile and General Site Settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('pastor_name', 'Bishop Morris Omondi'),
('pastor_epithet', 'of Eldoret'),
('tagline', 'Preacher of the Gospel · 23 Years in the Vineyard'),
('hero_verse', '“Your word is a lamp to my feet and a light to my path.” — Psalm 119:105'),
('portrait_url', 'https://picsum.photos/seed/eldoret-pastor-portrait/900/1125'),
('bio1', 'Bishop Morris Omondi has spent twenty-three years planting churches, training pastors and raising the young across Kenya.'),
('bio2', 'He preaches one thing only — Christ, and Him crucified — with an open Bible, a warm heart, and the conviction that no one is beyond the reach of grace.'),
('phone', '+254 712 000 000'),
('email', 'office@revlangat.or.ke'),
('address', 'Redeemed Gospel Church Eldoret'),
('postal_box', 'P.O. Box 1234-30100, Eldoret'),
('sun1', '6:30 AM — Dawn Service'),
('sun2', '9:00 AM — Main Service · Live'),
('sun3', '2:00 PM — New Believers'' Class'),
('radio_station', 'Voice of the Gospel 94.6 FM'),
('mpesa_paybill', '453 210'),
('bank_name', 'Equity Bank · Eldoret Branch'),
('bank_account', '04501234567890'),
('bank_account_name', 'Redeemed Gospel Church Eldoret'),
('swift_code', 'EQBLKENA'),
('stream_is_live', '1'),
('stream_viewers', '1243'),
('security_mfa', '1'),
('security_lockout', '1'),
('security_audit', '1')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Sermons
INSERT INTO `sermons` (`title`, `series`, `scripture_ref`, `sermon_date`, `duration`, `category`, `sort_order`) VALUES
('The God Who Runs to You', 'Grace Without Borders', 'Luke 15:11–32', 'Jul 26, 2026', '48:12', 'grace', 1),
('When Grace Becomes a Scandal', 'Grace Without Borders', 'Luke 7:36–50', 'Jul 12, 2026', '45:40', 'grace', 2),
('I Am the Bread: Feeding the 5,000', 'Gospel of John — Light in the Dark', 'John 6:1–14', 'Jun 28, 2026', '51:05', 'john', 3),
('Nicodemus in the Night', 'Gospel of John — Light in the Dark', 'John 3:1–21', 'Jun 21, 2026', '47:33', 'john', 4),
('Why Are You Cast Down, My Soul?', 'Songs in the Night', 'Psalm 42', 'Jun 07, 2026', '43:58', 'psalms', 5),
('A Table Before My Enemies', 'Songs in the Night', 'Psalm 23', 'May 24, 2026', '46:20', 'psalms', 6),
('As for Me and My House', 'Family Altar', 'Joshua 24:15', 'May 10, 2026', '44:07', 'family', 7),
('Lord, Teach Us to Pray', 'Prayer That Moves', 'Luke 11:1–13', 'Apr 26, 2026', '49:26', 'prayer', 8);

INSERT INTO `sermon_views` (`sermon_id`, `ip_address`, `user_agent`) VALUES
(1, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(1, '192.168.1.11', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
(1, '192.168.1.12', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'),
(1, '192.168.1.13', 'Mozilla/5.0 (Linux; Android 14)'),
(1, '192.168.1.14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(2, '192.168.1.15', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
(2, '192.168.1.16', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(2, '192.168.1.17', 'Mozilla/5.0 (Samsung Browser)'),
(3, '192.168.1.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(3, '192.168.1.19', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
(4, '192.168.1.20', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'),
(5, '192.168.1.21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(5, '192.168.1.22', 'Mozilla/5.0 (Linux; Android 14)'),
(6, '192.168.1.23', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'),
(7, '192.168.1.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(8, '192.168.1.25', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'),
(8, '192.168.1.26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(8, '192.168.1.27', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');

-- Live Schedule
INSERT INTO `live_schedule` (`day_code`, `stream_time`, `title`, `sort_order`) VALUES
('Sun', '9:00 AM', 'Sunday Main Service', 1),
('Wed', '7:00 PM', 'Midweek Bible Study', 2),
('Fri', '7:30 PM', 'Youth Night Live', 3);

-- Live Chat Messages
INSERT INTO `live_chat_messages` (`sender_name`, `location`, `message`, `is_admin`) VALUES
('Grace K.', 'Kitale', 'Amen! The Word is burning today 🔥', 0),
('Bro. Daniel', 'Eldoret', 'Sound is so clear, praise God', 0),
('Mama Sarah', 'Soy', 'Greetings from Soy — watching as a family', 0),
('Kevin O.', 'Kisumu', 'That point about grace really hit me', 0),
('Sr. Naomi', 'Iten', 'Sharing this with my cell group', 0);

-- Ministries
INSERT INTO `ministries` (`name`, `age_span`, `description`, `stats`, `image_url`, `sort_order`) VALUES
('Youth Ministry', 'Ages 13–30', 'Discipleship cells, football leagues, worship nights and career mentorship. Raising a generation that knows the Book and their calling.', '✦ 400+ youth · 18 cell groups · annual Soy Hills camp', 'https://picsum.photos/seed/kenya-youth-camp/640/400', 1),
('Women''s Fellowship', 'Mothers'' Union', 'Prayer bands, table-banking savings groups and mentorship for mothers — spiritual fire with financial dignity.', '✦ 26 savings groups · 340 women · monthly prayer mountain', 'https://picsum.photos/seed/women-fellowship-kenya/640/400', 2),
('Children''s Church', 'Ages 3–12', 'Sunday school in three languages, memory-verse clubs and the Huruma feeding programme — hot meals and the Bread of Life.', '✦ 500+ children · 300 meals served weekly', 'https://picsum.photos/seed/children-ministry-kenya/640/400', 3),
('Street Rescue Outreach', 'Rescue & Rehab', 'Rescue, rehabilitation and reconciliation for street-connected young people in Langas and Huruma — back to family, school and church.', '✦ 210 youth resettled since 2018 · 3 partner churches', 'https://picsum.photos/seed/street-outreach-eldoret/640/400', 4),
('Beacon Leadership School', 'Pastoral Training', 'A two-year evening school equipping bi-vocational pastors and lay leaders across the North Rift — hermeneutics, homiletics and shepherding.', '✦ 60 pastors trained yearly · 14 counties reached', 'https://picsum.photos/seed/bible-college-kenya/640/400', 5);

-- Weekly Rhythm Schedule
INSERT INTO `weekly_schedule` (`day_code`, `title`, `details`, `time_chip`, `is_sunday`, `sort_order`) VALUES
('Mon', 'Sabbath Rest & Family Day', 'School run, tea with Ruth, reading — no meetings booked.', 'Family Time', 0, 1),
('Tue', 'Prayer Mountain — Kaptagat Ridge', 'Corporate intercession for the church, the town and the nation. All welcome.', '5:30 – 7:00 AM', 0, 2),
('Wed', 'Midweek Bible Study + Radio', 'Exposition in the main hall, then the 6:00 PM recording of “Morning with God”.', '6:00 PM · FM 94.6', 0, 3),
('Thu', 'Counselling & Hospital Visits', 'Pastoral counselling by appointment; afternoons at MTRH Eldoret.', '9 AM – 4 PM', 0, 4),
('Fri', 'Youth Night — Worship & Word', 'Youth worship evening, followed by football tactics (serious business).', '7:00 – 9:30 PM', 0, 5),
('Sat', 'Sermon Study & Weddings', 'Morning locked in sermon preparation; afternoons officiating weddings when scheduled.', 'Study · 8 AM – 12', 0, 6),
('Sun', 'The Lord''s Day — Three Services', '6:30 AM dawn service · 9:00 AM main service (live-streamed) · 2:00 PM new-believers'' class. You are most welcome!', '6:30 · 9:00 · 2:00', 1, 7);

-- Gallery Photos
INSERT INTO `gallery` (`image_url`, `caption`, `sort_order`) VALUES
('https://picsum.photos/seed/open-air-crusade-eldoret/620/460', 'Glory Crusade, Eldoret Showgrounds — 12,000 gathered · 2025', 1),
('https://picsum.photos/seed/soy-hills-worship/620/460', 'Youth camp worship night, Soy Hills · Aug 2025', 2),
('https://picsum.photos/seed/river-baptism-kenya/620/460', '84 baptisms at the Kipkaren river · 2024', 3),
('https://picsum.photos/seed/gospel-choir-worship/620/460', 'Beacon Mass Choir, Sunday 9AM service', 4),
('https://picsum.photos/seed/huruma-feeding-program/620/460', 'Children''s feeding programme, Huruma — every Saturday', 5);

-- Testimonies
INSERT INTO `testimonies` (`quote`, `attribution`, `sort_order`) VALUES
('Our pastor does not love church politics; he loves the people of the church. He prayed for me in hospital, visited me at home, and taught me the Word until I stood firm.', '— Mama Grace W. · Church Mother since 2009', 1),
('I was on the streets of Eldoret for three years. The Street Rescue team — sent by Rev. Langat — found me, cleaned me up, and paid my school fees. Today I am in Form Four and a worship leader.', '— Brian O., 18 · Beacon Youth', 2),
('Every year I invite Rev. Langat to preach our revival week in Kitale. He comes with his own bus fare, a worn-out Bible, and fire from heaven. Kenya needs more shepherds like this.', '— Rev. David Mutai · Partner Pastor, Kitale', 3),
('His radio programme lifted my husband out of despair. We now serve together in the men''s fellowship. Thank you, Pastor — and above all, thank You, Lord.', '— Sr. Naomi K. · Radio Listener, Iten', 4);

-- Events
INSERT INTO `events` (`day_num`, `month_label`, `title`, `location`, `description`, `event_type`, `sort_order`) VALUES
('16', 'Aug 2026', 'Glory Open-Air Crusade', '✦ Eldoret Showgrounds · 2:00 PM · Free entry', 'One-day town crusade with worship, the Word and prayer for the sick. Interpreters in English, Kiswahili and Kalenjin.', 'crusade', 1),
('03', 'Sep 2026', 'Youth Summer Camp — “Born of Fire”', '✦ Soy Hills Campsite · Sep 3–6 · Ages 13–19', 'Four days of worship, teaching, football and riverside baptisms. Subsidised fees for students — no one turned away for lack of funds.', 'camp', 2),
('04', 'Oct 2026', 'Widows & Orphans — Fundraiser Sunday', '✦ Redeemed Gospel Church Eldoret · 9:00 AM', 'A kingdom fundraiser supporting school fees and roofing for 120 widows'' households across Uasin Gishu.', 'fundraiser', 3),
('22', 'Nov 2026', 'Harvest Thanksgiving & Praise Concert', '✦ Langas Main Auditorium · 2:00 PM', 'A night of gratitude with the Mass Choir and guest ministers — bring your harvest, bring your praise.', 'concert', 4);

-- Support Programs
INSERT INTO `support_programs` (`name`, `account_code`, `description`, `goal_text`, `sort_order`) VALUES
('Ministry Vehicle Fleet', 'FLEET', 'Four vehicles for crusades, hospital visits, youth camps, and pastoral outreach across the North Rift.', 'GOAL: KSh 7M', 1),
('Office Block Building', 'OFFICE', 'Two-storey administration and pastoral centre on the Langas church plot.', 'GOAL: KSh 25M', 2),
('Beacon Community School', 'SCHOOL', 'Primary and secondary school with bursary places for widows and orphans from Langas and Huruma.', 'GOAL: KSh 30M', 3),
('Widows, Orphans & Bursary Fund', 'WIDOWS', 'Annual school fees, food parcels, medical support and emergency relief for 120 widows households and 200 students.', 'GOAL: KSh 5M / YEAR', 4);

-- Fund Goals
INSERT INTO `fund_goals` (`name`, `raised_amount`, `goal_amount`, `note`, `sort_order`) VALUES
('Ministry Fleet — 4 Vehicles', 2100000.00, 7000000.00, '14-seater, 9-seater, pickup & sedan', 1),
('Office Block — Admin & Pastoral Centre', 5200000.00, 25000000.00, '2-storey administration block', 2),
('Beacon Community School', 3000000.00, 30000000.00, 'Primary & secondary with bursary places', 3),
('Widows, Orphans & Bursary Fund', 800000.00, 5000000.00, 'Annual fees, food parcels & relief', 4);

-- Projects & Proposals
INSERT INTO `projects` (`name`, `status`, `raised_amount`, `goal_amount`, `image_url`, `summary`, `budget_text`, `sort_order`) VALUES
('Ministry Fleet — 4 Vehicles for Outreach', 'ongoing', 2100000.00, 7000000.00, 'https://picsum.photos/seed/ministry-fleet-vehicles/800/400', 'Four dedicated ministry vehicles to serve the North Rift: a 14-seater for crusades and hospital visits, a 9-seater for youth camps, a pickup for field logistics and widows outreach, and a sedan for pastoral administration and elder meetings.', '14-seater Toyota Hiace (crusades &amp; hospital)|2200000\n9-seater Toyota Hiace (youth camps &amp; transport)|1500000\nPick-up truck (field logistics, widows outreach)|1800000\nExecutive sedan (pastoral visits &amp; admin)|1200000\nInsurance &amp; branding (all 4 vehicles, 1 year)|200000', 1),
('Office Block — Administration &amp; Pastoral Centre', 'ongoing', 5200000.00, 25000000.00, 'https://picsum.photos/seed/church-office-block/800/400', 'A permanent two-storey office block for church administration, pastoral staff offices, meeting rooms, and a community resource centre. Located on the Langas plot beside the main auditorium.', 'Foundation &amp; ground floor|8000000\nFirst floor construction|6000000\nRoofing, guttering &amp; damp-proofing|2500000\nPlumbing, electrical &amp; network cabling|2000000\nFinishing (tiles, paint, ceilings, joinery)|3000000\nFurniture, fittings &amp; security systems|1500000', 2),
('Beacon Community School — Primary &amp; Secondary', 'planning', 3000000.00, 30000000.00, 'https://picsum.photos/seed/beacon-community-school/800/400', 'A Christian primary and secondary school offering quality education to children from low-income families in Langas, Huruma, and surrounding estates. 40% of places reserved for widows children and orphans.', 'Land acquisition (2 acres near church)|4000000\nClassroom block (8 classrooms + offices)|12000000\nAdministration block &amp; staff room|3000000\nStaff quarters (2 units)|4000000\nWater, sanitation &amp; kitchen|1500000\nPlayground, sports &amp; security|800000\nStart-up desks, books &amp; stationery|1200000', 3),
('Widows, Orphans &amp; Bursary Fund — Annual Operation', 'ongoing', 800000.00, 5000000.00, 'https://picsum.photos/seed/widows-orphans-bursary/800/400', 'Sustained financial support for 120 widows households and 200 vulnerable students across Uasin Gishu. Covers school fees, termly bursaries, monthly food parcels, basic medical support, and emergency relief for the most vulnerable.', 'School fees bursary (200 students @ KSh 8,000/term)|1600000\nFood parcels for 120 widows (monthly @ KSh 2,000)|720000\nBasic medical &amp; health support|480000\nEmergency relief (funerals, fire, sickness)|200000\nAdministration &amp; field verification|200000\nBursary review &amp; report printing|100000\nContingency buffer|200000', 4);
