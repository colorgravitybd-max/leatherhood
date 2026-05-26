<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/Bootstrap.php';

use LH\Core\Router;
use LH\Core\Helpers;
use LH\Core\Database;
use LH\Services\CartService;
use LH\Services\CheckoutService;
use LH\Services\OtpService;
use LH\Services\ShippingService;
use LH\Services\MetaCAPI;

$router = new Router();

/* ---------------------- Pages ---------------------- */
$router->get('/',        function () { require LH_ROOT . '/public/views/home.php'; });
$router->get('/shop',    function () { require LH_ROOT . '/public/views/shop.php'; });
$router->get('/contact', function () { require LH_ROOT . '/public/views/contact.php'; });
$router->get('/cart',    function () { require LH_ROOT . '/public/views/cart.php'; });
$router->get('/checkout',function () { require LH_ROOT . '/public/views/checkout.php'; });
$router->get('/product/{slug}', function ($p) {
    $GLOBALS['slug'] = $p['slug'];
    require LH_ROOT . '/public/views/product.php';
});
$router->get('/page/{slug}', function ($p) {
    $GLOBALS['slug'] = $p['slug'];
    require LH_ROOT . '/public/views/page.php';
});
$router->get('/order/thank-you/{number}', function ($p) {
    $GLOBALS['order_number'] = $p['number'];
    require LH_ROOT . '/public/views/thankyou.php';
});

/* ---------------------- POST endpoints ---------------------- */
$router->post('/cart/add', function () {
    if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'Bad CSRF'], 403);
    CartService::add(
        (int)Helpers::input('product_id'),
        Helpers::input('variation_id') ? (int)Helpers::input('variation_id') : null,
        max(1, (int)(Helpers::input('qty', 1)))
    );
    Helpers::json(['ok'=>true, 'count'=>CartService::count()]);
});
$router->post('/cart/update', function () {
    if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'Bad CSRF'], 403);
    CartService::update((string)Helpers::input('key'), max(0, (int)Helpers::input('qty', 1)));
    $hyd = CartService::hydrate();
    Helpers::json(['ok'=>true, 'count'=>CartService::count(), 'subtotal'=>$hyd['subtotal']]);
});
$router->post('/cart/remove', function () {
    if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'Bad CSRF'], 403);
    CartService::remove((string)Helpers::input('key'));
    Helpers::json(['ok'=>true, 'count'=>CartService::count()]);
});

/* ---------------------- Geo API for cascading dropdowns ---------------------- */
$router->get('/api/geo/divisions', fn() =>
    Helpers::json(['divisions' => ShippingService::divisions()]));
$router->get('/api/geo/districts/{id}', fn($p) =>
    Helpers::json(['districts' => ShippingService::districts((int)$p['id'])]));
$router->get('/api/geo/police-stations/{id}', fn($p) =>
    Helpers::json(['police_stations' => ShippingService::policeStations((int)$p['id'])]));

$router->post('/api/checkout/quote', function () {
    $cart = CartService::hydrate();
    $q = ShippingService::quote(
        (int)Helpers::input('district_id', 0) ?: null,
        (int)Helpers::input('police_station_id', 0) ?: null,
        $cart['subtotal']
    );
    $coupon  = trim((string)Helpers::input('coupon_code', ''));
    $discount = 0.0;
    if ($coupon !== '') {
        $row = Database::i()->one('SELECT * FROM coupons WHERE code = ? AND is_active = 1', [$coupon]);
        if ($row && (float)$row['min_subtotal'] <= $cart['subtotal']) {
            if ($row['type'] === 'percent') $discount = $cart['subtotal'] * ((float)$row['value'] / 100);
            elseif ($row['type'] === 'fixed') $discount = (float)$row['value'];
            elseif ($row['type'] === 'free_shipping') $q['fee'] = 0.0;
        }
    }
    $grand = max(0, $cart['subtotal'] - $discount + (float)$q['fee']);
    Helpers::json([
        'subtotal'    => $cart['subtotal'],
        'discount'    => $discount,
        'shipping'    => (float)$q['fee'],
        'grand'       => $grand,
        'deliverable' => $q['deliverable'],
        'message'     => $q['message'],
        'subtotal_fmt'=> Helpers::bdt($cart['subtotal']),
        'shipping_fmt'=> Helpers::bdt($q['fee']),
        'grand_fmt'   => Helpers::bdt($grand),
        'discount_fmt'=> Helpers::bdt($discount),
    ]);
});

/* ---------------------- Place order + OTP ---------------------- */
$router->post('/api/checkout/place', function () {
    if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'Bad CSRF'], 403);
    try {
        $res = CheckoutService::placeOrder($_POST);
        $_SESSION['pending_order_id']     = $res['order_id'];
        $_SESSION['pending_order_number'] = $res['order_number'];
        Helpers::json(['ok' => true] + $res);
    } catch (\Throwable $e) {
        Helpers::json(['ok' => false, 'msg' => $e->getMessage()], 422);
    }
});

$router->post('/api/checkout/verify', function () {
    if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'Bad CSRF'], 403);
    $id   = (int)($_SESSION['pending_order_id'] ?? 0);
    $code = preg_replace('/\D/', '', (string)Helpers::input('otp'));
    if (!$id) Helpers::json(['ok'=>false,'msg'=>'No pending order.'], 422);
    if (!OtpService::verify($id, $code)) Helpers::json(['ok'=>false,'msg'=>'Invalid or expired OTP.'], 422);

    // Fire Meta CAPI server-side (best effort)
    try { MetaCAPI::purchase($id); } catch (\Throwable $e) { error_log('CAPI: '.$e->getMessage()); }

    $num = $_SESSION['pending_order_number'] ?? '';
    unset($_SESSION['pending_order_id'], $_SESSION['pending_order_number']);
    Helpers::json(['ok' => true, 'order_number' => $num,
                   'redirect' => Helpers::url('order/thank-you/'.$num)]);
});

$router->post('/api/checkout/resend-otp', function () {
    $id = (int)($_SESSION['pending_order_id'] ?? 0);
    if (!$id) Helpers::json(['ok'=>false,'msg'=>'No pending order.'], 422);
    $order = Database::i()->one('SELECT customer_phone FROM orders WHERE id = ?', [$id]);
    if (!$order) Helpers::json(['ok'=>false,'msg'=>'Order not found.'], 422);
    OtpService::sendForOrder($id, $order['customer_phone']);
    Helpers::json(['ok' => true]);
});

$router->notFound(function () {
    http_response_code(404);
    require LH_ROOT . '/public/views/404.php';
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
