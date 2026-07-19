<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

header('Content-Type: application/json');
require_auth(); // must be logged in to browse tutors

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$subject  = trim($_GET['subject'] ?? '');
$location = trim($_GET['location'] ?? '');

$pdo = get_db_connection();

$sql = 'SELECT u.id, u.name, tp.subjects, tp.bio, tp.hourly_rate, tp.location
        FROM users u
        JOIN tutor_profiles tp ON tp.user_id = u.id
        WHERE u.role = "tutor"';
$params = [];

if ($subject !== '') {
    $sql .= ' AND tp.subjects LIKE :subject';
    $params['subject'] = '%' . $subject . '%';
}

if ($location !== '') {
    $sql .= ' AND tp.location LIKE :location';
    $params['location'] = '%' . $location . '%';
}

$sql .= ' ORDER BY u.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tutors = $stmt->fetchAll();

echo json_encode(['success' => true, 'tutors' => $tutors]);
