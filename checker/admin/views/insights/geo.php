<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$days = (int) ($_GET['days'] ?? 30);
if (!in_array($days, [1, 7, 30, 90, 365], true)) { $days = 30; }

$countries = Database::all(
    'SELECT NULLIF(country,"") AS country,
            COUNT(*)                                              AS scans,
            SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END)         AS valid_scans,
            SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END)         AS invalid_scans,
            COUNT(DISTINCT ip_address)                            AS unique_ips
       FROM scan_logs
      WHERE scanned_at >= NOW() - INTERVAL ? DAY
      GROUP BY country
      ORDER BY scans DESC
      LIMIT 50',
    [$days]
);

$districts = Database::all(
    'SELECT NULLIF(country,"")  AS country,
            NULLIF(region,"")   AS region,
            NULLIF(district,"") AS district,
            COUNT(*)                                              AS scans,
            SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END)         AS valid_scans,
            SUM(CASE WHEN is_valid = 0 THEN 1 ELSE 0 END)         AS invalid_scans
       FROM scan_logs
      WHERE scanned_at >= NOW() - INTERVAL ? DAY
        AND district IS NOT NULL AND district <> ""
      GROUP BY country, region, district
      ORDER BY scans DESC
      LIMIT 100',
    [$days]
);

$_title = 'Geographic Log';
require __DIR__ . '/../_layout_top.php';
?>

<div class="card">
    <form method="get" action="." style="display:flex; gap:10px; align-items:end;">
        <input type="hidden" name="route" value="insights/geo">
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
    <h2>By Country (top 50)</h2>
    <?php if (empty($countries)): ?>
        <p style="color:var(--muted);">No scans in this window.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Country</th>
                    <th class="num">Total scans</th>
                    <th class="num">Authentic</th>
                    <th class="num">Failed</th>
                    <th class="num">Unique IPs</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($countries as $c): ?>
                <tr>
                    <td><?= Helpers::e($c['country'] ?? 'Unknown') ?></td>
                    <td class="num"><?= number_format((int) $c['scans']) ?></td>
                    <td class="num"><span class="badge badge-emerald"><?= number_format((int) $c['valid_scans']) ?></span></td>
                    <td class="num"><span class="badge badge-crimson"><?= number_format((int) $c['invalid_scans']) ?></span></td>
                    <td class="num"><?= number_format((int) $c['unique_ips']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>By District / City (top 100)</h2>
    <p style="color:var(--muted); font-size:12px; margin-bottom:10px;">
        The local territory map. Useful for spotting concentrations of counterfeit
        activity at the neighbourhood level.
    </p>
    <?php if (empty($districts)): ?>
        <p style="color:var(--muted);">No district-level data in this window.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>District / City</th>
                    <th>Region</th>
                    <th>Country</th>
                    <th class="num">Total scans</th>
                    <th class="num">Authentic</th>
                    <th class="num">Failed</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($districts as $d): ?>
                <tr>
                    <td><strong><?= Helpers::e($d['district']) ?></strong></td>
                    <td><?= Helpers::e($d['region']  ?? '—') ?></td>
                    <td><?= Helpers::e($d['country'] ?? '—') ?></td>
                    <td class="num"><?= number_format((int) $d['scans']) ?></td>
                    <td class="num"><span class="badge badge-emerald"><?= number_format((int) $d['valid_scans']) ?></span></td>
                    <td class="num"><span class="badge badge-crimson"><?= number_format((int) $d['invalid_scans']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../_layout_bottom.php';
