<?php
use LH\Core\Auth;
use LH\Core\Helpers;
use LH\Services\OtpService;
use LH\Services\MetaCAPI;

Auth::gate('orders.update');
if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'CSRF'], 403);
$id = (int)Helpers::input('order_id');
if (!$id) Helpers::json(['ok'=>false,'msg'=>'No order'], 422);
OtpService::manualVerify($id, Auth::user()['id']);
try { MetaCAPI::purchase($id); } catch (\Throwable $e) {}
Helpers::json(['ok'=>true]);
