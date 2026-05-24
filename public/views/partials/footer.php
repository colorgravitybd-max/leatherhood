<?php
use LH\Core\Helpers;
use LH\Core\Database;

$foot_scripts = Database::i()->all(
    "SELECT * FROM marketing_scripts WHERE is_active = 1 AND placement IN ('footer','body_close') ORDER BY sort_order");
?>
<footer>
  <div class="container">
    <div class="foot-grid">
      <div>
        <div class="brand" style="color:#fff; margin-bottom:14px"><?= Helpers::e(Helpers::setting('site_name','LeatherHood')) ?></div>
        <p style="margin:0 0 18px;color:#a8a8a8;max-width:340px"><?= Helpers::e(Helpers::setting('site_tagline','Premium leather goods, hand-stitched in Bangladesh.')) ?></p>
        <div style="font-size:13px;color:#bbb">
          <div>📞 <?= Helpers::e(Helpers::setting('contact_phone','+8801700000000')) ?></div>
          <div>✉ <?= Helpers::e(Helpers::setting('contact_email','support@leatherhoodbd.com')) ?></div>
        </div>
      </div>
      <div>
        <h4>Shop</h4>
        <ul>
          <li><a href="/shop">All Products</a></li>
          <li><a href="/shop?cat=belts">Belts</a></li>
          <li><a href="/shop?cat=wallets">Wallets</a></li>
          <li><a href="/shop?cat=shoes">Shoes</a></li>
          <li><a href="/shop?cat=bags">Bags</a></li>
        </ul>
      </div>
      <div>
        <h4>Customer Care</h4>
        <ul>
          <li><a href="/page/contact">Contact Us</a></li>
          <li><a href="/page/refund-and-returns">Refund & Returns</a></li>
          <li><a href="/page/privacy-policy">Privacy Policy</a></li>
          <li><a href="/page/terms-and-conditions">Terms & Conditions</a></li>
        </ul>
      </div>
      <div>
        <h4>Newsletter</h4>
        <p style="font-size:13px;color:#a8a8a8;margin:0 0 12px">Get 10% off your first order.</p>
        <form class="newsletter" onsubmit="event.preventDefault();this.querySelector('input').value='';alert('Thanks for subscribing!')">
          <input type="email" placeholder="Your email" required>
          <button type="submit">Join</button>
        </form>
      </div>
    </div>
    <div class="copy">© <?= date('Y') ?> <?= Helpers::e(Helpers::setting('site_name','LeatherHood')) ?>. All rights reserved.</div>
  </div>
</footer>
<script src="/assets/js/site.js" defer></script>
<?php foreach ($foot_scripts as $s) { echo $s['code']; } ?>
</body></html>
