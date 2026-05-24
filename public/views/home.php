<?php
use LH\Core\Helpers;
use LH\Core\Database;

$page_title = 'Premium Leather, Crafted in Bangladesh';
$popular = Database::i()->all(
    "SELECT id,name,slug,price,compare_at_price,featured_image,is_new,rating_avg,rating_count
       FROM products WHERE status='active' AND is_popular=1
       ORDER BY sales_count DESC, id DESC LIMIT 8");
$cats = Database::i()->all("SELECT * FROM categories WHERE is_active=1 AND is_featured=1 ORDER BY sort_order LIMIT 4");

include __DIR__ . '/partials/header.php';
?>

<section class="hero">
  <div class="hero-inner">
    <div class="eyebrow muted" style="font-size:11px;letter-spacing:.4em;text-transform:uppercase;margin-bottom:18px;color:var(--gold)">— LeatherHood —</div>
    <h1><?= Helpers::e(Helpers::setting('hero_headline', 'Crafted in Bangladesh. Built for a Lifetime.')) ?></h1>
    <p><?= Helpers::e(Helpers::setting('hero_subline', 'Genuine full-grain leather. Hand-stitched. One-year warranty.')) ?></p>
    <a class="btn" href="/shop">Shop the Collection</a>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">Most Popular</div>
      <h2>Bestsellers</h2>
      <p>Loved by 10,000+ customers across Bangladesh.</p>
    </div>
    <div class="grid grid-4">
      <?php foreach ($popular as $p): ?>
        <a class="card" href="/product/<?= Helpers::e($p['slug']) ?>">
          <div class="thumb">
            <?php if ($p['compare_at_price'] && $p['compare_at_price'] > $p['price']): ?>
              <span class="badge sale">Sale</span>
            <?php elseif ($p['is_new']): ?>
              <span class="badge new">New</span>
            <?php endif; ?>
            <img src="<?= Helpers::e($p['featured_image'] ?: '/assets/images/placeholder.webp') ?>" alt="<?= Helpers::e($p['name']) ?>" loading="lazy">
            <button class="quickview" data-id="<?= (int)$p['id'] ?>" onclick="event.preventDefault();LH.quickAdd(<?= (int)$p['id'] ?>)">Add to Cart</button>
          </div>
          <div class="info">
            <div class="stars">★ ★ ★ ★ ★</div>
            <h3><?= Helpers::e($p['name']) ?></h3>
            <div class="price">
              <?php if ($p['compare_at_price'] && $p['compare_at_price'] > $p['price']): ?>
                <del><?= Helpers::bdt($p['compare_at_price']) ?></del>
              <?php endif; ?>
              <span class="tk"><?= Helpers::bdt($p['price']) ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="background:var(--bg-soft)">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">Shop by Category</div>
      <h2>Iconic Essentials</h2>
    </div>
    <div class="split">
      <?php foreach (array_slice($cats, 0, 2) as $c): ?>
        <a href="/shop?cat=<?= Helpers::e($c['slug']) ?>">
          <img src="<?= Helpers::e($c['image'] ?: '/assets/images/cat-'.$c['slug'].'.webp') ?>" alt="<?= Helpers::e($c['name']) ?>" loading="lazy">
          <div class="split-overlay">
            <div>
              <h3><?= Helpers::e($c['name']) ?></h3>
              <span>Shop now →</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (count($cats) > 2): ?>
      <div class="split" style="margin-top:30px">
        <?php foreach (array_slice($cats, 2, 2) as $c): ?>
          <a href="/shop?cat=<?= Helpers::e($c['slug']) ?>">
            <img src="<?= Helpers::e($c['image'] ?: '/assets/images/cat-'.$c['slug'].'.webp') ?>" alt="<?= Helpers::e($c['name']) ?>" loading="lazy">
            <div class="split-overlay">
              <div>
                <h3><?= Helpers::e($c['name']) ?></h3>
                <span>Shop now →</span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="trust">
  <div class="container">
    <div class="trust-grid">
      <div class="trust-item"><div class="icon">🛡</div><h4>1 Year Warranty</h4><p>On every leather product.</p></div>
      <div class="trust-item"><div class="icon">🚚</div><h4>Fast Delivery</h4><p>Inside Dhaka in 24 hours.</p></div>
      <div class="trust-item"><div class="icon">↩</div><h4>Easy Returns</h4><p>7-day no-questions returns.</p></div>
      <div class="trust-item"><div class="icon">✦</div><h4>100% Genuine</h4><p>Full-grain leather only.</p></div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
