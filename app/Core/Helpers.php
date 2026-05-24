<?php
declare(strict_types=1);

namespace LH\Core;

final class Helpers
{
    /** Format BDT currency with the Taka symbol. */
    public static function bdt(float|int|string $amount, bool $withSymbol = true): string
    {
        $n = number_format((float)$amount, 0, '.', ',');
        return $withSymbol ? '৳'.$n : $n;
    }

    /** Slugify a string. ASCII fallback (works without ext-intl). */
    public static function slug(string $text, string $sep = '-'): string
    {
        $text = trim($text);
        $text = preg_replace('~[^\\pL\\d]+~u', $sep, $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, $sep);
        $text = preg_replace('~-+~', $sep, $text);
        return strtolower($text ?: bin2hex(random_bytes(4)));
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function checkCsrf(string $token): bool
    {
        return !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function redirect(string $url, int $code = 302): never
    {
        header('Location: '.$url, true, $code);
        exit;
    }

    public static function json(array|object $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function input(string $key, $default = null)
    {
        $src = $_POST[$key] ?? $_GET[$key] ?? $default;
        if (is_string($src)) $src = trim($src);
        return $src;
    }

    public static function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = explode(',', (string)$_SERVER[$h])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }

    public static function generateOrderNumber(): string
    {
        return 'LH'.date('ymd').strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    public static function clamp(int $n, int $min, int $max): int
    {
        return max($min, min($max, $n));
    }

    public static function asset(string $path): string
    {
        $base = rtrim($GLOBALS['LH_CFG']['app']['url'] ?? '', '/');
        return $base.'/assets/'.ltrim($path, '/');
    }

    public static function url(string $path = '/'): string
    {
        $base = rtrim($GLOBALS['LH_CFG']['app']['url'] ?? '', '/');
        return $base.'/'.ltrim($path, '/');
    }

    public static function adminUrl(string $path = '/'): string
    {
        $base = rtrim($GLOBALS['LH_CFG']['app']['admin_url'] ?? '/admin', '/');
        return $base.'/'.ltrim($path, '/');
    }

    public static function setting(string $key, $default = null)
    {
        static $cache = null;
        if ($cache === null) {
            try {
                $rows = Database::i()->all('SELECT key_name, value FROM settings');
                $cache = [];
                foreach ($rows as $r) $cache[$r['key_name']] = $r['value'];
            } catch (\Throwable $e) { $cache = []; }
        }
        return $cache[$key] ?? $default;
    }

    public static function flash(string $key, ?string $value = null)
    {
        if ($value === null) {
            $v = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $v;
        }
        $_SESSION['_flash'][$key] = $value;
    }
}
