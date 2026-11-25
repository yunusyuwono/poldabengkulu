<?php

require_once dirname(__DIR__) . '/config/config.php';

class CSRF {
    public static function generateToken(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function getToken(): string {
        return self::generateToken();
    }

    public static function validateToken(string $token): bool {
        if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    public static function validateRequest(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::validateToken($token)) {
                http_response_code(403);
                die(json_encode(['error' => 'CSRF token tidak valid. Refresh halaman dan coba lagi.']));
            }
            
            self::regenerateToken();
        }
    }

    public static function regenerateToken(): void {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
    }

    public static function field(): string {
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . self::getToken() . '">';
    }

    public static function meta(): string {
        return '<meta name="csrf-token" content="' . self::getToken() . '">';
    }
}
