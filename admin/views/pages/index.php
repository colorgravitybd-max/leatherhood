<?php
use LH\Core\Database;
use LH\Core\Helpers;

$page_title = 'Pages';
$rows = Database::i()->all('SELECT * FROM pages ORDER BY id DESC');
include __DIR__ . '/../_layout_top.php';
?>
<div class="card">
  <div class="searchbar">
    <h3 style="margin:0">CMS Pages</h3>
    <span style="flex:1"></span>
    <a class="btn btn-primary" href="/admin/page?id=new">+ New Page</a>
  </div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
      <tr>
        <td><b><?= Helpers::e($p['title']) ?></b></td>
        <td><code>/page/<?= Helpers::e($p['slug']) ?></code></td>
        <td><span class="pill <?= $p['status']==='published'?'delivered':'cancelled' ?>"><?= $p['status'] ?></span></td>
        <td><?= Helpers::e(date('M d, H:i', strtotime($p['updated_at']))) ?></td>
        <td>
          <a class="btn btn-sm" href="/admin/page?id=<?= (int)$p['id'] ?>">Edit</a>
          <a class="btn btn-sm" href="/page/<?= Helpers::e($p['slug']) ?>" target="_blank">View</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
