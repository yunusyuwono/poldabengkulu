<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';

class RateLimit {
    public static function check(string $endpoint, int $maxRequests = RATE_LIMIT_REQUESTS, int $windowSeconds = RATE_LIMIT_WINDOW): bool {
        $ip = Auth::getClientIP();
        $now = time();
        $windowStart = $now - $windowSeconds;

        try {
            $db = db();

            $db->query("DELETE FROM rate_limit WHERE window_start < ?", [$windowStart]);

            $record = $db->fetchOne(
                "SELECT id, attempts FROM rate_limit WHERE ip = ? AND endpoint = ? AND window_start >= ?",
                [$ip, $endpoint, $windowStart]
            );

            if ($record) {
                if ($record['attempts'] >= $maxRequests) {
                    return false; 
                }
                $db->query(
                    "UPDATE rate_limit SET attempts = attempts + 1 WHERE id = ?",
                    [$record['id']]
                );
            } else {
                $db->insert('rate_limit', [
                    'ip' => $ip,
                    'endpoint' => $endpoint,
                    'attempts' => 1,
                    'window_start' => $now,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Rate limit error: ' . $e->getMessage());
            return true; 
        }
    }

    public static function enforce(string $endpoint, int $maxRequests = RATE_LIMIT_REQUESTS, int $windowSeconds = RATE_LIMIT_WINDOW): void {
        if (!self::check($endpoint, $maxRequests, $windowSeconds)) {
            http_response_code(429);
            header('Retry-After: ' . $windowSeconds);
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Terlalu banyak permintaan. Coba lagi nanti.']);
            } else {
                echo '<h1>429 - Too Many Requests</h1><p>Terlalu banyak permintaan. Coba lagi dalam beberapa menit.</p>';
            }
            exit;
        }
    }

    public static function enforceLoginLimit(): void {
        self::enforce('login', MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION);
    }
}
