<?php
use LH\Core\Helpers;
use LH\Services\CartService;

$page_title = 'Your Cart';
$hyd = CartService::hydrate();
include __DIR__ . '/partials/header.php';
?>

<section class="container" style="padding:60px 0;min-height:50vh">
  <div class="section-head">
    <div class="eyebrow">Cart</div>
    <h2>Your Bag</h2>
  </div>

  <?php if (empty($hyd['items'])): ?>
    <div class="text-center" style="padding:60px 0">
      <p class="muted">Your bag is empty.</p>
      <a class="btn" href="/shop">Start Shopping</a>
    </div>
  <?php else: ?>
    <div class="checkout-layout">
      <div class="checkout-card">
        <h3>Items</h3>
        <?php foreach ($hyd['items'] as $it): ?>
          <div class="summary item" style="border-bottom:1px solid var(--line);padding:14px 0">
            <img src="<?= Helpers::e($it['image'] ?: '/assets/images/placeholder.webp') ?>" alt="">
            <div class="info" style="flex:1">
              <b><?= Helpers::e($it['name']) ?></b><br>
              <small class="muted"><?= Helpers::e($it['option_summary'] ?? $it['sku']) ?></small>
              <div class="qty" style="margin-top:8px;display:inline-flex;border:1px solid var(--line)">
                <button onclick="LH.cartUpdate('<?= Helpers::e($it['key']) ?>', <?= $it['qty']-1 ?>)" style="width:32px;height:32px;background:none;border:0;cursor:pointer">−</button>
                <span style="width:38px;text-align:center;line-height:32px"><?= (int)$it['qty'] ?></span>
                <button onclick="LH.cartUpdate('<?= Helpers::e($it['key']) ?>', <?= $it['qty']+1 ?>)" style="width:32px;height:32px;background:none;border:0;cursor:pointer">+</button>
              </div>
              <button class="muted" style="background:none;border:0;cursor:pointer;margin-left:14px;font-size:12px;text-decoration:underline" onclick="LH.cartRemove('<?= Helpers::e($it['key']) ?>')">Remove</button>
            </div>
            <div class="price"><b><?= Helpers::bdt($it['line_total']) ?></b></div>
          </div>
        <?php endforeach; ?>
      </div>

      <aside class="summary checkout-card">
        <h3>Order Summary</h3>
        <div class="totals">
          <div class="row"><span>Subtotal</span><span class="tk"><?= Helpers::bdt($hyd['subtotal']) ?></span></div>
          <div class="row"><span>Shipping</span><span class="muted">Calculated at checkout</span></div>
          <div class="row grand"><span>Total</span><span class="tk"><?= Helpers::bdt($hyd['subtotal']) ?></span></div>
        </div>
        <a class="btn btn-block" href="/checkout" style="margin-top:20px;text-align:center">Proceed to Checkout</a>
        <a class="btn btn-ghost btn-block" href="/shop" style="margin-top:8px;text-align:center">Continue Shopping</a>
      </aside>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
