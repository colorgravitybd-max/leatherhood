/* ELHOE Verify - frontend controller (vanilla JS, no deps).
   - i18n via the inline <script id="i18n-data"> JSON
   - Camera scanning via html5-qrcode (loaded in <head>)
   - Verification via POST /verify_action.php  (JSON in / JSON out) */

(() => {
  const I18N = JSON.parse(document.getElementById('i18n-data').textContent);
  let lang = (localStorage.getItem('elhoe.lang') || 'en');
  if (!I18N[lang]) lang = 'en';

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  // ---------------- i18n ----------------
  function applyLang(next) {
    lang = next;
    localStorage.setItem('elhoe.lang', lang);
    document.documentElement.setAttribute('lang', lang);
    document.body.setAttribute('lang', lang);
    document.body.classList.toggle('font-bn', lang === 'bn');

    const dict = I18N[lang];
    $$('[data-i18n]').forEach(el => {
      const k = el.dataset.i18n;
      if (dict[k]) el.textContent = dict[k];
    });
    $$('[data-i18n-attr]').forEach(el => {
      el.dataset.i18nAttr.split(';').forEach(rule => {
        const [attr, key] = rule.split('=').map(s => s.trim());
        if (attr && key && dict[key]) el.setAttribute(attr, dict[key]);
      });
    });

    $$('.lang-btn').forEach(btn => {
      const active = btn.dataset.lang === lang;
      btn.setAttribute('aria-pressed', String(active));
      btn.classList.toggle('bg-elhoe-ink', active);
      btn.classList.toggle('text-elhoe-cream', active);
      btn.classList.toggle('text-elhoe-ink/60', !active);
    });
  }
  $$('.lang-btn').forEach(btn => btn.addEventListener('click', () => applyLang(btn.dataset.lang)));
  applyLang(lang);

  // ---------------- Verify form ----------------
  const form     = $('#verify-form');
  const codeInp  = $('#code');
  const btnVerify= $('#btn-verify');
  const card     = $('#verify-card');
  const result   = $('#result');

  function t(k) { return I18N[lang][k] || I18N.en[k] || k; }

  function setBusy(on) {
    btnVerify.disabled = on;
    btnVerify.textContent = on ? t('verifying') : t('verify_btn');
    card.classList.toggle('elhoe-scanning', on);
  }

  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'",'&#39;');
  }

  function renderSuccess(p) {
    // p = { product:{title,description,how_to_use,image_url,wordpress_url,expiry_date,batch_number}, scan_count, code_type, reused }
    const pr = p.product || {};
    const titleField = lang === 'bn' && pr.title_bn ? pr.title_bn : pr.title;
    const descField  = lang === 'bn' && pr.description_bn ? pr.description_bn : pr.description;
    const howField   = lang === 'bn' && pr.how_to_use_bn  ? pr.how_to_use_bn  : pr.how_to_use;

    const cta = pr.wordpress_url || '#';
    const reusedBlock = (p.code_type === 'unique' && p.scan_count > 1) ? `
      <div class="elhoe-result-warning rounded-2xl p-4 mt-4 text-sm text-amber-900">
        <div class="flex items-start gap-2">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" class="mt-0.5 shrink-0">
            <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          </svg>
          <p>${escapeHtml(t('reused_pre'))} <strong>${p.scan_count}</strong> ${escapeHtml(t('reused_mid'))}</p>
        </div>
      </div>` : '';

    const howList = howField
      ? howField.split(/\r?\n|(?<=\.)\s+(?=\d\))/).filter(Boolean).map(s => `<li>${escapeHtml(s.trim())}</li>`).join('')
      : '';

    result.innerHTML = `
      <div class="elhoe-result-success rounded-2xl p-5">
        <div class="flex items-center gap-2 text-emerald-700 text-sm font-medium tracking-wide">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M5 12l5 5L20 7"/>
          </svg>
          ${escapeHtml(t('genuine'))}
        </div>
        <a href="${escapeHtml(cta)}" target="_blank" rel="noopener"
           class="block mt-3 group">
          ${pr.image_url ? `<img src="${escapeHtml(pr.image_url)}" alt="" loading="lazy"
                class="w-full h-40 object-cover rounded-xl group-hover:opacity-95 transition">` : ''}
          <h3 class="font-serif text-xl mt-3 text-elhoe-ink group-hover:text-elhoe-gold transition">
            ${escapeHtml(titleField || '')}
          </h3>
        </a>
        ${descField ? `<p class="text-sm text-elhoe-ink/75 mt-2 leading-relaxed">${escapeHtml(descField)}</p>` : ''}

        <div class="grid grid-cols-2 gap-2 mt-4 text-[12px]">
          ${pr.batch_number ? `<div class="bg-white/60 rounded-lg px-3 py-2">
              <div class="text-elhoe-ink/50">${escapeHtml(t('batch'))}</div>
              <div class="font-medium">${escapeHtml(pr.batch_number)}</div></div>` : ''}
          ${pr.expiry_date ? `<div class="bg-white/60 rounded-lg px-3 py-2">
              <div class="text-elhoe-ink/50">${escapeHtml(t('expires'))}</div>
              <div class="font-medium">${escapeHtml(pr.expiry_date)}</div></div>` : ''}
        </div>

        ${howList ? `
          <div class="mt-4">
            <div class="text-xs uppercase tracking-[0.2em] text-elhoe-gold mb-1">${escapeHtml(t('how_to_use'))}</div>
            <ol class="list-decimal list-inside text-sm text-elhoe-ink/80 space-y-1">${howList}</ol>
          </div>` : ''}

        ${reusedBlock}

        <a href="${escapeHtml(cta)}" target="_blank" rel="noopener"
           class="elhoe-btn block text-center w-full rounded-xl py-3 font-medium tracking-wide mt-5">
          ${escapeHtml(t('cta'))}
        </a>
      </div>`;
  }

  function renderFailure(message) {
    result.innerHTML = `
      <div class="elhoe-result-error rounded-2xl p-5">
        <div class="flex items-center gap-2 text-rose-700 text-sm font-semibold tracking-wide">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M6 6l12 12M18 6L6 18"/>
          </svg>
          ${escapeHtml(t('fail_title'))}
        </div>
        <p class="text-sm text-elhoe-ink/80 mt-2">${escapeHtml(message || t('fail_body'))}</p>
        <button type="button" id="btn-retry"
                class="elhoe-btn-ghost w-full rounded-xl py-2.5 text-sm font-medium mt-4">
          ${escapeHtml(t('fail_cta'))}
        </button>
      </div>`;
    $('#btn-retry')?.addEventListener('click', () => {
      result.innerHTML = '';
      codeInp.value = '';
      codeInp.focus();
    });
  }

  function renderRateLimit() {
    result.innerHTML = `
      <div class="elhoe-result-warning rounded-2xl p-5">
        <h3 class="font-medium">${escapeHtml(t('rate_title'))}</h3>
        <p class="text-sm text-elhoe-ink/80 mt-1">${escapeHtml(t('rate_body'))}</p>
      </div>`;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    result.innerHTML = '';
    const code = codeInp.value.trim();
    if (!code) { codeInp.focus(); return; }

    const fd = new FormData(form);
    let turnstileToken = '';
    if (window.turnstile) {
      turnstileToken = (fd.get('cf-turnstile-response') || '').toString();
      if (!turnstileToken) { renderFailure(t('turnstile_required')); return; }
    }

    setBusy(true);
    try {
      const res = await fetch('/verify_action.php', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd,
      });

      if (res.status === 429) { renderRateLimit(); return; }

      const data = await res.json().catch(() => ({}));
      if (data.ok && data.product) {
        renderSuccess(data);
      } else {
        renderFailure(data.message);
      }
    } catch (err) {
      renderFailure();
    } finally {
      setBusy(false);
      if (window.turnstile) window.turnstile.reset();
    }
  });

  // ---------------- QR scanner ----------------
  const modal      = $('#scanner-modal');
  const btnScan    = $('#btn-scan');
  const btnClose   = $('#btn-scan-close');
  let scanner = null;

  function openScanner() {
    if (typeof Html5Qrcode === 'undefined') {
      // Library not loaded yet - retry shortly
      setTimeout(openScanner, 200);
      return;
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    scanner = new Html5Qrcode('qr-reader', { verbose: false });
    Html5Qrcode.getCameras().then(cams => {
      const back = cams.find(c => /back|rear|environment/i.test(c.label)) || cams[0];
      if (!back) throw new Error('no-camera');
      return scanner.start(
        back.id,
        { fps: 10, qrbox: { width: 240, height: 240 }, aspectRatio: 1.0 },
        (decoded) => {
          codeInp.value = decoded;
          closeScanner();
          form.requestSubmit();
        },
        () => { /* per-frame errors are normal, ignore */ }
      );
    }).catch(() => {
      closeScanner();
      renderFailure('Camera unavailable. Please type the code manually.');
    });
  }

  function closeScanner() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    if (scanner) {
      scanner.stop().catch(() => {}).finally(() => { scanner.clear(); scanner = null; });
    }
  }

  btnScan.addEventListener('click', openScanner);
  btnClose.addEventListener('click', closeScanner);
  modal.addEventListener('click', (e) => { if (e.target === modal) closeScanner(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeScanner(); });

  // Auto-uppercase nicety
  codeInp.addEventListener('input', () => {
    const start = codeInp.selectionStart;
    codeInp.value = codeInp.value.toUpperCase();
    codeInp.setSelectionRange(start, start);
  });
})();
