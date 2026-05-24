<?php
/**
 * LeatherHood — Master Configuration
 * --------------------------------------------------------------
 * All env-specific values are read from environment variables (Hostinger
 * supports setenv via .htaccess) with sensible fallbacks for local dev.
 */

declare(strict_types=1);

if (!defined('LH_ROOT')) {
    define('LH_ROOT', dirname(__DIR__));
}

// ---------- Helper to read env with default ----------
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = getenv($key);
        if ($val === false || $val === '') {
            return $default;
        }
        if (in_array(strtolower($val), ['true', 'false'], true)) {
            return strtolower($val) === 'true';
        }
        return $val;
    }
}

return [

    'app' => [
        'name'        => 'LeatherHood',
        'tagline'     => 'Premium Leather. Crafted in Bangladesh.',
        'url'         => env('APP_URL', 'https://leatherhoodbd.com'),
        'admin_url'   => env('ADMIN_URL', 'https://leatherhoodbd.com/admin'),
        'env'         => env('APP_ENV', 'production'),
        'debug'       => (bool) env('APP_DEBUG', false),
        'timezone'    => 'Asia/Dhaka',
        'locale'      => 'en',
        'currency'    => 'BDT',
        'currency_sym'=> '৳',
        'jwt_secret'  => env('JWT_SECRET', 'change-me-in-production-please-32chars-min'),
        'session_lifetime' => 60 * 60 * 8, // 8h
    ],

    'db' => [
        'host'     => env('DB_HOST', 'localhost'),
        'port'     => (int) env('DB_PORT', 3306),
        'name'     => env('DB_NAME', 'leatherhood'),
        'user'     => env('DB_USER', 'root'),
        'pass'     => env('DB_PASS', ''),
        'charset'  => 'utf8mb4',
        'collate'  => 'utf8mb4_unicode_ci',
    ],

    'mail' => [
        'driver'   => env('MAIL_DRIVER', 'smtp'),
        'host'     => env('MAIL_HOST', 'smtp.sendgrid.net'),
        'port'     => (int) env('MAIL_PORT', 587),
        'user'     => env('MAIL_USER', ''),
        'pass'     => env('MAIL_PASS', ''),
        'from'     => env('MAIL_FROM', 'orders@leatherhoodbd.com'),
        'from_name'=> env('MAIL_FROM_NAME', 'LeatherHood'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    ],

    'sms' => [
        // Default provider: GreenWeb / BulkSMSBD style HTTP API
        'provider'   => env('SMS_PROVIDER', 'greenweb'),
        'api_url'    => env('SMS_API_URL', 'http://api.greenweb.com.bd/api.php'),
        'api_token'  => env('SMS_API_TOKEN', ''),
        'sender_id'  => env('SMS_SENDER_ID', '8809612440000'),
        'otp_length' => 4,
        'otp_ttl'    => 600, // seconds
    ],

    'cloudflare' => [
        'zone_id' => env('CF_ZONE_ID', ''),
        'token'   => env('CF_API_TOKEN', ''),
        'enabled' => (bool) env('CF_ENABLED', false),
    ],

    'meta' => [
        'pixel_id'      => env('META_PIXEL_ID', ''),
        'capi_token'    => env('META_CAPI_TOKEN', ''),
        'test_event_code' => env('META_TEST_EVENT_CODE', ''),
        'enabled'       => (bool) env('META_CAPI_ENABLED', false),
    ],

    'couriers' => [
        'steadfast' => [
            'base'     => env('SF_API_URL', 'https://portal.packzy.com/api/v1'),
            'api_key'  => env('SF_API_KEY', ''),
            'secret'   => env('SF_SECRET', ''),
        ],
        'pathao' => [
            'base'         => env('PATHAO_API_URL', 'https://api-hermes.pathao.com'),
            'client_id'    => env('PATHAO_CLIENT_ID', ''),
            'client_secret'=> env('PATHAO_CLIENT_SECRET', ''),
            'username'     => env('PATHAO_USER', ''),
            'password'     => env('PATHAO_PASS', ''),
            'store_id'     => env('PATHAO_STORE_ID', ''),
        ],
    ],

    'payments' => [
        'cod' => [
            'enabled' => true,
            'label'   => 'Cash on Delivery',
        ],
        'bkash' => [
            'enabled'   => (bool) env('BKASH_ENABLED', false),
            'mode'      => env('BKASH_MODE', 'sandbox'), // sandbox|live
            'app_key'   => env('BKASH_APP_KEY', ''),
            'app_secret'=> env('BKASH_APP_SECRET', ''),
            'username'  => env('BKASH_USERNAME', ''),
            'password'  => env('BKASH_PASSWORD', ''),
        ],
    ],

    'images' => [
        'driver'   => extension_loaded('imagick') ? 'imagick' : 'gd',
        'quality'  => 82,
        'max_w'    => 1600,
        'thumb_w'  => 600,
        'webp'     => true,
        'upload_dir' => LH_ROOT . '/public/assets/uploads',
    ],

    'shipping' => [
        'free_threshold' => 5000, // ৳
        'inside_dhaka_default' => 70,
        'outside_dhaka_default'=> 130,
    ],
];
