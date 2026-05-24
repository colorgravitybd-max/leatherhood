<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;
use LH\Core\Helpers;

/**
 * The Checkout pipeline.
 *
 *   1.  Validate input (phone, address, delivery)
 *   2.  Open a PDO transaction
 *   3.  SELECT ... FOR UPDATE every product/variation row in the cart
 *      → MySQL queues concurrent transactions; oversells become impossible
 *   4.  Re-validate stock under lock; bail if insufficient
 *   5.  Decrement stock, insert order + items, write inventory ledger
 *   6.  Generate OTP, log SMS, ping SMS gateway
 *   7.  Commit; return order with masked next-step
 *
 * On any error, the transaction is rolled back so stock is never lost.
 */
final class CheckoutService
{
    public static function placeOrder(array $input): array
    {
        $cart = CartService::hydrate();
        if (empty($cart['items'])) {
            throw new \RuntimeException('Your cart is empty.');
        }

        // ---------- Input validation ----------
        $name  = trim($input['customer_name'] ?? '');
        $phone = preg_replace('/[^\d+]/', '', $input['customer_phone'] ?? '');
        $email = filter_var($input['customer_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
        $div   = (int)($input['division_id'] ?? 0);
        $dist  = (int)($input['district_id'] ?? 0);
        $ps    = (int)($input['police_station_id'] ?? 0);
        $addr  = trim($input['address_line'] ?? '');
        $note  = trim($input['note'] ?? '');
        $payment = in_array($input['payment_method'] ?? 'cod', ['cod','bkash'], true)
                   ? $input['payment_method'] : 'cod';
        $coupon = trim($input['coupon_code'] ?? '');

        if (mb_strlen($name) < 2)   throw new \RuntimeException('Please enter your full name.');
        if (!preg_match('/^(\+?88)?01[3-9]\d{8}$/', $phone))
            throw new \RuntimeException('Please enter a valid Bangladeshi mobile number.');
        if (!$div || !$dist) throw new \RuntimeException('Please select division and district.');
        if (mb_strlen($addr) < 8)   throw new \RuntimeException('Please enter a complete delivery address.');

        $shipping = ShippingService::quote($dist, $ps ?: null, $cart['subtotal']);
        if (!$shipping['deliverable']) {
            throw new \RuntimeException($shipping['message'] ?? 'Cannot deliver to this address.');
        }

        $discount = 0.0;
        $couponRow = null;
        if ($coupon !== '') {
            $couponRow = Database::i()->one(
                'SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1', [$coupon]);
            if ($couponRow && (float)$couponRow['min_subtotal'] <= $cart['subtotal']) {
                if ($couponRow['type'] === 'percent') {
                    $discount = $cart['subtotal'] * ((float)$couponRow['value'] / 100);
                } elseif ($couponRow['type'] === 'fixed') {
                    $discount = (float)$couponRow['value'];
                } elseif ($couponRow['type'] === 'free_shipping') {
                    $shipping['fee'] = 0.0;
                }
            }
        }

        $subtotal = $cart['subtotal'];
        $shipFee  = (float)$shipping['fee'];
        $grand    = max(0, $subtotal - $discount + $shipFee);

        $db = Database::i();

        // ---------- Transaction with row locking ----------
        return $db->transaction(function () use (
            $db, $cart, $shipping, $couponRow, $name, $phone, $email,
            $div, $dist, $ps, $addr, $note, $payment, $subtotal,
            $shipFee, $discount, $grand
        ) {

            // 1. Lock every product / variation row referenced by the cart.
            //    Lock in deterministic order to prevent deadlocks.
            $locks = [];
            foreach ($cart['items'] as $it) {
                $locks[] = ['type' => $it['variation_id'] ? 'v' : 'p',
                            'id'   => $it['variation_id'] ?? $it['product_id']];
            }
            usort($locks, fn($a, $b) => [$a['type'],$a['id']] <=> [$b['type'],$b['id']]);

            $stockMap = [];
            foreach ($locks as $l) {
                $tbl = $l['type'] === 'v' ? 'product_variations' : 'products';
                $row = $db->one("SELECT id, stock_qty " .
                    ($l['type']==='p' ? ', track_inventory' : ', 1 AS track_inventory') .
                    " FROM `$tbl` WHERE id = ? FOR UPDATE", [$l['id']]);
                if (!$row) throw new \RuntimeException('Product/variation no longer available.');
                $stockMap[$l['type'].$l['id']] = (int)$row['stock_qty'];
            }

            // 2. Re-validate stock under lock.
            foreach ($cart['items'] as $it) {
                if (!$it['track_inventory']) continue;
                $key = ($it['variation_id'] ? 'v' : 'p').($it['variation_id'] ?: $it['product_id']);
                $available = $stockMap[$key] ?? 0;
                if ($available < $it['qty']) {
                    throw new \RuntimeException(
                        sprintf('Sorry — "%s" only has %d in stock.', $it['name'], $available));
                }
            }

            // 3. Insert order shell.
            $orderNumber = Helpers::generateOrderNumber();
            $eventId     = bin2hex(random_bytes(16));
            $orderId = $db->insert('orders', [
                'order_number'   => $orderNumber,
                'customer_id'    => self::upsertCustomer($name, $phone, $email, $div, $dist, $ps, $addr),
                'status'         => 'pending_otp',
                'payment_status' => 'unpaid',
                'payment_method' => $payment,
                'customer_name'  => $name,
                'customer_phone' => $phone,
                'customer_email' => $email,
                'division_id'    => $div ?: null,
                'district_id'    => $dist ?: null,
                'police_station_id' => $ps ?: null,
                'address_line'   => $addr,
                'note'           => $note ?: null,
                'subtotal'       => $subtotal,
                'discount_total' => $discount,
                'coupon_code'    => $couponRow['code'] ?? null,
                'shipping_total' => $shipFee,
                'tax_total'      => 0,
                'grand_total'    => $grand,
                'currency'       => 'BDT',
                'is_verified'    => 0,
                'client_ip'      => Helpers::ip(),
                'user_agent'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
                'referrer'       => substr($_SERVER['HTTP_REFERER'] ?? '', 0, 250),
                'utm_source'     => $_COOKIE['utm_source']   ?? null,
                'utm_medium'     => $_COOKIE['utm_medium']   ?? null,
                'utm_campaign'   => $_COOKIE['utm_campaign'] ?? null,
                'fbc'            => $_COOKIE['_fbc'] ?? null,
                'fbp'            => $_COOKIE['_fbp'] ?? null,
                'event_id'       => $eventId,
                'placed_at'      => date('Y-m-d H:i:s'),
            ]);

            // 4. Insert items + decrement stock + ledger entries.
            foreach ($cart['items'] as $it) {
                $db->insert('order_items', [
                    'order_id'       => $orderId,
                    'product_id'     => $it['product_id'],
                    'variation_id'   => $it['variation_id'],
                    'sku'            => $it['sku'],
                    'name'           => $it['name'],
                    'option_summary' => $it['option_summary'],
                    'image'          => $it['image'],
                    'unit_price'     => $it['unit_price'],
                    'quantity'       => $it['qty'],
                    'line_total'     => $it['line_total'],
                ]);

                if ($it['track_inventory']) {
                    if ($it['variation_id']) {
                        $db->run('UPDATE product_variations
                                     SET stock_qty = stock_qty - ?
                                   WHERE id = ?',
                                  [$it['qty'], $it['variation_id']]);
                    } else {
                        $db->run('UPDATE products
                                     SET stock_qty = stock_qty - ?
                                   WHERE id = ?',
                                  [$it['qty'], $it['product_id']]);
                    }
                    $db->insert('inventory_movements', [
                        'variation_id'   => $it['variation_id'],
                        'product_id'     => $it['product_id'],
                        'delta'          => -$it['qty'],
                        'reason'         => 'sale',
                        'reference_type' => 'order',
                        'reference_id'   => $orderId,
                        'note'           => 'Order '.$orderNumber,
                    ]);
                }

                $db->run('UPDATE products SET sales_count = sales_count + ? WHERE id = ?',
                          [$it['qty'], $it['product_id']]);
            }

            $db->insert('order_status_history', [
                'order_id'    => $orderId,
                'from_status' => null,
                'to_status'   => 'pending_otp',
                'note'        => 'Order created. Awaiting OTP verification.',
            ]);

            if ($couponRow) {
                $db->run('UPDATE coupons SET uses_count = uses_count + 1 WHERE id = ?',
                          [$couponRow['id']]);
            }

            // 5. Generate + send OTP (best-effort, do not fail the transaction).
            try {
                OtpService::sendForOrder((int)$orderId, $phone);
            } catch (\Throwable $e) {
                error_log('[OTP send fail] '.$e->getMessage());
            }

            // 6. Mark abandoned-cart row as recovered if any.
            if (!empty($_SESSION['ac_token'])) {
                $db->run('UPDATE abandoned_carts SET recovered_at = NOW() WHERE recovery_token = ?',
                          [$_SESSION['ac_token']]);
                unset($_SESSION['ac_token']);
            }

            CartService::clear();

            return [
                'order_id'     => (int)$orderId,
                'order_number' => $orderNumber,
                'event_id'     => $eventId,
                'grand_total'  => $grand,
                'status'       => 'pending_otp',
                'requires_otp' => true,
            ];
        });
    }

    public static function upsertCustomer(string $name, string $phone, ?string $email,
                                          int $div, int $dist, int $ps, string $addr): int
    {
        $existing = Database::i()->one(
            'SELECT id, total_orders FROM customers WHERE phone = ? LIMIT 1', [$phone]);
        if ($existing) {
            Database::i()->update('customers', [
                'first_name' => $name,
                'email'      => $email,
                'default_division_id' => $div ?: null,
                'default_district_id' => $dist ?: null,
                'default_police_station_id' => $ps ?: null,
                'address_line' => $addr,
                'last_order_at' => date('Y-m-d H:i:s'),
                'total_orders' => (int)($existing['total_orders'] ?? 0) + 1,
            ], 'id = :_id', [':_id' => $existing['id']]);
            return (int)$existing['id'];
        }
        return Database::i()->insert('customers', [
            'first_name' => $name,
            'email'      => $email,
            'phone'      => $phone,
            'default_division_id' => $div ?: null,
            'default_district_id' => $dist ?: null,
            'default_police_station_id' => $ps ?: null,
            'address_line' => $addr,
            'total_orders' => 1,
            'last_order_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Restock and cancel an order. Called from admin panel and by
     * automatic cancellation flows.
     */
    public static function cancelAndRestock(int $orderId, ?int $userId = null, string $note = 'Cancelled by admin'): void
    {
        $db = Database::i();
        $db->transaction(function () use ($db, $orderId, $userId, $note) {
            $order = $db->one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if (!$order) throw new \RuntimeException('Order not found.');
            if (in_array($order['status'], ['cancelled','refunded'], true)) return;

            $items = $db->all('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
            foreach ($items as $it) {
                if ($it['variation_id']) {
                    $db->run('UPDATE product_variations SET stock_qty = stock_qty + ? WHERE id = ?',
                              [$it['quantity'], $it['variation_id']]);
                } elseif ($it['product_id']) {
                    $db->run('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?',
                              [$it['quantity'], $it['product_id']]);
                }
                $db->insert('inventory_movements', [
                    'variation_id' => $it['variation_id'],
                    'product_id'   => $it['product_id'],
                    'delta'        => $it['quantity'],
                    'reason'       => 'return',
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                    'note'         => 'Restocked: '.$note,
                    'user_id'      => $userId,
                ]);
            }

            $db->update('orders', [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
            ], 'id = :_id', [':_id' => $orderId]);

            $db->insert('order_status_history', [
                'order_id' => $orderId,
                'from_status' => $order['status'],
                'to_status'   => 'cancelled',
                'note'        => $note,
                'user_id'     => $userId,
            ]);
        });
    }
}
