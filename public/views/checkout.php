<?php
use LH\Core\Helpers;
use LH\Services\CartService;
use LH\Services\ShippingService;

$page_title = 'Checkout';
$hyd = CartService::hydrate();
if (empty($hyd['items'])) { Helpers::redirect('/cart'); }
$divisions = ShippingService::divisions();
include __DIR__ . '/partials/header.php';
?>

<section class="container">
  <div class="section-head" style="padding-top:60px;margin-bottom:0">
    <div class="eyebrow">Checkout</div>
    <h2>Complete Your Order</h2>
    <p class="muted">All transactions are secure. Your data is encrypted.</p>
  </div>

  <div class="checkout-layout">

    <!-- LEFT: Forms -->
    <div>
      <div class="checkout-card" id="form-card">
        <h3>Billing & Shipping</h3>
        <div id="form-error"></div>

        <form id="checkout-form" onsubmit="return LH.placeOrder(event)">
          <input type="hidden" name="_csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">

          <div class="field-row">
            <div class="field">
              <label class="field-required">Full Name</label>
              <input type="text" name="customer_name" required>
            </div>
            <div class="field">
              <label class="field-required">Mobile</label>
              <input type="tel" name="customer_phone" required placeholder="01XXXXXXXXX" pattern="^(\+?88)?01[3-9]\d{8}$">
            </div>
          </div>

          <div class="field" style="margin-bottom:14px">
            <label>Email (optional, for receipts)</label>
            <input type="email" name="customer_email">
          </div>

          <div class="field-row">
            <div class="field">
              <label class="field-required">Division</label>
              <select name="division_id" id="division" required>
                <option value="">Select division</option>
                <?php foreach ($divisions as $d): ?>
                  <option value="<?= (int)$d['id'] ?>"><?= Helpers::e($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label class="field-required">District</label>
              <select name="district_id" id="district" required disabled>
                <option value="">Select district</option>
              </select>
            </div>
          </div>

          <div class="field-row">
            <div class="field">
              <label class="field-required">Police Station / Upazila</label>
              <select name="police_station_id" id="police-station" required disabled>
                <option value="">Select police station</option>
              </select>
            </div>
            <div class="field">
              <label>Landmark (optional)</label>
              <input type="text" name="landmark" placeholder="Near …">
            </div>
          </div>

          <div class="field" style="margin-bottom:14px">
            <label class="field-required">Full Address</label>
            <textarea name="address_line" rows="2" required placeholder="House, Road, Area"></textarea>
          </div>

          <div class="field" style="margin-bottom:14px">
            <label>Order Note (optional)</label>
            <textarea name="note" rows="2"></textarea>
          </div>

          <h3 style="margin-top:24px">Payment Method</h3>
          <div class="payment-method">
            <label>
              <input type="radio" name="payment_method" value="cod" checked>
              <span><b>Cash on Delivery</b><br><small class="muted">Pay when your order arrives.</small></span>
            </label>
            <?php if (Helpers::setting('bkash_enabled') === '1'): ?>
            <label>
              <input type="radio" name="payment_method" value="bkash">
              <span><b>bKash</b><br><small class="muted">Pay securely with your bKash wallet.</small></span>
            </label>
            <?php endif; ?>
          </div>

          <div class="field" style="margin-bottom:14px">
            <label>Coupon Code</label>
            <div style="display:flex;gap:8px">
              <input type="text" name="coupon_code" id="coupon" placeholder="SAVE10">
              <button type="button" class="btn btn-ghost" onclick="LH.recalc()">Apply</button>
            </div>
          </div>

          <button class="btn btn-block btn-gold" type="submit" id="place-btn">Place Order — <span id="grand-display">৳0</span></button>
          <p class="muted" style="font-size:12px;margin-top:14px;text-align:center">By placing this order you agree to our <a href="/page/terms-and-conditions" style="text-decoration:underline">Terms</a> and <a href="/page/privacy-policy" style="text-decoration:underline">Privacy Policy</a>.</p>
        </form>
      </div>

      <!-- OTP CARD (hidden until pending_otp) -->
      <div class="checkout-card otp-screen" id="otp-card" hidden>
        <h3>Verify your phone</h3>
        <p class="muted">We've sent a <strong>4-digit code</strong> to <span id="otp-phone"></span>. Enter it below to confirm your order.</p>
        <div id="otp-error"></div>
        <div class="otp-input">
          <input type="tel" maxlength="1" inputmode="numeric"><input type="tel" maxlength="1" inputmode="numeric"><input type="tel" maxlength="1" inputmode="numeric"><input type="tel" maxlength="1" inputmode="numeric">
        </div>
        <button class="btn btn-block btn-gold" onclick="LH.verifyOtp()">Verify & Confirm</button>
        <p class="muted" style="font-size:12px;margin-top:18px">
          Didn't get the code? <a href="#" onclick="LH.resendOtp(event)" style="text-decoration:underline">Resend OTP</a>
        </p>
      </div>
    </div>

    <!-- RIGHT: Sticky summary -->
    <aside class="summary checkout-card">
      <h3>Order Summary</h3>
      <?php foreach ($hyd['items'] as $it): ?>
        <div class="item">
          <img src="<?= Helpers::e($it['image'] ?: '/assets/images/placeholder.webp') ?>" alt="">
          <div class="info">
            <b><?= Helpers::e($it['name']) ?></b><br>
            <small class="muted"><?= Helpers::e($it['option_summary'] ?? $it['sku']) ?> × <?= (int)$it['qty'] ?></small>
          </div>
          <div class="price"><?= Helpers::bdt($it['line_total']) ?></div>
        </div>
      <?php endforeach; ?>

      <div class="totals">
        <div class="row"><span>Subtotal</span><span id="sub" class="tk"><?= Helpers::bdt($hyd['subtotal']) ?></span></div>
        <div class="row"><span>Discount</span><span id="disc" class="tk">৳0</span></div>
        <div class="row"><span>Shipping</span><span id="ship" class="tk muted">— select district —</span></div>
        <div class="row grand"><span>Total</span><span id="grand" class="tk"><?= Helpers::bdt($hyd['subtotal']) ?></span></div>
      </div>
    </aside>
  </div>
</section>

<script>
window.LH_CART = <?= json_encode([
  'subtotal' => $hyd['subtotal'],
  'csrf'     => Helpers::csrfToken(),
]) ?>;
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
