<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

header('Content-Type: application/json');
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$pdo = get_db_connection();

// Find every user this person has exchanged a message with, plus the most
// recent message and unread count for each conversation.
$stmt = $pdo->prepare(
    'SELECT
        other.id AS other_user_id,
        other.name AS other_user_name,
        (SELECT body FROM messages m2
         WHERE (m2.sender_id = :uid1 AND m2.recipient_id = other.id)
            OR (m2.sender_id = other.id AND m2.recipient_id = :uid2)
         ORDER BY m2.created_at DESC LIMIT 1) AS last_message,
        (SELECT created_at FROM messages m3
         WHERE (m3.sender_id = :uid3 AND m3.recipient_id = other.id)
            OR (m3.sender_id = other.id AND m3.recipient_id = :uid4)
         ORDER BY m3.created_at DESC LIMIT 1) AS last_message_at,
        (SELECT COUNT(*) FROM messages m4
         WHERE m4.sender_id = other.id AND m4.recipient_id = :uid5 AND m4.is_read = 0) AS unread_count
     FROM users other
     WHERE other.id IN (
        SELECT recipient_id FROM messages WHERE sender_id = :uid6
        UNION
        SELECT sender_id FROM messages WHERE recipient_id = :uid7
     )
     ORDER BY last_message_at DESC'
);
$stmt->execute([
    'uid1' => $user['id'], 'uid2' => $user['id'],
    'uid3' => $user['id'], 'uid4' => $user['id'],
    'uid5' => $user['id'],
    'uid6' => $user['id'], 'uid7' => $user['id'],
]);
$conversations = $stmt->fetchAll();

echo json_encode(['success' => true, 'conversations' => $conversations]);
