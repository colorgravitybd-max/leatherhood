/* =====================================================================
 * LeatherHood — Storefront (Vanilla JS, no dependencies)
 * ===================================================================== */
(() => {
'use strict';

const $  = (s, p = document) => p.querySelector(s);
const $$ = (s, p = document) => [...p.querySelectorAll(s)];

const csrf = () => (window.LH_CART && window.LH_CART.csrf) || '';

async function api(method, url, body) {
  const opts = { method, headers: {} };
  if (body) {
    if (body instanceof FormData) {
      opts.body = body;
    } else {
      opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      opts.body = new URLSearchParams(body).toString();
    }
  }
  const res = await fetch(url, opts);
  let data;
  try { data = await res.json(); } catch { data = { ok: false, msg: 'Bad response' }; }
  return data;
}

const fmt = n => '৳' + Math.round(Number(n) || 0).toLocaleString('en-IN');

const LH = {
  /* ---------- Add to cart ---------- */
  async quickAdd(productId) {
    const data = await api('POST', '/cart/add', {
      product_id: productId, qty: 1, _csrf: csrf(),
    });
    if (data.ok) this.bumpCart(data.count), this.toast('Added to cart');
    else this.toast(data.msg || 'Failed', true);
  },

  async addToCartForm(e) {
    e.preventDefault();
    const f = e.target;
    const fd = new FormData(f);
    fd.append('_csrf', csrf());
    const data = await api('POST', '/cart/add', fd);
    if (data.ok) { this.bumpCart(data.count); this.toast('Added to cart'); }
    else this.toast(data.msg || 'Failed', true);
    return false;
  },

  qty(d) {
    const i = $('#qty');
    if (!i) return;
    i.value = Math.max(1, (parseInt(i.value, 10) || 1) + d);
  },

  async cartUpdate(key, qty) {
    const data = await api('POST', '/cart/update', { key, qty, _csrf: csrf() });
    if (data.ok) location.reload();
  },

  async cartRemove(key) {
    if (!confirm('Remove this item?')) return;
    const data = await api('POST', '/cart/remove', { key, _csrf: csrf() });
    if (data.ok) location.reload();
  },

  bumpCart(n) {
    let c = $('#cart-count');
    if (!c && n > 0) {
      const wrap = $('.cart-wrap');
      if (wrap) {
        c = document.createElement('span');
        c.id = 'cart-count'; c.className = 'cart-count';
        wrap.appendChild(c);
      }
    }
    if (c) c.textContent = n;
  },

  toast(msg, err = false) {
    let t = $('#lh-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'lh-toast';
      t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
        'padding:12px 22px;border-radius:2px;color:#fff;font-size:14px;z-index:999;' +
        'box-shadow:0 8px 24px rgba(0,0,0,.2);transition:.3s;opacity:0';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.background = err ? '#b54141' : '#1a1a1a';
    requestAnimationFrame(() => { t.style.opacity = '1'; });
    clearTimeout(this._toastTo);
    this._toastTo = setTimeout(() => { t.style.opacity = '0'; }, 2400);
  },

  /* ---------- Cascading geo dropdowns + live shipping ---------- */
  async initCheckout() {
    const div  = $('#division'),
          dis  = $('#district'),
          ps   = $('#police-station');
    if (!div) return;

    div.addEventListener('change', async () => {
      dis.disabled = true; ps.disabled = true;
      dis.innerHTML = '<option>Loading…</option>';
      ps.innerHTML  = '<option>Select division first</option>';
      if (!div.value) { dis.innerHTML = '<option value="">Select district</option>'; return; }
      const r = await fetch('/api/geo/districts/' + div.value).then(r => r.json());
      dis.innerHTML = '<option value="">Select district</option>' +
        r.districts.map(d =>
          `<option value="${d.id}" ${d.is_deliverable ? '' : 'disabled'}>` +
          `${d.name}${d.is_deliverable ? '' : ' (Unavailable)'}</option>`
        ).join('');
      dis.disabled = false;
      this.recalc();
    });

    dis.addEventListener('change', async () => {
      ps.disabled = true;
      ps.innerHTML = '<option>Loading…</option>';
      if (!dis.value) { ps.innerHTML = '<option value="">Select police station</option>'; return; }
      const r = await fetch('/api/geo/police-stations/' + dis.value).then(r => r.json());
      ps.innerHTML = '<option value="">Select police station</option>' +
        r.police_stations.map(p =>
          `<option value="${p.id}" ${p.is_deliverable ? '' : 'disabled'}>` +
          `${p.name}${p.is_deliverable ? '' : ' (Unavailable)'}</option>`
        ).join('');
      ps.disabled = false;
      this.recalc();
    });
    ps.addEventListener('change', () => this.recalc());
  },

  async recalc() {
    if (!$('#checkout-form')) return;
    const fd = new FormData($('#checkout-form'));
    const r = await api('POST', '/api/checkout/quote', {
      district_id: fd.get('district_id') || 0,
      police_station_id: fd.get('police_station_id') || 0,
      coupon_code: fd.get('coupon_code') || '',
    });
    $('#sub').textContent  = r.subtotal_fmt;
    $('#disc').textContent = '-' + r.discount_fmt;
    $('#ship').textContent = r.shipping_fmt;
    $('#ship').classList.remove('muted');
    $('#grand').textContent = r.grand_fmt;
    const btn = $('#grand-display'); if (btn) btn.textContent = r.grand_fmt;
    if (!r.deliverable && r.message) this.formError(r.message);
    else this.formError('');
  },

  formError(msg) {
    const e = $('#form-error'); if (!e) return;
    e.innerHTML = msg ? `<div class="alert alert-error">${msg}</div>` : '';
  },

  /* ---------- Place order + OTP ---------- */
  async placeOrder(e) {
    e.preventDefault();
    const btn = $('#place-btn');
    btn.disabled = true; btn.textContent = 'Placing order…';
    this.formError('');

    const fd = new FormData($('#checkout-form'));
    const data = await api('POST', '/api/checkout/place', fd);
    btn.disabled = false;
    if (!data.ok) {
      this.formError(data.msg || 'Could not place order.');
      btn.textContent = 'Place Order — ' + ($('#grand').textContent || '');
      return false;
    }

    if (data.requires_otp) {
      $('#form-card').hidden = true;
      $('#otp-card').hidden  = false;
      $('#otp-phone').textContent = (fd.get('customer_phone') || '');
      this.bindOtpInputs();
    } else {
      window.location.href = '/order/thank-you/' + data.order_number;
    }
    return false;
  },

  bindOtpInputs() {
    const inputs = $$('#otp-card .otp-input input');
    inputs.forEach((inp, idx) => {
      inp.addEventListener('input', () => {
        inp.value = inp.value.replace(/\D/g, '').slice(0,1);
        if (inp.value && idx < inputs.length - 1) inputs[idx+1].focus();
        if ([...inputs].every(i => i.value)) this.verifyOtp();
      });
      inp.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !inp.value && idx > 0) inputs[idx-1].focus();
      });
    });
    inputs[0].focus();
  },

  async verifyOtp() {
    const code = $$('#otp-card .otp-input input').map(i => i.value).join('');
    if (code.length < 4) return;
    const data = await api('POST', '/api/checkout/verify', { otp: code, _csrf: csrf() });
    if (data.ok && data.redirect) window.location.href = data.redirect;
    else $('#otp-error').innerHTML = `<div class="alert alert-error">${data.msg || 'Invalid OTP'}</div>`;
  },

  async resendOtp(e) {
    e?.preventDefault();
    const data = await api('POST', '/api/checkout/resend-otp', {});
    this.toast(data.ok ? 'OTP resent' : 'Could not resend');
  },
};

window.LH = LH;
document.addEventListener('DOMContentLoaded', () => LH.initCheckout());
})();
