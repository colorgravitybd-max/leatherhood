<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$batchFilter   = trim((string) ($_GET['batch']   ?? ''));
$productFilter = (int)         ($_GET['product'] ?? 0);
$typeFilter    = trim((string) ($_GET['type']    ?? ''));
$search        = trim((string) ($_GET['q']       ?? ''));
$page          = max(1, (int) ($_GET['p'] ?? 1));
$perPage       = 50;

$where  = [];
$params = [];
if ($batchFilter !== '')         { $where[] = 'vc.batch_number = ?';        $params[] = $batchFilter; }
if ($productFilter > 0)          { $where[] = 'vc.product_id = ?';          $params[] = $productFilter; }
if (in_array($typeFilter, ['unique','universal'], true)) {
                                   $where[] = 'vc.code_type = ?';           $params[] = $typeFilter; }
if ($search !== '')              { $where[] = 'vc.code LIKE ?';             $params[] = '%' . $search . '%'; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) Database::scalar(
    "SELECT COUNT(*) FROM verification_codes vc $whereSql", $params
);
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$rows = Database::all(
    "SELECT vc.id, vc.code, vc.code_type, vc.batch_number, vc.expiry_date,
            vc.scan_count, vc.last_scan_at, vc.is_disabled,
            p.id AS p_id, p.title AS product_title
       FROM verification_codes vc
       JOIN products p ON p.id = vc.product_id
       $whereSql
       ORDER BY vc.created_at DESC, vc.id DESC
       LIMIT $perPage OFFSET $offset",
    $params
);

$products = Database::all('SELECT id, title FROM products ORDER BY title');
$batches  = Database::all('SELECT DISTINCT batch_number FROM verification_codes
                            WHERE batch_number IS NOT NULL ORDER BY batch_number DESC LIMIT 200');

$_title = 'All Codes';
require __DIR__ . '/../_layout_top.php';
?>
<div class="card">
    <form method="get" action="." style="display:flex; flex-wrap:wrap; gap:10px; align-items:end;">
        <input type="hidden" name="route" value="codes">

        <div class="form-row" style="margin-bottom:0; min-width:170px;">
            <label>Search code</label>
            <input type="text" name="q" value="<?= Helpers::e($search) ?>" placeholder="ELH-...">
        </div>

        <div class="form-row" style="margin-bottom:0; min-width:170px;">
            <label>Product</label>
            <select name="product">
                <option value="">All</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                            <?= $productFilter === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($p['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row" style="margin-bottom:0; min-width:170px;">
            <label>Batch</label>
            <select name="batch">
                <option value="">All</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= Helpers::e($b['batch_number']) ?>"
                            <?= $batchFilter === $b['batch_number'] ? 'selected' : '' ?>>
                        <?= Helpers::e($b['batch_number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row" style="margin-bottom:0; min-width:130px;">
            <label>Type</label>
            <select name="type">
                <option value="">All</option>
                <option value="unique"    <?= $typeFilter === 'unique'    ? 'selected' : '' ?>>Unique</option>
                <option value="universal" <?= $typeFilter === 'universal' ? 'selected' : '' ?>>Universal</option>
            </select>
        </div>

        <div style="display:flex; gap:6px;">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes" class="btn">Reset</a>
        </div>

        <?php if ($batchFilter !== ''): ?>
            <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes/export&batch=<?= urlencode($batchFilter) ?>"
               class="btn" style="margin-left:auto;">Export this batch (CSV) ↓</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h2 style="margin:0;">Codes <span style="color:var(--muted); font-weight:400;">(<?= number_format($total) ?> total)</span></h2>
        <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes/generate" class="btn btn-primary">+ New Batch</a>
    </div>

    <?php if (empty($rows)): ?>
        <p style="color:var(--muted); padding:20px 0;">No codes found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Batch</th>
                    <th>Expiry</th>
                    <th class="num">Scans</th>
                    <th>Last scan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="mono"><?= Helpers::e($r['code']) ?></td>
                    <td><?= Helpers::e($r['product_title']) ?></td>
                    <td>
                        <span class="badge <?= $r['code_type'] === 'unique' ? 'badge-gold' : 'badge-slate' ?>">
                            <?= Helpers::e($r['code_type']) ?>
                        </span>
                    </td>
                    <td class="mono"><?= Helpers::e($r['batch_number'] ?? '—') ?></td>
                    <td><?= Helpers::e($r['expiry_date'] ?? '—') ?></td>
                    <td class="num"><?= (int) $r['scan_count'] ?></td>
                    <td><?= $r['last_scan_at'] ? Helpers::e($r['last_scan_at']) : '<span style="color:var(--muted);">—</span>' ?></td>
                    <td>
                        <?php if ((int) $r['is_disabled'] === 1): ?>
                            <span class="badge badge-crimson">Disabled</span>
                        <?php elseif ($r['code_type'] === 'unique' && (int) $r['scan_count'] > 1): ?>
                            <span class="badge badge-amber">Re-used</span>
                        <?php elseif ((int) $r['scan_count'] === 0): ?>
                            <span class="badge badge-slate">Unused</span>
                        <?php else: ?>
                            <span class="badge badge-emerald">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
            <div style="display:flex; gap:6px; justify-content:center; margin-top:14px;">
                <?php
                $base = http_build_query(array_filter([
                    'route'   => 'codes',
                    'q'       => $search,
                    'product' => $productFilter ?: null,
                    'batch'   => $batchFilter   ?: null,
                    'type'    => $typeFilter    ?: null,
                ]));
                for ($i = 1; $i <= $pages; $i++):
                    if ($i > 1 && $i < $pages && abs($i - $page) > 2) {
                        if ($i === 2 || $i === $pages - 1) echo '<span style="padding:6px 8px;">…</span>';
                        continue;
                    }
                ?>
                    <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?<?= $base ?>&p=<?= $i ?>"
                       class="btn btn-sm <?= $i === $page ? 'btn-primary' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../_layout_bottom.php';
