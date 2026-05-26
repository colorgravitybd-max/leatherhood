/* ELHOE admin - tiny vanilla helpers (no framework). */
(() => {
  // Confirm-on-click for any element with [data-confirm]
  document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-confirm]');
    if (!t) return;
    if (!confirm(t.dataset.confirm)) {
      e.preventDefault();
      e.stopPropagation();
    }
  });

  // Auto-dismiss flash messages after 4s
  document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; }, 4000);
    setTimeout(() => el.remove(), 4500);
  });
})();
