<?php
declare(strict_types=1);

/**
 * ELHOE Verify - global configuration.
 *
 * Loads .env (if present) and exposes constants used across the app.
 * Keep this file the SINGLE source of truth for environment values.
 */

// ---------- .env loader (zero-dependency) ----------
$envPath = dirname(__DIR__) . '/.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        // strip optional surrounding quotes
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[0] === substr($v, -1)) {
            $v = substr($v, 1, -1);
        }
        if (!array_key_exists($k, $_ENV)) {
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }
    }
}

function env(string $key, mixed $default = null): mixed {
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') {
        return $default;
    }
    return $v;
}

// ---------- App ----------
define('APP_NAME',     env('APP_NAME', 'ELHOE Verify'));
define('APP_ENV',      env('APP_ENV', 'production'));
define('APP_DEBUG',    filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));
define('APP_BASE_URL', rtrim((string) env('APP_BASE_URL', ''), '/'));
// Public sub-folder, e.g. "/checker" when installed at https://elhoe.com/checker
// Use "" if the app is mounted at the domain root.
define('BASE_PATH',    rtrim((string) env('APP_BASE_PATH', '/checker'), '/'));
date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

// ---------- Database ----------
define('DB_HOST',    (string) env('DB_HOST', '127.0.0.1'));
define('DB_PORT',    (int)    env('DB_PORT', 3306));
define('DB_NAME',    (string) env('DB_NAME', 'elhoe_verify'));
define('DB_USER',    (string) env('DB_USER', 'root'));
define('DB_PASS',    (string) env('DB_PASS', ''));
define('DB_CHARSET', (string) env('DB_CHARSET', 'utf8mb4'));

// ---------- Turnstile ----------
define('TURNSTILE_SITE_KEY',   (string) env('TURNSTILE_SITE_KEY', ''));
define('TURNSTILE_SECRET_KEY', (string) env('TURNSTILE_SECRET_KEY', ''));

// ---------- Rate limiter ----------
define('RATE_LIMIT_MAX',        (int) env('RATE_LIMIT_MAX', 5));
define('RATE_LIMIT_WINDOW_SEC', (int) env('RATE_LIMIT_WINDOW_SEC', 600));

// ---------- Geo IP ----------
define('GEOIP_ENDPOINT',    (string) env('GEOIP_ENDPOINT', 'http://ip-api.com/json/'));
define('GEOIP_TIMEOUT_SEC', (int)    env('GEOIP_TIMEOUT_SEC', 2));

// ---------- Admin session ----------
define('ADMIN_SESSION_NAME',     (string) env('ADMIN_SESSION_NAME', 'elhoe_admin'));
define('ADMIN_SESSION_LIFETIME', (int)    env('ADMIN_SESSION_LIFETIME', 10800));

// ---------- Paths ----------
define('APP_ROOT',     dirname(__DIR__));
define('UPLOAD_DIR',   APP_ROOT . '/assets/uploads');
define('UPLOAD_URL',   BASE_PATH . '/assets/uploads');

// ---------- Error handling ----------
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ---------- Autoloader (PSR-4-ish, zero deps) ----------
spl_autoload_register(static function (string $class): void {
    // Map "App\Core\Database" → /app/Core/Database.php
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $path = APP_ROOT . '/app/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (is_file($path)) {
        require $path;
    }
}); 
