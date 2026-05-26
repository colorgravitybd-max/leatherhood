<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$validFilter = $_GET['valid'] ?? '';
$ipFilter    = trim((string) ($_GET['ip']    ?? ''));
$qFilter     = trim((string) ($_GET['q']     ?? ''));
$page        = max(1, (int) ($_GET['p'] ?? 1));
$perPage     = 100;

$where = [];
$params = [];
if ($validFilter === '1') { $where[] = 'is_valid = 1'; }
elseif ($validFilter === '0') { $where[] = 'is_valid = 0'; }
if ($ipFilter !== '') { $where[] = 'ip_address = ?';  $params[] = $ipFilter; }
if ($qFilter  !== '') { $where[] = 'code_searched LIKE ?'; $params[] = '%' . $qFilter . '%'; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total  = (int) Database::scalar("SELECT COUNT(*) FROM scan_logs $whereSql", $params);
$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$rows = Database::all(
    "SELECT id, code_searched, is_valid, product_id,
            ip_address, country, region, district, scanned_at
       FROM scan_logs
       $whereSql
       ORDER BY scanned_at DESC, id DESC
       LIMIT $perPage OFFSET $offset",
    $params
);

$_title = 'Raw Scan Logs';
require __DIR__ . '/../_layout_top.php';
?>

<div class="card">
    <form method="get" action="<?= Helpers::e(BASE_PATH) ?>/admin/" style="display:flex; flex-wrap:wrap; gap:10px; align-items:end;">
        <input type="hidden" name="route" value="logs">
        <div class="form-row" style="margin-bottom:0; min-width:170px;">
            <label>Code contains</label>
            <input type="text" name="q" value="<?= Helpers::e($qFilter) ?>">
        </div>
        <div class="form-row" style="margin-bottom:0; min-width:140px;">
            <label>IP address</label>
            <input type="text" name="ip" value="<?= Helpers::e($ipFilter) ?>">
        </div>
        <div class="form-row" style="margin-bottom:0; min-width:140px;">
            <label>Result</label>
            <select name="valid">
                <option value=""  <?= $validFilter === ''  ? 'selected' : '' ?>>All</option>
                <option value="1" <?= $validFilter === '1' ? 'selected' : '' ?>>Authentic only</option>
                <option value="0" <?= $validFilter === '0' ? 'selected' : '' ?>>Failed only</option>
            </select>
        </div>
        <div style="display:flex; gap:6px;">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=logs" class="btn">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Scan logs <span style="color:var(--muted); font-weight:400;">(<?= number_format($total) ?> total)</span></h2>

    <?php if (empty($rows)): ?>
        <p style="color:var(--muted);">No scan logs.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Code searched</th>
                    <th>Result</th>
                    <th>IP</th>
                    <th>Country</th>
                    <th>District</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= Helpers::e($r['scanned_at']) ?></td>
                    <td class="mono"><?= Helpers::e($r['code_searched']) ?></td>
                    <td>
                        <?php if ((int) $r['is_valid'] === 1): ?>
                            <span class="badge badge-emerald">Authentic</span>
                        <?php else: ?>
                            <span class="badge badge-crimson">Failed</span>
                        <?php endif; ?>
                    </td>
                    <td class="mono"><?= Helpers::e($r['ip_address'] ?? '—') ?></td>
                    <td><?= Helpers::e($r['country']  ?: '—') ?></td>
                    <td><?= Helpers::e($r['district'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
            <div style="display:flex; gap:6px; justify-content:center; margin-top:14px;">
                <?php
                $base = http_build_query(array_filter([
                    'route' => 'logs',
                    'q'     => $qFilter,
                    'ip'    => $ipFilter,
                    'valid' => $validFilter !== '' ? $validFilter : null,
                ]));
                $start = max(1, $page - 3);
                $end   = min($pages, $page + 3);
                if ($start > 1) {
                    echo '<a href="<?= Helpers::e(BASE_PATH) ?>/admin/?' . $base . '&p=1" class="btn btn-sm">1</a>';
                    if ($start > 2) echo '<span style="padding:6px 8px;">…</span>';
                }
                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?<?= $base ?>&p=<?= $i ?>"
                       class="btn btn-sm <?= $i === $page ? 'btn-primary' : '' ?>"><?= $i ?></a>
                <?php endfor;
                if ($end < $pages) {
                    if ($end < $pages - 1) echo '<span style="padding:6px 8px;">…</span>';
                    echo '<a href="<?= Helpers::e(BASE_PATH) ?>/admin/?' . $base . '&p=' . $pages . '" class="btn btn-sm">' . $pages . '</a>';
                }
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../_layout_bottom.php';
