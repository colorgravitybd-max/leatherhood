<?php
use LH\Core\Auth;
use LH\Core\Database;
use LH\Core\Helpers;

Auth::gate('orders.update');
if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'CSRF'], 403);
$id = (int)Helpers::input('order_id');
$status = (string)Helpers::input('status');
$valid = ['processing','on_hold','shipped','out_for_delivery','delivered','completed'];
if (!$id || !in_array($status, $valid, true)) Helpers::json(['ok'=>false,'msg'=>'Invalid'], 422);

$db = Database::i();
$o  = $db->one('SELECT status FROM orders WHERE id=?', [$id]);
if (!$o) Helpers::json(['ok'=>false,'msg'=>'No order'], 404);

$payload = ['status' => $status];
if ($status === 'shipped')   $payload['shipped_at']   = date('Y-m-d H:i:s');
if ($status === 'delivered') $payload['delivered_at'] = date('Y-m-d H:i:s');
$db->update('orders', $payload, 'id = :_id', [':_id' => $id]);

$db->insert('order_status_history', [
    'order_id'    => $id,
    'from_status' => $o['status'],
    'to_status'   => $status,
    'note'        => 'Manual status update',
    'user_id'     => Auth::user()['id'] ?? null,
]);
Helpers::json(['ok'=>true]);
