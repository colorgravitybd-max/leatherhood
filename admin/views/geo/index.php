<?php
use LH\Core\Database;
use LH\Core\Helpers;

$page_title = 'Shipping Zones';
$db = Database::i();

$divisions = $db->all('SELECT * FROM geo_divisions ORDER BY sort_order, name');
$selDiv = (int)Helpers::input('division', $divisions[0]['id'] ?? 0);
$districts = $db->all('SELECT * FROM geo_districts WHERE division_id=? ORDER BY sort_order, name', [$selDiv]);
$selDist = (int)Helpers::input('district', $districts[0]['id'] ?? 0);
$stations = $db->all('SELECT * FROM geo_police_stations WHERE district_id=? ORDER BY sort_order, name', [$selDist]);

include __DIR__ . '/../_layout_top.php';
?>
<div class="card">
  <h2>Shipping Zones — Bangladesh</h2>
  <p style="color:var(--ink-soft)">Set base fees per district and extra fees / deliverability per police station. Inline edits save instantly.</p>

  <div class="form-grid">
    <div>
      <label class="lbl">Division</label>
      <select class="select" onchange="window.location='?division='+this.value">
        <?php foreach ($divisions as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= $selDiv===(int)$d['id']?'selected':'' ?>><?= Helpers::e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="lbl">District</label>
      <select class="select" onchange="const u=new URLSearchParams(location.search);u.set('district',this.value);location.search=u.toString()">
        <?php foreach ($districts as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= $selDist===(int)$d['id']?'selected':'' ?>><?= Helpers::e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<div class="card">
  <h2>Districts in <?= Helpers::e($divisions[array_search($selDiv, array_column($divisions,'id'))]['name'] ?? '') ?></h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Base Fee (৳)</th><th>Deliverable</th></tr></thead>
    <tbody>
    <?php foreach ($districts as $d): ?>
      <tr>
        <td><?= Helpers::e($d['name']) ?></td>
        <td><input class="input" style="width:110px" type="number" min="0" step="1" value="<?= Helpers::e($d['base_shipping_fee']) ?>" onchange="LHA.geoUpdate('geo_districts','base_shipping_fee',<?= (int)$d['id'] ?>,this.value)"></td>
        <td><label class="switch"><input type="checkbox" <?= $d['is_deliverable']?'checked':'' ?> onchange="LHA.geoUpdate('geo_districts','is_deliverable',<?= (int)$d['id'] ?>,this.checked?1:0)"> deliver</label></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<div class="card">
  <h2>Police Stations</h2>
  <?php if (!$stations): ?><p style="color:var(--ink-soft)">No police stations seeded for this district.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Extra Fee (৳)</th><th>Deliverable</th></tr></thead>
    <tbody>
    <?php foreach ($stations as $s): ?>
      <tr>
        <td><?= Helpers::e($s['name']) ?></td>
        <td><input class="input" style="width:110px" type="number" min="0" step="1" value="<?= Helpers::e($s['extra_shipping_fee']) ?>" onchange="LHA.geoUpdate('geo_police_stations','extra_shipping_fee',<?= (int)$s['id'] ?>,this.value)"></td>
        <td><label><input type="checkbox" <?= $s['is_deliverable']?'checked':'' ?> onchange="LHA.geoUpdate('geo_police_stations','is_deliverable',<?= (int)$s['id'] ?>,this.checked?1:0)"> deliver</label></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
