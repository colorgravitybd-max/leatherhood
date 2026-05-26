<?php
use LH\Core\Database;
use LH\Core\Helpers;

$page_title = 'Customers';
$db = Database::i();
$q = trim((string)Helpers::input('q',''));
$where='1=1'; $params=[];
if ($q) { $where .= ' AND (first_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
          $like="%$q%"; array_push($params,$like,$like,$like); }
$rows = $db->all("SELECT * FROM customers WHERE $where ORDER BY total_spent DESC LIMIT 100", $params);
include __DIR__ . '/../_layout_top.php';
?>
<div class="card">
  <form class="searchbar" method="get">
    <input class="input" type="search" name="q" value="<?= Helpers::e($q) ?>" placeholder="Search by name, phone, email…">
    <button class="btn">Search</button>
  </form>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Orders</th><th>Spent</th><th>Last Order</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $c): ?>
      <tr>
        <td><b><?= Helpers::e(trim($c['first_name'].' '.$c['last_name'])) ?></b></td>
        <td><?= Helpers::e($c['phone']) ?></td>
        <td><?= Helpers::e($c['email']) ?></td>
        <td><?= (int)$c['total_orders'] ?></td>
        <td class="tk"><?= Helpers::bdt($c['total_spent']) ?></td>
        <td><?= $c['last_order_at'] ? Helpers::e(date('M d, Y', strtotime($c['last_order_at']))) : '—' ?></td>
        <td><a class="btn btn-sm" href="/admin/customer?id=<?= (int)$c['id'] ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
