<?php
use LH\Core\Auth;
use LH\Core\Helpers;
use LH\Services\CourierService;

Auth::gate('couriers.push');
if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'CSRF'], 403);
$id      = (int)Helpers::input('order_id');
$courier = Helpers::input('courier');
if (!$id) Helpers::json(['ok'=>false,'msg'=>'No order'], 422);
$r = $courier === 'pathao' ? CourierService::pushPathao($id) : CourierService::pushSteadFast($id);
Helpers::json(['ok' => (bool)($r['ok'] ?? false), 'detail' => $r]);
