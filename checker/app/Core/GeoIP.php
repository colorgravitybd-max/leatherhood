<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight IP geolocation lookup against ip-api.com (free, no key).
 *
 * Result is intentionally best-effort: any failure returns a populated
 * struct with empty strings so the caller can still write a scan_logs row.
 *
 * Free tier limit: 45 req/min per server IP. For volume, swap to a paid
 * service or self-hosted MaxMind by editing the GEOIP_ENDPOINT.
 */
final class GeoIP
{
    /**
     * @return array{country:string, region:string, district:string}
     */
    public static function lookup(string $ip): array
    {
        $blank = ['country' => '', 'region' => '', 'district' => ''];

        // Don't waste a request on private / loopback ranges.
        if ($ip === '' || $ip === '0.0.0.0'
            || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $blank;
        }

        $url = rtrim(GEOIP_ENDPOINT, '/') . '/' . urlencode($ip)
             . '?fields=status,country,regionName,city';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT        => GEOIP_TIMEOUT_SEC,
            CURLOPT_USERAGENT      => 'ELHOE-Verify/1.0',
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $err  = curl_errno($ch);
        curl_close($ch);

        if ($err !== 0 || !is_string($body) || $body === '') {
            return $blank;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return $blank;
        }

        return [
            'country'  => (string) ($data['country']    ?? ''),
            'region'   => (string) ($data['regionName'] ?? ''),
            'district' => (string) ($data['city']       ?? ''),
        ];
    }
}
