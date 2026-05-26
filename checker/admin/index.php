<?php
declare(strict_types=1);

/**
 * ELHOE Verify - admin front controller.
 *
 * Routes:
 *   ?route=login                    GET  - login form
 *   ?route=login          (POST)    POST - login attempt
 *   ?route=logout                   GET
 *   ?route=dashboard       (default)
 *   ?route=products
 *   ?route=products/edit&id=N
 *   ?route=products/save  (POST)
 *   ?route=products/delete&id=N (POST)
 *   ?route=codes
 *   ?route=codes/generate
 *   ?route=codes/generate (POST)
 *   ?route=codes/export&batch=...
 *   ?route=codes/import   (POST)
 *   ?route=insights/geo
 *   ?route=insights/radar
 *   ?route=insights/expiry
 *   ?route=logs
 */

require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Auth;
use App\Core\Helpers;

Auth::start();

$route = (string) ($_GET['route'] ?? 'dashboard');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---- Public routes (no auth) ----
if ($route === 'login') {
    if ($method === 'POST') {
        if (!Helpers::csrfCheck($_POST['csrf'] ?? '')) {
            Helpers::flash('error', 'Session expired. Please try again.');
            Helpers::redirect('/admin/?route=login');
        }
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $pass  = (string) ($_POST['password'] ?? '');
        if (Auth::attempt($email, $pass)) {
            Helpers::redirect('/admin/?route=dashboard');
        }
        // Slow down brute force a little
        usleep(400_000);
        Helpers::flash('error', 'Invalid credentials.');
        Helpers::redirect('/admin/?route=login');
    }
    require __DIR__ . '/views/login.php';
    exit;
}

if ($route === 'logout') {
    Auth::logout();
    Helpers::redirect('/admin/?route=login');
}

// ---- Protected routes ----
Auth::requireLogin('/admin/?route=login');

switch ($route) {
    case 'dashboard':
        require __DIR__ . '/views/dashboard.php'; break;

    case 'products':
        require __DIR__ . '/views/products/index.php'; break;
    case 'products/edit':
        require __DIR__ . '/views/products/edit.php'; break;
    case 'products/save':
        require __DIR__ . '/actions/product_save.php'; break;
    case 'products/delete':
        require __DIR__ . '/actions/product_delete.php'; break;

    case 'codes':
        require __DIR__ . '/views/codes/index.php'; break;
    case 'codes/generate':
        if ($method === 'POST') { require __DIR__ . '/actions/batch_generate.php'; }
        else                    { require __DIR__ . '/views/codes/generate.php'; }
        break;
    case 'codes/paste':
        require __DIR__ . '/actions/batch_paste.php'; break;
    case 'codes/export':
        require __DIR__ . '/actions/batch_export_csv.php'; break;
    case 'codes/import':
        require __DIR__ . '/actions/batch_import_csv.php'; break;

    case 'insights/geo':
        require __DIR__ . '/views/insights/geo.php'; break;
    case 'insights/radar':
        require __DIR__ . '/views/insights/radar.php'; break;
    case 'insights/expiry':
        require __DIR__ . '/views/insights/expiry.php'; break;

    case 'logs':
        require __DIR__ . '/views/logs/index.php'; break;

    default:
        http_response_code(404);
        echo '<p style="font-family:sans-serif;padding:40px">Unknown route.</p>';
}
