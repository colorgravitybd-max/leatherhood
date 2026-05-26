<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$products = Database::all(
    'SELECT id, title FROM products WHERE is_active = 1 ORDER BY title ASC'
);

$_title = 'Batch Generator';
require __DIR__ . '/../_layout_top.php';
?>
<div class="card" style="max-width:720px;">
    <h2>Generate a new batch of authenticity codes</h2>
    <p style="color:var(--muted); font-size:12px; margin-bottom:14px;">
        Codes use a 30-character ambiguity-free alphabet (no <code>0/O/1/I/U</code>) so they remain
        readable even on smudged thermal labels. After generation you can export the batch as CSV
        for your label-printing house.
    </p>

    <?php if (empty($products)): ?>
        <p style="color:var(--crimson)">
            You need to create at least one active product before generating codes.
            <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=products/edit" class="btn btn-sm" style="margin-left:8px;">Add product</a>
        </p>
    <?php else: ?>
    <form method="post" action="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes/generate" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">

        <div class="form-grid-2">
            <div class="form-row">
                <label for="product_id">Product *</label>
                <select id="product_id" name="product_id" required>
                    <option value="">— Select —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= Helpers::e($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="code_type">Code type *</label>
                <select id="code_type" name="code_type" required>
                    <option value="unique">Unique (one-time scratch-off)</option>
                    <option value="universal">Universal (multi-use, generic)</option>
                </select>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-row">
                <label for="batch_number">Batch identifier *</label>
                <input id="batch_number" name="batch_number" type="text" required maxlength="50"
                       placeholder="BATCH-2026-Q1">
            </div>
            <div class="form-row">
                <label for="expiry_date">Expiry date</label>
                <input id="expiry_date" name="expiry_date" type="date">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-row">
                <label for="quantity">Quantity (1 - 100,000)</label>
                <input id="quantity" name="quantity" type="number" min="1" max="100000" value="500" required>
                <small style="color:var(--muted); font-size:11px;">For "universal" type, only 1 code is created regardless.</small>
            </div>
            <div class="form-row">
                <label for="prefix">Prefix</label>
                <input id="prefix" name="prefix" type="text" maxlength="10" value="ELH">
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
            <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes" class="btn">Cancel</a>
            <button type="submit" class="btn btn-primary">Generate batch</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<div class="card" style="max-width:720px;">
    <h2>Or import an existing batch (CSV)</h2>
    <p style="color:var(--muted); font-size:12px; margin-bottom:10px;">
        CSV columns required: <code>code, product_id, code_type, batch_number, expiry_date</code>.
        Header row is required. Duplicate <code>code</code> values are skipped.
    </p>
    <form method="post" action="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes/import" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
        <div class="form-row">
            <input type="file" name="file" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn">Import CSV</button>
    </form>
</div>

<?php require __DIR__ . '/../_layout_bottom.php';
