<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

header('Content-Type: application/json');
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$recipientId = (int) ($input['recipient_id'] ?? 0);
$body        = trim($input['body'] ?? '');
$bookingId   = !empty($input['booking_id']) ? (int) $input['booking_id'] : null;

if ($recipientId <= 0 || $body === '') {
    http_response_code(400);
    echo json_encode(['error' => 'recipient_id and body are required']);
    exit;
}

if ($recipientId === (int) $user['id']) {
    http_response_code(400);
    echo json_encode(['error' => 'You cannot message yourself']);
    exit;
}

if (strlen($body) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is too long']);
    exit;
}

$pdo = get_db_connection();

$stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id');
$stmt->execute(['id' => $recipientId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Recipient not found']);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO messages (booking_id, sender_id, recipient_id, body) VALUES (:booking_id, :sender_id, :recipient_id, :body)'
);
$stmt->execute([
    'booking_id'   => $bookingId,
    'sender_id'    => $user['id'],
    'recipient_id' => $recipientId,
    'body'         => $body,
]);

echo json_encode(['success' => true, 'message_id' => (int) $pdo->lastInsertId()]);
