<?php
require_once __DIR__ . '/db.php';

const AUTH_COOKIE_NAME  = 'tf_auth_token';
const TOKEN_LIFETIME_DAYS = 7;

/**
 * Create a new auth token for a user, store it, and set the cookie.
 */
function create_auth_token(int $userId): string
{
    $pdo   = get_db_connection();
    $token = bin2hex(random_bytes(32)); // 64 hex chars
    $expiresAt = (new DateTime())->modify('+' . TOKEN_LIFETIME_DAYS . ' days')->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare(
        'INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)'
    );
    $stmt->execute([
        'user_id'    => $userId,
        'token'      => $token,
        'expires_at' => $expiresAt,
    ]);

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie(AUTH_COOKIE_NAME, $token, [
        'expires'  => time() + (TOKEN_LIFETIME_DAYS * 86400),
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    return $token;
}

/**
 * Look up the currently authenticated user from the auth cookie.
 * Returns the user row (without password_hash) or null if not authenticated.
 */
function get_authenticated_user(): ?array
{
    if (empty($_COOKIE[AUTH_COOKIE_NAME])) {
        return null;
    }

    $token = $_COOKIE[AUTH_COOKIE_NAME];
    $pdo   = get_db_connection();

    $stmt = $pdo->prepare(
        'SELECT u.id, u.name, u.email, u.role, u.created_at
         FROM auth_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token = :token AND t.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    return $user ?: null;
}

/**
 * Require authentication for a page or API endpoint.
 * Sends a 401 JSON response and stops execution if not authenticated.
 */
function require_auth(): array
{
    $user = get_authenticated_user();

    if ($user === null) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    return $user;
}

/**
 * Require authentication for a regular web page.
 * Redirects to the login page (with a return-to url) if not authenticated.
 */
function require_web_auth(): array
{
    $user = get_authenticated_user();

    if ($user === null) {
        $redirectTo = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?redirect=' . $redirectTo);
        exit;
    }

    return $user;
}

/**
 * Invalidate the current auth token (logout).
 */
function invalidate_auth_token(): void
{
    if (!empty($_COOKIE[AUTH_COOKIE_NAME])) {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('DELETE FROM auth_tokens WHERE token = :token');
        $stmt->execute(['token' => $_COOKIE[AUTH_COOKIE_NAME]]);

        setcookie(AUTH_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
