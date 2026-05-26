<?php
use LH\Core\Auth;
use LH\Core\Helpers;
use LH\Services\ImageOptimizer;

Auth::gate('media.upload');
if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) Helpers::json(['ok'=>false,'msg'=>'CSRF'], 403);
if (empty($_FILES['file'])) Helpers::json(['ok'=>false,'msg'=>'No file'], 422);
try {
    $r = ImageOptimizer::processUpload($_FILES['file'], 'products', Auth::user()['id'] ?? null);
    Helpers::json(['ok'=>true] + $r);
} catch (\Throwable $e) {
    Helpers::json(['ok'=>false,'msg'=>$e->getMessage()], 422);
}
