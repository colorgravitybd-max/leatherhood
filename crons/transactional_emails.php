<?php
/**
 * Cron 1 — Transactional Emails
 * --------------------------------------------------------------
 * Runs every 5–15 minutes via Hostinger Cron:
 *   php /home/USER/domains/leatherhoodbd.com/leatherhood/crons/transactional_emails.php
 *
 * Scans for orders that:
 *  - Just transitioned to `pending` (after OTP) → fire confirmation email.
 *  - Just transitioned to `shipped`            → fire shipping email.
 *  - Just transitioned to `delivered`          → fire thank-you / review email.
 */
declare(strict_types=1);
require __DIR__ . '/../app/Core/Bootstrap.php';

use LH\Core\Database;
use LH\Core\Helpers;
use LH\Services\Mailer;

$db   = Database::i();
$now  = date('Y-m-d H:i:s');
$send = function (array $o, string $template, string $subject, string $html) use ($db) {
    if (empty($o['customer_email'])) return; // skip if no email
    $sent = $db->value('SELECT 1 FROM email_logs WHERE order_id=? AND template=? AND status=\'sent\' LIMIT 1',
                       [$o['id'], $template]);
    if ($sent) return;
    Mailer::send($o['customer_email'], $subject, $html, null, (int)$o['id']);
};

$tpl = function (string $title, string $body, array $o) {
    $brand = Helpers::setting('site_name','LeatherHood');
    $bdt   = fn($n) => '৳'.number_format((float)$n, 0);
    return "<div style=\"font-family:Inter,Arial,sans-serif;max-width:560px;margin:0 auto\">
      <h1 style=\"font-family:'Playfair Display',serif;letter-spacing:.18em\">$brand</h1>
      <h2 style=\"font-family:'Playfair Display',serif\">$title</h2>
      $body
      <hr style=\"border:0;border-top:1px solid #eee;margin:24px 0\">
      <p style=\"color:#666;font-size:13px\">Order #{$o['order_number']} · Total {$bdt($o['grand_total'])}</p>
      <p style=\"font-size:12px;color:#aaa\">© ".date('Y')." $brand</p></div>";
};

$confirmed = $db->all("SELECT * FROM orders WHERE status='pending' AND verified_at IS NOT NULL
                        AND id NOT IN (SELECT order_id FROM email_logs WHERE template='order_confirmed' AND status='sent')
                       LIMIT 30");
foreach ($confirmed as $o) {
    $body = "<p>Hi {$o['customer_name']},</p>
             <p>Thanks for your order — it's been confirmed and is being prepared.</p>";
    $send($o, 'order_confirmed', 'Order Confirmed — #'.$o['order_number'],
          $tpl('Your order is confirmed', $body, $o));
}

$shipped = $db->all("SELECT * FROM orders WHERE status='shipped'
                      AND id NOT IN (SELECT order_id FROM email_logs WHERE template='order_shipped' AND status='sent')
                     LIMIT 30");
foreach ($shipped as $o) {
    $track = $o['courier_tracking_url'] ? "<p><a href=\"{$o['courier_tracking_url']}\">Track your parcel</a></p>" : '';
    $body  = "<p>Hi {$o['customer_name']},</p>
              <p>Your order #{$o['order_number']} has shipped via {$o['courier']}.</p>$track";
    $send($o, 'order_shipped', 'Your order is on the way — #'.$o['order_number'],
          $tpl('Out for delivery', $body, $o));
}

$delivered = $db->all("SELECT * FROM orders WHERE status='delivered'
                        AND id NOT IN (SELECT order_id FROM email_logs WHERE template='order_delivered' AND status='sent')
                       LIMIT 30");
foreach ($delivered as $o) {
    $body = "<p>Hi {$o['customer_name']},</p>
             <p>We hope you love your new LeatherHood pieces! If you have a minute, leave us a review — it really helps.</p>";
    $send($o, 'order_delivered', 'Thank you, {$o["customer_name"]} — please review',
          $tpl('Thanks for shopping LeatherHood', $body, $o));
}

echo "[".$now."] Transactional emails cycle complete.\n";
