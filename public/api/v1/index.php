<?php
declare(strict_types=1);

require __DIR__ . '/../../../app/Core/Bootstrap.php';

use LH\Core\Database;
use LH\Core\Helpers;
use LH\Services\Jwt;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
$path = '/'.trim(preg_replace('#^.*?/api/v1#', '', $path), '/');
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode((string)file_get_contents('php://input'), true) ?: [];
$db     = Database::i();

/* ---------- Auth helpers ---------- */
$bearer = function (): ?string {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', (string)$h, $m)) return trim($m[1]);
    return null;
};
$authUser = function () use ($bearer, $db): ?array {
    $tok = $bearer(); if (!$tok) return null;
    $payload = Jwt::decode($tok); if (!$payload) return null;
    $u = $db->one('SELECT id, name, email, role, is_active FROM users WHERE id=? LIMIT 1', [$payload['sub'] ?? 0]);
    if (!$u || !$u['is_active']) return null;
    return $u;
};
$require = function () use ($authUser): array {
    $u = $authUser();
    if (!$u) Helpers::json(['ok'=>false,'msg'=>'Unauthorized'], 401);
    return $u;
};

/* ---------- Routes ---------- */
try {
    if ($method === 'POST' && $path === '/auth/login') {
        $email = (string)($body['email'] ?? '');
        $pass  = (string)($body['password'] ?? '');
        $u = $db->one('SELECT * FROM users WHERE email=? AND is_active=1', [$email]);
        if (!$u || !password_verify($pass, $u['password']))
            Helpers::json(['ok'=>false,'msg'=>'Invalid credentials'], 401);
        $token = Jwt::encode(['sub'=>(int)$u['id'],'role'=>$u['role'],'name'=>$u['name']], null, 86400 * 30);
        Helpers::json(['ok'=>true,'token'=>$token,
            'user'=>['id'=>(int)$u['id'],'name'=>$u['name'],'role'=>$u['role']]]);
    }

    if ($method === 'GET' && $path === '/me') {
        Helpers::json(['ok'=>true,'user'=>$require()]);
    }

    /* --- Orders --- */
    if ($method === 'GET' && $path === '/orders') {
        $require();
        $status = (string)($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per  = 20;
        $where = '1=1'; $p = [];
        if ($status) { $where .= ' AND status = ?'; $p[] = $status; }
        $rows = $db->all("SELECT id,order_number,status,payment_method,grand_total,
                                 customer_name,customer_phone,created_at
                            FROM orders WHERE $where ORDER BY id DESC
                            LIMIT $per OFFSET ".(($page-1)*$per), $p);
        Helpers::json(['ok'=>true,'orders'=>$rows]);
    }

    if ($method === 'GET' && preg_match('#^/orders/(\d+)$#', $path, $m)) {
        $require();
        $o = $db->one('SELECT * FROM orders WHERE id=?', [(int)$m[1]]);
        if (!$o) Helpers::json(['ok'=>false,'msg'=>'Not found'], 404);
        $o['items'] = $db->all('SELECT * FROM order_items WHERE order_id=?', [(int)$m[1]]);
        Helpers::json(['ok'=>true,'order'=>$o]);
    }

    if ($method === 'POST' && preg_match('#^/orders/(\d+)/status$#', $path, $m)) {
        $require();
        $valid = ['processing','on_hold','shipped','out_for_delivery','delivered','completed'];
        $st = (string)($body['status'] ?? '');
        if (!in_array($st, $valid, true)) Helpers::json(['ok'=>false,'msg'=>'Invalid status'], 422);
        $payload = ['status' => $st];
        if ($st === 'shipped')   $payload['shipped_at']   = date('Y-m-d H:i:s');
        if ($st === 'delivered') $payload['delivered_at'] = date('Y-m-d H:i:s');
        $db->update('orders', $payload, 'id = :_id', [':_id' => (int)$m[1]]);
        Helpers::json(['ok'=>true]);
    }

    /* --- Inventory by barcode (warehouse APK) --- */
    if ($method === 'POST' && $path === '/inventory/scan') {
        $u = $require();
        $barcode = (string)($body['barcode'] ?? '');
        $delta   = (int)($body['delta'] ?? 0);
        $reason  = (string)($body['reason'] ?? 'adjustment');
        if (!$barcode || !$delta) Helpers::json(['ok'=>false,'msg'=>'barcode + delta required'], 422);

        $variation = $db->one('SELECT * FROM product_variations WHERE barcode = ? OR sku = ? LIMIT 1', [$barcode, $barcode]);
        if ($variation) {
            $db->run('UPDATE product_variations SET stock_qty = stock_qty + ? WHERE id = ?',
                [$delta, $variation['id']]);
            $db->insert('inventory_movements', [
                'variation_id' => $variation['id'],
                'product_id'   => $variation['product_id'],
                'delta'        => $delta,
                'reason'       => $reason,
                'reference_type' => 'mobile_scan',
                'note'         => 'APK scan',
                'user_id'      => $u['id'],
            ]);
            $newQty = $db->value('SELECT stock_qty FROM product_variations WHERE id = ?', [$variation['id']]);
            Helpers::json(['ok'=>true,'sku'=>$variation['sku'],'stock'=>(int)$newQty]);
        }
        $product = $db->one('SELECT * FROM products WHERE sku = ? LIMIT 1', [$barcode]);
        if ($product) {
            $db->run('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?', [$delta, $product['id']]);
            $db->insert('inventory_movements', [
                'product_id' => $product['id'],
                'delta'      => $delta,
                'reason'     => $reason,
                'reference_type' => 'mobile_scan',
                'note'         => 'APK scan',
                'user_id'      => $u['id'],
            ]);
            $newQty = $db->value('SELECT stock_qty FROM products WHERE id = ?', [$product['id']]);
            Helpers::json(['ok'=>true,'sku'=>$product['sku'],'stock'=>(int)$newQty]);
        }
        Helpers::json(['ok'=>false,'msg'=>'SKU/Barcode not found'], 404);
    }

    /* --- Lookups --- */
    if ($method === 'GET' && $path === '/products') {
        $rows = $db->all("SELECT id, name, slug, sku, price, stock_qty, status, featured_image
                          FROM products WHERE status='active' ORDER BY id DESC LIMIT 200");
        Helpers::json(['ok'=>true,'products'=>$rows]);
    }

    Helpers::json(['ok'=>false,'msg'=>'Not found'], 404);

} catch (\Throwable $e) {
    error_log('[API] '.$e->getMessage());
    Helpers::json(['ok'=>false,'msg'=>'Server error'], 500);
}
