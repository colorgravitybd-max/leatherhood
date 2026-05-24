<?php
declare(strict_types=1);

namespace LH\Services;

final class CloudflareService
{
    public static function purgeAll(): array
    {
        $cfg = $GLOBALS['LH_CFG']['cloudflare'];
        if (empty($cfg['enabled']) || empty($cfg['zone_id']) || empty($cfg['token'])) {
            return ['ok' => false, 'reason' => 'cloudflare_disabled'];
        }
        return Http::request('POST',
            "https://api.cloudflare.com/client/v4/zones/{$cfg['zone_id']}/purge_cache",
            [
                'Authorization' => 'Bearer '.$cfg['token'],
                'Content-Type'  => 'application/json',
            ],
            json_encode(['purge_everything' => true]),
            'cloudflare');
    }

    public static function purgeUrls(array $urls): array
    {
        $cfg = $GLOBALS['LH_CFG']['cloudflare'];
        if (empty($cfg['enabled']) || empty($cfg['zone_id']) || empty($cfg['token'])) {
            return ['ok' => false, 'reason' => 'cloudflare_disabled'];
        }
        return Http::request('POST',
            "https://api.cloudflare.com/client/v4/zones/{$cfg['zone_id']}/purge_cache",
            [
                'Authorization' => 'Bearer '.$cfg['token'],
                'Content-Type'  => 'application/json',
            ],
            json_encode(['files' => array_values($urls)]),
            'cloudflare');
    }

    public static function purgeProduct(string $slug): array
    {
        $base = rtrim($GLOBALS['LH_CFG']['app']['url'], '/');
        return self::purgeUrls([
            $base.'/product/'.$slug,
            $base.'/shop',
            $base.'/',
        ]);
    }
}
