<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$products = Database::all(
    'SELECT p.id, p.title, p.is_active, p.image_url, p.wordpress_url, p.created_at,
            (SELECT COUNT(*) FROM verification_codes vc WHERE vc.product_id = p.id) AS code_count
       FROM products p
      ORDER BY p.created_at DESC'
);

$_title = 'Products';
require __DIR__ . '/../_layout_top.php';
?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h2 style="margin:0;">All Products</h2>
        <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=products/edit" class="btn btn-primary">+ New Product</a>
    </div>

    <?php if (empty($products)): ?>
        <p style="color:var(--muted); padding:20px 0;">No products yet. Add your first one.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th style="width:60px;"></th>
                    <th>Title</th>
                    <th class="num">Codes</th>
                    <th>Status</th>
                    <th>WordPress URL</th>
                    <th style="width:160px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <?php $img = $p['image_url'] ?? ''; ?>
                <tr>
                    <td>
                        <?php if ($img): ?>
                            <img src="<?= Helpers::e($img) ?>" alt=""
                                 style="width:46px; height:46px; object-fit:cover; border-radius:8px; border:1px solid var(--border);">
                        <?php else: ?>
                            <div style="width:46px; height:46px; border-radius:8px; background:#f3eee7;"></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= Helpers::e($p['title']) ?></strong><br>
                        <span style="color:var(--muted); font-size:11px;">#<?= (int) $p['id'] ?></span>
                    </td>
                    <td class="num"><?= (int) $p['code_count'] ?></td>
                    <td>
                        <?php if ((int) $p['is_active'] === 1): ?>
                            <span class="badge badge-emerald">Active</span>
                        <?php else: ?>
                            <span class="badge badge-slate">Hidden</span>
                        <?php endif; ?>
                    </td>
                    <td class="mono" style="max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?php if ($p['wordpress_url']): ?>
                            <a href="<?= Helpers::e($p['wordpress_url']) ?>" target="_blank" rel="noopener">
                                <?= Helpers::e($p['wordpress_url']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:var(--muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=products/edit&id=<?= (int) $p['id'] ?>" class="btn btn-sm">Edit</a>
                        <form method="post" action="<?= Helpers::e(BASE_PATH) ?>/admin/?route=products/delete&id=<?= (int) $p['id'] ?>"
                              style="display:inline;">
                            <input type="hidden" name="csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
                            <button class="btn btn-sm btn-danger"
                                    data-confirm="Delete this product and ALL its verification codes?">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../_layout_bottom.php';
