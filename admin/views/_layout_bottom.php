<?php use LH\Core\Helpers; ?>
  </main>
</div>
<div class="toast" id="toast"></div>
<script>
window.LHA_CSRF = <?= json_encode(Helpers::csrfToken()) ?>;
</script>
<script src="/admin/assets/admin.js" defer></script>
</body></html>
