<?php
use LH\Core\Database;
use LH\Core\Helpers;
use LH\Core\Auth;

$id = Helpers::input('id', 'new');
$isNew = $id === 'new';
$db = Database::i();
$p  = $isNew
    ? ['id'=>0,'title'=>'','slug'=>'','content'=>'','status'=>'draft','meta_title'=>'','meta_description'=>'']
    : $db->one('SELECT * FROM pages WHERE id=?', [(int)$id]);
if (!$p) { http_response_code(404); echo 'Not found.'; return; }

$msg='';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Helpers::checkCsrf((string)Helpers::input('_csrf'))) {
    $payload = [
        'title'   => trim((string)Helpers::input('title')),
        'slug'    => Helpers::slug(Helpers::input('slug') ?: Helpers::input('title')),
        'content' => Helpers::input('content', ''),
        'status'  => Helpers::input('status', 'draft'),
        'meta_title' => Helpers::input('meta_title'),
        'meta_description' => Helpers::input('meta_description'),
        'author_id' => Auth::user()['id'] ?? null,
        'published_at' => Helpers::input('status')==='published' ? date('Y-m-d H:i:s') : ($p['published_at'] ?? null),
    ];
    if ($isNew) {
        $newId = $db->insert('pages', $payload);
        Helpers::redirect('/admin/page?id='.$newId.'&saved=1');
    } else {
        $db->update('pages', $payload, 'id = :_id', [':_id' => $p['id']]);
        $msg='Saved.';
    }
    $p = array_merge($p, $payload);
}
$page_title = $isNew ? 'New Page' : 'Edit · '.$p['title'];
include __DIR__ . '/../_layout_top.php';
?>
<form method="post">
<input type="hidden" name="_csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
<div class="card">
  <?php if ($msg): ?><div style="background:#dff6e8;padding:8px 12px;border-radius:6px;margin-bottom:12px"><?= Helpers::e($msg) ?></div><?php endif; ?>
  <h2><?= $isNew ? 'New Page' : 'Edit Page' ?></h2>
  <div class="form-grid">
    <div class="full"><label class="lbl">Title</label><input class="input" name="title" required value="<?= Helpers::e($p['title']) ?>"></div>
    <div><label class="lbl">Slug</label><input class="input" name="slug" value="<?= Helpers::e($p['slug']) ?>"></div>
    <div><label class="lbl">Status</label>
      <select class="select" name="status">
        <?php foreach (['draft','published','private'] as $s): ?>
          <option value="<?= $s ?>" <?= $p['status']===$s?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="full"><label class="lbl">Content (HTML)</label>
      <textarea class="input" name="content" rows="14"><?= Helpers::e($p['content']) ?></textarea>
      <p style="font-size:12px;color:var(--ink-soft);margin-top:6px">Tip: HTML is allowed. A future release can wire any lightweight JS editor here.</p>
    </div>
    <div class="full"><label class="lbl">Meta Title</label><input class="input" name="meta_title" value="<?= Helpers::e($p['meta_title']) ?>"></div>
    <div class="full"><label class="lbl">Meta Description</label><textarea class="input" name="meta_description" rows="2"><?= Helpers::e($p['meta_description']) ?></textarea></div>
  </div>
  <button class="btn btn-primary" style="margin-top:16px">Save</button>
</div>
</form>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
