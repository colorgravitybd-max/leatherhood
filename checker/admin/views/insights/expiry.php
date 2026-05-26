<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$horizon = (int) ($_GET['horizon'] ?? 180);
if (!in_array($horizon, [30, 60, 90, 180, 365, 1000], true)) { $horizon = 180; }

$active = Database::all(
    'SELECT vc.batch_number,
            p.title AS product_title, p.id AS product_id,
            MIN(vc.expiry_date) AS expiry_date,
            COUNT(*)            AS code_count,
            SUM(vc.scan_count)  AS total_scans,
            DATEDIFF(MIN(vc.expiry_date), CURDATE()) AS days_left
       FROM verification_codes vc
       JOIN products p ON p.id = vc.product_id
      WHERE vc.expiry_date IS NOT NULL
        AND vc.expiry_date >= CURDATE()
        AND vc.expiry_date <= CURDATE() + INTERVAL ? DAY
      GROUP BY vc.batch_number, p.id
      ORDER BY expiry_date ASC',
    [$horizon]
);

$expired = Database::all(
    'SELECT vc.batch_number,
            p.title AS product_title,
            MAX(vc.expiry_date) AS expiry_date,
            COUNT(*)            AS code_count
       FROM verification_codes vc
       JOIN products p ON p.id = vc.product_id
      WHERE vc.expiry_date IS NOT NULL
        AND vc.expiry_date < CURDATE()
      GROUP BY vc.batch_number, p.id
      ORDER BY expiry_date DESC
      LIMIT 50'
);

$_title = 'Batch Expiry Monitor';
require __DIR__ . '/../_layout_top.php';
?>

<div class="card">
    <form method="get" action="." style="display:flex; gap:10px; align-items:end;">
        <input type="hidden" name="route" value="insights/expiry">
        <div class="form-row" style="margin-bottom:0;">
            <label>Horizon</label>
            <select name="horizon" onchange="this.form.submit()">
                <?php foreach ([30, 60, 90, 180, 365, 1000] as $h): ?>
                    <option value="<?= $h ?>" <?= $horizon === $h ? 'selected' : '' ?>>
                        Next <?= $h ?> days
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="card">
    <h2>Active batches expiring within <?= (int) $horizon ?> days</h2>
    <?php if (empty($active)): ?>
        <p style="color:var(--muted);">No active batches expiring in this window.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Product</th>
                    <th>Expiry</th>
                    <th class="num">Days left</th>
                    <th class="num">Codes</th>
                    <th class="num">Total scans</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($active as $b): ?>
                <?php
                $days = (int) $b['days_left'];
                $cls  = $days <= 30 ? 'badge-crimson' : ($days <= 90 ? 'badge-amber' : 'badge-slate');
                ?>
                <tr>
                    <td class="mono">
                        <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes&batch=<?= urlencode($b['batch_number']) ?>">
                            <?= Helpers::e($b['batch_number']) ?>
                        </a>
                    </td>
                    <td><?= Helpers::e($b['product_title']) ?></td>
                    <td><?= Helpers::e($b['expiry_date']) ?></td>
                    <td class="num"><span class="badge <?= $cls ?>"><?= $days ?>d</span></td>
                    <td class="num"><?= number_format((int) $b['code_count']) ?></td>
                    <td class="num"><?= number_format((int) $b['total_scans']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Already expired batches</h2>
    <p style="color:var(--muted); font-size:12px; margin-bottom:10px;">
        Codes from these batches will still verify, but customers should be warned.
        Consider disabling them in bulk if they are out of stock.
    </p>
    <?php if (empty($expired)): ?>
        <p style="color:var(--muted);">No expired batches.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Product</th>
                    <th>Expired on</th>
                    <th class="num">Codes</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($expired as $b): ?>
                <tr>
                    <td class="mono">
                        <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes&batch=<?= urlencode($b['batch_number']) ?>">
                            <?= Helpers::e($b['batch_number']) ?>
                        </a>
                    </td>
                    <td><?= Helpers::e($b['product_title']) ?></td>
                    <td><span class="badge badge-crimson"><?= Helpers::e($b['expiry_date']) ?></span></td>
                    <td class="num"><?= number_format((int) $b['code_count']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../_layout_bottom.php';
