<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

// ---- KPIs ----
$totalScans     = (int) Database::scalar('SELECT COUNT(*) FROM scan_logs');
$totalValid     = (int) Database::scalar('SELECT COUNT(*) FROM scan_logs WHERE is_valid = 1');
$totalInvalid   = (int) Database::scalar('SELECT COUNT(*) FROM scan_logs WHERE is_valid = 0');
$totalCodes     = (int) Database::scalar('SELECT COUNT(*) FROM verification_codes');
$totalProducts  = (int) Database::scalar('SELECT COUNT(*) FROM products WHERE is_active = 1');
$reusedUnique   = (int) Database::scalar(
    "SELECT COUNT(*) FROM verification_codes WHERE code_type = 'unique' AND scan_count > 1"
);
$scans24h       = (int) Database::scalar(
    'SELECT COUNT(*) FROM scan_logs WHERE scanned_at >= NOW() - INTERVAL 1 DAY'
);

// ---- Counterfeit radar (top failed strings, last 7 days) ----
$topFails = Database::all(
    'SELECT code_searched, COUNT(*) AS attempts,
            MAX(scanned_at) AS last_seen,
            COUNT(DISTINCT ip_address) AS uniq_ips,
            GROUP_CONCAT(DISTINCT NULLIF(country,"") ORDER BY country SEPARATOR ", ") AS countries
       FROM scan_logs
      WHERE is_valid = 0
        AND scanned_at >= NOW() - INTERVAL 7 DAY
        AND code_searched <> ""
      GROUP BY code_searched
      ORDER BY attempts DESC, last_seen DESC
      LIMIT 8'
);

// ---- Re-used unique codes (signals: cloned scratch panels) ----
$reusedRows = Database::all(
    "SELECT vc.code, vc.scan_count, vc.last_scan_at,
            p.title AS product_title, vc.batch_number
       FROM verification_codes vc
       JOIN products p ON p.id = vc.product_id
      WHERE vc.code_type = 'unique' AND vc.scan_count > 1
      ORDER BY vc.scan_count DESC, vc.last_scan_at DESC
      LIMIT 8"
);

// ---- Batches expiring within 90 days ----
$expiringSoon = Database::all(
    'SELECT vc.batch_number, p.title AS product_title,
            MIN(vc.expiry_date) AS expiry_date,
            COUNT(*) AS code_count,
            DATEDIFF(MIN(vc.expiry_date), CURDATE()) AS days_left
       FROM verification_codes vc
       JOIN products p ON p.id = vc.product_id
      WHERE vc.expiry_date IS NOT NULL
        AND vc.expiry_date >= CURDATE()
        AND vc.expiry_date <= CURDATE() + INTERVAL 90 DAY
      GROUP BY vc.batch_number, p.id
      ORDER BY expiry_date ASC
      LIMIT 8'
);

$_title = 'Dashboard';
require __DIR__ . '/_layout_top.php';
?>

<!-- ============ KPI tiles ============ -->
<div class="kpi-grid">
    <div class="kpi"><div class="label">Total Scans</div>
        <div class="value"><?= number_format($totalScans) ?></div></div>
    <div class="kpi success"><div class="label">Authentic Verifications</div>
        <div class="value"><?= number_format($totalValid) ?></div></div>
    <div class="kpi danger"><div class="label">Failed / Counterfeit</div>
        <div class="value"><?= number_format($totalInvalid) ?></div></div>
    <div class="kpi"><div class="label">Codes Issued</div>
        <div class="value"><?= number_format($totalCodes) ?></div></div>
    <div class="kpi warning"><div class="label">Re-used Unique Codes</div>
        <div class="value"><?= number_format($reusedUnique) ?></div></div>
    <div class="kpi"><div class="label">Scans in last 24h</div>
        <div class="value"><?= number_format($scans24h) ?></div></div>
    <div class="kpi"><div class="label">Active Products</div>
        <div class="value"><?= number_format($totalProducts) ?></div></div>
</div>

<!-- ============ Counterfeit radar ============ -->
<div class="card" style="margin-top:18px;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0;">Counterfeit Radar — last 7 days</h2>
        <a href="/admin/?route=insights/radar" class="btn btn-sm">Full radar →</a>
    </div>
    <p style="color:var(--muted); font-size:12px; margin:6px 0 12px;">
        Strings that failed verification most frequently. Repeated identical attempts
        from many IPs strongly suggest a counterfeiter is testing fake codes.
    </p>

    <?php if (empty($topFails) && empty($reusedRows)): ?>
        <p style="color:var(--muted);">All clear. No suspicious activity in the last week.</p>
    <?php else: ?>
        <?php if (!empty($topFails)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Failed string</th>
                    <th class="num">Attempts</th>
                    <th class="num">Unique IPs</th>
                    <th>Countries</th>
                    <th>Last seen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($topFails as $f): ?>
                <tr>
                    <td class="mono"><?= Helpers::e($f['code_searched']) ?></td>
                    <td class="num"><?= (int) $f['attempts'] ?></td>
                    <td class="num"><?= (int) $f['uniq_ips'] ?></td>
                    <td><?= Helpers::e($f['countries'] ?: '—') ?></td>
                    <td><?= Helpers::e($f['last_seen']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($reusedRows)): ?>
            <h3 style="font-size:13px; margin:18px 0 8px; color:var(--amber);">
                Re-scanned unique codes (possible cloned scratch panels)
            </h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Product</th>
                        <th>Batch</th>
                        <th class="num">Scans</th>
                        <th>Last scan</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reusedRows as $r): ?>
                    <tr>
                        <td class="mono"><?= Helpers::e($r['code']) ?></td>
                        <td><?= Helpers::e($r['product_title']) ?></td>
                        <td class="mono"><?= Helpers::e($r['batch_number'] ?? '—') ?></td>
                        <td class="num"><span class="badge badge-amber"><?= (int) $r['scan_count'] ?></span></td>
                        <td><?= Helpers::e($r['last_scan_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ============ Batch expiry monitor ============ -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0;">Batch Expiry Monitor — next 90 days</h2>
        <a href="/admin/?route=insights/expiry" class="btn btn-sm">Full list →</a>
    </div>
    <?php if (empty($expiringSoon)): ?>
        <p style="color:var(--muted); margin-top:8px;">No batches expiring in the next 90 days.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Product</th>
                    <th>Expiry</th>
                    <th class="num">Days left</th>
                    <th class="num">Codes in batch</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($expiringSoon as $b): ?>
                <?php
                $days = (int) $b['days_left'];
                $cls  = $days <= 30 ? 'badge-crimson' : ($days <= 60 ? 'badge-amber' : 'badge-slate');
                ?>
                <tr>
                    <td class="mono">
                        <a href="/admin/?route=codes&batch=<?= urlencode($b['batch_number']) ?>">
                            <?= Helpers::e($b['batch_number']) ?>
                        </a>
                    </td>
                    <td><?= Helpers::e($b['product_title']) ?></td>
                    <td><?= Helpers::e($b['expiry_date']) ?></td>
                    <td class="num"><span class="badge <?= $cls ?>"><?= $days ?>d</span></td>
                    <td class="num"><?= (int) $b['code_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_bottom.php';
