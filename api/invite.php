<?php
/**
 * Procedural API for Submitting Preaching Invitations
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

$name = sanitize_input($input['contact_name'] ?? ($member['name'] ?? ''));
$church = sanitize_input($input['church_org'] ?? '');
$phone = sanitize_input($input['phone_email'] ?? ($member['phone'] ?? ($member['email'] ?? '')));
$town = sanitize_input($input['town_county'] ?? ($member['location'] ?? ''));
$date = sanitize_input($input['preferred_date'] ?? '');
$type = sanitize_input($input['service_type'] ?? 'sunday');
$msg = sanitize_input($input['message'] ?? '');

if (empty($name) || empty($church) || empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Please provide your name, church name, and contact details.']);
    exit;
}

$refNo = generate_ref_no('INV');

$sql = "INSERT INTO invitations (user_id, ref_no, contact_name, church_org, phone_email, town_county, preferred_date, service_type, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')";
$id = db_query($sql, "issssssss", [
    $userId,
    $refNo,
    $name,
    $church,
    $phone,
    $town,
    $date,
    $type,
    $msg
]);

if ($id) {
    log_audit($userId, $name, "New ministry invitation received ($refNo - $church)");
    echo json_encode([
        'success' => true,
        'ref_no' => $refNo,
        'message' => 'Invitation submitted successfully.'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not save invitation. Please try again.']);
}
