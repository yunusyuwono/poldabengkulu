<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class Auth {
    public static function login(string $username, string $password): array {
        $db = db();

        $user = $db->fetchOne(
            "SELECT * FROM users WHERE username = ?",
            [self::sanitizeInput($username)]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Username atau password salah.'];
        }

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            return ['success' => false, 'message' => "Akun terkunci. Coba lagi dalam {$remaining} menit."];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $attempts = $user['failed_attempts'] + 1;
            $lockedUntil = null;

            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
                $attempts = 0;
            }

            $db->query(
                "UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?",
                [$attempts, $lockedUntil, $user['id']]
            );

            $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
            if ($lockedUntil) {
                return ['success' => false, 'message' => 'Terlalu banyak percobaan. Akun dikunci selama 15 menit.'];
            }
            return ['success' => false, 'message' => "Password salah. Sisa percobaan: {$remaining}."];
        }

        $db->query(
            "UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = CURRENT_TIMESTAMP WHERE id = ?",
            [$user['id']]
        );

        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['login_time']    = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip']            = self::getClientIP();
        $_SESSION['user_agent']    = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return ['success' => true, 'message' => 'Login berhasil.'];
    }

    public static function logout(): void {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    public static function isLoggedIn(): bool {
        if (!isset($_SESSION['user_id'])) return false;

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            self::logout();
            return false;
        }

        if (isset($_SESSION['ip']) && $_SESSION['ip'] !== self::getClientIP()) {
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/admin/index.php?msg=session_expired');
            exit;
        }
    }

    public static function requireRole(string $role): void {
        self::requireLogin();
        if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'superadmin') {
            header('Location: ' . APP_URL . '/admin/dashboard.php?msg=unauthorized');
            exit;
        }
    }

    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return db()->fetchOne("SELECT id, username, full_name, email, role FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }

    public static function changePassword(int $userId, string $newPassword): bool {
        if (strlen($newPassword) < 8) return false;
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        return db()->update('users', ['password_hash' => $hash], 'id = :id', [':id' => $userId]) > 0;
    }

    private static function sanitizeInput(?string $input): string {
        return trim(strip_tags((string)$input));
    }

    public static function getClientIP(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
}
