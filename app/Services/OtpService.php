<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;
use LH\Core\Helpers;

final class OtpService
{
    public static function sendForOrder(int $orderId, string $phone): void
    {
        $cfg     = $GLOBALS['LH_CFG']['sms'];
        $length  = (int)$cfg['otp_length'];
        $ttl     = (int)$cfg['otp_ttl'];

        // Generate cryptographically random numeric OTP
        $code = '';
        for ($i = 0; $i < $length; $i++) $code .= random_int(0, 9);
        $hash = password_hash($code, PASSWORD_BCRYPT);

        $expires = date('Y-m-d H:i:s', time() + $ttl);

        Database::i()->insert('otp_codes', [
            'phone'     => $phone,
            'code_hash' => $hash,
            'purpose'   => 'checkout',
            'order_id'  => $orderId,
            'expires_at'=> $expires,
        ]);

        $template = (string) Helpers::setting('otp_template',
            'Your LeatherHood OTP is {OTP}. Valid for 10 minutes.');
        $message  = strtr($template, ['{OTP}' => $code]);

        SmsGateway::send($phone, $message, 'otp', $orderId);
    }

    /**
     * Verify a code submitted by the customer for a given order.
     * Marks the order as verified and enqueues a confirmation SMS.
     */
    public static function verify(int $orderId, string $code): bool
    {
        $row = Database::i()->one(
            'SELECT * FROM otp_codes
              WHERE order_id = ? AND consumed_at IS NULL
              ORDER BY id DESC LIMIT 1', [$orderId]);
        if (!$row) return false;
        if (strtotime($row['expires_at']) < time()) return false;
        if ((int)$row['attempts'] >= 5) return false;

        Database::i()->run('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?',
            [$row['id']]);

        if (!password_verify($code, $row['code_hash'])) return false;

        Database::i()->run('UPDATE otp_codes SET consumed_at = NOW() WHERE id = ?', [$row['id']]);

        Database::i()->update('orders', [
            'is_verified' => 1,
            'verified_at' => date('Y-m-d H:i:s'),
            'status'      => 'pending',
        ], 'id = :_id', [':_id' => $orderId]);

        // Bump customer lifetime spend
        $o = Database::i()->one('SELECT customer_id, grand_total FROM orders WHERE id = ?', [$orderId]);
        if ($o && $o['customer_id']) {
            Database::i()->run('UPDATE customers SET total_spent = total_spent + ? WHERE id = ?',
                [(float)$o['grand_total'], (int)$o['customer_id']]);
        }

        Database::i()->insert('order_status_history', [
            'order_id'    => $orderId,
            'from_status' => 'pending_otp',
            'to_status'   => 'pending',
            'note'        => 'OTP verified by customer.',
        ]);

        return true;
    }

    /** Manual override from admin panel. */
    public static function manualVerify(int $orderId, int $userId): void
    {
        Database::i()->update('orders', [
            'is_verified' => 1,
            'verified_at' => date('Y-m-d H:i:s'),
            'verified_by' => $userId,
            'status'      => 'pending',
        ], 'id = :_id', [':_id' => $orderId]);

        Database::i()->insert('order_status_history', [
            'order_id'    => $orderId,
            'from_status' => 'pending_otp',
            'to_status'   => 'pending',
            'note'        => 'OTP manually verified by admin.',
            'user_id'     => $userId,
        ]);
    }
}
