<?php
use LH\Core\Helpers;
use LH\Core\Database;

$slug = $GLOBALS['slug'] ?? '';
$page = Database::i()->one("SELECT * FROM pages WHERE slug = ? AND status='published' LIMIT 1", [$slug]);
if (!$page) { http_response_code(404); require __DIR__.'/404.php'; return; }

$page_title = $page['title'];
$page_desc  = $page['meta_description'] ?: $page['excerpt'];
include __DIR__ . '/partials/header.php';
?>

<section class="container" style="padding:60px 0;max-width:780px">
  <div class="section-head">
    <div class="eyebrow">LeatherHood</div>
    <h2><?= Helpers::e($page['title']) ?></h2>
  </div>

  <article style="font-size:15px;line-height:1.85"><?= $page['content'] ?></article>

  <?php if ($page['slug'] === 'contact'): ?>
    <hr class="hr">
    <form onsubmit="alert('Thanks for your message! We will reach out shortly.');event.preventDefault();this.reset()" style="display:grid;gap:14px;max-width:560px;margin:0 auto">
      <div class="field"><label>Name</label><input required></div>
      <div class="field"><label>Email</label><input type="email" required></div>
      <div class="field"><label>Phone</label><input type="tel"></div>
      <div class="field"><label>Message</label><textarea rows="4" required></textarea></div>
      <button class="btn btn-gold" type="submit">Send Message</button>
    </form>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
