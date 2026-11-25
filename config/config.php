<?php

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('DATA_PATH', BASE_PATH . '/data');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'poldabengkulu');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'Polda Bengkulu');
define('APP_URL', 'http://localhost/poldabengkulu');
define('APP_VERSION', '1.0.0');

define('CSRF_TOKEN_NAME', '_csrf_token');
define('CSRF_TOKEN_LENGTH', 64);
define('SESSION_LIFETIME', 1800); 
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); 

define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); 
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);

define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'Admin@Polda2024!');

define('RATE_LIMIT_REQUESTS', 60);
define('RATE_LIMIT_WINDOW', 60);

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
