<?php
use LH\Core\Database;
use LH\Core\Helpers;
use LH\Services\CloudflareService;

$id = Helpers::input('id', 'new');
$isNew = $id === 'new';
$db = Database::i();
$p  = $isNew ? [
        'id'=>0,'sku'=>'','name'=>'','slug'=>'','price'=>0,'compare_at_price'=>null,
        'short_description'=>'','description'=>'','stock_qty'=>0,'low_stock_threshold'=>5,
        'status'=>'draft','featured_image'=>'','template_type'=>'default',
        'is_featured'=>0,'is_popular'=>0,'is_new'=>0,'track_inventory'=>1,
        'meta_title'=>'','meta_description'=>'','primary_category_id'=>null,
      ]
    : $db->one('SELECT * FROM products WHERE id=?', [(int)$id]);
if (!$p) { http_response_code(404); echo 'Not found.'; return; }

$cats = $db->all('SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order, name');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) { $msg = 'Bad CSRF.'; }
    else {
        $payload = [
            'sku'   => trim((string)Helpers::input('sku')),
            'name'  => trim((string)Helpers::input('name')),
            'slug'  => Helpers::slug(Helpers::input('slug') ?: Helpers::input('name')),
            'price' => (float) Helpers::input('price', 0),
            'compare_at_price' => Helpers::input('compare_at_price') ? (float)Helpers::input('compare_at_price') : null,
            'short_description' => Helpers::input('short_description'),
            'description'       => Helpers::input('description'),
            'stock_qty'         => (int) Helpers::input('stock_qty', 0),
            'low_stock_threshold' => (int) Helpers::input('low_stock_threshold', 5),
            'status' => Helpers::input('status', 'draft'),
            'featured_image' => Helpers::input('featured_image'),
            'template_type'  => Helpers::input('template_type', 'default'),
            'is_featured' => Helpers::input('is_featured') ? 1 : 0,
            'is_popular'  => Helpers::input('is_popular') ? 1 : 0,
            'is_new'      => Helpers::input('is_new') ? 1 : 0,
            'track_inventory' => Helpers::input('track_inventory') ? 1 : 0,
            'meta_title'        => Helpers::input('meta_title'),
            'meta_description'  => Helpers::input('meta_description'),
            'primary_category_id' => (int) Helpers::input('primary_category_id') ?: null,
            'published_at' => (Helpers::input('status')==='active' && !$p['id']) ? date('Y-m-d H:i:s') : ($p['published_at'] ?? null),
        ];
        if ($isNew) {
            $newId = $db->insert('products', $payload);
            $msg = 'Product created.';
            Helpers::redirect('/admin/product?id='.$newId.'&saved=1');
        } else {
            $db->update('products', $payload, 'id = :_id', [':_id' => $p['id']]);
            $msg = 'Saved.';
            // Auto-purge Cloudflare for this product
            try { CloudflareService::purgeProduct($payload['slug']); } catch (\Throwable $e) {}
        }
        $p = array_merge($p, $payload);
    }
}
$page_title = $isNew ? 'New Product' : 'Edit · '.$p['name'];
include __DIR__ . '/../_layout_top.php';
?>

<?php if ($msg): ?><div class="card" style="background:#dff6e8;border-color:#9ed5b3"><?= Helpers::e($msg) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px">
  <div>
    <div class="card">
      <h2><?= $isNew ? 'Create Product' : 'Edit Product' ?></h2>
      <div class="form-grid">
        <div class="full"><label class="lbl">Name</label><input class="input" name="name" required value="<?= Helpers::e($p['name']) ?>"></div>
        <div><label class="lbl">SKU</label><input class="input" name="sku" required value="<?= Helpers::e($p['sku']) ?>"></div>
        <div><label class="lbl">Slug (auto if blank)</label><input class="input" name="slug" value="<?= Helpers::e($p['slug']) ?>"></div>
        <div><label class="lbl">Price (৳)</label><input class="input" name="price" type="number" min="0" step="1" required value="<?= Helpers::e($p['price']) ?>"></div>
        <div><label class="lbl">Compare-at Price</label><input class="input" name="compare_at_price" type="number" min="0" step="1" value="<?= Helpers::e($p['compare_at_price']) ?>"></div>
        <div><label class="lbl">Stock Qty</label><input class="input" name="stock_qty" type="number" min="0" value="<?= (int)$p['stock_qty'] ?>"></div>
        <div><label class="lbl">Low-Stock Threshold</label><input class="input" name="low_stock_threshold" type="number" min="0" value="<?= (int)$p['low_stock_threshold'] ?>"></div>
        <div class="full"><label class="lbl">Short Description</label><textarea class="input" name="short_description" rows="2"><?= Helpers::e($p['short_description']) ?></textarea></div>
        <div class="full"><label class="lbl">Full Description (HTML)</label><textarea class="input" name="description" rows="8"><?= Helpers::e($p['description']) ?></textarea></div>
      </div>
    </div>

    <div class="card">
      <h2>SEO</h2>
      <div class="form-grid">
        <div class="full"><label class="lbl">Meta Title</label><input class="input" name="meta_title" value="<?= Helpers::e($p['meta_title']) ?>"></div>
        <div class="full"><label class="lbl">Meta Description</label><textarea class="input" name="meta_description" rows="2"><?= Helpers::e($p['meta_description']) ?></textarea></div>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <h2>Publish</h2>
      <label class="lbl">Status</label>
      <select class="select" name="status">
        <?php foreach (['draft','active','archived','out_of_stock'] as $s): ?>
          <option value="<?= $s ?>" <?= $p['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>

      <div style="margin-top:14px">
        <label class="lbl">Template Type</label>
        <select class="select" name="template_type">
          <?php foreach (['default','minimal','storyteller','landing','luxury','split'] as $t): ?>
            <option value="<?= $t ?>" <?= $p['template_type']===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="margin-top:14px">
        <label class="lbl">Primary Category</label>
        <select class="select" name="primary_category_id">
          <option value="">— None —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $p['primary_category_id']==$c['id']?'selected':'' ?>><?= Helpers::e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="margin-top:14px;line-height:2">
        <label><input type="checkbox" name="is_featured" <?= $p['is_featured']?'checked':'' ?>> Featured</label><br>
        <label><input type="checkbox" name="is_popular"  <?= $p['is_popular']?'checked':'' ?>> Popular</label><br>
        <label><input type="checkbox" name="is_new"      <?= $p['is_new']?'checked':'' ?>> New Arrival</label><br>
        <label><input type="checkbox" name="track_inventory" <?= $p['track_inventory']?'checked':'' ?>> Track inventory</label>
      </div>

      <button class="btn btn-primary" style="width:100%;margin-top:18px;justify-content:center" type="submit">Save Product</button>
    </div>

    <div class="card">
      <h2>Featured Image</h2>
      <input type="hidden" name="featured_image" id="featured_image" value="<?= Helpers::e($p['featured_image']) ?>">
      <?php if ($p['featured_image']): ?>
        <img src="<?= Helpers::e($p['featured_image']) ?>" style="width:100%;border-radius:8px;margin-bottom:10px">
      <?php endif; ?>
      <input class="input" type="file" accept="image/*" onchange="LHA.uploadImage(this, '#featured_image')">
      <p style="font-size:12px;color:var(--ink-soft);margin-top:8px">Auto-converted to WebP. Compression savings shown after upload.</p>
    </div>
  </div>
</div>
</form>

<?php include __DIR__ . '/../_layout_bottom.php'; ?>
