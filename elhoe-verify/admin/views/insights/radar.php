<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$days = (int) ($_GET['days'] ?? 30);
if (!in_array($days, [1, 7, 30, 90, 365], true)) { $days = 30; }

// Top failed strings (with location summary)
$topFails = Database::all(
    'SELECT code_searched,
            COUNT(*)                                              AS attempts,
            COUNT(DISTINCT ip_address)                            AS uniq_ips,
            MAX(scanned_at)                                       AS last_seen,
            GROUP_CONCAT(DISTINCT NULLIF(country,"")  ORDER BY country  SEPARATOR ", ") AS countries,
            GROUP_CONCAT(DISTINCT NULLIF(district,"") ORDER BY district SEPARATOR ", ") AS districts
       FROM scan_logs
      WHERE is_valid = 0
        AND scanned_at >= NOW() - INTERVAL ? DAY
        AND code_searched <> ""
      GROUP BY code_searched
      HAVING attempts >= 1
      ORDER BY attempts DESC, last_seen DESC
      LIMIT 100',
    [$days]
);

// Re-scanned unique codes
$reused = Database::all(
    "SELECT vc.code, vc.scan_count, vc.first_scan_at, vc.last_scan_at,
            p.title AS product_title, vc.batch_number, vc.expiry_date
       FROM verification_codes vc
       JOIN products p ON p.id = vc.product_id
      WHERE vc.code_type = 'unique' AND vc.scan_count > 1
      ORDER BY vc.scan_count DESC, vc.last_scan_at DESC
      LIMIT 100"
);

// Hotspot IPs (most failed attempts)
$hotIps = Database::all(
    'SELECT ip_address,
            COUNT(*) AS attempts,
            COUNT(DISTINCT code_searched) AS unique_codes_tried,
            MAX(scanned_at) AS last_seen,
            MAX(country)  AS country,
            MAX(district) AS district
       FROM scan_logs
      WHERE is_valid = 0
        AND ip_address IS NOT NULL
        AND scanned_at >= NOW() - INTERVAL ? DAY
      GROUP BY ip_address
      ORDER BY attempts DESC
      LIMIT 30',
    [$days]
);

$_title = 'Counterfeit Radar';
require __DIR__ . '/../_layout_top.php';
?>

<div class="card">
    <form method="get" action="/admin/" style="display:flex; gap:10px; align-items:end;">
        <input type="hidden" name="route" value="insights/radar">
        <div class="form-row" style="margin-bottom:0;">
            <label>Time window</label>
            <select name="days" onchange="this.form.submit()">
                <?php foreach ([1, 7, 30, 90, 365] as $d): ?>
                    <option value="<?= $d ?>" <?= $days === $d ? 'selected' : '' ?>>
                        Last <?= $d ?> day<?= $d === 1 ? '' : 's' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="card">
    <h2>Failed verification strings</h2>
    <p style="color:var(--muted); font-size:12px; margin-bottom:10px;">
        Codes that didn't match any record. High attempts × many IPs ⇒ probable fake-code distribution.
    </p>
    <?php if (empty($topFails)): ?>
        <p style="color:var(--muted);">No failed scans in this window.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>String tried</th>
                    <th class="num">Attempts</th>
                    <th class="num">Unique IPs</th>
                    <th>Last seen</th>
                    <th>Countries</th>
                    <th>Districts</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($topFails as $f): ?>
                <?php
                $att = (int) $f['attempts'];
                $cls = $att >= 20 ? 'badge-crimson' : ($att >= 5 ? 'badge-amber' : 'badge-slate');
                ?>
                <tr>
                    <td class="mono"><?= Helpers::e($f['code_searched']) ?></td>
                    <td class="num"><span class="badge <?= $cls ?>"><?= $att ?></span></td>
                    <td class="num"><?= (int) $f['uniq_ips'] ?></td>
                    <td><?= Helpers::e($f['last_seen']) ?></td>
                    <td><?= Helpers::e($f['countries']  ?: '—') ?></td>
                    <td><?= Helpers::e($f['districts']  ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Re-scanned unique codes</h2>
    <p style="color:var(--muted); font-size:12px; margin-bottom:10px;">
        A unique code that has been verified more than once likely indicates a cloned scratch panel
        or a customer scanning twice. Investigate codes with high counts.
    </p>
    <?php if (empty($reused)): ?>
        <p style="color:var(--muted);">No re-scanned unique codes.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Product</th>
                    <th>Batch</th>
                    <th>Expiry</th>
                    <th class="num">Scans</th>
                    <th>First scan</th>
                    <th>Last scan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reused as $r): ?>
                <tr>
                    <td class="mono"><?= Helpers::e($r['code']) ?></td>
                    <td><?= Helpers::e($r['product_title']) ?></td>
                    <td class="mono"><?= Helpers::e($r['batch_number'] ?? '—') ?></td>
                    <td><?= Helpers::e($r['expiry_date']  ?? '—') ?></td>
                    <td class="num">
                        <span class="badge <?= (int) $r['scan_count'] >= 5 ? 'badge-crimson' : 'badge-amber' ?>">
                            <?= (int) $r['scan_count'] ?>
                        </span>
                    </td>
                    <td><?= Helpers::e($r['first_scan_at'] ?? '—') ?></td>
                    <td><?= Helpers::e($r['last_scan_at']  ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Hotspot IPs</h2>
    <p style="color:var(--muted); font-size:12px; margin-bottom:10px;">
        Sources generating the most failed verifications. Likely candidates for Cloudflare WAF blocking.
    </p>
    <?php if (empty($hotIps)): ?>
        <p style="color:var(--muted);">No suspicious IPs in this window.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>IP</th>
                    <th>Country</th>
                    <th>District</th>
                    <th class="num">Failed attempts</th>
                    <th class="num">Unique strings tried</th>
                    <th>Last seen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hotIps as $h): ?>
                <tr>
                    <td class="mono"><?= Helpers::e($h['ip_address']) ?></td>
                    <td><?= Helpers::e($h['country']  ?? '—') ?></td>
                    <td><?= Helpers::e($h['district'] ?? '—') ?></td>
                    <td class="num"><?= (int) $h['attempts'] ?></td>
                    <td class="num"><?= (int) $h['unique_codes_tried'] ?></td>
                    <td><?= Helpers::e($h['last_seen']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../_layout_bottom.php';
