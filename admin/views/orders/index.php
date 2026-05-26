<?php
use LH\Core\Database;
use LH\Core\Helpers;

$page_title = 'Orders';
$db = Database::i();

$status = (string)Helpers::input('status', '');
$q      = trim((string)Helpers::input('q', ''));
$page   = max(1, (int)Helpers::input('p', 1));
$per    = 25;

$where = '1=1'; $params = [];
if ($status) { $where .= ' AND o.status = ?'; $params[] = $status; }
if ($q) {
    $where .= ' AND (o.order_number LIKE ? OR o.customer_phone LIKE ? OR o.customer_name LIKE ?)';
    $like = "%$q%"; array_push($params, $like, $like, $like);
}

$total = (int) $db->value("SELECT COUNT(*) FROM orders o WHERE $where", $params);
$rows  = $db->all("SELECT o.* FROM orders o WHERE $where ORDER BY o.id DESC
                    LIMIT $per OFFSET ".(($page-1)*$per), $params);

$statuses = ['','pending_otp','pending','processing','on_hold','shipped','out_for_delivery','delivered','completed','cancelled','refunded','returned','abandoned'];
include __DIR__ . '/../_layout_top.php';
?>

<div class="card">
  <form class="searchbar" method="get">
    <input type="hidden" name="" value="">
    <input class="input" type="search" name="q" value="<?= Helpers::e($q) ?>" placeholder="Search by # / phone / name…">
    <select class="select" name="status" onchange="this.form.submit()">
      <option value="">All Statuses</option>
      <?php foreach (array_slice($statuses,1) as $s): ?>
        <option value="<?= Helpers::e($s) ?>" <?= $status===$s?'selected':'' ?>><?= str_replace('_',' ', $s) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn">Search</button>
    <span style="flex:1"></span>
    <button type="button" class="btn btn-gold" onclick="LHA.bulkInvoices()">Bulk Print Invoices</button>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead><tr>
        <th><input type="checkbox" id="check-all"></th>
        <th>Order</th><th>Customer</th><th>Phone</th><th>Total</th><th>Payment</th><th>Status</th><th>OTP</th><th class="actions">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $o): ?>
        <tr>
          <td><input class="row-check" type="checkbox" value="<?= (int)$o['id'] ?>"></td>
          <td><a href="/admin/order?id=<?= (int)$o['id'] ?>"><b>#<?= Helpers::e($o['order_number']) ?></b></a><br>
              <small style="color:var(--ink-soft)"><?= Helpers::e(date('M d, H:i', strtotime($o['created_at']))) ?></small></td>
          <td><?= Helpers::e($o['customer_name']) ?></td>
          <td><?= Helpers::e($o['customer_phone']) ?></td>
          <td class="tk"><?= Helpers::bdt($o['grand_total']) ?></td>
          <td><?= strtoupper($o['payment_method']) ?></td>
          <td><span class="pill <?= Helpers::e($o['status']) ?>"><?= str_replace('_',' ', $o['status']) ?></span></td>
          <td><?= $o['is_verified'] ? '<span class="pill delivered">verified</span>' : '<span class="pill pending">unverified</span>' ?></td>
          <td class="actions">
            <a class="btn btn-sm" href="/admin/order?id=<?= (int)$o['id'] ?>">Open</a>
            <?php if (!$o['is_verified']): ?>
              <button class="btn btn-sm btn-success" onclick="LHA.manualVerify(<?= (int)$o['id'] ?>)">Verify</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php $pages = (int)ceil($total / $per); if ($pages > 1): ?>
    <nav class="pager" style="margin-top:18px">
      <?php for ($i=1;$i<=$pages;$i++):
        $params2 = array_merge($_GET, ['p'=>$i]); ?>
        <a class="<?= $i===$page?'current':'' ?>" href="?<?= http_build_query($params2) ?>"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../_layout_bottom.php'; ?>
