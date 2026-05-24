<?php
use LH\Core\Database;
use LH\Core\Helpers;

$id = (int) Helpers::input('id', 0);
$db = Database::i();
$o  = $db->one('SELECT o.*, d.name AS district_name, ps.name AS ps_name, dv.name AS division_name
                FROM orders o
           LEFT JOIN geo_districts d ON d.id=o.district_id
           LEFT JOIN geo_police_stations ps ON ps.id=o.police_station_id
           LEFT JOIN geo_divisions dv ON dv.id=o.division_id
               WHERE o.id = ?', [$id]);
if (!$o) { http_response_code(404); echo 'Order not found.'; return; }

$items   = $db->all('SELECT * FROM order_items WHERE order_id=?', [$id]);
$history = $db->all('SELECT h.*, u.name as user_name FROM order_status_history h LEFT JOIN users u ON u.id=h.user_id WHERE h.order_id=? ORDER BY h.id DESC', [$id]);
$smsLogs = $db->all('SELECT * FROM sms_logs WHERE order_id=? ORDER BY id DESC LIMIT 8', [$id]);
$page_title = 'Order #'.$o['order_number'];
include __DIR__ . '/../_layout_top.php';
?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px">
<div>
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <h2 style="margin:0;border:0;padding:0">Order #<?= Helpers::e($o['order_number']) ?></h2>
        <p style="margin:6px 0 0;color:var(--ink-soft)">Placed <?= Helpers::e(date('M d, Y H:i', strtotime($o['created_at']))) ?></p>
      </div>
      <div>
        <span class="pill <?= Helpers::e($o['status']) ?>" style="padding:6px 14px;font-size:13px"><?= str_replace('_',' ', $o['status']) ?></span>
      </div>
    </div>

    <div class="form-grid" style="margin-top:18px">
      <div><div class="lbl">Customer</div><b><?= Helpers::e($o['customer_name']) ?></b><br><?= Helpers::e($o['customer_phone']) ?><br><?= Helpers::e($o['customer_email']) ?></div>
      <div><div class="lbl">Shipping Address</div>
        <?= Helpers::e($o['address_line']) ?><br>
        <?= Helpers::e($o['ps_name'].', '.$o['district_name'].', '.$o['division_name']) ?></div>
      <div><div class="lbl">Payment</div><b><?= strtoupper($o['payment_method']) ?></b> · <?= $o['payment_status'] ?></div>
      <div><div class="lbl">Verified</div><?= $o['is_verified'] ? '✓ Yes' : '✗ No' ?></div>
    </div>
  </div>

  <div class="card">
    <h2>Items</h2>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= Helpers::e($it['sku']) ?></td>
          <td><?= Helpers::e($it['name']) ?><?php if ($it['option_summary']): ?><br><small><?= Helpers::e($it['option_summary']) ?></small><?php endif; ?></td>
          <td><?= (int)$it['quantity'] ?></td>
          <td><?= Helpers::bdt($it['unit_price']) ?></td>
          <td class="tk"><?= Helpers::bdt($it['line_total']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div style="margin-top:14px;text-align:right;line-height:2">
      <div>Subtotal: <b><?= Helpers::bdt($o['subtotal']) ?></b></div>
      <?php if ($o['discount_total'] > 0): ?><div>Discount: -<?= Helpers::bdt($o['discount_total']) ?></div><?php endif; ?>
      <div>Shipping: <b><?= Helpers::bdt($o['shipping_total']) ?></b></div>
      <div style="font-size:1.2rem;font-family:var(--serif)">Grand Total: <b><?= Helpers::bdt($o['grand_total']) ?></b></div>
    </div>
  </div>

  <div class="card">
    <h2>Status History</h2>
    <ul style="list-style:none;padding:0;margin:0">
      <?php foreach ($history as $h): ?>
        <li style="padding:8px 0;border-bottom:1px dashed var(--line);font-size:13px">
          <b><?= str_replace('_',' ', $h['to_status']) ?></b> <?= $h['from_status'] ? '← '.str_replace('_',' ', $h['from_status']) : '' ?>
          <small style="color:var(--ink-soft);margin-left:8px"><?= Helpers::e(date('M d, H:i', strtotime($h['created_at']))) ?> <?= $h['user_name'] ? ' · '.Helpers::e($h['user_name']) : '' ?></small>
          <?php if ($h['note']): ?><br><span style="color:var(--ink-soft)"><?= Helpers::e($h['note']) ?></span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div>
  <div class="card">
    <h2>Actions</h2>
    <?php if (!$o['is_verified']): ?>
      <button class="btn btn-success" style="width:100%;margin-bottom:8px" onclick="LHA.manualVerify(<?= (int)$o['id'] ?>)">✓ Manually Verify OTP</button>
    <?php endif; ?>
    <button class="btn btn-primary" style="width:100%;margin-bottom:8px" onclick="LHA.pushCourier(<?= (int)$o['id'] ?>,'steadfast')">→ Push to SteadFast</button>
    <button class="btn btn-primary" style="width:100%;margin-bottom:8px" onclick="LHA.pushCourier(<?= (int)$o['id'] ?>,'pathao')">→ Push to Pathao</button>

    <select class="select" style="width:100%;margin-bottom:8px" onchange="LHA.setStatus(<?= (int)$o['id'] ?>, this.value)">
      <option value="">— Change status —</option>
      <?php foreach (['processing','shipped','out_for_delivery','delivered','completed'] as $s): ?>
        <option value="<?= $s ?>"><?= str_replace('_',' ', $s) ?></option>
      <?php endforeach; ?>
    </select>

    <a class="btn btn-gold" style="width:100%;margin-bottom:8px;justify-content:center" href="/admin/invoices/bulk?ids=<?= (int)$o['id'] ?>" target="_blank">🖨 Print Invoice</a>
    <button class="btn btn-danger" style="width:100%" onclick="LHA.cancelOrder(<?= (int)$o['id'] ?>)">⛔ Cancel & Restock</button>

    <?php if ($o['courier_tracking_url']): ?>
      <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
      <div class="lbl">Courier</div>
      <b><?= Helpers::e($o['courier']) ?></b> · <?= Helpers::e($o['courier_consignment_id']) ?><br>
      <a class="btn btn-sm" href="<?= Helpers::e($o['courier_tracking_url']) ?>" target="_blank">Open Tracking</a>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>SMS Logs</h2>
    <?php foreach ($smsLogs as $s): ?>
      <div style="font-size:12.5px;padding:8px 0;border-bottom:1px dashed var(--line)">
        <span class="pill <?= $s['status']==='sent'?'delivered':'cancelled' ?>"><?= $s['status'] ?></span>
        <span style="color:var(--ink-soft)"><?= $s['purpose'] ?></span><br>
        <?= Helpers::e($s['message']) ?><br>
        <small style="color:var(--ink-soft)"><?= Helpers::e($s['created_at']) ?></small>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</div>

<?php include __DIR__ . '/../_layout_bottom.php'; ?>
