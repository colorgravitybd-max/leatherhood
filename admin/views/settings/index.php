<?php
use LH\Core\Database;
use LH\Core\Helpers;
use LH\Core\Auth;

$page_title = 'Settings';
Auth::gate('settings.read');
$db = Database::i();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Helpers::checkCsrf((string)Helpers::input('_csrf'))) {
    Auth::gate('settings.write');
    $values = (array)($_POST['s'] ?? []);
    foreach ($values as $key => $val) {
        $db->run("INSERT INTO settings (key_name, value, group_name, type)
                   VALUES (?, ?, 'general', 'text')
                   ON DUPLICATE KEY UPDATE value = VALUES(value)",
                  [$key, (string)$val]);
    }
}

$rows = $db->all('SELECT * FROM settings ORDER BY group_name, key_name');
$groups = [];
foreach ($rows as $r) $groups[$r['group_name']][$r['key_name']] = $r;

include __DIR__ . '/../_layout_top.php';
?>
<form method="post">
<input type="hidden" name="_csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
<?php foreach ($groups as $gname => $items): ?>
  <div class="card">
    <h2><?= ucfirst($gname) ?></h2>
    <div class="form-grid">
      <?php foreach ($items as $k => $r): ?>
        <div<?= $r['type']==='html'||strlen((string)$r['value'])>80?' class="full"':'' ?>>
          <label class="lbl"><?= Helpers::e(str_replace('_',' ',$k)) ?></label>
          <?php if ($r['type'] === 'boolean'): ?>
            <label><input type="checkbox" name="s[<?= Helpers::e($k) ?>]" value="1" <?= $r['value']?'checked':'' ?>> Enable</label>
          <?php elseif ($r['type'] === 'html' || strlen((string)$r['value']) > 80): ?>
            <textarea class="input" name="s[<?= Helpers::e($k) ?>]" rows="3"><?= Helpers::e($r['value']) ?></textarea>
          <?php else: ?>
            <input class="input" name="s[<?= Helpers::e($k) ?>]" value="<?= Helpers::e($r['value']) ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<button class="btn btn-primary">Save All Settings</button>
</form>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
