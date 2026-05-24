<?php
use LH\Core\Database;
use LH\Core\Helpers;

$id = (int)Helpers::input('id',0);
$db = Database::i();
$c  = $db->one('SELECT * FROM customers WHERE id=?', [$id]);
if (!$c) { http_response_code(404); echo 'Not found.'; return; }
$orders = $db->all('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 50', [$id]);
$page_title = 'Customer · '.$c['first_name'];
include __DIR__ . '/../_layout_top.php';
?>
<div class="card">
  <h2><?= Helpers::e(trim($c['first_name'].' '.$c['last_name'])) ?></h2>
  <div class="form-grid">
    <div><div class="lbl">Phone</div><b><?= Helpers::e($c['phone']) ?></b></div>
    <div><div class="lbl">Email</div><?= Helpers::e($c['email']) ?: '—' ?></div>
    <div><div class="lbl">Total Orders</div><?= (int)$c['total_orders'] ?></div>
    <div><div class="lbl">Lifetime Spend</div><b class="tk"><?= Helpers::bdt($c['total_spent']) ?></b></div>
    <div class="full"><div class="lbl">Notes</div><?= Helpers::e($c['notes']) ?: '—' ?></div>
  </div>
</div>

<div class="card"><h2>Order History</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><a href="/admin/order?id=<?= (int)$o['id'] ?>">#<?= Helpers::e($o['order_number']) ?></a></td>
        <td><?= Helpers::e(date('M d, Y', strtotime($o['created_at']))) ?></td>
        <td class="tk"><?= Helpers::bdt($o['grand_total']) ?></td>
        <td><span class="pill <?= Helpers::e($o['status']) ?>"><?= str_replace('_',' ', $o['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
