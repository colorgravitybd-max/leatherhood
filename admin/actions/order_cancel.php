<?php
use LH\Core\Auth;
use LH\Core\Helpers;
use LH\Services\CheckoutService;

Auth::gate('orders.update');
if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'CSRF'], 403);
$id = (int)Helpers::input('order_id');
if (!$id) Helpers::json(['ok'=>false,'msg'=>'No order'], 422);
try {
    CheckoutService::cancelAndRestock($id, Auth::user()['id'], (string)Helpers::input('note', 'Cancelled'));
    Helpers::json(['ok'=>true]);
} catch (\Throwable $e) {
    Helpers::json(['ok'=>false,'msg'=>$e->getMessage()], 422);
}
