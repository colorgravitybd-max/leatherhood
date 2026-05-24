<?php
use LH\Core\Helpers;
use LH\Core\Database;

$num = $GLOBALS['order_number'] ?? '';
$order = Database::i()->one('SELECT * FROM orders WHERE order_number = ? LIMIT 1', [$num]);
if (!$order) { http_response_code(404); require __DIR__.'/404.php'; return; }

$page_title = 'Order Confirmed';
include __DIR__ . '/partials/header.php';
?>

<section class="container" style="padding:80px 0;text-align:center;max-width:680px">
  <div style="font-size:60px;margin-bottom:20px">✓</div>
  <h2 style="font-size:2rem">Thank you, <?= Helpers::e(explode(' ', $order['customer_name'])[0]) ?>!</h2>
  <p class="muted">Your order <strong>#<?= Helpers::e($order['order_number']) ?></strong> has been received.</p>

  <div class="checkout-card" style="text-align:left;margin-top:30px">
    <div class="totals">
      <div class="row"><span>Order Number</span><span class="tk">#<?= Helpers::e($order['order_number']) ?></span></div>
      <div class="row"><span>Payment</span><span class="tk"><?= strtoupper($order['payment_method']) ?></span></div>
      <div class="row"><span>Status</span><span class="tk"><?= str_replace('_',' ', $order['status']) ?></span></div>
      <div class="row grand"><span>Grand Total</span><span class="tk"><?= Helpers::bdt($order['grand_total']) ?></span></div>
    </div>
  </div>

  <p class="muted" style="margin-top:30px">
    A confirmation will be sent to your phone shortly.<br>
    For inquiries, call <strong><?= Helpers::e(Helpers::setting('contact_phone','+8801700000000')) ?></strong>.
  </p>
  <a class="btn" href="/shop">Continue Shopping</a>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
