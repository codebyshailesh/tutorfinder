<?php
// Database connection config.
// Update these values with your own database credentials.

define('DB_HOST', 'sql301.infinityfree.com');
define('DB_NAME', 'if0_42447065_tutorfinder');
define('DB_USER', 'if0_42447065');
define('DB_PASS', '5WEM9ONEjFvI');
define('DB_CHARSET', 'utf8mb4');

function get_db_connection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Don't leak connection details to the client.
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('Database connection error.');
        }
    }

    return $pdo;
}
