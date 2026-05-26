<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

// File presence checks - relative to APP_ROOT (= /checker on the server).
$files = [
    '.env',
    '.htaccess',
    'config/config.php',
    'index.php',
    'verify_action.php',
    'app/Core/Auth.php',
    'app/Core/Database.php',
    'app/Core/GeoIP.php',
    'app/Core/Helpers.php',
    'app/Core/RateLimiter.php',
    'app/Services/CodeGenerator.php',
    'app/Services/Turnstile.php',
    'admin/index.php',
    'admin/.htaccess',
    'admin/actions/batch_export_csv.php',
    'admin/actions/batch_generate.php',
    'admin/actions/batch_import_csv.php',
    'admin/actions/batch_paste.php',
    'admin/actions/product_delete.php',
    'admin/actions/product_save.php',
    'admin/views/_layout_top.php',
    'admin/views/_layout_bottom.php',
    'admin/views/login.php',
    'admin/views/dashboard.php',
    'admin/views/codes/generate.php',
    'admin/views/codes/index.php',
    'admin/views/products/edit.php',
    'admin/views/products/index.php',
    'admin/views/insights/geo.php',
    'admin/views/insights/radar.php',
    'admin/views/insights/expiry.php',
    'admin/views/logs/index.php',
    'database/schema.sql',
    'database/seed.sql',
];

$dbOk = false; $dbErr = ''; $dbVersion = '';
try {
    $row = Database::one('SELECT VERSION() AS v');
    $dbOk = true;
    $dbVersion = (string) ($row['v'] ?? 'unknown');
} catch (Throwable $e) {
    $dbErr = $e->getMessage();
}

$counts = ['products' => 0, 'codes' => 0, 'scans' => 0];
if ($dbOk) {
    try {
        $counts['products'] = (int) Database::scalar('SELECT COUNT(*) FROM products');
        $counts['codes']    = (int) Database::scalar('SELECT COUNT(*) FROM verification_codes');
        $counts['scans']    = (int) Database::scalar('SELECT COUNT(*) FROM scan_logs');
    } catch (Throwable $e) { /* tables may not exist yet */ }
}

$_title = 'Diagnostics';
require __DIR__ . '/_layout_top.php';
?>
<div class="card">
    <h2>Deployment Diagnostics</h2>
    <p style="color:var(--muted); font-size:13px; margin-bottom:18px;">
        Use this page after every upload to verify the deployment is healthy.
        It is gated by admin login, so it's safe to leave on production.
    </p>

    <table class="table">
        <tr><th colspan="2">Environment</th></tr>
        <tr><td><code>PHP_VERSION</code></td><td><?= Helpers::e(PHP_VERSION) ?></td></tr>
        <tr><td><code>APP_ROOT</code></td><td><code><?= Helpers::e(APP_ROOT) ?></code></td></tr>
        <tr><td><code>BASE_PATH</code></td><td><code><?= Helpers::e(BASE_PATH === '' ? '(empty - means root install)' : BASE_PATH) ?></code></td></tr>
        <tr><td><code>APP_BASE_URL</code></td><td><code><?= Helpers::e(APP_BASE_URL ?: '(empty)') ?></code></td></tr>
        <tr><td><code>APP_DEBUG</code></td><td><?= APP_DEBUG ? '<strong style="color:var(--crimson)">true (DISABLE BEFORE GO-LIVE)</strong>' : 'false' ?></td></tr>
        <tr><td><code>TURNSTILE</code></td><td><?= TURNSTILE_SITE_KEY !== '' ? 'configured' : '<span style="color:var(--muted)">not configured (OK for now)</span>' ?></td></tr>

        <tr><th colspan="2">Database</th></tr>
        <?php if ($dbOk): ?>
            <tr><td>Connection</td><td><span class="badge badge-emerald">OK</span> &nbsp; MySQL <?= Helpers::e($dbVersion) ?></td></tr>
            <tr><td>products rows</td><td><?= number_format($counts['products']) ?></td></tr>
            <tr><td>verification_codes rows</td><td><?= number_format($counts['codes']) ?></td></tr>
            <tr><td>scan_logs rows</td><td><?= number_format($counts['scans']) ?></td></tr>
        <?php else: ?>
            <tr><td>Connection</td><td><span class="badge badge-crimson">FAILED</span> &nbsp; <code style="color:var(--crimson)"><?= Helpers::e($dbErr) ?></code></td></tr>
        <?php endif; ?>
    </table>

    <h3 style="margin-top:24px; font-size:13px;">Files</h3>
    <table class="table">
        <thead>
            <tr><th>Path (relative to <?= Helpers::e(APP_ROOT) ?>)</th><th>Status</th><th>Size</th></tr>
        </thead>
        <tbody>
        <?php foreach ($files as $f):
            $abs = APP_ROOT . '/' . $f;
            $ok  = is_file($abs);
            ?>
            <tr>
                <td><code><?= Helpers::e($f) ?></code></td>
                <td><?= $ok ? '<span class="badge badge-emerald">found</span>' : '<span class="badge badge-crimson">MISSING</span>' ?></td>
                <td class="num"><?= $ok ? number_format(filesize($abs)) . ' B' : '&mdash;' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3 style="margin-top:24px; font-size:13px;">Form-action sanity test</h3>
    <p style="color:var(--muted); font-size:12px;">
        These three links should ALL land on this admin's codes page.
        If any of them sends you to a WordPress 404 page, you have a base-path
        problem (or an .htaccess problem).
    </p>
    <ul style="line-height:1.9;">
        <li><a href="?route=codes">Relative: <code>?route=codes</code></a></li>
        <li><a href="<?= Helpers::e(BASE_PATH) ?>/admin/?route=codes">Absolute with BASE_PATH: <code><?= Helpers::e(BASE_PATH) ?>/admin/?route=codes</code></a></li>
        <li><a href="/admin/?route=codes" style="color:var(--crimson)">BAD (no BASE_PATH): <code>/admin/?route=codes</code></a> &nbsp;<small style="color:var(--muted)">- this one SHOULD 404 to WordPress, that proves the test works</small></li>
    </ul>

    <h3 style="margin-top:24px; font-size:13px;">Quick links</h3>
    <p style="line-height:2;">
        <a class="btn btn-sm" href="?route=dashboard">Dashboard</a>
        <a class="btn btn-sm" href="?route=products">Products</a>
        <a class="btn btn-sm" href="?route=codes">Codes</a>
        <a class="btn btn-sm" href="?route=codes/generate">Codes / Generate &amp; Paste</a>
    </p>
</div>

<?php require __DIR__ . '/_layout_bottom.php';
