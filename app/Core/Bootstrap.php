<?php
declare(strict_types=1);

/**
 * Application bootstrap. Loaded by both /public/index.php and /admin/*.
 * Sets up the autoloader, sessions, error reporting, config and DB.
 */

if (!defined('LH_ROOT')) {
    define('LH_ROOT', dirname(__DIR__, 2));
}

// PSR-4-ish autoloader for namespace LH\
spl_autoload_register(function (string $class): void {
    $prefix = 'LH\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = LH_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require_once $file;
});

$cfg = require LH_ROOT . '/config/config.php';
$GLOBALS['LH_CFG'] = $cfg;

date_default_timezone_set($cfg['app']['timezone']);
mb_internal_encoding('UTF-8');

if ($cfg['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LH_ROOT . '/storage/logs/php-error.log');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Secure session
if (session_status() === PHP_SESSION_NONE) {
    $secure = (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => $cfg['app']['session_lifetime'],
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('LHSESSID');
    session_start();
}

LH\Core\Database::boot($cfg['db']);
