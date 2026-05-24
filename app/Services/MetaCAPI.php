<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;

/**
 * Server-side Meta Conversions API. Hashes PII before sending and
 * supplies an event_id matching the browser pixel so Meta can dedupe.
 */
final class MetaCAPI
{
    private static function hash(?string $v): ?string
    {
        if ($v === null || $v === '') return null;
        return hash('sha256', strtolower(trim($v)));
    }

    public static function purchase(int $orderId): array
    {
        $cfg = $GLOBALS['LH_CFG']['meta'];
        if (empty($cfg['enabled']) || empty($cfg['pixel_id']) || empty($cfg['capi_token'])) {
            return ['ok' => false, 'reason' => 'meta_disabled'];
        }

        $order = Database::i()->one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$order) return ['ok' => false, 'reason' => 'order_missing'];
        $items = Database::i()->all('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);

        $contents = array_map(fn($it) => [
            'id'          => $it['sku'],
            'quantity'    => (int)$it['quantity'],
            'item_price'  => (float)$it['unit_price'],
        ], $items);

        $payload = [
            'data' => [[
                'event_name'   => 'Purchase',
                'event_time'   => time(),
                'event_id'     => $order['event_id'] ?: ('order-'.$orderId),
                'action_source'=> 'website',
                'event_source_url' => rtrim($GLOBALS['LH_CFG']['app']['url'],'/'),
                'user_data' => array_filter([
                    'em'   => self::hash($order['customer_email']),
                    'ph'   => self::hash(preg_replace('/\D/','',$order['customer_phone'])),
                    'fn'   => self::hash(explode(' ', $order['customer_name'])[0] ?? null),
                    'client_ip_address' => $order['client_ip'],
                    'client_user_agent' => $order['user_agent'],
                    'fbc'  => $order['fbc'],
                    'fbp'  => $order['fbp'],
                ]),
                'custom_data' => [
                    'currency'     => 'BDT',
                    'value'        => (float)$order['grand_total'],
                    'order_id'     => $order['order_number'],
                    'content_ids'  => array_column($items, 'sku'),
                    'content_type' => 'product',
                    'contents'     => $contents,
                    'num_items'    => array_sum(array_column($items, 'quantity')),
                ],
            ]],
        ];
        if (!empty($cfg['test_event_code'])) {
            $payload['test_event_code'] = $cfg['test_event_code'];
        }

        return Http::request('POST',
            'https://graph.facebook.com/v18.0/'.$cfg['pixel_id'].'/events?access_token='.$cfg['capi_token'],
            ['Content-Type'=>'application/json'],
            json_encode($payload),
            'meta_capi','order',$orderId);
    }
}
