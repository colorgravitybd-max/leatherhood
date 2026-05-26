<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Admin authentication. Cookie-based PHP session, hardened defaults.
 */
final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name(ADMIN_SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => ADMIN_SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Idle expiration
        if (!empty($_SESSION['_last']) && (time() - (int) $_SESSION['_last']) > ADMIN_SESSION_LIFETIME) {
            self::logout();
        }
        $_SESSION['_last'] = time();
    }

    public static function attempt(string $email, string $password): bool
    {
        self::start();
        $user = Database::one(
            'SELECT id, name, email, password_hash, role FROM admin_users WHERE email = ? LIMIT 1',
            [$email]
        );
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        // Rotate session id to prevent fixation
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        Database::run('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?', [(int) $user['id']]);
        return true;
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['admin'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(string $loginUrl = '/admin/?route=login'): void
    {
        if (!self::check()) {
            Helpers::redirect($loginUrl);
        }
    }

    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
