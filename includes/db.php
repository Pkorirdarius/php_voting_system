<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_app');
define('DB_USER', 'root');        // ← change to your DB user
define('DB_PASS', 'root');            // ← change to your DB password
define('DB_CHARSET', 'utf8mb4');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<p style="color:red;font-family:monospace;padding:2rem">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>');
        }
    }
    return $pdo;
}
