<?php
use LH\Core\Helpers;
use LH\Core\Database;
use LH\Services\CartService;

$cartCount = CartService::count();
$brand     = Helpers::setting('site_name', 'LeatherHood');
$page_title = $page_title ?? $brand;
$page_desc  = $page_desc  ?? Helpers::setting('site_tagline', 'Premium Leather, Crafted in Bangladesh.');

// Marketing scripts (head)
$head_scripts = Database::i()->all(
    "SELECT * FROM marketing_scripts WHERE is_active = 1 AND placement = 'head' ORDER BY sort_order");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= Helpers::e($page_title) ?> — <?= Helpers::e($brand) ?></title>
  <meta name="description" content="<?= Helpers::e($page_desc) ?>">
  <meta property="og:title"  content="<?= Helpers::e($page_title) ?>">
  <meta property="og:type"   content="website">
  <meta property="og:site_name" content="<?= Helpers::e($brand) ?>">
  <link rel="canonical" href="<?= Helpers::e(Helpers::url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
  <link rel="icon" href="/assets/images/favicon.png" type="image/png">
  <link rel="stylesheet" href="/assets/css/site.css">
  <?php foreach ($head_scripts as $s) { echo $s['code']; } ?>
</head>
<body>
<div class="topbar">
  Free Shipping over <strong>৳<?= Helpers::e(Helpers::setting('free_shipping_threshold', '5000')) ?></strong> · 1 Year Warranty · COD Available
</div>
<header class="nav">
  <div class="container nav-inner">
    <button class="menu-toggle" aria-label="Menu" onclick="document.body.classList.toggle('mobile-open')">☰</button>
    <a class="brand" href="/"><?= Helpers::e($brand) ?><small>Crafted in BD</small></a>
    <ul>
      <li><a href="/">Home</a></li>
      <li><a href="/shop">Shop</a></li>
      <li><a href="/shop?cat=belts">Belts</a></li>
      <li><a href="/shop?cat=wallets">Wallets</a></li>
      <li><a href="/shop?cat=shoes">Shoes</a></li>
      <li><a href="/page/contact">Contact</a></li>
    </ul>
    <div class="nav-icons">
      <button class="icon-btn" aria-label="Search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
      </button>
      <a class="icon-btn cart-wrap" href="/cart" aria-label="Cart">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.5 12h11l2-8H6"/></svg>
        <?php if ($cartCount > 0): ?><span class="cart-count" id="cart-count"><?= $cartCount ?></span><?php endif; ?>
      </a>
    </div>
  </div>
</header>
