<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;
use LH\Core\Helpers;

/**
 * Bulk packing-slip / invoice generator. Produces a single self-contained
 * HTML document optimised for `window.print()` → "Save as PDF" or for the
 * Dompdf library when present (Composer optional install).
 */
final class PdfInvoice
{
    public static function bulkHtml(array $orderIds): string
    {
        $orders = [];
        foreach ($orderIds as $id) {
            $o = Database::i()->one('SELECT o.*, d.name AS district_name, ps.name AS ps_name
                                      FROM orders o
                                 LEFT JOIN geo_districts d  ON d.id = o.district_id
                                 LEFT JOIN geo_police_stations ps ON ps.id = o.police_station_id
                                     WHERE o.id = ?', [(int)$id]);
            if (!$o) continue;
            $o['items'] = Database::i()->all('SELECT * FROM order_items WHERE order_id = ?', [$o['id']]);
            $orders[] = $o;
        }

        ob_start(); ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8">
<title>LeatherHood — Packing Slips</title>
<style>
  *{box-sizing:border-box}
  body{font-family:'Helvetica',Arial,sans-serif;color:#1a1a1a;margin:0;padding:0;font-size:12px}
  .slip{page-break-after:always;padding:24px;border-bottom:1px dashed #ccc}
  .slip:last-child{page-break-after:auto;border:none}
  .head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px}
  .brand{font-family:'Times New Roman',serif;font-size:22px;letter-spacing:2px}
  .meta{text-align:right;font-size:11px;color:#555}
  table{width:100%;border-collapse:collapse;margin-top:8px}
  th,td{padding:6px 8px;border-bottom:1px solid #e6e6e6;text-align:left}
  th{background:#fafafa;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:1px}
  .totals td{border:none;padding:3px 8px}
  .totals .grand{font-weight:bold;border-top:1px solid #1a1a1a}
  .barcode{font-family:'Libre Barcode 128',monospace;font-size:42px;letter-spacing:0;line-height:1}
  .addr{margin-top:8px;font-size:12px;line-height:1.5}
  .addr strong{font-size:13px}
  @media print{ .slip{padding:18px} }
</style></head><body>
<?php foreach ($orders as $o): ?>
  <section class="slip">
    <div class="head">
      <div>
        <div class="brand">LEATHERHOOD</div>
        <div style="font-size:10px;color:#666">Premium Leather. Crafted in Bangladesh.</div>
      </div>
      <div class="meta">
        <div><strong>#<?= Helpers::e($o['order_number']) ?></strong></div>
        <div><?= Helpers::e($o['placed_at']) ?></div>
        <div class="barcode">*<?= Helpers::e($o['order_number']) ?>*</div>
      </div>
    </div>
    <div class="addr">
      <strong>Ship to:</strong><br>
      <?= Helpers::e($o['customer_name']) ?> — <?= Helpers::e($o['customer_phone']) ?><br>
      <?= Helpers::e($o['address_line']) ?><br>
      <?= Helpers::e(($o['ps_name'] ?? '').($o['district_name'] ? ', '.$o['district_name'] : '')) ?>
    </div>
    <table>
      <thead><tr><th>SKU</th><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
      <tbody>
      <?php foreach ($o['items'] as $it): ?>
        <tr>
          <td><?= Helpers::e($it['sku']) ?></td>
          <td><?= Helpers::e($it['name']) ?>
              <?php if ($it['option_summary']): ?><br><small><?= Helpers::e($it['option_summary']) ?></small><?php endif; ?>
          </td>
          <td><?= (int)$it['quantity'] ?></td>
          <td><?= Helpers::bdt($it['unit_price']) ?></td>
          <td><?= Helpers::bdt($it['line_total']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <table class="totals" style="margin-top:6px;width:55%;margin-left:auto">
      <tr><td>Subtotal</td><td style="text-align:right"><?= Helpers::bdt($o['subtotal']) ?></td></tr>
      <?php if ($o['discount_total'] > 0): ?>
        <tr><td>Discount</td><td style="text-align:right">-<?= Helpers::bdt($o['discount_total']) ?></td></tr>
      <?php endif; ?>
      <tr><td>Shipping</td><td style="text-align:right"><?= Helpers::bdt($o['shipping_total']) ?></td></tr>
      <tr class="grand"><td>Grand Total (<?= Helpers::e($o['payment_method']) ?>)</td>
          <td style="text-align:right"><?= Helpers::bdt($o['grand_total']) ?></td></tr>
    </table>
  </section>
<?php endforeach; ?>
<script>window.onload=()=>setTimeout(()=>window.print(),300);</script>
</body></html>
<?php
        return ob_get_clean();
    }
}
