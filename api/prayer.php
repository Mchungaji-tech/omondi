<?php
/**
 * Procedural API for Submitting Confidential Prayer Requests
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$member = user_auth_data();
$userId = $member ? (int)$member['id'] : null;

$name = sanitize_input($input['sender_name'] ?? ($member['name'] ?? 'Friend'));
$contact = sanitize_input($input['contact'] ?? ($member['phone'] ?? ($member['email'] ?? '')));
$category = sanitize_input($input['category'] ?? 'general');
$text = sanitize_input($input['request_text'] ?? '');
$isAnon = !empty($input['is_anonymous']) ? 1 : 0;

if (empty($text)) {
    echo json_encode(['success' => false, 'error' => 'Please provide the details of your prayer request.']);
    exit;
}

$refNo = generate_ref_no('PRAY');

$sql = "INSERT INTO prayer_requests (user_id, ref_no, sender_name, contact, category, request_text, is_anonymous, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'new')";
$id = db_query($sql, "isssssi", [
    $userId,
    $refNo,
    $isAnon ? '' : $name,
    $isAnon ? '' : $contact,
    $category,
    $text,
    $isAnon
]);

if ($id) {
    log_audit($userId, $isAnon ? 'Anonymous' : $name, "New prayer request submitted ($refNo)");
    echo json_encode([
        'success' => true,
        'ref_no' => $refNo,
        'message' => 'Prayer request received in confidence.'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not save prayer request. Please try again.']);
}
