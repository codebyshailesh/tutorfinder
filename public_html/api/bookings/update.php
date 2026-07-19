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

$bookingId = (int) ($input['booking_id'] ?? 0);
$action    = $input['action'] ?? '';

$allowedActions = ['accept', 'decline', 'cancel', 'complete'];
if ($bookingId <= 0 || !in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'booking_id and a valid action are required']);
    exit;
}

$pdo = get_db_connection();
$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    echo json_encode(['error' => 'Booking not found']);
    exit;
}

$isTutor   = $user['role'] === 'tutor' && (int) $booking['tutor_id'] === (int) $user['id'];
$isStudent = $user['role'] === 'student' && (int) $booking['student_id'] === (int) $user['id'];

if (!$isTutor && !$isStudent) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this booking']);
    exit;
}

// Only the tutor can accept/decline. Either party can cancel a pending/accepted booking.
// Only the tutor can mark a session complete.
$newStatus = match ($action) {
    'accept'   => $isTutor ? 'accepted' : null,
    'decline'  => $isTutor ? 'declined' : null,
    'complete' => $isTutor ? 'completed' : null,
    'cancel'   => in_array($booking['status'], ['pending', 'accepted'], true) ? 'cancelled' : null,
    default    => null,
};

if ($newStatus === null) {
    http_response_code(403);
    echo json_encode(['error' => 'You are not allowed to perform this action on this booking']);
    exit;
}

$stmt = $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id');
$stmt->execute(['status' => $newStatus, 'id' => $bookingId]);

echo json_encode(['success' => true, 'status' => $newStatus]);
