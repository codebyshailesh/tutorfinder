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

$otherId = (int) ($_GET['with'] ?? 0);
if ($otherId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing "with" user id']);
    exit;
}

$pdo = get_db_connection();

$stmt = $pdo->prepare(
    'SELECT id, sender_id, recipient_id, body, created_at
     FROM messages
     WHERE (sender_id = :me AND recipient_id = :other)
        OR (sender_id = :other2 AND recipient_id = :me2)
     ORDER BY created_at ASC'
);
$stmt->execute([
    'me' => $user['id'], 'other' => $otherId,
    'other2' => $otherId, 'me2' => $user['id'],
]);
$messages = $stmt->fetchAll();

// Mark incoming messages from this user as read.
$stmt = $pdo->prepare(
    'UPDATE messages SET is_read = 1 WHERE sender_id = :other AND recipient_id = :me AND is_read = 0'
);
$stmt->execute(['other' => $otherId, 'me' => $user['id']]);

$stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
$stmt->execute(['id' => $otherId]);
$other = $stmt->fetch();

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'other_user' => $other ? ['id' => $otherId, 'name' => $other['name']] : null,
]);
