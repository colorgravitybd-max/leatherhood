<?php
use LH\Core\Database;
use LH\Core\Helpers;

$page_title = 'Marketing Scripts';
$db = Database::i();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Helpers::checkCsrf((string)Helpers::input('_csrf'))) {
    $id = (int)Helpers::input('id', 0);
    $payload = [
        'name'      => trim((string)Helpers::input('name')),
        'provider'  => Helpers::input('provider','custom'),
        'placement' => Helpers::input('placement','head'),
        'tag_id'    => Helpers::input('tag_id'),
        'code'      => Helpers::input('code',''),
        'is_active' => Helpers::input('is_active') ? 1 : 0,
        'sort_order'=> (int)Helpers::input('sort_order',0),
    ];
    if ($id) $db->update('marketing_scripts', $payload, 'id = :_id', [':_id' => $id]);
    else     $db->insert('marketing_scripts', $payload);
}
if ($_GET['delete'] ?? null) {
    $db->run('DELETE FROM marketing_scripts WHERE id=?', [(int)$_GET['delete']]);
}
$rows = $db->all('SELECT * FROM marketing_scripts ORDER BY sort_order, id');
include __DIR__ . '/../_layout_top.php';
?>
<div class="card">
  <h2>Tracking & Marketing</h2>
  <p style="color:var(--ink-soft)">Drop GTM, Meta Pixel, MS Clarity, TikTok or any custom script. Choose where to inject (head/body/footer).</p>

  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Provider</th><th>Placement</th><th>Tag ID</th><th>Active</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $s): ?>
      <tr>
        <td><b><?= Helpers::e($s['name']) ?></b></td>
        <td><?= Helpers::e($s['provider']) ?></td>
        <td><?= Helpers::e($s['placement']) ?></td>
        <td><?= Helpers::e($s['tag_id']) ?></td>
        <td><?= $s['is_active']?'✓':'—' ?></td>
        <td><a class="btn btn-sm btn-danger" href="?delete=<?= (int)$s['id'] ?>" onclick="return confirm('Delete?')">Delete</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<div class="card">
  <h2>Add Script</h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="_csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
    <div><label class="lbl">Name</label><input class="input" name="name" required placeholder="Meta Pixel"></div>
    <div><label class="lbl">Provider</label>
      <select class="select" name="provider">
        <?php foreach (['gtm','meta_pixel','ms_clarity','google_ads','google_analytics','tiktok','custom'] as $p): ?>
          <option><?= $p ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label class="lbl">Placement</label>
      <select class="select" name="placement">
        <?php foreach (['head','body_open','body_close','footer'] as $p): ?>
          <option><?= $p ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label class="lbl">Tag ID (optional)</label><input class="input" name="tag_id" placeholder="GTM-XXXXX"></div>
    <div class="full"><label class="lbl">Code</label><textarea class="input" name="code" rows="6" placeholder="<script>...</script>"></textarea></div>
    <div><label class="lbl">Sort</label><input class="input" type="number" name="sort_order" value="0"></div>
    <div><label class="lbl">Active</label><label><input type="checkbox" name="is_active" checked> Yes</label></div>
    <div class="full"><button class="btn btn-primary">Save</button></div>
  </form>
</div>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
