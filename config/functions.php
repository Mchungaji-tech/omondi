<?php
/**
 * Procedural Helper Functions - Beacon Gospel Centre
 */

require_once __DIR__ . '/config.php';

/**
 * Escape and sanitize output for HTML rendering
 */
function esc($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return trim(strip_tags((string)$data));
}

/**
 * Format number as Kenyan Shillings (KSh)
 */
function format_ksh($amount) {
    return '<span class="money" data-money="' . esc((float)($amount ?? 0)) . '" data-money-format="full">KSh ' . number_format((float)($amount ?? 0), 0) . '</span>';
}

/**
 * Format compact monetary representation (e.g., 12.0M, 750K)
 */
function format_compact_ksh($amount) {
    $amount = (float)$amount;
    $value = $amount >= 1000000 ? number_format($amount / 1000000, fmod($amount / 1000000, 1) === 0.0 ? 0 : 1) . 'M' : ($amount >= 1000 ? number_format($amount / 1000, fmod($amount / 1000, 1) === 0.0 ? 0 : 1) . 'K' : number_format($amount, 0));
    return '<span class="money" data-money="' . esc($amount) . '" data-money-format="compact">KSh ' . $value . '</span>';
}

/**
 * Generate unique reference string with prefix
 */
function generate_ref_no($prefix = 'REF') {
    return strtoupper($prefix) . '-' . date('Y') . '-' . mt_rand(1000, 9999);
}

/**
 * Calculate 2-letter uppercase initials from full name
 */
function calculate_initials($name) {
    $words = preg_split('/\s+/', trim((string)$name));
    if (empty($words) || empty($words[0])) {
        return 'U';
    }
    if (count($words) === 1) {
        return strtoupper(substr($words[0], 0, 2));
    }
    return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
}

/* ==========================================================================
   IMAGE HELPERS: RESOLVE URL + UPLOAD
   ========================================================================== */

/**
 * Resolve an image value (URL or local path) to a fully-qualified src attribute value.
 * Handles external http(s):// URLs pass through unchanged; local uploads paths get BASE_URL prepended.
 */
function img_src($image_value, $fallback = '') {
    $v = (string)($image_value ?? '');
    if ($v === '') {
        $v = (string)$fallback;
    }
    if ($v === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $v)) {
        return $v;
    }
    return rtrim(BASE_URL, '/') . '/' . ltrim($v, '/');
}

function ensure_project_images_table() {
    static $done = false;
    if ($done) return;
    $done = true;
    db_query("CREATE TABLE IF NOT EXISTS `project_images` (`id` INT AUTO_INCREMENT PRIMARY KEY, `project_id` INT NOT NULL, `image_url` VARCHAR(500) NOT NULL, `sort_order` INT NOT NULL DEFAULT 0, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, KEY `idx_project_images_project` (`project_id`), CONSTRAINT `fk_project_images_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/* ==========================================================================
   VIDEO EMBED HELPERS (YouTube + Facebook)
   ========================================================================== */

/**
 * Convert a user-pasted YouTube/Facebook share URL into an embeddable iframe src.
 * Supports:
 *   youtube.com/watch?v=ID
 *   youtu.be/ID
 *   youtube.com/embed/ID
 *   m.youtube.com/watch?v=ID
 *   facebook.com/watch?v=ID
 *   fb.watch/xxx/
 *   facebook.com/[user]/videos/[id]/
 * Returns plain URL unchanged if no pattern matched (for raw embed URLs).
 * Returns '' for empty input.
 *
 * @param string $rawUrl The user-supplied share URL.
 * @param string $autoplay '1' or '0' — most browsers block autoplay with sound, but live streams need it.
 * @return string Embed-ready URL (YouTube: https://www.youtube.com/embed/ID?... or Facebook: https://www.facebook.com/plugins/video.php?...)
 */
function video_embed_src($rawUrl, $autoplay = '0') {
    $url = trim((string)$rawUrl);
    if ($url === '') {
        return '';
    }

    $ap = ((string)$autoplay === '1') ? '1' : '0';

    // --- YouTube patterns ---
    if (preg_match('#(?:youtube\.com/watch\?(?:[^&]*&)?v=|youtu\.be/|youtube\.com/embed/|m\.youtube\.com/watch\?(?:[^&]*&)?v=)([A-Za-z0-9_-]{6,})#i', $url, $m)) {
        $id = $m[1];
        return 'https://www.youtube.com/embed/' . $id . '?autoplay=' . $ap . '&mute=' . $ap . '&rel=0&modestbranding=1&playsinline=1&origin=' . rawurlencode($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    // --- YouTube shorts -> embed ---
    if (preg_match('#youtube\.com/shorts/([A-Za-z0-9_-]{6,})#i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=' . $ap . '&mute=' . $ap . '&rel=0&playsinline=1';
    }

    // --- Facebook /watch?v=ID ---
    if (preg_match('#facebook\.com/watch/?\?v=(\d+)#i', $url, $m)) {
        return 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode('https://www.facebook.com/watch/?v=' . $m[1]) . '&show_text=0&width=1200';
    }
    // --- Facebook /[user|page]/videos/[id]/ ---
    if (preg_match('#facebook\.com/[^/\s]+/videos/(\d+)#i', $url, $m)) {
        return 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($url) . '&show_text=0&width=1200';
    }
    // --- fb.watch short links (redirect — best-effort, pass through with a generic embed URL -- we can't resolve fb.watch IDs serverside
    if (stripos($url, 'fb.watch') !== false) {
        // fallback: return the raw URL in iframe for browser resolution (may fail CORS but better than nothing)
        return $url;
    }

    // --- Vimeo fallback ---
    if (preg_match('#vimeo\.com/(?:video/)?(\d{5,})#i', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=' . $ap . '&title=0&byline=0';
    }

    // Already an embed URL or unrecognized: return as-is
    return $url;
}

/**
 * Build a complete <iframe> tag for a video URL.
 */
function video_iframe($rawUrl, $ratio = '16:9', $attrs = []) {
    $src = video_embed_src($rawUrl, $attrs['autoplay'] ?? '0');
    if ($src === '') {
        return '';
    }
    $atts = '';
    foreach ($attrs as $k => $v) {
        if ($k === 'autoplay') continue;
        $atts .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
    return '<div class="videowrap" style="aspect-ratio:' . htmlspecialchars($ratio) . ';overflow:hidden;border-radius:8px;background:#000;border:1px solid rgba(0,0,0,.1);">'
        . '<iframe src="' . esc($src) . '"'
        . ' title="Video player"'
        . ' style="width:100%;height:100%;border:0;display:block;"'
        . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
        . ' allowfullscreen'
        . ' referrerpolicy="strict-origin-when-cross-origin"'
        . $atts
        . '></iframe></div>';
}

/* ==========================================================================
   SERMON COMMENTS: AUTO-INSTALL TABLE + HELPERS
   ========================================================================== */

/**
 * Idempotent: if the sermon_comments table is missing, create it silently.
 * Runs once per DB connection — wraps check in a static flag.
 */
function ensure_sermon_comments_table() {
    static $done = false;
    if ($done) return;
    $done = true;

    $check = db_fetch_all("SHOW TABLES LIKE 'sermon_comments'");
    $exists = !empty($check);
    if ($exists) return;

    $sql = "CREATE TABLE IF NOT EXISTS `sermon_comments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sermon_id` INT NOT NULL,
        `parent_id` INT DEFAULT 0,
        `user_id` INT DEFAULT NULL,
        `author_name` VARCHAR(120) NOT NULL DEFAULT 'Anonymous',
        `author_email` VARCHAR(180) DEFAULT '',
        `author_location` VARCHAR(120) DEFAULT '',
        `comment_text` TEXT NOT NULL,
        `likes` INT DEFAULT 0,
        `is_approved` TINYINT DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_sermon` (`sermon_id`),
        KEY `idx_parent` (`parent_id`),
        KEY `idx_approved` (`is_approved`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    db_query($sql);
}

function ensure_sermon_views_table() {
    static $done = false;
    if ($done) return;
    $done = true;
    db_query("CREATE TABLE IF NOT EXISTS `sermon_views` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sermon_id` INT NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT '',
        `user_agent` VARCHAR(255) DEFAULT '',
        `user_id` INT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_sermon_views_sermon` (`sermon_id`),
        KEY `idx_sermon_views_created` (`created_at`),
        CONSTRAINT `fk_sermon_views_sermon` FOREIGN KEY (`sermon_id`) REFERENCES `sermons`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function get_sermon_view_count($sermonId) {
    $sermonId = (int)$sermonId;
    if ($sermonId <= 0) return 0;
    $row = db_fetch_one("SELECT COUNT(*) as cnt FROM sermon_views WHERE sermon_id = ?", "i", [$sermonId]);
    return $row ? (int)$row['cnt'] : 0;
}

function record_sermon_view($sermonId) {
    ensure_sermon_views_table();
    $sermonId = (int)$sermonId;
    if ($sermonId <= 0) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $userId = user_auth_check() ? (int)$_SESSION['user_id'] : null;
    return db_query("INSERT INTO sermon_views (sermon_id, ip_address, user_agent, user_id) VALUES (?, ?, ?, ?)", "isss", [$sermonId, $ip, $ua, $userId]);
}

function ensure_event_image_column() {
    static $done = false;
    if ($done) return;
    $done = true;

    $columns = db_fetch_all("SHOW COLUMNS FROM `events` LIKE 'image_url'");
    if (empty($columns)) {
        db_query("ALTER TABLE `events` ADD COLUMN `image_url` VARCHAR(500) DEFAULT NULL AFTER `description`");
    }
}

/**
 * Return a nested comments tree for a sermon (top-level + replies threaded below).
 * Only approved comments unless $includePending=true called from admin.
 */
function get_sermon_comments_tree($sermonId, $includePending = false) {
    ensure_sermon_comments_table();
    $sermonId = (int)$sermonId;
    $where = "WHERE sermon_id = ?" . ($includePending ? "" : " AND is_approved = 1");
    $rows = db_fetch_all("SELECT * FROM sermon_comments $where ORDER BY parent_id ASC, created_at DESC", "i", [$sermonId]);
    if (!$rows) return ['count' => 0, 'tree' => []];
    $byId = [];
    $children = [];
    $roots = [];
    foreach ($rows as $r) {
        $rid = (int)$r['id'];
        $pid = (int)($r['parent_id'] ?? 0);
        $byId[$rid] = $r + ['replies' => []];
        if ($pid <= 0) {
            $roots[$rid] = &$byId[$rid];
        } else {
            if (!isset($children[$pid])) $children[$pid] = [];
            $children[$pid][] = $rid;
        }
    }
    // Attach replies
    foreach ($children as $pid => $rids) {
        if (isset($byId[$pid])) {
            foreach ($rids as $rid) {
                $byId[$pid]['replies'][] = &$byId[$rid];
            }
        } else {
            // Orphan reply → promote to root
            foreach ($rids as $rid) {
                if (isset($byId[$rid])) $roots[$rid] = &$byId[$rid];
            }
        }
    }
    // Sort roots by created_at DESC
    usort($roots, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return ['count' => count($rows), 'tree' => array_values($roots)];
}

/**
 * Insert a sermon comment. Returns the new row as array, or false on failure.
 * Auto-approves if user is authenticated member; otherwise holds for approval unless site setting allows guest approval.
 */
function add_sermon_comment($sermonId, $parentId, $userId, $name, $email, $location, $text) {
    ensure_sermon_comments_table();
    $sermonId = (int)$sermonId;
    $parentId = max(0, (int)$parentId);
    $userId = $userId ? (int)$userId : null;
    $name = trim(sanitize_input($name));
    if ($name === '') $name = 'Anonymous';
    $email = sanitize_input($email ?? '');
    $location = sanitize_input($location ?? '');
    $text = trim($text ?? '');
    if ($text === '' || $sermonId <= 0) return false;

    $approved = $userId ? 1 : ((int)get_setting('comments_auto_approve_guest', '0') ? 1 : 0);

    $sql = "INSERT INTO sermon_comments (sermon_id, parent_id, user_id, author_name, author_email, author_location, comment_text, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $ok = db_query($sql, "iiissssi", [$sermonId, $parentId, $userId, $name, $email, $location, $text, $approved]);
    if (!$ok) return false;
    $conn = get_db_connection();
    $id = (int)mysqli_insert_id($conn);
    return db_fetch_one("SELECT * FROM sermon_comments WHERE id = ?", "i", [$id]);
}

function like_sermon_comment($commentId) {
    ensure_sermon_comments_table();
    $id = (int)$commentId;
    db_query("UPDATE sermon_comments SET likes = likes + 1 WHERE id = ?", "i", [$id]);
    return (int)db_fetch_one("SELECT likes FROM sermon_comments WHERE id = ?", "i", [$id])['likes'];
}

/**
 * Handles image file uploads with validation, resizing/renaming, and returns relative URL
 *
 * @param array $file_array Pass $_FILES['field_name']
 * @param string $subfolder Subfolder inside uploads/ (e.g. 'gallery', 'projects', 'profile')
 * @return string|false Returns relative path like 'uploads/gallery/photo_xyz.jpg' or false on failure
 */
function upload_image($file_array, $subfolder = '') {
    if (!isset($file_array) || !is_array($file_array) || $file_array['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $tmpName = $file_array['tmp_name'];
    $fileSize = $file_array['size'];
    $origName = $file_array['name'];

    // Max 5MB file size
    if ($fileSize > 5 * 1024 * 1024) {
        return false;
    }

    // Allowed extensions & mime types
    $allowed = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif'
    ];

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!array_key_exists($ext, $allowed)) {
        return false;
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        return false;
    }

    // Destination Directory
    $baseUploadDir = __DIR__ . '/../uploads';
    if (!is_dir($baseUploadDir)) {
        @mkdir($baseUploadDir, 0755, true);
    }

    $targetDir = $baseUploadDir;
    $relPath = 'uploads/';

    if (!empty($subfolder)) {
        $cleanSub = preg_replace('/[^a-zA-Z0-9_\-]/', '', $subfolder);
        $targetDir = $baseUploadDir . '/' . $cleanSub;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $relPath .= $cleanSub . '/';
    }

    // Unique filename
    $uniqueName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $destFile = $targetDir . '/' . $uniqueName;

    if (move_uploaded_file($tmpName, $destFile)) {
        return $relPath . $uniqueName;
    }

    return false;
}

/* ==========================================================================
   DATABASE PROCEDURAL QUERY WRAPPERS
   ========================================================================== */

/**
 * Execute a prepared SQL query and return true/false or inserted ID
 */
function db_query($sql, $types = "", $params = []) {
    global $db;
    if (!$db) {
        $db = get_db_connection();
    }

    $stmt = mysqli_stmt_init($db);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        error_log("DB Query Prepare Failed: " . mysqli_error($db) . " Query: " . $sql);
        return false;
    }

    if (!empty($params) && !empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    $success = mysqli_stmt_execute($stmt);
    if (!$success) {
        error_log("DB Query Execute Failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $insert_id = mysqli_stmt_insert_id($stmt);
    mysqli_stmt_close($stmt);

    return $insert_id ?: true;
}

/**
 * Execute a prepared SELECT query and return all matching rows as associative array
 */
function db_fetch_all($sql, $types = "", $params = []) {
    global $db;
    if (!$db) {
        $db = get_db_connection();
    }

    $stmt = mysqli_stmt_init($db);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        error_log("DB Fetch All Prepare Failed: " . mysqli_error($db) . " Query: " . $sql);
        return [];
    }

    if (!empty($params) && !empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

/**
 * Execute a prepared SELECT query and return a single row as associative array
 */
function db_fetch_one($sql, $types = "", $params = []) {
    $rows = db_fetch_all($sql, $types, $params);
    return !empty($rows) ? $rows[0] : null;
}

/* ==========================================================================
   SITE SETTINGS HELPERS
   ========================================================================== */

/**
 * Retrieve all site settings as key-value array
 */
function get_all_settings() {
    static $settings = null;
    if ($settings === null) {
        $rows = db_fetch_all("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
    }
    return $settings;
}

/**
 * Retrieve a specific setting value
 */
function get_setting($key, $default = '') {
    $settings = get_all_settings();
    return $settings[$key] ?? $default;
}

/**
 * Update or insert a site setting
 */
function set_setting($key, $value) {
    $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP";
    return db_query($sql, "ss", [$key, $value]);
}

/* ==========================================================================
   FLASH MESSAGES & CSRF
   ========================================================================== */

/**
 * Set flash message
 */
function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Check if flash message exists
 */
function has_flash() {
    return isset($_SESSION['flash_message']);
}

/**
 * Get and clear flash message
 */
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Generate or get CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render hidden CSRF form input
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

/**
 * Verify CSRF token from POST request
 */
function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die("CSRF validation failed. Please refresh the page and try again.");
        }
    }
}

/* ==========================================================================
   PUBLIC MEMBER / USER AUTHENTICATION
   ========================================================================== */

/**
 * Check if a public member is logged in
 */
function user_auth_check() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in member's user ID
 */
function user_auth_id() {
    return user_auth_check() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get logged in member's data
 */
function user_auth_data() {
    if (!user_auth_check()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Member',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'initials' => $_SESSION['user_initials'] ?? 'M',
        'location' => $_SESSION['user_location'] ?? ''
    ];
}

/**
 * Log in public member
 */
function user_auth_login($user) {
    session_regenerate_id(true);
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
    $_SESSION['user_location'] = $user['location'] ?? '';
    $_SESSION['user_initials'] = $user['avatar_initials'] ?: calculate_initials($user['name']);
    $_SESSION['user_login_time'] = time();

    log_audit($user['id'], $user['name'], "Public member logged in");
}

/**
 * Log out public member
 */
function user_auth_logout() {
    unset($_SESSION['user_logged_in'], $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_phone'], $_SESSION['user_location'], $_SESSION['user_initials']);
}

/* ==========================================================================
   ADMIN AUTHENTICATION & SECURITY
   ========================================================================== */

/**
 * Check if admin is currently logged in
 */
function auth_check() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !empty($_SESSION['admin_id']);
}

/**
 * Get logged in admin data
 */
function auth_user() {
    if (!auth_check()) {
        return null;
    }
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'email' => $_SESSION['admin_email'] ?? '',
        'role' => $_SESSION['admin_role'] ?? 'admin'
    ];
}

/**
 * Require admin authentication, redirecting to admin login if not authenticated
 */
function auth_require() {
    if (!auth_check()) {
        set_flash('error', 'Please sign in to access the administration console.');
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

/**
 * Log in admin user session
 */
function auth_login($user) {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_role'] = $user['role'] ?? 'admin';
    $_SESSION['admin_login_time'] = time();

    // Reset failed attempts upon successful login
    db_query("UPDATE admins SET failed_attempts = 0, locked_until = NULL, last_login_at = CURRENT_TIMESTAMP WHERE id = ?", "i", [$user['id']]);

    log_audit($user['id'], $user['username'], "Admin logged in successfully");
}

/**
 * Log out admin user session
 */
function auth_logout() {
    $user = auth_user();
    if ($user) {
        log_audit($user['id'], $user['username'], "Admin logged out");
    }
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_email'], $_SESSION['admin_role']);
    session_regenerate_id(true);
}

/**
 * Record action in audit log
 */
function log_audit($user_id, $username, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_query(
        "INSERT INTO audit_logs (user_id, username, action, ip_address) VALUES (?, ?, ?, ?)",
        "isss",
        [$user_id ?: null, $username ?: 'System', $action, $ip]
    );
}

function base32_decode_value($secret) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string)$secret));
    $bits = '';
    for ($i = 0; $i < strlen($secret); $i++) {
        $value = strpos($alphabet, $secret[$i]);
        if ($value === false) continue;
        $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
    }
    $binary = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
        $binary .= chr(bindec(substr($bits, $i, 8)));
    }
    return $binary;
}

function generate_totp_secret() {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bytes = random_bytes(20);
    $bits = '';
    for ($i = 0; $i < strlen($bytes); $i++) $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
    $secret = '';
    for ($i = 0; $i < strlen($bits); $i += 5) $secret .= $alphabet[bindec(str_pad(substr($bits, $i, 5), 5, '0'))];
    return $secret;
}

function generate_totp_code($secret, $time_step = 30, $timestamp = null) {
    $timestamp = $timestamp ?? time();
    $counter = pack('N*', 0) . pack('N*', (int)floor($timestamp / $time_step));
    $hash = hash_hmac('sha1', $counter, base32_decode_value($secret), true);
    $offset = ord($hash[19]) & 0x0f;
    $binary = ((ord($hash[$offset]) & 0x7f) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);
    return str_pad((string)($binary % 1000000), 6, '0', STR_PAD_LEFT);
}

function verify_totp_code($code, $secret, $time_step = 30) {
    $code = preg_replace('/\D/', '', (string)$code);
    if (strlen($code) !== 6 || empty($secret)) return false;
    for ($window = -1; $window <= 1; $window++) {
        if (hash_equals(generate_totp_code($secret, $time_step, time() + ($window * $time_step)), $code)) return true;
    }
    return false;
}

function totp_uri($secret, $account, $issuer = 'Redeemed Gospel Church Eldoret') {
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account) . '?secret=' . rawurlencode($secret) . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
}

function generate_recovery_codes($count = 10) {
    $plain = [];
    $hashed = [];
    for ($i = 0; $i < $count; $i++) {
        $code = strtoupper(bin2hex(random_bytes(5)));
        $plain[] = substr($code, 0, 5) . '-' . substr($code, 5);
        $hashed[] = hash('sha256', $code);
    }
    return ['plain' => $plain, 'stored' => json_encode($hashed)];
}

function consume_recovery_code($admin, $code) {
    $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$code));
    if (strlen($normalized) !== 10 || empty($admin['recovery_codes'])) return false;
    $codes = json_decode($admin['recovery_codes'], true);
    if (!is_array($codes)) return false;
    $target = hash('sha256', $normalized);
    foreach ($codes as $index => $stored) {
        if (hash_equals((string)$stored, $target)) {
            unset($codes[$index]);
            db_query("UPDATE admins SET recovery_codes = ? WHERE id = ?", "si", [json_encode(array_values($codes)), $admin['id']]);
            return true;
        }
    }
    return false;
}

function create_mfa_setup_token($adminId, $minutes = 15) {
    $token = strtoupper(bin2hex(random_bytes(8)));
    $expiresAt = date('Y-m-d H:i:s', time() + ((int)$minutes * 60));
    db_query("UPDATE admin_mfa_setup_tokens SET used_at = CURRENT_TIMESTAMP WHERE admin_id = ? AND used_at IS NULL", "i", [$adminId]);
    db_query("INSERT INTO admin_mfa_setup_tokens (admin_id, token_hash, expires_at) VALUES (?, ?, ?)", "iss", [$adminId, hash('sha256', $token), $expiresAt]);
    return $token;
}

function consume_mfa_setup_token($adminId, $token) {
    $row = db_fetch_one("SELECT id FROM admin_mfa_setup_tokens WHERE admin_id = ? AND token_hash = ? AND used_at IS NULL AND expires_at > NOW()", "is", [$adminId, hash('sha256', strtoupper(trim((string)$token)))]);
    if (!$row) return false;
    db_query("UPDATE admin_mfa_setup_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = ? AND used_at IS NULL", "i", [$row['id']]);
    return true;
}
