<?php
declare(strict_types=1);

namespace LH\Core;

/**
 * Admin authentication + RBAC.  Customers use a separate (lighter) flow
 * driven by the OTP verification engine, not this class.
 */
final class Auth
{
    private const ROLE_PERMS = [
        'super_admin' => ['*'],
        'admin'       => [
            'orders.*','products.*','customers.*','pages.*','reviews.*',
            'geo.*','marketing.*','settings.read','settings.write',
            'users.read','users.write','cloudflare.purge','couriers.push',
            'reports.*','media.*'
        ],
        'moderator'   => [
            'orders.read','orders.update','products.read','products.update',
            'customers.read','reviews.*','pages.read','pages.update',
            'media.read','media.upload','geo.read'
        ],
        'support'     => [
            'orders.read','customers.read','reviews.read','reviews.update'
        ],
    ];

    public static function login(string $email, string $password): bool
    {
        $u = Database::i()->one(
            'SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1', [$email]);
        if (!$u || !password_verify($password, $u['password'])) return false;

        $_SESSION['admin_id']    = (int)$u['id'];
        $_SESSION['admin_role']  = $u['role'];
        $_SESSION['admin_name']  = $u['name'];
        $_SESSION['admin_email'] = $u['email'];

        Database::i()->update('users',
            ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => Helpers::ip()],
            'id = :id', [':id' => $u['id']]);

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_role'],
              $_SESSION['admin_name'], $_SESSION['admin_email']);
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['admin_id'])) return null;
        return [
            'id'    => (int)$_SESSION['admin_id'],
            'role'  => $_SESSION['admin_role'] ?? 'support',
            'name'  => $_SESSION['admin_name'] ?? '',
            'email' => $_SESSION['admin_email'] ?? '',
        ];
    }

    public static function check(): bool { return !empty($_SESSION['admin_id']); }

    public static function require(): void
    {
        if (!self::check()) Helpers::redirect(Helpers::adminUrl('login.php'));
    }

    public static function can(string $perm): bool
    {
        $u = self::user();
        if (!$u) return false;
        $list = self::ROLE_PERMS[$u['role']] ?? [];
        if (in_array('*', $list, true)) return true;
        foreach ($list as $p) {
            if ($p === $perm) return true;
            // Wildcard matching: "orders.*" matches "orders.read"
            if (str_ends_with($p, '.*')) {
                $prefix = substr($p, 0, -2);
                if (str_starts_with($perm, $prefix.'.')) return true;
            }
        }
        return false;
    }

    public static function gate(string $perm): void
    {
        if (!self::can($perm)) {
            http_response_code(403);
            exit('Forbidden — missing permission: '.$perm);
        }
    }
}
