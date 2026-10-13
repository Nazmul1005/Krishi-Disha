<?php
function env(string $key, string $default = ''): string { return getenv($key) === false ? $default : (string)getenv($key); }
date_default_timezone_set('Asia/Dhaka');
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_URL', rtrim(env('APP_URL', 'http://localhost:8080'), '/'));
define('APP_KEY', env('APP_KEY'));
define('STORAGE_PATH', env('STORAGE_PATH', dirname(__DIR__) . '/storage'));
if (APP_ENV === 'production' && (strlen(APP_KEY) < 32 || !str_starts_with(APP_URL, 'https://'))) {
    throw new RuntimeException('Production requires APP_KEY and an HTTPS APP_URL.');
}
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['lifetime'=>0, 'path'=>'/KrishiDisha/', 'secure'=>APP_ENV === 'production', 'httponly'=>true, 'samesite'=>'Lax']);
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (APP_ENV === 'production') header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    set_exception_handler(function (Throwable $error): void {
        error_log(get_class($error) . ': ' . $error->getMessage());
        if(http_response_code()<400) http_response_code($error instanceof InvalidArgumentException ? 422 : 500);
        echo $error instanceof InvalidArgumentException ? htmlspecialchars($error->getMessage(),ENT_QUOTES,'UTF-8') : 'The request could not be completed. Please try again or contact support.';
    });
}
