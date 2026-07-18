<?php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');   // 'localhost' for XAMPP, 'db' injected via Docker
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'krishidisha');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Log the real reason server-side; never leak DB internals to the client.
    error_log('KrishiDisha DB connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center;color:#334155;">'
        . '<h2 style="color:#1b4332;">🌱 KrishiDisha</h2>'
        . '<p>We could not reach the database right now. Please try again in a moment.</p>'
        . '</div>');
}
