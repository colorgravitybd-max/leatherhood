<?php
use LH\Core\Auth;
use LH\Core\Helpers;

$u = Auth::user();
$active = $_SERVER['REQUEST_URI'] ?? '';
function nav_active(string $href): string { return str_contains($_SERVER['REQUEST_URI'] ?? '', $href) ? 'active' : ''; }

$pendingOrders = (int) LH\Core\Database::i()->value(
    "SELECT COUNT(*) FROM orders WHERE status IN ('pending','pending_otp','processing')");
$pendingReviews = (int) LH\Core\Database::i()->value(
    "SELECT COUNT(*) FROM product_reviews WHERE status='pending'");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= Helpers::e($page_title ?? 'Admin') ?> · LeatherHood</title>
<link rel="stylesheet" href="/admin/assets/admin.css">
<link rel="icon" href="/assets/images/favicon.png">
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand">LEATHERHOOD<small>Admin Console</small></div>

    <div class="nav-section">
      <h6>Overview</h6>
      <a class="<?= nav_active('/dashboard') ?>" href="/admin/dashboard"><span class="icon">▦</span> Dashboard</a>
    </div>

    <div class="nav-section">
      <h6>Commerce</h6>
      <a class="<?= nav_active('/admin/orders') ?>" href="/admin/orders">
        <span class="icon">⊞</span> Orders
        <?php if ($pendingOrders): ?><span class="chip" style="margin-left:auto"><?= $pendingOrders ?></span><?php endif; ?>
      </a>
      <a class="<?= nav_active('/admin/products') ?>" href="/admin/products"><span class="icon">◫</span> Products</a>
      <a class="<?= nav_active('/admin/customers') ?>" href="/admin/customers"><span class="icon">☺</span> Customers</a>
      <a class="<?= nav_active('/admin/reviews') ?>" href="/admin/reviews">
        <span class="icon">★</span> Reviews
        <?php if ($pendingReviews): ?><span class="chip" style="margin-left:auto"><?= $pendingReviews ?></span><?php endif; ?>
      </a>
    </div>

    <div class="nav-section">
      <h6>Content</h6>
      <a class="<?= nav_active('/admin/pages') ?>" href="/admin/pages"><span class="icon">◰</span> Pages</a>
      <a class="<?= nav_active('/admin/marketing') ?>" href="/admin/marketing"><span class="icon">⚡</span> Marketing</a>
    </div>

    <div class="nav-section">
      <h6>Operations</h6>
      <a class="<?= nav_active('/admin/geo') ?>" href="/admin/geo"><span class="icon">⌖</span> Shipping Zones</a>
      <a class="<?= nav_active('/admin/users') ?>" href="/admin/users"><span class="icon">☄</span> Team & RBAC</a>
      <a class="<?= nav_active('/admin/settings') ?>" href="/admin/settings"><span class="icon">⚙</span> Settings</a>
    </div>

    <div class="nav-section">
      <h6>Account</h6>
      <a href="/admin/logout"><span class="icon">⇲</span> Logout</a>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <h1><?= Helpers::e($page_title ?? 'Dashboard') ?></h1>
      </div>
      <div class="right">
        <button class="cf-btn" onclick="LHA.cfPurge()">⟳ Purge Cloudflare Cache</button>
        <span class="user">Hi, <?= Helpers::e($u['name'] ?? 'Admin') ?></span>
      </div>
    </div>
