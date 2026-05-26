<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Helpers;

$_route   = (string) ($_GET['route'] ?? 'dashboard');
$_user    = Auth::user();
$_title   = $_title ?? 'Dashboard';

function nav_link(string $route, string $label, string $current): string {
    $cls = str_starts_with($current, $route) ? 'active' : '';
    return '<a href="/admin/?route=' . htmlspecialchars($route) . '" class="' . $cls . '">' . htmlspecialchars($label) . '</a>';
}

$_flashSuccess = Helpers::flash('success');
$_flashError   = Helpers::flash('error');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= Helpers::e($_title) ?> · ELHOE Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-side">
        <div class="brand">ELHOE · ADMIN</div>

        <div class="group-label">Overview</div>
        <?= nav_link('dashboard', 'Dashboard', $_route) ?>

        <div class="group-label">Catalogue</div>
        <?= nav_link('products', 'Products', $_route) ?>

        <div class="group-label">Codes</div>
        <?= nav_link('codes', 'All Codes', $_route) ?>
        <?= nav_link('codes/generate', 'Batch Generator', $_route) ?>

        <div class="group-label">Insights</div>
        <?= nav_link('insights/geo',    'Geographic Log',   $_route) ?>
        <?= nav_link('insights/radar',  'Counterfeit Radar',$_route) ?>
        <?= nav_link('insights/expiry', 'Batch Expiry',     $_route) ?>
        <?= nav_link('logs',            'Raw Scan Logs',    $_route) ?>

        <div class="group-label">Account</div>
        <a href="/admin/?route=logout">Logout</a>
        <a href="/" target="_blank" rel="noopener">View public portal ↗</a>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <h1><?= Helpers::e($_title) ?></h1>
            <div class="user">
                Signed in as <strong><?= Helpers::e($_user['name'] ?? '') ?></strong>
                · <?= Helpers::e($_user['role'] ?? '') ?>
            </div>
        </div>

        <?php if ($_flashSuccess): ?>
            <div class="flash flash-success"><?= Helpers::e($_flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($_flashError): ?>
            <div class="flash flash-error"><?= Helpers::e($_flashError) ?></div>
        <?php endif; ?>
