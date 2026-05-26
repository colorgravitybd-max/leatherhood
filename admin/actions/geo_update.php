<?php
use LH\Core\Auth;
use LH\Core\Helpers;
use LH\Core\Database;

Auth::gate('geo.write');
if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'CSRF'], 403);
$tbl   = Helpers::input('table');
$field = Helpers::input('field');
$id    = (int)Helpers::input('id');
$val   = Helpers::input('value');

$allow = [
    'geo_districts'       => ['base_shipping_fee','is_deliverable'],
    'geo_police_stations' => ['extra_shipping_fee','is_deliverable'],
];
if (!isset($allow[$tbl]) || !in_array($field, $allow[$tbl], true) || !$id) {
    Helpers::json(['ok'=>false,'msg'=>'Invalid'], 422);
}
Database::i()->run("UPDATE `$tbl` SET `$field` = ? WHERE id = ?", [$val, $id]);
Helpers::json(['ok'=>true]);
