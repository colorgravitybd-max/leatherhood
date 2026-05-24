<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/Bootstrap.php';

use LH\Core\Auth;
use LH\Core\Helpers;

// Map URL → view file (kept simple to avoid a heavy router in admin)
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim(preg_replace('#^.*?/admin#', '', $path), '/');
if ($path === '/') $path = '/dashboard';

// Public routes (no auth)
$public = ['/login', '/logout'];
if (!in_array($path, $public, true)) Auth::require();

switch ($path) {
    case '/login':       require __DIR__ . '/views/login.php';        break;
    case '/logout':      Auth::logout(); Helpers::redirect(Helpers::adminUrl('login'));
    case '/dashboard':   require __DIR__ . '/views/dashboard.php';    break;
    case '/orders':      require __DIR__ . '/views/orders/index.php'; break;
    case '/order':       require __DIR__ . '/views/orders/show.php';  break;
    case '/products':    require __DIR__ . '/views/products/index.php'; break;
    case '/product':     require __DIR__ . '/views/products/edit.php';  break;
    case '/customers':   require __DIR__ . '/views/customers/index.php';break;
    case '/customer':    require __DIR__ . '/views/customers/show.php'; break;
    case '/pages':       require __DIR__ . '/views/pages/index.php';   break;
    case '/page':        require __DIR__ . '/views/pages/edit.php';    break;
    case '/reviews':     require __DIR__ . '/views/reviews/index.php'; break;
    case '/geo':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') require __DIR__ . '/actions/geo_update.php';
        else require __DIR__ . '/views/geo/index.php';
        break;
    case '/users':       require __DIR__ . '/views/users/index.php';   break;
    case '/marketing':   require __DIR__ . '/views/marketing/index.php';break;
    case '/settings':    require __DIR__ . '/views/settings/index.php';break;
    case '/cloudflare':  require __DIR__ . '/actions/cloudflare.php';  break;
    case '/courier':     require __DIR__ . '/actions/courier.php';     break;
    case '/order/verify':require __DIR__ . '/actions/order_verify.php';break;
    case '/order/cancel':require __DIR__ . '/actions/order_cancel.php';break;
    case '/order/status':require __DIR__ . '/actions/order_status.php';break;
    case '/media/upload':require __DIR__ . '/actions/media_upload.php';break;
    case '/invoices/bulk':require __DIR__ . '/actions/invoices_bulk.php';break;
    default:
        http_response_code(404);
        echo '<h1>404</h1><p>Admin page not found.</p>';
}
