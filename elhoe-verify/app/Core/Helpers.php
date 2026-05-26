<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Stateless utility helpers. Intentionally short and side-effect free
 * (except for csrf_token() which seeds $_SESSION).
 */
final class Helpers
{
    /** HTML-escape a value for echo into templates. */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Trim + collapse whitespace, return null for empty. */
    public static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        return $value === '' ? null : $value;
    }

    /** Normalise a verification code: uppercase, strip non-alnum and dashes. */
    public static function normalizeCode(string $raw): string
    {
        $raw = strtoupper(trim($raw));
        return preg_replace('/[^A-Z0-9\-]/', '', $raw) ?? '';
    }

    /** Resolve the real client IP, honouring CF / proxy headers. */
    public static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP']  ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR']   ?? null,
            $_SERVER['HTTP_X_REAL_IP']         ?? null,
            $_SERVER['REMOTE_ADDR']            ?? null,
        ];
        foreach ($candidates as $c) {
            if (!$c) {
                continue;
            }
            // X-Forwarded-For may be "client, proxy1, proxy2"
            $first = trim(explode(',', $c)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        return '0.0.0.0';
    }

    /** Append marketing UTM params to a product URL. */
    public static function withUtm(string $url, int $productId): string
    {
        if ($url === '') {
            return '';
        }
        $sep = str_contains($url, '?') ? '&' : '?';
        $utm = http_build_query([
            'utm_source'   => 'verify_portal',
            'utm_medium'   => 'referral',
            'utm_campaign' => 'repurchase',
            'utm_content'  => (string) $productId,
        ]);
        return $url . $sep . $utm;
    }

    // ---------- CSRF ----------
    public static function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function csrfCheck(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return is_string($token)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    // ---------- HTTP responses ----------
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }
}
