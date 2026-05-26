<?php
use LH\Core\Helpers;
use LH\Core\Database;

$page_title = 'Shop';
$cat   = trim((string)Helpers::input('cat',''));
$sort  = Helpers::input('sort','newest');
$q     = trim((string)Helpers::input('q',''));
$page  = max(1, (int)Helpers::input('p', 1));
$per   = 12;

$where  = "p.status='active'";
$params = [];
if ($cat) {
    $where .= " AND p.id IN (SELECT product_id FROM product_categories pc
                             JOIN categories c ON c.id=pc.category_id WHERE c.slug = ?)";
    $params[] = $cat;
}
if ($q) { $where .= " AND p.name LIKE ?"; $params[] = "%$q%"; }

$orderBy = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular'    => 'p.sales_count DESC',
    default      => 'p.published_at DESC, p.id DESC',
};

$total = (int) Database::i()->value(
    "SELECT COUNT(*) FROM products p WHERE $where", $params);

$products = Database::i()->all(
    "SELECT p.id,p.name,p.slug,p.price,p.compare_at_price,p.featured_image,p.is_new,
            p.rating_avg,p.rating_count
       FROM products p WHERE $where ORDER BY $orderBy
       LIMIT $per OFFSET ".(($page - 1) * $per),
    $params);

$cats = Database::i()->all("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order");

include __DIR__ . '/partials/header.php';
?>

<section class="container">
  <div class="section-head" style="padding-top:60px;margin-bottom:0">
    <div class="eyebrow">Collection</div>
    <h2><?= $cat ? Helpers::e(ucfirst($cat)) : 'All Products' ?></h2>
    <p>Hand-stitched, full-grain leather. <?= $total ?> products.</p>
  </div>

  <div class="shop-layout">
    <aside class="filters">
      <h4>Categories</h4>
      <ul>
        <li><a href="/shop" class="<?= !$cat ? 'active' : '' ?>">All</a></li>
        <?php foreach ($cats as $c): ?>
          <li><a href="/shop?cat=<?= Helpers::e($c['slug']) ?>" class="<?= $cat === $c['slug'] ? 'active' : '' ?>"><?= Helpers::e($c['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>

      <h4>Search</h4>
      <form method="get" action="/shop">
        <?php if ($cat): ?><input type="hidden" name="cat" value="<?= Helpers::e($cat) ?>"><?php endif; ?>
        <input class="" type="search" name="q" value="<?= Helpers::e($q) ?>" placeholder="Search…"
               style="width:100%;padding:10px 12px;border:1px solid var(--line);">
      </form>
    </aside>

    <main>
      <div class="shop-toolbar">
        <span class="muted">Showing <?= count($products) ?> of <?= $total ?></span>
        <form method="get">
          <?php foreach (['cat','q'] as $k) if ($val = Helpers::input($k)) echo '<input type="hidden" name="'.Helpers::e($k).'" value="'.Helpers::e($val).'">'; ?>
          <select name="sort" onchange="this.form.submit()">
            <option value="newest"     <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            <option value="popular"    <?= $sort==='popular'?'selected':'' ?>>Most Popular</option>
            <option value="price_asc"  <?= $sort==='price_asc'?'selected':'' ?>>Price: Low → High</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High → Low</option>
          </select>
        </form>
      </div>

      <div class="grid grid-3">
        <?php if (!$products): ?>
          <p class="muted">No products found.</p>
        <?php endif; ?>
        <?php foreach ($products as $p): ?>
          <a class="card" href="/product/<?= Helpers::e($p['slug']) ?>">
            <div class="thumb">
              <?php if ($p['compare_at_price'] && $p['compare_at_price'] > $p['price']): ?>
                <span class="badge sale">−<?= round((1 - $p['price']/$p['compare_at_price'])*100) ?>%</span>
              <?php elseif ($p['is_new']): ?>
                <span class="badge new">New</span>
              <?php endif; ?>
              <img src="<?= Helpers::e($p['featured_image'] ?: '/assets/images/placeholder.webp') ?>" alt="<?= Helpers::e($p['name']) ?>" loading="lazy">
              <button class="quickview" onclick="event.preventDefault();LH.quickAdd(<?= (int)$p['id'] ?>)">Select Options</button>
            </div>
            <div class="info">
              <div class="stars">★ ★ ★ ★ ★ <span class="muted" style="color:var(--ink-soft);font-size:11px">(<?= (int)$p['rating_count'] ?>)</span></div>
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

      <?php
      $pages = (int) ceil($total / $per);
      if ($pages > 1): ?>
        <nav style="text-align:center;margin-top:48px">
          <?php for ($i=1; $i<=$pages; $i++):
            $params2 = array_merge($_GET, ['p' => $i]);
          ?>
            <a href="?<?= http_build_query($params2) ?>"
               style="display:inline-block;padding:8px 14px;border:1px solid var(--line);margin:0 2px;<?= $i===$page?'background:var(--ink);color:#fff;border-color:var(--ink)':'' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    </main>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
