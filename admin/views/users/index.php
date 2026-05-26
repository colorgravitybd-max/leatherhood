<?php
use LH\Core\Database;
use LH\Core\Helpers;
use LH\Core\Auth;

$page_title = 'Team & RBAC';
Auth::gate('users.read');
$db = Database::i();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Helpers::checkCsrf((string)Helpers::input('_csrf'))) {
    Auth::gate('users.write');
    $uid = (int)Helpers::input('id', 0);
    $payload = [
        'name'   => trim((string)Helpers::input('name')),
        'email'  => trim((string)Helpers::input('email')),
        'phone'  => Helpers::input('phone'),
        'role'   => Helpers::input('role','moderator'),
        'is_active' => Helpers::input('is_active') ? 1 : 0,
    ];
    if (Helpers::input('password')) {
        $payload['password'] = password_hash((string)Helpers::input('password'), PASSWORD_BCRYPT);
    }
    if ($uid) {
        $db->update('users', $payload, 'id = :_id', [':_id' => $uid]);
    } else {
        if (!isset($payload['password'])) $payload['password'] = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
        $db->insert('users', $payload);
    }
}

$rows = $db->all('SELECT * FROM users ORDER BY id ASC');
include __DIR__ . '/../_layout_top.php';
?>
<div class="card">
  <h2>Team Members</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Last Login</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $u): ?>
      <tr>
        <td><b><?= Helpers::e($u['name']) ?></b></td>
        <td><?= Helpers::e($u['email']) ?></td>
        <td><span class="pill <?= $u['role']==='super_admin'?'delivered':'pending' ?>"><?= $u['role'] ?></span></td>
        <td><?= $u['is_active']?'✓':'—' ?></td>
        <td><?= $u['last_login_at'] ? Helpers::e($u['last_login_at']) : '—' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<div class="card">
  <h2>Add Team Member</h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="_csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
    <div><label class="lbl">Name</label><input class="input" name="name" required></div>
    <div><label class="lbl">Email</label><input class="input" type="email" name="email" required></div>
    <div><label class="lbl">Phone</label><input class="input" name="phone"></div>
    <div><label class="lbl">Role</label>
      <select class="select" name="role">
        <?php foreach (['super_admin','admin','moderator','support'] as $r): ?>
          <option value="<?= $r ?>"><?= $r ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label class="lbl">Password</label><input class="input" type="password" name="password" placeholder="Leave blank to auto-generate"></div>
    <div><label class="lbl">Active</label><label><input type="checkbox" name="is_active" checked> Yes</label></div>
    <div class="full"><button class="btn btn-primary">Create User</button></div>
  </form>
</div>
<?php include __DIR__ . '/../_layout_bottom.php'; ?>
