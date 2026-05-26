<?php
declare(strict_types=1);

namespace App\Core;

/**
 * IP-based rate limiter for the verification endpoint.
 *
 * Counts only INVALID attempts in the most recent window. We re-use the
 * `scan_logs` table (already indexed by ip_address + scanned_at) instead
 * of standing up Redis or a dedicated bucket table. Cheap and accurate.
 */
final class RateLimiter
{
    /**
     * @return array{blocked:bool, retry_after:int, fails:int}
     */
    public static function check(string $ip): array
    {
        $window = RATE_LIMIT_WINDOW_SEC;
        $max    = RATE_LIMIT_MAX;

        $sql = 'SELECT COUNT(*) FROM scan_logs
                 WHERE ip_address = ?
                   AND is_valid = 0
                   AND scanned_at >= (NOW() - INTERVAL ? SECOND)';
        $fails = (int) Database::scalar($sql, [$ip, $window]);

        if ($fails >= $max) {
            return [
                'blocked'     => true,
                'retry_after' => $window,
                'fails'       => $fails,
            ];
        }
        return ['blocked' => false, 'retry_after' => 0, 'fails' => $fails];
    }
}
