<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Cloudflare Turnstile server-side verification.
 *
 * If TURNSTILE_SECRET_KEY is empty, verify() returns true so local dev
 * works without Cloudflare credentials. Production .env MUST set the keys.
 */
final class Turnstile
{
    private const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function enabled(): bool
    {
        return TURNSTILE_SITE_KEY !== '' && TURNSTILE_SECRET_KEY !== '';
    }

    public static function verify(?string $token, string $ip = ''): bool
    {
        if (!self::enabled()) {
            return true; // dev bypass
        }
        if (!is_string($token) || $token === '') {
            return false;
        }

        $payload = http_build_query([
            'secret'   => TURNSTILE_SECRET_KEY,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT        => 4,
        ]);
        $body = curl_exec($ch);
        $err  = curl_errno($ch);
        curl_close($ch);

        if ($err !== 0 || !is_string($body)) {
            return false;
        }
        $data = json_decode($body, true);
        return is_array($data) && !empty($data['success']);
    }
}
