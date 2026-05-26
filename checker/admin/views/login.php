<?php
declare(strict_types=1);

use App\Core\Helpers;

$csrf  = Helpers::csrfToken();
$error = Helpers::flash('error');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in · ELHOE Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Cormorant+Garamond:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= Helpers::e(BASE_PATH) ?>/admin/assets/admin.css">
</head>
<body>
<div class="login-shell">
    <form class="login-card" method="post" action="?route=login" autocomplete="off">
        <?php
        $logoFile = APP_ROOT . '/assets/logo.png';
        if (is_file($logoFile)):
            $logoUrl = BASE_PATH . '/assets/logo.png?v=' . filemtime($logoFile);
        ?>
            <img src="<?= Helpers::e($logoUrl) ?>" alt="ELHOE"
                 style="display:block; max-height:54px; max-width:180px; margin:0 auto 8px; object-fit:contain;">
        <?php else: ?>
            <h1>ELHOE</h1>
        <?php endif; ?>
        <div class="subtitle">ADMIN CONTROL CENTER</div>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= Helpers::e($error) ?></div>
        <?php endif; ?>

        <input type="hidden" name="csrf" value="<?= Helpers::e($csrf) ?>">

        <div class="form-row">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required autofocus>
        </div>

        <div class="form-row">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:11px;">
            Sign in
        </button>
    </form>
</div>
</body>
</html>
