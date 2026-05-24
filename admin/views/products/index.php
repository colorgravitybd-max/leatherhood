<?php
use LH\Core\Database;
use LH\Core\Helpers;

$page_title = 'Products';
$db = Database::i();

$q     = trim((string)Helpers::input('q', ''));
$cat   = (int) Helpers::input('cat', 0);
$page  = max(1, (int)Helpers::input('p', 1));
$per   = 25;

$where = '1=1'; $params = [];
if ($q) { $where .= ' AND (p.name LIKE ? OR p.sku LIKE ?)'; $params[]="%$q%"; $params[]="%$q%"; }
if ($cat) { $where .= ' AND p.primary_category_id = ?'; $params[] = $cat; }

$total = (int)$db->value("SELECT COUNT(*) FROM products p WHERE $where", $params);
$rows  = $db->all("SELECT p.* FROM products p WHERE $where ORDER BY p.id DESC LIMIT $per OFFSET ".(($page-1)*$per), $params);
$cats  = $db->all('SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order, name');
include __DIR__ . '/../_layout_top.php';
?>

<div class="card">
  <form class="searchbar" method="get">
    <input class="input" type="search" name="q" value="<?= Helpers::e($q) ?>" placeholder="Search by name or SKU…">
    <select class="select" name="cat" onchange="this.form.submit()">
      <option value="0">All categories</option>
      <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat===(int)$c['id']?'selected':'' ?>><?= Helpers::e($c['name']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn">Search</button>
    <span style="flex:1"></span>
    <a class="btn btn-primary" href="/admin/product?id=new">+ New Product</a>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead><tr><th></th><th>Product</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th class="actions"></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $p): ?>
        <tr>
          <td><img src="<?= Helpers::e($p['featured_image'] ?: '/assets/images/placeholder.webp') ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px"></td>
          <td><b><?= Helpers::e($p['name']) ?></b><br><small style="color:var(--ink-soft)"><?= Helpers::e($p['template_type']) ?> · <?= (int)$p['views_count'] ?> views</small></td>
          <td><?= Helpers::e($p['sku']) ?></td>
          <td class="tk"><?= Helpers::bdt($p['price']) ?></td>
          <td><?= (int)$p['stock_qty'] ?></td>
          <td><span class="pill <?= $p['status']==='active'?'delivered':'cancelled' ?>"><?= Helpers::e($p['status']) ?></span></td>
          <td class="actions">
            <a class="btn btn-sm" href="/admin/product?id=<?= (int)$p['id'] ?>">Edit</a>
            <a class="btn btn-sm" href="/product/<?= Helpers::e($p['slug']) ?>" target="_blank">View</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php $pages=(int)ceil($total/$per); if ($pages>1): ?>
    <nav class="pager" style="margin-top:18px">
      <?php for ($i=1;$i<=$pages;$i++): $params2=array_merge($_GET,['p'=>$i]); ?>
        <a class="<?= $i===$page?'current':'' ?>" href="?<?= http_build_query($params2) ?>"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../_layout_bottom.php'; ?>
