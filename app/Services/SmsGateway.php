<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;
use LH\Core\Helpers;

/**
 * Minimal multi-provider SMS gateway. Defaults to GreenWeb / BulkSMSBD
 * style (single GET with token, to, message).  Override via settings.
 */
final class SmsGateway
{
    public static function send(string $phone, string $message, string $purpose = 'otp', ?int $orderId = null): array
    {
        $cfg = $GLOBALS['LH_CFG']['sms'];

        $logId = Database::i()->insert('sms_logs', [
            'phone'    => $phone,
            'message'  => $message,
            'purpose'  => $purpose,
            'provider' => $cfg['provider'],
            'status'   => 'queued',
            'order_id' => $orderId,
        ]);

        if (empty($cfg['api_token'])) {
            // No token configured — log only.
            Database::i()->update('sms_logs', [
                'status' => 'failed',
                'provider_response' => 'SMS gateway token missing',
            ], 'id = :_id', [':_id' => $logId]);
            return ['ok' => false, 'reason' => 'no_token'];
        }

        // Strip +88 prefix (most local gateways want 11-digit local format)
        $local = preg_replace('/^\+?88/', '', $phone);

        $resp = Http::request('GET',
            $cfg['api_url'].'?token='.urlencode($cfg['api_token']).
            '&to='.urlencode($local).'&message='.urlencode($message),
            [], null, 'sms', 'sms_log', (int)$logId);

        $ok = $resp['ok'] && stripos($resp['body'], 'error') === false;

        Database::i()->update('sms_logs', [
            'status' => $ok ? 'sent' : 'failed',
            'provider_response' => substr($resp['body'] ?: $resp['error'] ?? '', 0, 5000),
        ], 'id = :_id', [':_id' => $logId]);

        return ['ok' => $ok, 'log_id' => (int)$logId];
    }
}
