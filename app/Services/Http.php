<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;

/**
 * Thin cURL wrapper that automatically logs each outbound API call to
 * `webhook_logs` so the admin can audit every push to SMS, courier,
 * bKash, Meta and Cloudflare.
 */
final class Http
{
    public static function request(
        string $method,
        string $url,
        array $headers = [],
        array|string|null $body = null,
        string $service = 'misc',
        ?string $refType = null,
        ?int $refId = null,
        int $timeout = 20
    ): array {
        $ch = curl_init();

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => array_map(fn($k,$v)=>"$k: $v",
                                                array_keys($headers),
                                                array_values($headers)),
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = is_array($body) ? http_build_query($body) : $body;
        }
        curl_setopt_array($ch, $opts);

        $resp   = curl_exec($ch);
        $code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        $success = $code >= 200 && $code < 400 && !$err;
        $bodyStr = is_array($body) ? json_encode($body) : (string)$body;

        try {
            Database::i()->insert('webhook_logs', [
                'direction'     => 'out',
                'endpoint'      => $url,
                'service'       => $service,
                'method'        => strtoupper($method),
                'request_payload'  => substr((string)$bodyStr, 0, 65000),
                'response_payload' => substr((string)($resp ?: $err), 0, 65000),
                'status_code'   => $code,
                'success'       => $success ? 1 : 0,
                'reference_type'=> $refType,
                'reference_id'  => $refId,
            ]);
        } catch (\Throwable $e) { /* swallow logging errors */ }

        return [
            'ok'     => $success,
            'status' => $code,
            'body'   => $resp ?: '',
            'error'  => $err ?: null,
        ];
    }
}
