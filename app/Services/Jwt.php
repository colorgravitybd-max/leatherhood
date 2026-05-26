<?php
declare(strict_types=1);

namespace LH\Services;

/**
 * Tiny HS256 JWT encoder/decoder (no Composer dependency).
 */
final class Jwt
{
    public static function encode(array $payload, ?string $secret = null, int $ttl = 3600): string
    {
        $secret = $secret ?: $GLOBALS['LH_CFG']['app']['jwt_secret'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttl;

        $header  = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body    = self::b64(json_encode($payload));
        $sig     = self::b64(hash_hmac('sha256', "$header.$body", $secret, true));
        return "$header.$body.$sig";
    }

    public static function decode(string $token, ?string $secret = null): ?array
    {
        $secret = $secret ?: $GLOBALS['LH_CFG']['app']['jwt_secret'];
        $parts  = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$h, $b, $s] = $parts;
        $expected = self::b64(hash_hmac('sha256', "$h.$b", $secret, true));
        if (!hash_equals($expected, $s)) return null;
        $payload = json_decode(self::b64d($b), true);
        if (!is_array($payload)) return null;
        if (!empty($payload['exp']) && $payload['exp'] < time()) return null;
        return $payload;
    }

    private static function b64(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
    private static function b64d(string $b): string
    {
        $pad = 4 - (strlen($b) % 4); if ($pad < 4) $b .= str_repeat('=', $pad);
        return base64_decode(strtr($b, '-_', '+/'));
    }
}
