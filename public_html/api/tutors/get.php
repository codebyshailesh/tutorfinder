<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

header('Content-Type: application/json');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$tutorId = (int) ($_GET['id'] ?? 0);
if ($tutorId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid tutor id']);
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    'SELECT u.id, u.name, u.created_at, tp.subjects, tp.bio, tp.hourly_rate, tp.location
     FROM users u
     JOIN tutor_profiles tp ON tp.user_id = u.id
     WHERE u.id = :id AND u.role = "tutor"
     LIMIT 1'
);
$stmt->execute(['id' => $tutorId]);
$tutor = $stmt->fetch();

if (!$tutor) {
    http_response_code(404);
    echo json_encode(['error' => 'Tutor not found']);
    exit;
}

echo json_encode(['success' => true, 'tutor' => $tutor]);
