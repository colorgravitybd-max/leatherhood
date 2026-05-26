<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$p  = $id > 0
    ? Database::one('SELECT * FROM products WHERE id = ?', [$id])
    : null;

$_title = $id > 0 ? 'Edit Product' : 'New Product';
require __DIR__ . '/../_layout_top.php';
?>
<div class="card" style="max-width:920px;">
    <form method="post" action="<?= Helpers::e(BASE_PATH) ?>/admin/?route=products/save" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 0) ?>">

        <div class="form-grid-2">
            <div class="form-row">
                <label for="title">Title (English) *</label>
                <input id="title" name="title" type="text" required maxlength="255"
                       value="<?= Helpers::e($p['title'] ?? '') ?>">
            </div>
            <div class="form-row">
                <label for="title_bn">Title (Bengali)</label>
                <input id="title_bn" name="title_bn" type="text" maxlength="255"
                       value="<?= Helpers::e($p['title_bn'] ?? '') ?>">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-row">
                <label for="description">Description (English)</label>
                <textarea id="description" name="description" rows="4"><?= Helpers::e($p['description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <label for="description_bn">Description (Bengali)</label>
                <textarea id="description_bn" name="description_bn" rows="4"><?= Helpers::e($p['description_bn'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <label for="ingredients">Ingredients</label>
            <textarea id="ingredients" name="ingredients" rows="3"><?= Helpers::e($p['ingredients'] ?? '') ?></textarea>
        </div>

        <div class="form-grid-2">
            <div class="form-row">
                <label for="how_to_use">How to use (English) — one step per line</label>
                <textarea id="how_to_use" name="how_to_use" rows="5"><?= Helpers::e($p['how_to_use'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <label for="how_to_use_bn">How to use (Bengali)</label>
                <textarea id="how_to_use_bn" name="how_to_use_bn" rows="5"><?= Helpers::e($p['how_to_use_bn'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-row">
                <label for="wordpress_url">WordPress product URL <span style="color:var(--muted); font-weight:400;">(optional)</span></label>
                <input id="wordpress_url" name="wordpress_url" type="url" maxlength="255"
                       placeholder="https://elhoe.com/product/..."
                       value="<?= Helpers::e($p['wordpress_url'] ?? '') ?>">
                <small style="color:var(--muted); font-size:11px;">
                    If left blank, the verification page will not show a clickable product link or "Buy Again" button.
                </small>
            </div>
            <div class="form-row">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1" <?= ((int) ($p['is_active'] ?? 1) === 1 ? 'selected' : '') ?>>Active</option>
                    <option value="0" <?= ((int) ($p['is_active'] ?? 1) === 0 ? 'selected' : '') ?>>Hidden</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <label for="image">Product image (JPG/PNG/WebP, max 4 MB)</label>
            <?php if (!empty($p['image_url'])): ?>
                <div style="margin-bottom:8px;">
                    <img src="<?= Helpers::e($p['image_url']) ?>" alt=""
                         style="height:90px; border-radius:8px; border:1px solid var(--border);">
                    <p style="font-size:11px; color:var(--muted); margin-top:4px;">
                        Current: <?= Helpers::e($p['image_url']) ?>
                    </p>
                </div>
            <?php endif; ?>
            <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
            <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=products" class="btn">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <?= $id > 0 ? 'Save changes' : 'Create product' ?>
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../_layout_bottom.php';
