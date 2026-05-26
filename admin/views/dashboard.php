<?php
use LH\Core\Database;
use LH\Core\Helpers;

$page_title = 'Dashboard';

$db = Database::i();
$today        = $db->one("SELECT COUNT(*) c, COALESCE(SUM(grand_total),0) v FROM orders WHERE DATE(created_at)=CURDATE()");
$yest         = $db->one("SELECT COUNT(*) c, COALESCE(SUM(grand_total),0) v FROM orders WHERE DATE(created_at)=CURDATE()-INTERVAL 1 DAY");
$this_month   = $db->one("SELECT COUNT(*) c, COALESCE(SUM(grand_total),0) v FROM orders WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
$pending      = (int)$db->value("SELECT COUNT(*) FROM orders WHERE status IN ('pending','pending_otp','processing')");
$lowStock     = $db->all("SELECT id,name,stock_qty,low_stock_threshold FROM products WHERE track_inventory=1 AND stock_qty <= low_stock_threshold ORDER BY stock_qty ASC LIMIT 8");
$recent       = $db->all("SELECT * FROM orders ORDER BY id DESC LIMIT 8");
$topProducts  = $db->all("SELECT p.id, p.name, p.featured_image, SUM(oi.quantity) AS qty, SUM(oi.line_total) AS rev
                          FROM order_items oi JOIN products p ON p.id=oi.product_id
                          JOIN orders o ON o.id=oi.order_id
                          WHERE o.created_at >= NOW() - INTERVAL 30 DAY
                          GROUP BY p.id ORDER BY rev DESC LIMIT 5");

$pct = function($a, $b) {
    if ($b == 0) return $a > 0 ? 100 : 0;
    return round((($a - $b) / $b) * 100, 1);
};

include __DIR__ . '/_layout_top.php';
?>

<div class="kpis">
  <div class="kpi">
    <div class="label">Orders Today</div>
    <div class="value"><?= (int)$today['c'] ?></div>
    <div class="delta <?= $pct($today['c'],$yest['c']) >= 0 ? 'up' : 'down' ?>">
      <?= $pct($today['c'],$yest['c']) ?>% vs yesterday
    </div>
  </div>
  <div class="kpi">
    <div class="label">Revenue Today</div>
    <div class="value"><?= Helpers::bdt($today['v']) ?></div>
    <div class="delta <?= $pct($today['v'],$yest['v']) >= 0 ? 'up' : 'down' ?>">
      <?= $pct($today['v'],$yest['v']) ?>% vs yesterday
    </div>
  </div>
  <div class="kpi">
    <div class="label">This Month</div>
    <div class="value"><?= Helpers::bdt($this_month['v']) ?></div>
    <div class="delta"><?= (int)$this_month['c'] ?> orders</div>
  </div>
  <div class="kpi">
    <div class="label">Pending Orders</div>
    <div class="value"><?= $pending ?></div>
    <div class="delta">Awaiting action</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px">
<div>
  <div class="card">
    <h2>Recent Orders</h2>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th class="actions"></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $o): ?>
          <tr>
            <td><a href="/admin/order?id=<?= (int)$o['id'] ?>">#<?= Helpers::e($o['order_number']) ?></a><br>
                <small style="color:var(--ink-soft)"><?= Helpers::e(date('M d, H:i', strtotime($o['created_at']))) ?></small></td>
            <td><?= Helpers::e($o['customer_name']) ?><br><small style="color:var(--ink-soft)"><?= Helpers::e($o['customer_phone']) ?></small></td>
            <td class="tk"><?= Helpers::bdt($o['grand_total']) ?></td>
            <td><span class="pill <?= Helpers::e($o['status']) ?>"><?= str_replace('_',' ', $o['status']) ?></span></td>
            <td class="actions"><a class="btn btn-sm" href="/admin/order?id=<?= (int)$o['id'] ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Top Products (last 30 days)</h2>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($topProducts as $p): ?>
          <tr><td><?= Helpers::e($p['name']) ?></td><td><?= (int)$p['qty'] ?></td><td class="tk"><?= Helpers::bdt($p['rev']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div>
  <div class="card">
    <h2>Low Stock Alerts</h2>
    <?php if (!$lowStock): ?>
      <p style="color:var(--ink-soft)">All inventory healthy.</p>
    <?php else: ?>
      <?php foreach ($lowStock as $l): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed var(--line)">
          <a href="/admin/product?id=<?= (int)$l['id'] ?>"><?= Helpers::e($l['name']) ?></a>
          <span class="pill cancelled"><?= (int)$l['stock_qty'] ?> left</span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Quick Actions</h2>
    <a class="btn btn-primary" href="/admin/product?id=new" style="margin-bottom:8px">+ Add Product</a>
    <a class="btn" href="/admin/page?id=new" style="margin-bottom:8px">+ Add Page</a>
    <button class="btn cf-btn" onclick="LHA.cfPurge()" style="margin-bottom:8px">Purge Cloudflare</button>
  </div>
</div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
