<?php
use LH\Core\Database;
use LH\Core\Helpers;

$page_title = 'Reviews';
$db = Database::i();
$status = Helpers::input('status','pending');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Helpers::checkCsrf((string)Helpers::input('_csrf'))) {
    $rid = (int)Helpers::input('rid');
    $act = Helpers::input('act');
    $map = ['approve'=>'approved','spam'=>'spam','trash'=>'trash','pending'=>'pending'];
    if ($rid && isset($map[$act])) {
        $db->update('product_reviews', ['status' => $map[$act]], 'id = :_id', [':_id' => $rid]);
        // Recalculate product rating
        $r = $db->one('SELECT product_id FROM product_reviews WHERE id=?', [$rid]);
        if ($r) {
            $agg = $db->one('SELECT AVG(rating) AS a, COUNT(*) AS c FROM product_reviews WHERE product_id=? AND status="approved"', [$r['product_id']]);
            $db->update('products',
                ['rating_avg' => round((float)$agg['a'],2), 'rating_count' => (int)$agg['c']],
                'id = :_id', [':_id' => $r['product_id']]);
        }
    }
}

$rows = $db->all("SELECT r.*, p.name AS product_name, p.slug AS product_slug
                  FROM product_reviews r JOIN products p ON p.id=r.product_id
                  WHERE r.status = ? ORDER BY r.id DESC LIMIT 200", [$status]);
include __DIR__ . '/../_layout_top.php';
?>
<div class="card">
  <div class="searchbar">
    <?php foreach (['pending','approved','spam','trash'] as $s): ?>
      <a class="btn <?= $status===$s?'btn-primary':'' ?>" href="?status=<?= $s ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="table-wrap"><table class="table">
    <thead><tr><th>Product</th><th>Author</th><th>Rating</th><th>Comment</th><th>Date</th><th class="actions"></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="/product/<?= Helpers::e($r['product_slug']) ?>" target="_blank"><?= Helpers::e($r['product_name']) ?></a></td>
        <td><?= Helpers::e($r['name']) ?><br><small style="color:var(--ink-soft)"><?= Helpers::e($r['email']) ?></small></td>
        <td style="color:var(--gold)"><?= str_repeat('★', (int)$r['rating']) ?></td>
        <td style="max-width:340px"><b><?= Helpers::e($r['title']) ?></b><br><span style="color:var(--ink-soft)"><?= Helpers::e($r['body']) ?></span></td>
        <td><?= Helpers::e(date('M d, H:i', strtotime($r['created_at']))) ?></td>
        <td class="actions">
          <form method="post" style="display:inline">
            <input type="hidden" name="_csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
            <input type="hidden" name="rid" value="<?= (int)$r['id'] ?>">
            <?php if ($status !== 'approved'): ?><button class="btn btn-sm btn-success" name="act" value="approve">Approve</button><?php endif; ?>
            <?php if ($status !== 'spam'):     ?><button class="btn btn-sm" name="act" value="spam">Spam</button><?php endif; ?>
            <?php if ($status !== 'trash'):    ?><button class="btn btn-sm btn-danger" name="act" value="trash">Trash</button><?php endif; ?>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
