<?php
/**
 * Cron 2 — Cart Abandonment Recovery
 * --------------------------------------------------------------
 * Runs every 30–60 minutes via Hostinger Cron.
 * Targets carts/orders that:
 *   • Status = 'pending_otp' and older than 30 minutes  → SMS reminder
 *   • Status = 'pending_otp' and older than 24 hours    → mark abandoned
 *   • abandoned_carts row not recovered after 1h        → email/SMS
 */
declare(strict_types=1);
require __DIR__ . '/../app/Core/Bootstrap.php';

use LH\Core\Database;
use LH\Core\Helpers;
use LH\Services\SmsGateway;
use LH\Services\Mailer;

$db = Database::i();
$now = date('Y-m-d H:i:s');

/* 1. Pending-OTP older than 30 min — nudge SMS */
$nudges = $db->all("SELECT * FROM orders
                    WHERE status='pending_otp'
                      AND created_at < NOW() - INTERVAL 30 MINUTE
                      AND created_at > NOW() - INTERVAL 24 HOUR
                      AND id NOT IN (SELECT order_id FROM sms_logs WHERE purpose='recovery' AND order_id IS NOT NULL)
                    LIMIT 50");
foreach ($nudges as $o) {
    $msg = "LeatherHood: Your order #{$o['order_number']} (".Helpers::bdt($o['grand_total']).") is waiting OTP confirmation. Complete it: "
        .rtrim($GLOBALS['LH_CFG']['app']['url'],'/')."/cart";
    SmsGateway::send($o['customer_phone'], $msg, 'recovery', (int)$o['id']);
}

/* 2. Pending-OTP older than 24h → abandoned + restock (best-effort) */
$old = $db->all("SELECT id FROM orders WHERE status='pending_otp' AND created_at < NOW() - INTERVAL 24 HOUR LIMIT 50");
foreach ($old as $o) {
    try {
        \LH\Services\CheckoutService::cancelAndRestock((int)$o['id'], null, 'Auto-abandoned (no OTP)');
        $db->update('orders', ['status'=>'abandoned'], 'id = :_id', [':_id' => $o['id']]);
    } catch (\Throwable $e) { error_log('Auto-abandon: '.$e->getMessage()); }
}

/* 3. Abandoned-cart rows older than 1 hour but unrecovered */
$ac = $db->all("SELECT * FROM abandoned_carts
                 WHERE recovered_at IS NULL
                   AND recovery_email_sent_at IS NULL
                   AND created_at < NOW() - INTERVAL 1 HOUR LIMIT 50");
foreach ($ac as $row) {
    $url = rtrim($GLOBALS['LH_CFG']['app']['url'],'/').'/cart?recover='.$row['recovery_token'];
    if ($row['customer_email']) {
        Mailer::send($row['customer_email'],
            'Did you forget something?',
            "<p>You left some lovely leather behind. Pick up where you left off:</p>
             <p><a href='$url'>Resume your cart →</a></p>");
    }
    if ($row['customer_phone']) {
        SmsGateway::send($row['customer_phone'],
            "LeatherHood: Pick up your cart at ".$url, 'recovery');
    }
    $db->update('abandoned_carts',
        ['recovery_email_sent_at' => $now],
        'id = :_id', [':_id' => $row['id']]);
}

echo "[$now] Abandonment cron done.\n";
