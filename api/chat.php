<?php
/**
 * Procedural API for Live Stream Chat (Fetch & Post)
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $messages = db_fetch_all("
        SELECT m.id, m.user_id, m.sender_name, m.location, m.message, m.is_admin, m.created_at, u.avatar_initials
        FROM live_chat_messages m
        LEFT JOIN users u ON m.user_id = u.id
        ORDER BY m.id DESC LIMIT 40
    ");
    echo json_encode(['success' => true, 'messages' => array_reverse($messages)]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $user = user_auth_data();
    $userId = $user ? (int)$user['id'] : null;
    $sender = $user ? $user['name'] : sanitize_input($input['sender_name'] ?? 'Friend in Christ');
    $location = $user ? ($user['location'] ?: 'Kenya') : sanitize_input($input['location'] ?? 'Eldoret');
    $message = sanitize_input($input['message'] ?? '');

    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty.']);
        exit;
    }

    if (strlen($message) > 250) {
        $message = substr($message, 0, 250);
    }

    $sql = "INSERT INTO live_chat_messages (user_id, sender_name, location, message, is_admin) VALUES (?, ?, ?, ?, 0)";
    $id = db_query($sql, "isss", [$userId, $sender, $location, $message]);

    if ($id) {
        echo json_encode([
            'success' => true,
            'message' => [
                'id' => $id,
                'user_id' => $userId,
                'sender_name' => $sender,
                'location' => $location,
                'message' => $message,
                'avatar_initials' => $user ? $user['initials'] : calculate_initials($sender),
                'is_admin' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error while saving message.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
