<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$name     = trim($input['name'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role     = $input['role'] ?? 'student';

if ($role !== 'tutor') {
    $role = 'student';
}

if ($name === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name, email, and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters']);
    exit;
}

$pdo = get_db_connection();

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'An account with that email already exists']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
    );
    $stmt->execute([
        'name'          => $name,
        'email'         => $email,
        'password_hash' => $passwordHash,
        'role'          => $role,
    ]);

    $userId = (int) $pdo->lastInsertId();

    // If registering as a tutor, create an (initially empty) tutor profile.
    if ($role === 'tutor') {
        $subjects   = trim($input['subjects'] ?? '');
        $bio        = trim($input['bio'] ?? '');
        $hourlyRate = $input['hourly_rate'] ?? null;
        $location   = trim($input['location'] ?? '');

        $stmt = $pdo->prepare(
            'INSERT INTO tutor_profiles (user_id, subjects, bio, hourly_rate, location)
             VALUES (:user_id, :subjects, :bio, :hourly_rate, :location)'
        );
        $stmt->execute([
            'user_id'     => $userId,
            'subjects'    => $subjects,
            'bio'         => $bio,
            'hourly_rate' => $hourlyRate !== null && $hourlyRate !== '' ? $hourlyRate : null,
            'location'    => $location,
        ]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Registration failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed. Please try again.']);
    exit;
}

create_auth_token($userId);

echo json_encode([
    'success' => true,
    'user' => [
        'id'    => $userId,
        'name'  => $name,
        'email' => $email,
        'role'  => $role,
    ],
]);
