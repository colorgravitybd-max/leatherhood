<?php
use LH\Core\Auth;
use LH\Core\Helpers;

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::checkCsrf((string)Helpers::input('_csrf'))) {
        $err = 'Session expired, please retry.';
    } elseif (Auth::login((string)Helpers::input('email'), (string)Helpers::input('password'))) {
        Helpers::redirect(Helpers::adminUrl('dashboard'));
    } else {
        $err = 'Invalid credentials.';
    }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in · LeatherHood Admin</title>
<link rel="stylesheet" href="/admin/assets/admin.css">
</head><body>
<div class="login-shell">
  <form class="card login-card" method="post">
    <div class="brand">LEATHERHOOD</div>
    <p class="muted" style="text-align:center;color:var(--ink-soft);font-size:13px;margin-bottom:22px">
      Sign in to your admin console.
    </p>

    <?php if ($err): ?><div class="card" style="background:#fce0e0;border-color:#f3b6b6;padding:10px 14px;margin-bottom:16px;font-size:13px"><?= Helpers::e($err) ?></div><?php endif; ?>

    <input type="hidden" name="_csrf" value="<?= Helpers::e(Helpers::csrfToken()) ?>">
    <label class="lbl">Email</label>
    <input class="input" type="email" name="email" required autofocus value="admin@leatherhoodbd.com">
    <div style="height:14px"></div>
    <label class="lbl">Password</label>
    <input class="input" type="password" name="password" required>
    <div style="height:22px"></div>
    <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">Sign in →</button>
  </form>
</div>
</body></html>
