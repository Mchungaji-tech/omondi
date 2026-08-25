<?php
/**
 * Procedural API for Submitting Project Partner Commitments & Pledges
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

$projectId = (int)($input['project_id'] ?? 0);
$name = sanitize_input($input['partner_name'] ?? ($member['name'] ?? ''));
$contact = sanitize_input($input['partner_contact'] ?? ($member['phone'] ?? ($member['email'] ?? '')));
$amount = (float)($input['amount_pledged'] ?? 0);
$notes = sanitize_input($input['notes'] ?? '');

if (empty($name) || empty($contact)) {
    echo json_encode(['success' => false, 'error' => 'Please provide your name and contact details.']);
    exit;
}

$refNo = generate_ref_no('PROP');

$sql = "INSERT INTO project_commitments (user_id, project_id, ref_no, partner_name, partner_contact, amount_pledged, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
$id = db_query($sql, "iisssds", [
    $userId,
    $projectId ?: null,
    $refNo,
    $name,
    $contact,
    $amount,
    $notes
]);

if ($id) {
    log_audit($userId, $name, "Project commitment registered ($refNo)");
    echo json_encode([
        'success' => true,
        'ref_no' => $refNo,
        'message' => 'Partner commitment recorded.'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not save commitment. Please try again.']);
}
