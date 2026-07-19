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

if ($user['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Only students can request bookings']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$tutorId  = (int) ($input['tutor_id'] ?? 0);
$subject  = trim($input['subject'] ?? '');
$time     = trim($input['requested_time'] ?? '');
$duration = (int) ($input['duration_minutes'] ?? 60);
$note     = trim($input['note'] ?? '');

if ($tutorId <= 0 || $subject === '' || $time === '') {
    http_response_code(400);
    echo json_encode(['error' => 'tutor_id, subject, and requested_time are required']);
    exit;
}

$dt = DateTime::createFromFormat('Y-m-d\TH:i', $time) ?: DateTime::createFromFormat('Y-m-d H:i:s', $time);
if (!$dt) {
    http_response_code(400);
    echo json_encode(['error' => 'requested_time must be a valid date/time']);
    exit;
}

if ($duration < 15 || $duration > 480) {
    $duration = 60;
}

$pdo = get_db_connection();

// Confirm the target user is actually a tutor.
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = "tutor"');
$stmt->execute(['id' => $tutorId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Tutor not found']);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO bookings (student_id, tutor_id, subject, requested_time, duration_minutes, note, status)
     VALUES (:student_id, :tutor_id, :subject, :requested_time, :duration_minutes, :note, "pending")'
);
$stmt->execute([
    'student_id'       => $user['id'],
    'tutor_id'         => $tutorId,
    'subject'          => $subject,
    'requested_time'   => $dt->format('Y-m-d H:i:s'),
    'duration_minutes' => $duration,
    'note'             => $note !== '' ? $note : null,
]);

$bookingId = (int) $pdo->lastInsertId();

// Send an automatic first message to the tutor so the conversation has context.
if ($note !== '') {
    $stmt = $pdo->prepare(
        'INSERT INTO messages (booking_id, sender_id, recipient_id, body)
         VALUES (:booking_id, :sender_id, :recipient_id, :body)'
    );
    $stmt->execute([
        'booking_id'   => $bookingId,
        'sender_id'    => $user['id'],
        'recipient_id' => $tutorId,
        'body'         => 'Booking request for ' . $subject . ': ' . $note,
    ]);
}

echo json_encode(['success' => true, 'booking_id' => $bookingId]);
