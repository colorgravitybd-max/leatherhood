<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;

/**
 * bKash Payment Gateway (PGW v1.2 Tokenized Checkout).
 * Three-step flow: grant -> create -> execute -> query.
 */
final class BkashService
{
    private static function base(): string
    {
        $cfg = $GLOBALS['LH_CFG']['payments']['bkash'];
        return $cfg['mode'] === 'live'
            ? 'https://tokenized.pay.bka.sh/v1.2.0-beta'
            : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
    }

    public static function token(): ?string
    {
        $cfg = $GLOBALS['LH_CFG']['payments']['bkash'];
        if (empty($cfg['enabled'])) return null;
        $resp = Http::request('POST',
            self::base().'/tokenized/checkout/token/grant',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'username'      => $cfg['username'],
                'password'      => $cfg['password'],
            ],
            json_encode([
                'app_key'    => $cfg['app_key'],
                'app_secret' => $cfg['app_secret'],
            ]),
            'bkash');
        return $resp['ok'] ? (json_decode($resp['body'],true)['id_token'] ?? null) : null;
    }

    public static function createPayment(int $orderId, float $amount): array
    {
        $cfg   = $GLOBALS['LH_CFG']['payments']['bkash'];
        $token = self::token();
        if (!$token) return ['ok' => false, 'reason' => 'token_failed'];

        return Http::request('POST',
            self::base().'/tokenized/checkout/create',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'authorization' => $token,
                'x-app-key'     => $cfg['app_key'],
            ],
            json_encode([
                'mode'                => '0011',
                'payerReference'      => 'order_'.$orderId,
                'callbackURL'         => rtrim($GLOBALS['LH_CFG']['app']['url'],'/').'/payment/bkash/callback',
                'amount'              => number_format($amount, 2, '.', ''),
                'currency'            => 'BDT',
                'intent'              => 'sale',
                'merchantInvoiceNumber'=> 'INV-'.$orderId,
            ]),
            'bkash', 'order', $orderId);
    }

    public static function executePayment(string $paymentId): array
    {
        $cfg   = $GLOBALS['LH_CFG']['payments']['bkash'];
        $token = self::token();
        if (!$token) return ['ok' => false, 'reason' => 'token_failed'];

        return Http::request('POST',
            self::base().'/tokenized/checkout/execute',
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'authorization' => $token,
                'x-app-key'     => $cfg['app_key'],
            ],
            json_encode(['paymentID' => $paymentId]),
            'bkash');
    }
}
