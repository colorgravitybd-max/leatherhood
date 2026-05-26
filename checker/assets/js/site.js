/* =====================================================================
   ELHOE Verify - frontend controller (vanilla JS, no deps).
   - English only (i18n removed per business requirement).
   - Camera scanning via html5-qrcode (loaded in <head>).
   - Verification via POST /checker/verify_action.php (JSON in / JSON out).
   ===================================================================== */

(() => {
  const BASE = (document.querySelector('meta[name="base-path"]')?.content || '').replace(/\/+$/, '');

  const $  = (sel, root = document) => root.querySelector(sel);

  const form     = $('#verify-form');
  const codeInp  = $('#code');
  const btnVerify= $('#btn-verify');
  const card     = $('#verify-card');
  const result   = $('#result');

  // ---------- helpers ----------
  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'",'&#39;');
  }

  function setBusy(on) {
    btnVerify.disabled = on;
    btnVerify.querySelector('.cta-text').textContent = on ? 'Verifying…' : 'Verify Authenticity';
    card.classList.toggle('is-scanning', on);
  }

  // ---------- result renderers ----------
  function renderSuccess(p) {
    const pr = p.product || {};
    const cta = pr.wordpress_url || '#';

    const reusedBlock = (p.code_type === 'unique' && p.scan_count > 1) ? `
      <div class="result-warning">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        </svg>
        <p>This code is authentic but has been verified
           <strong>${p.scan_count}</strong> times before. If you just unsealed
           this scratch-off panel, please contact ELHOE support immediately.</p>
      </div>` : '';

    const howList = pr.how_to_use
      ? pr.how_to_use
          .split(/\r?\n|(?<=\.)\s+(?=\d\))/)
          .map(s => s.trim())
          .filter(Boolean)
          .map(s => `<li>${escapeHtml(s)}</li>`).join('')
      : '';

    result.innerHTML = `
      <div class="result-success">
        <span class="result-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12l5 5L20 7"/>
          </svg>
          Authentic ELHOE Product
        </span>

        <a href="${escapeHtml(cta)}" target="_blank" rel="noopener" class="result-product-link">
          ${pr.image_url ? `<img src="${escapeHtml(pr.image_url)}" alt="" loading="lazy" class="result-product-img">` : ''}
          <h3 class="result-product-title">${escapeHtml(pr.title || '')}</h3>
        </a>

        ${pr.description ? `<p class="result-desc">${escapeHtml(pr.description)}</p>` : ''}

        <div class="result-meta-grid">
          ${pr.batch_number ? `
            <div class="result-meta-tile">
              <div class="k">Batch</div>
              <div class="v">${escapeHtml(pr.batch_number)}</div>
            </div>` : ''}
          ${pr.expiry_date ? `
            <div class="result-meta-tile">
              <div class="k">Expires</div>
              <div class="v">${escapeHtml(pr.expiry_date)}</div>
            </div>` : ''}
        </div>

        ${howList ? `
          <span class="result-howto-label">How to use</span>
          <ol class="result-howto-list">${howList}</ol>` : ''}

        ${reusedBlock}

        <a href="${escapeHtml(cta)}" target="_blank" rel="noopener" class="result-cta">
          Buy Again — Restock Now
        </a>
      </div>`;
  }

  function renderFailure(message) {
    const body = message || 'This code is invalid or may indicate a counterfeit. '
                          + 'Please double-check your entry. If the package looks '
                          + 'genuine, contact ELHOE support.';
    result.innerHTML = `
      <div class="result-error">
        <span class="result-error-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 6l12 12M18 6L6 18"/>
          </svg>
          Code Not Recognised
        </span>
        <p class="result-error-body">${escapeHtml(body)}</p>
        <button type="button" id="btn-retry" class="result-error-retry">Try again</button>
      </div>`;
    $('#btn-retry')?.addEventListener('click', () => {
      result.innerHTML = '';
      codeInp.value = '';
      codeInp.focus();
    });
  }

  function renderRateLimit() {
    result.innerHTML = `
      <div class="result-ratelimit">
        <h3>Too many attempts</h3>
        <p>For security, please wait a few minutes before trying again.</p>
      </div>`;
  }

  // ---------- form submit ----------
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    result.innerHTML = '';
    const code = codeInp.value.trim();
    if (!code) { codeInp.focus(); return; }

    const fd = new FormData(form);

    // Turnstile is required when the widget is loaded.
    if (window.turnstile) {
      const tok = (fd.get('cf-turnstile-response') || '').toString();
      if (!tok) { renderFailure('Please complete the security check.'); return; }
    }

    setBusy(true);
    try {
      const res = await fetch(`${BASE}/verify_action.php`, {
        method:  'POST',
        headers: { 'Accept': 'application/json' },
        body:    fd,
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

  // Auto-uppercase the code field as the user types.
  codeInp.addEventListener('input', () => {
    const start = codeInp.selectionStart;
    codeInp.value = codeInp.value.toUpperCase();
    codeInp.setSelectionRange(start, start);
  });

  // ---------- QR scanner ----------
  const modal    = $('#scanner-modal');
  const btnScan  = $('#btn-scan');
  const btnClose = $('#btn-scan-close');
  let scanner = null;

  function openScanner() {
    if (typeof Html5Qrcode === 'undefined') {
      // Library still loading — try again shortly.
      setTimeout(openScanner, 200);
      return;
    }
    modal.classList.add('is-open');
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
        () => { /* per-frame scan errors are normal */ }
      );
    }).catch(() => {
      closeScanner();
      renderFailure('Camera unavailable. Please type the code manually.');
    });
  }

  function closeScanner() {
    modal.classList.remove('is-open');
    if (scanner) {
      scanner.stop().catch(() => {}).finally(() => { scanner.clear(); scanner = null; });
    }
  }

  btnScan.addEventListener('click',  openScanner);
  btnClose.addEventListener('click', closeScanner);
  modal.addEventListener('click', (e) => { if (e.target === modal) closeScanner(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeScanner(); });
})();
