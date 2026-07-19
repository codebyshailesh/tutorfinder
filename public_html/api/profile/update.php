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

$name = trim($input['name'] ?? '');
if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name is required']);
    exit;
}

$pdo = get_db_connection();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE users SET name = :name WHERE id = :id');
    $stmt->execute(['name' => $name, 'id' => $user['id']]);

    if ($user['role'] === 'tutor') {
        $subjects   = trim($input['subjects'] ?? '');
        $bio        = trim($input['bio'] ?? '');
        $hourlyRate = $input['hourly_rate'] ?? null;
        $location   = trim($input['location'] ?? '');

        $stmt = $pdo->prepare('SELECT id FROM tutor_profiles WHERE user_id = :uid');
        $stmt->execute(['uid' => $user['id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare(
                'UPDATE tutor_profiles
                 SET subjects = :subjects, bio = :bio, hourly_rate = :hourly_rate, location = :location
                 WHERE user_id = :uid'
            );
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO tutor_profiles (subjects, bio, hourly_rate, location, user_id)
                 VALUES (:subjects, :bio, :hourly_rate, :location, :uid)'
            );
        }

        $stmt->execute([
            'subjects'    => $subjects,
            'bio'         => $bio,
            'hourly_rate' => $hourlyRate !== null && $hourlyRate !== '' ? $hourlyRate : null,
            'location'    => $location,
            'uid'         => $user['id'],
        ]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Profile update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not update profile']);
    exit;
}

echo json_encode(['success' => true]);
