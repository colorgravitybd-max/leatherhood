<?php
use LH\Core\Helpers;
use LH\Core\Auth;
use LH\Services\CloudflareService;

Auth::gate('cloudflare.purge');
if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'CSRF'], 403);

if (Helpers::input('all')) {
    $r = CloudflareService::purgeAll();
} elseif ($urls = Helpers::input('urls')) {
    $r = CloudflareService::purgeUrls(explode(',', (string)$urls));
} else {
    Helpers::json(['ok'=>false,'msg'=>'No target.'], 422);
}
Helpers::json(['ok' => (bool)($r['ok'] ?? false), 'detail' => $r]);
