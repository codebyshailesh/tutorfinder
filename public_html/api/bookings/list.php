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

if ($user['role'] === 'tutor') {
    $stmt = $pdo->prepare(
        'SELECT b.id, b.subject, b.requested_time, b.duration_minutes, b.status, b.note, b.created_at,
                u.id AS other_user_id, u.name AS other_user_name
         FROM bookings b
         JOIN users u ON u.id = b.student_id
         WHERE b.tutor_id = :uid
         ORDER BY b.requested_time DESC'
    );
} else {
    $stmt = $pdo->prepare(
        'SELECT b.id, b.subject, b.requested_time, b.duration_minutes, b.status, b.note, b.created_at,
                u.id AS other_user_id, u.name AS other_user_name
         FROM bookings b
         JOIN users u ON u.id = b.tutor_id
         WHERE b.student_id = :uid
         ORDER BY b.requested_time DESC'
    );
}

$stmt->execute(['uid' => $user['id']]);
$bookings = $stmt->fetchAll();

echo json_encode(['success' => true, 'bookings' => $bookings]);
