<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

header('Content-Type: application/json');

invalidate_auth_token();

echo json_encode(['success' => true]);
