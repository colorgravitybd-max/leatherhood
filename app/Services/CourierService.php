<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;

/**
 * One-click courier dispatch for SteadFast and Pathao.
 * Saves the returned consignment ID + tracking URL on the order.
 */
final class CourierService
{
    public static function pushSteadFast(int $orderId): array
    {
        $cfg = $GLOBALS['LH_CFG']['couriers']['steadfast'];
        if (empty($cfg['api_key']) || empty($cfg['secret'])) {
            return ['ok' => false, 'reason' => 'steadfast_unconfigured'];
        }

        $order = self::fetchOrderForCourier($orderId);
        if (!$order) return ['ok' => false, 'reason' => 'order_missing'];

        $payload = [
            'invoice'        => $order['order_number'],
            'recipient_name' => $order['customer_name'],
            'recipient_phone'=> preg_replace('/\D/','',$order['customer_phone']),
            'recipient_address' => self::fullAddress($order),
            'cod_amount'     => $order['payment_method'] === 'cod' ? (float)$order['grand_total'] : 0,
            'note'           => $order['note'],
        ];

        $resp = Http::request('POST',
            rtrim($cfg['base'],'/').'/create_order',
            [
                'Api-Key'      => $cfg['api_key'],
                'Secret-Key'   => $cfg['secret'],
                'Content-Type' => 'application/json',
            ],
            json_encode($payload),
            'steadfast', 'order', $orderId);

        if ($resp['ok']) {
            $json = json_decode($resp['body'], true) ?: [];
            $consignment = $json['consignment'] ?? [];
            $cid    = (string)($consignment['consignment_id'] ?? $consignment['tracking_code'] ?? '');
            $track  = $cid ? 'https://steadfast.com.bd/t/'.$cid : null;
            Database::i()->update('orders', [
                'courier'                => 'steadfast',
                'courier_consignment_id' => $cid ?: null,
                'courier_tracking_url'   => $track,
                'courier_pushed_at'      => date('Y-m-d H:i:s'),
                'status'                 => 'shipped',
                'shipped_at'             => date('Y-m-d H:i:s'),
            ], 'id = :_id', [':_id' => $orderId]);

            Database::i()->insert('order_status_history', [
                'order_id'    => $orderId,
                'from_status' => $order['status'],
                'to_status'   => 'shipped',
                'note'        => 'Pushed to SteadFast: '.$cid,
            ]);
        }
        return $resp;
    }

    public static function pushPathao(int $orderId): array
    {
        $cfg = $GLOBALS['LH_CFG']['couriers']['pathao'];
        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            return ['ok' => false, 'reason' => 'pathao_unconfigured'];
        }

        $order = self::fetchOrderForCourier($orderId);
        if (!$order) return ['ok' => false, 'reason' => 'order_missing'];

        // 1. Token
        $tokenResp = Http::request('POST', rtrim($cfg['base'],'/').'/aladdin/api/v1/issue-token',
            ['Content-Type' => 'application/json'],
            json_encode([
                'client_id'     => $cfg['client_id'],
                'client_secret' => $cfg['client_secret'],
                'username'      => $cfg['username'],
                'password'      => $cfg['password'],
                'grant_type'    => 'password',
            ]),
            'pathao', 'order', $orderId);
        if (!$tokenResp['ok']) return $tokenResp;
        $token = (json_decode($tokenResp['body'], true)['access_token'] ?? null);
        if (!$token) return ['ok' => false, 'reason' => 'no_token'];

        // 2. Create order
        $payload = [
            'store_id'             => $cfg['store_id'],
            'merchant_order_id'    => $order['order_number'],
            'recipient_name'       => $order['customer_name'],
            'recipient_phone'      => preg_replace('/\D/','',$order['customer_phone']),
            'recipient_address'    => self::fullAddress($order),
            'recipient_city'       => 1,
            'recipient_zone'       => 1,
            'delivery_type'        => 48,
            'item_type'            => 2,
            'special_instruction'  => $order['note'] ?? '',
            'item_quantity'        => 1,
            'item_weight'          => 0.5,
            'amount_to_collect'    => $order['payment_method'] === 'cod' ? (float)$order['grand_total'] : 0,
            'item_description'     => 'LeatherHood order',
        ];
        $resp = Http::request('POST', rtrim($cfg['base'],'/').'/aladdin/api/v1/orders',
            [
                'Authorization' => 'Bearer '.$token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            json_encode($payload),
            'pathao', 'order', $orderId);

        if ($resp['ok']) {
            $json = json_decode($resp['body'], true) ?: [];
            $cid  = $json['data']['consignment_id'] ?? '';
            Database::i()->update('orders', [
                'courier'                => 'pathao',
                'courier_consignment_id' => $cid ?: null,
                'courier_tracking_url'   => $cid ? 'https://merchant.pathao.com/courier/orders/'.$cid : null,
                'courier_pushed_at'      => date('Y-m-d H:i:s'),
                'status'                 => 'shipped',
                'shipped_at'             => date('Y-m-d H:i:s'),
            ], 'id = :_id', [':_id' => $orderId]);
        }
        return $resp;
    }

    private static function fetchOrderForCourier(int $orderId): ?array
    {
        return Database::i()->one(
            'SELECT o.*, d.name AS district_name, p.name AS ps_name
               FROM orders o
          LEFT JOIN geo_districts d ON d.id = o.district_id
          LEFT JOIN geo_police_stations p ON p.id = o.police_station_id
              WHERE o.id = ? LIMIT 1', [$orderId]);
    }

    private static function fullAddress(array $o): string
    {
        return trim($o['address_line'] . ', ' .
                    ($o['ps_name'] ?? '') . ', ' . ($o['district_name'] ?? ''), ', ');
    }
}
