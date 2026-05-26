/* LeatherHood Admin JS */
(() => {
const $  = (s,p=document)=>p.querySelector(s);
const $$ = (s,p=document)=>[...p.querySelectorAll(s)];

async function api(method, url, body){
  const opts = { method, headers: {} };
  if (body){
    if (body instanceof FormData) opts.body = body;
    else { opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
           opts.body = new URLSearchParams(body).toString(); }
  }
  const r = await fetch(url, opts);
  try { return await r.json(); }
  catch { return { ok:false, msg:'Bad response' }; }
}

const toast = (m, ok=true) => {
  const t = $('#toast'); if (!t) return alert(m);
  t.textContent = m;
  t.className = 'toast show ' + (ok ? 'ok' : 'err');
  clearTimeout(toast._); toast._ = setTimeout(() => t.classList.remove('show'), 2400);
};

const LHA = {
  csrf: () => window.LHA_CSRF || '',

  async cfPurge(){
    if (!confirm('Purge ALL Cloudflare cache for the site?')) return;
    const r = await api('POST', '/admin/cloudflare', { all: 1, _csrf: this.csrf() });
    toast(r.ok ? 'Cloudflare cache purged.' : (r.msg || 'Failed'), r.ok);
  },

  async pushCourier(orderId, courier){
    if (!confirm(`Push order #${orderId} to ${courier.toUpperCase()}?`)) return;
    const r = await api('POST', '/admin/courier',
      { order_id: orderId, courier, _csrf: this.csrf() });
    toast(r.ok ? 'Pushed to '+courier : (r.msg || 'Failed'), r.ok);
    if (r.ok) setTimeout(()=>location.reload(), 800);
  },

  async manualVerify(orderId){
    if (!confirm(`Manually verify order #${orderId}?`)) return;
    const r = await api('POST', '/admin/order/verify',
      { order_id: orderId, _csrf: this.csrf() });
    toast(r.ok ? 'Verified.' : (r.msg||'Failed'), r.ok);
    if (r.ok) setTimeout(()=>location.reload(), 600);
  },

  async cancelOrder(orderId){
    const note = prompt('Cancellation note (optional):') ?? '';
    if (note === null) return;
    const r = await api('POST', '/admin/order/cancel',
      { order_id: orderId, note, _csrf: this.csrf() });
    toast(r.ok ? 'Cancelled & restocked.' : (r.msg||'Failed'), r.ok);
    if (r.ok) setTimeout(()=>location.reload(), 600);
  },

  async setStatus(orderId, status){
    const r = await api('POST', '/admin/order/status',
      { order_id: orderId, status, _csrf: this.csrf() });
    toast(r.ok ? 'Status updated.' : (r.msg||'Failed'), r.ok);
    if (r.ok) setTimeout(()=>location.reload(), 600);
  },

  async geoUpdate(table, field, id, value){
    const r = await api('POST', '/admin/geo',
      { table, field, id, value, _csrf: this.csrf() });
    toast(r.ok ? 'Updated.' : (r.msg||'Failed'), r.ok);
  },

  async uploadImage(input, target){
    const fd = new FormData();
    fd.append('file', input.files[0]);
    fd.append('_csrf', this.csrf());
    const r = await api('POST', '/admin/media/upload', fd);
    if (!r.ok) return toast(r.msg || 'Upload failed', false);
    if (target) document.querySelector(target).value = r.path;
    toast('Saved ' + r.saved_human + ' (' + r.ratio + '%)', true);
  },

  bulkInvoices(){
    const ids = $$('input.row-check:checked').map(i => i.value);
    if (!ids.length) return toast('Select at least one order', false);
    window.open('/admin/invoices/bulk?ids=' + ids.join(','), '_blank');
  },
};

window.LHA = LHA;

// Toggle row checkbox helpers
document.addEventListener('DOMContentLoaded', () => {
  const all = $('#check-all');
  if (all) all.addEventListener('change', e => $$('.row-check').forEach(c => c.checked = e.target.checked));
});
})();
