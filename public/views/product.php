<?php
use LH\Core\Helpers;
use LH\Core\Database;

$slug = $GLOBALS['slug'] ?? '';
$product = Database::i()->one(
    "SELECT * FROM products WHERE slug = ? AND status = 'active' LIMIT 1", [$slug]);
if (!$product) { http_response_code(404); $GLOBALS['slug'] = $slug; require __DIR__.'/404.php'; return; }

$gallery = $product['gallery'] ? json_decode($product['gallery'], true) : [];
$variations = Database::i()->all(
    "SELECT * FROM product_variations WHERE product_id = ? AND is_active = 1", [$product['id']]);
$reviews = Database::i()->all(
    "SELECT * FROM product_reviews WHERE product_id = ? AND status='approved' ORDER BY id DESC LIMIT 8",
    [$product['id']]);

$page_title = $product['name'];
$page_desc  = $product['short_description'] ?: strip_tags(substr((string)$product['description'], 0, 160));

// Bump view counter (best-effort)
Database::i()->run('UPDATE products SET views_count = views_count + 1 WHERE id = ?', [$product['id']]);

include __DIR__ . '/partials/header.php';
$tpl = $product['template_type'] ?: 'default';
?>

<section class="container product-detail" data-template="<?= Helpers::e($tpl) ?>">
  <div>
    <div class="gallery">
      <img src="<?= Helpers::e($product['featured_image'] ?: '/assets/images/placeholder.webp') ?>" alt="<?= Helpers::e($product['name']) ?>" id="main-img" style="width:100%;height:100%;object-fit:cover">
    </div>
    <?php if (count($gallery) > 1): ?>
      <div class="grid grid-4" style="margin-top:14px;gap:8px">
        <?php foreach ($gallery as $g): ?>
          <img src="<?= Helpers::e($g) ?>" style="aspect-ratio:1/1;object-fit:cover;cursor:pointer;border:1px solid var(--line)" onclick="document.getElementById('main-img').src=this.src">
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="muted" style="font-size:11px;letter-spacing:.3em;text-transform:uppercase;color:var(--gold)">LeatherHood</div>
    <h1><?= Helpers::e($product['name']) ?></h1>
    <div class="stars" style="color:var(--gold);margin:6px 0">★ ★ ★ ★ ★ <span class="muted" style="color:var(--ink-soft);font-size:12px">(<?= (int)$product['rating_count'] ?> reviews)</span></div>

    <div class="price-row">
      <?php if ($product['compare_at_price'] && $product['compare_at_price'] > $product['price']): ?>
        <del><?= Helpers::bdt($product['compare_at_price']) ?></del>
      <?php endif; ?>
      <span class="tk"><?= Helpers::bdt($product['price']) ?></span>
    </div>

    <p class="muted"><?= Helpers::e($product['short_description']) ?></p>

    <?php if ($variations): ?>
      <div style="margin:18px 0">
        <label style="font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--ink-soft)">Choose option</label>
        <select id="variation-select" style="width:100%;padding:12px;border:1px solid var(--line);margin-top:6px">
          <?php foreach ($variations as $v): ?>
            <option value="<?= (int)$v['id'] ?>" data-stock="<?= (int)$v['stock_qty'] ?>" data-price="<?= (float)($v['price'] ?? $product['price']) ?>">
              <?= Helpers::e($v['option_summary'] ?: $v['sku']) ?> — <?= Helpers::bdt($v['price'] ?? $product['price']) ?>
              <?= $v['stock_qty'] <= 0 ? ' (Out of stock)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <form id="add-form" onsubmit="return LH.addToCartForm(event)">
      <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
      <input type="hidden" name="variation_id" id="variation-id" value="">
      <div style="display:flex;align-items:center;margin-bottom:14px">
        <div class="qty">
          <button type="button" onclick="LH.qty(-1)">−</button>
          <input type="number" name="qty" value="1" min="1" id="qty">
          <button type="button" onclick="LH.qty(1)">+</button>
        </div>
        <button class="btn" type="submit" style="flex:1">Add to Cart</button>
      </div>
      <a class="btn btn-gold btn-block" href="/checkout" style="text-align:center">Buy It Now</a>
    </form>

    <hr class="hr">
    <div class="muted" style="font-size:13px;line-height:2">
      <div>✓ <?= Helpers::e(Helpers::setting('warranty_period','1 Year Manufacturer Warranty')) ?></div>
      <div>✓ <?= Helpers::e(Helpers::setting('return_policy','7-Day Easy Returns')) ?></div>
      <div>✓ Cash on Delivery available</div>
      <div>✓ SKU: <?= Helpers::e($product['sku']) ?></div>
    </div>
  </div>
</section>

<section class="container" style="padding-bottom:60px">
  <h2 style="font-size:1.4rem">Description</h2>
  <div class="muted" style="font-size:15px;line-height:1.8;max-width:780px"><?= $product['description'] ?></div>

  <?php if ($reviews): ?>
    <h2 style="font-size:1.4rem;margin-top:48px">Customer Reviews</h2>
    <?php foreach ($reviews as $r): ?>
      <div style="border-top:1px solid var(--line);padding:18px 0">
        <div style="color:var(--gold)"><?= str_repeat('★', (int)$r['rating']).str_repeat('☆', 5-(int)$r['rating']) ?></div>
        <h4 style="margin:6px 0;font-family:var(--serif)"><?= Helpers::e($r['title'] ?: 'Excellent') ?></h4>
        <p class="muted" style="margin:0"><?= Helpers::e($r['body']) ?></p>
        <small class="muted">— <?= Helpers::e($r['name']) ?>, <?= Helpers::e(date('M Y', strtotime($r['created_at']))) ?></small>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<script>
document.getElementById('variation-select')?.addEventListener('change', e=>{
  document.getElementById('variation-id').value = e.target.value;
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
