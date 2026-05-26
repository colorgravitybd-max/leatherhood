<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

use App\Core\Helpers;

$csrf      = Helpers::csrfToken();
$logoFile  = APP_ROOT . '/assets/logo.png';
$hasLogo   = is_file($logoFile);
$logoUrl   = BASE_PATH . '/assets/logo.png?v=' . ($hasLogo ? filemtime($logoFile) : '1');

// Encourage downstream caching of the document itself (5 min).
header('Cache-Control: public, max-age=300');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#f8f7f2">
<meta name="color-scheme" content="light">
<meta name="description" content="Verify the authenticity of your ELHOE skincare product.">
<title>ELHOE — Verify Authenticity</title>

<?php if ($hasLogo): ?>
<link rel="icon" type="image/png" href="<?= Helpers::e($logoUrl) ?>">
<link rel="preload" as="image" href="<?= Helpers::e($logoUrl) ?>">
<?php endif; ?>

<?php if (TURNSTILE_SITE_KEY !== ''): ?>
<!-- Warm up DNS+TLS so lazy-loaded Turnstile script is fast when needed. -->
<link rel="dns-prefetch"  href="//challenges.cloudflare.com">
<link rel="preconnect"    href="https://challenges.cloudflare.com" crossorigin>
<?php endif; ?>

<style>
/* ============================================================
   ELHOE Verify - inline critical CSS (no external sheet,
   no Tailwind, no web fonts -> single round-trip render).
   ============================================================ */
*,*::before,*::after{box-sizing:border-box}
html,body{margin:0;padding:0}
:root{
    --bg:#f8f7f2;
    --paper:#ffffff;
    --ink:#1d1c19;
    --muted:#6e6a62;
    --hair:#e8e5dd;
    --sage:#8a9d7a;
    --sage-dp:#5d6e4f;
    --gold:#b89968;
    --emerald:#0f7a55;
    --crimson:#b62929;
    --amber:#a96a08;
    --radius:14px;
    --serif:Georgia,"Iowan Old Style","Apple Garamond",Cambria,"Times New Roman",serif;
    --sans:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,system-ui,sans-serif;
}
body{
    background:var(--bg);
    color:var(--ink);
    font:15px/1.55 var(--sans);
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

/* ---------- Layout ---------- */
.shell{
    width:100%;
    max-width:480px;
    margin:0 auto;
    padding:36px 20px 32px;
    flex:1;
    position:relative;
    z-index:1;
}
@media (min-width:520px){ .shell{ padding-top:60px; } }

/* ---------- Logo + heading ---------- */
.logo{
    display:block;
    margin:0 auto 22px;
    max-height:54px;
    max-width:180px;
    width:auto;
    height:auto;
}
.kicker-row{
    display:flex;align-items:center;justify-content:center;gap:10px;
    color:var(--sage-dp);font-size:10px;letter-spacing:.34em;text-transform:uppercase;
    margin-bottom:14px;
}
.kicker-row .line{height:1px;width:36px;background:linear-gradient(90deg,transparent,var(--sage),transparent)}
h1{
    font:500 clamp(26px,6vw,32px)/1.15 var(--serif);
    text-align:center;letter-spacing:-.005em;margin:0 0 12px;
}
h1 em{font-style:italic;color:var(--sage-dp);font-weight:500}
.lede{
    color:var(--muted);font-size:14px;line-height:1.6;
    text-align:center;max-width:380px;margin:0 auto 28px;
}

/* ---------- Card ---------- */
.card{
    background:var(--paper);
    border:1px solid var(--hair);
    border-radius:var(--radius);
    padding:22px 20px;
    box-shadow:0 1px 2px rgba(60,50,30,.04),0 8px 24px -16px rgba(60,50,30,.12);
}
@media (min-width:520px){ .card{ padding:28px 26px; } }

label{
    display:block;
    font-size:10px;letter-spacing:.24em;text-transform:uppercase;
    color:var(--sage-dp);
    margin-bottom:9px;
}
.row{display:flex;gap:8px;margin-bottom:18px}
.input,.scan{
    font:inherit;
    border:1px solid var(--hair);
    border-radius:10px;
    background:#fff;
    color:var(--ink);
    transition:border-color .15s,box-shadow .15s;
    -webkit-appearance:none;appearance:none;
}
.input{
    flex:1;
    padding:13px 14px;
    font-size:15px;
    letter-spacing:.04em;
    font-variant-numeric:tabular-nums;
}
.input::placeholder{color:#b8b3a7;letter-spacing:.06em}
.input:focus,.scan:focus{
    outline:0;border-color:var(--sage);
    box-shadow:0 0 0 3px rgba(138,157,122,.18);
}
.scan{
    padding:0 14px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;
    font-size:13px;color:var(--ink);
}
.scan:hover{background:#fafafa;border-color:#d6d3c8}
.scan svg{width:18px;height:18px;display:block}

/* Lazy-loaded Turnstile mounts here */
#ts-mount{margin-bottom:16px;min-height:0}
#ts-mount:not(:empty){min-height:65px}

.cta{
    display:block;width:100%;
    padding:14px 16px;
    font:500 12px/1 var(--sans);
    letter-spacing:.22em;text-transform:uppercase;
    color:#fff;background:var(--ink);
    border:1px solid var(--ink);border-radius:10px;cursor:pointer;
    transition:transform .12s,background .12s;
}
.cta:hover{background:#2d2a25;transform:translateY(-1px)}
.cta:active{transform:translateY(0)}
.cta:disabled{opacity:.55;cursor:not-allowed;transform:none}

.trust{
    margin:14px 0 0;text-align:center;
    font-size:11px;color:#8c8678;letter-spacing:.04em;
}

/* ---------- Verifying state (subtle bar, no laser noise) ---------- */
.card.is-verifying{position:relative;overflow:hidden}
.card.is-verifying::after{
    content:"";position:absolute;left:0;right:0;top:0;height:2px;
    background:linear-gradient(90deg,transparent,var(--sage),transparent);
    animation:shimmer 1.2s linear infinite;
}
@keyframes shimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}

/* ---------- Result panels ---------- */
.result{margin-top:18px}
.r-ok,.r-err,.r-rl,.r-warn{border-radius:12px;padding:18px}
.r-ok{background:#f0f7f3;border:1px solid #c4ddd0}
.r-err{background:#fbf1f1;border:1px solid #e9c5c5}
.r-rl{background:#fcf5e8;border:1px solid #e9d8b3}
.r-warn{background:#fcf5e8;border:1px solid #e9d8b3;margin-top:14px;font-size:13px;color:#7a4a08;display:flex;gap:10px}
.r-warn svg{flex:0 0 auto;width:18px;height:18px;color:var(--amber)}

.pill{
    display:inline-flex;align-items:center;gap:6px;
    font-size:11px;font-weight:600;letter-spacing:.18em;text-transform:uppercase;
}
.pill.ok{color:var(--emerald)}
.pill.err{color:var(--crimson)}
.pill svg{width:15px;height:15px}

.r-product{display:block;text-decoration:none;color:inherit;margin-top:14px}
.r-img{width:100%;aspect-ratio:16/10;object-fit:cover;border-radius:10px;border:1px solid var(--hair);background:#f3f2ec}
.r-title{
    font:500 22px/1.2 var(--serif);
    margin:12px 0 4px;letter-spacing:-.005em;
}
.r-desc{font-size:14px;line-height:1.6;color:var(--muted);margin:8px 0 0}

.r-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}
.r-meta .t{
    background:#fff;border:1px solid var(--hair);border-radius:10px;padding:9px 12px;
}
.r-meta .k{font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--sage-dp)}
.r-meta .v{font-size:13px;font-weight:500;margin-top:2px;font-variant-numeric:tabular-nums}

.r-howto-h{
    display:block;font-size:9px;letter-spacing:.24em;text-transform:uppercase;
    color:var(--sage-dp);margin:18px 0 6px;
}
.r-howto-l{margin:0;padding-left:18px;color:var(--muted);font-size:14px;line-height:1.7}

.r-cta{
    display:block;text-align:center;width:100%;
    margin-top:20px;padding:13px 14px;
    background:var(--ink);color:#fff;border-radius:10px;
    font:500 11px/1 var(--sans);letter-spacing:.22em;text-transform:uppercase;
    text-decoration:none;
    transition:transform .12s,background .12s;
}
.r-cta:hover{background:#2d2a25;transform:translateY(-1px)}

.r-retry{
    width:100%;margin-top:14px;padding:11px 14px;
    background:#fff;color:var(--ink);
    border:1px solid var(--hair);border-radius:10px;cursor:pointer;
    font:500 11px/1 var(--sans);letter-spacing:.18em;text-transform:uppercase;
}
.r-retry:hover{background:#fafafa}

/* ---------- Footer ---------- */
.footer{
    text-align:center;color:#8c8678;font-size:12px;line-height:1.7;
    margin-top:28px;padding:0 8px;
}
.footer a{color:var(--ink);text-decoration:underline;text-decoration-color:rgba(138,157,122,.55);text-underline-offset:3px}
.footer a:hover{color:var(--sage-dp)}
.footer .eco{
    display:inline-flex;align-items:center;gap:5px;
    color:var(--sage-dp);margin-top:10px;font-size:11px;letter-spacing:.04em;
}
.footer .eco svg{width:12px;height:12px}

/* ---------- Decorative botanical sprig (bottom-right, very subtle) ---------- */
.sprig{
    position:fixed;right:-30px;bottom:-30px;width:200px;height:200px;
    color:var(--sage);opacity:.18;pointer-events:none;z-index:0;
}
@media (max-width:520px){ .sprig{ width:140px;height:140px;right:-40px;bottom:-40px; } }

/* ---------- Scanner modal (only seen if user clicks Scan) ---------- */
.modal{
    position:fixed;inset:0;display:none;align-items:center;justify-content:center;
    background:rgba(28,24,21,.5);backdrop-filter:blur(4px);
    z-index:50;padding:14px;
}
.modal.open{display:flex}
.modal-card{
    width:100%;max-width:380px;
    background:#fff;border-radius:18px;padding:18px;
    box-shadow:0 20px 60px -16px rgba(0,0,0,.35);
}
.modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.modal-head h2{font:500 18px/1 var(--serif);margin:0}
.modal-close{
    width:32px;height:32px;border-radius:50%;border:0;background:#f3f2ec;color:var(--ink);
    font-size:20px;line-height:1;cursor:pointer;
}
.modal-close:hover{background:#e6e3d8}
.modal .hint{margin:10px 0 0;text-align:center;font-size:11px;color:var(--muted)}

#qr-reader{width:100%!important;aspect-ratio:1/1;border-radius:12px;overflow:hidden;background:#0c0a08}
#qr-reader video{width:100%!important;border-radius:12px}
#qr-reader__scan_region img,#qr-reader__header_message,
#qr-reader__dashboard_section_swaplink{display:none!important}

@media (prefers-reduced-motion:reduce){
    .card.is-verifying::after,.cta,.r-cta{animation:none!important;transition:none!important}
}
</style>
</head>
<body>

<!-- Decorative line-drawn sprig in the corner. Eco accent, ~600 bytes inline. -->
<svg class="sprig" viewBox="0 0 100 100" fill="none" stroke="currentColor"
     stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M5 95 C 25 80, 45 55, 65 30 S 92 8, 96 4"/>
    <path d="M22 78 Q14 76, 10 70 Q15 68, 22 78 Z" fill="currentColor" fill-opacity=".2"/>
    <path d="M34 64 Q28 60, 26 53 Q33 53, 34 64 Z" fill="currentColor" fill-opacity=".2"/>
    <path d="M48 48 Q42 44, 41 36 Q49 37, 48 48 Z" fill="currentColor" fill-opacity=".2"/>
    <path d="M61 32 Q57 26, 58 19 Q65 21, 61 32 Z" fill="currentColor" fill-opacity=".2"/>
    <path d="M76 18 Q73 13, 75 7 Q81 9, 76 18 Z" fill="currentColor" fill-opacity=".2"/>
    <path d="M28 80 Q34 84, 38 88"/>
    <path d="M42 64 Q48 67, 52 70"/>
    <path d="M55 48 Q60 50, 64 53"/>
</svg>

<main class="shell">

    <?php if ($hasLogo): ?>
        <img class="logo" src="<?= Helpers::e($logoUrl) ?>" alt="ELHOE" width="180" height="54" decoding="async" fetchpriority="high">
    <?php else: ?>
        <!-- SVG fallback wordmark -->
        <svg class="logo" viewBox="0 0 240 54" aria-label="ELHOE" role="img">
            <text x="120" y="40" text-anchor="middle"
                  font-family="Georgia,serif" font-size="38" font-weight="500"
                  letter-spacing="10" fill="#7a6e5e">ELHOE</text>
        </svg>
    <?php endif; ?>

    <div class="kicker-row">
        <span class="line"></span>
        <span>Authenticity</span>
        <span class="line"></span>
    </div>

    <h1>Verify your <em>ELHOE</em></h1>

    <p class="lede">
        Each authentic ELHOE product is sealed with a unique verification code.
        Enter yours below — or scan the QR — to confirm it.
    </p>

    <section id="card" class="card">
        <form id="form" autocomplete="off" novalidate>
            <input type="hidden" name="csrf" value="<?= Helpers::e($csrf) ?>">

            <label for="code">Verification Code</label>
            <div class="row">
                <input id="code" name="code" class="input" type="text"
                       inputmode="text" autocapitalize="characters" spellcheck="false"
                       required maxlength="100"
                       placeholder="ELH-XXXX-XXXX-XXXX">
                <button type="button" id="scan" class="scan" aria-label="Scan QR code">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 8V5a2 2 0 0 1 2-2h3"/>
                        <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
                        <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
                        <path d="M21 16v3a2 2 0 0 1-2 2h-3"/>
                        <rect x="8" y="8" width="8" height="8" rx="1.5"/>
                    </svg><span>Scan</span>
                </button>
            </div>

            <!-- Turnstile widget mounts here, lazy-loaded after first paint -->
            <div id="ts-mount" data-sitekey="<?= Helpers::e(TURNSTILE_SITE_KEY) ?>"></div>

            <button type="submit" id="verify" class="cta">Verify Authenticity</button>

            <p class="trust">Protected · No personal data stored</p>
        </form>

        <div id="result" class="result" aria-live="polite"></div>
    </section>

    <footer class="footer">
        <p>Can't find your code? Look beneath the scratch panel on the carton.</p>
        <p>Spotted a counterfeit? <a href="mailto:support@elhoe.com">support@elhoe.com</a></p>
        <span class="eco" title="Lightweight, energy-efficient page">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M11 20a8 8 0 1 1 8-8c0 4-3 7-7 7"/>
                <path d="M11 20c0-4 2-7 6-7"/>
            </svg>
            Lightweight by design
        </span>
    </footer>

</main>

<!-- Scanner modal (created here, made visible only when needed) -->
<div id="modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-card">
        <div class="modal-head">
            <h2 id="modal-title">Scan QR Code</h2>
            <button id="modal-close" class="modal-close" aria-label="Close">×</button>
        </div>
        <div id="qr-reader"></div>
        <p class="hint">Hold the QR steady inside the frame.</p>
    </div>
</div>

<script>
/* =================================================================
   ELHOE Verify - inline JS (~2 KB).
   - Form submit -> /checker/verify_action.php (JSON in / JSON out)
   - QR scanner library is lazy-loaded only on first Scan click.
   - Turnstile is lazy-loaded after first idle (or on input focus).
   ================================================================= */
(function(){
"use strict";

var BASE       = "<?= Helpers::e(BASE_PATH) ?>";
var TURNSTILE  = <?= TURNSTILE_SITE_KEY !== '' ? 'true' : 'false' ?>;

var form    = document.getElementById("form");
var card    = document.getElementById("card");
var codeIn  = document.getElementById("code");
var btn     = document.getElementById("verify");
var result  = document.getElementById("result");
var tsMount = document.getElementById("ts-mount");

// ---------- helpers ----------
function esc(s){ return String(s==null?"":s)
    .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
    .replace(/"/g,"&quot;").replace(/'/g,"&#39;"); }

function busy(on){
    btn.disabled = on;
    btn.textContent = on ? "Verifying\u2026" : "Verify Authenticity";
    card.classList.toggle("is-verifying", on);
}

// Auto-uppercase code input.
codeIn.addEventListener("input", function(){
    var s = codeIn.selectionStart;
    codeIn.value = codeIn.value.toUpperCase();
    codeIn.setSelectionRange(s, s);
});

// ---------- Turnstile lazy-load ----------
var tsLoaded = false;
function loadTurnstile(){
    if (tsLoaded || !TURNSTILE) return;
    tsLoaded = true;
    tsMount.className = "cf-turnstile";
    tsMount.setAttribute("data-theme","light");
    tsMount.setAttribute("data-size","flexible");
    var s = document.createElement("script");
    s.src = "https://challenges.cloudflare.com/turnstile/v0/api.js";
    s.async = true; s.defer = true;
    document.head.appendChild(s);
}
if (TURNSTILE){
    codeIn.addEventListener("focus", loadTurnstile, { once:true });
    if (window.requestIdleCallback){
        requestIdleCallback(loadTurnstile, { timeout: 1500 });
    } else {
        setTimeout(loadTurnstile, 800);
    }
}

// ---------- Result rendering ----------
function renderOk(d){
    var p = d.product || {};
    var cta = p.wordpress_url || "#";
    var reused = (d.code_type === "unique" && d.scan_count > 1) ?
        '<div class="r-warn">'+
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>'+
            '<p>This code is authentic but has been verified <strong>'+d.scan_count+
            '</strong> times before. If you just unsealed this scratch-off panel, please contact ELHOE support.</p>'+
        '</div>' : '';

    var howList = '';
    if (p.how_to_use){
        var steps = p.how_to_use.split(/\r?\n|(?<=\.)\s+(?=\d\))/).map(function(x){return x.trim();}).filter(Boolean);
        if (steps.length){
            howList = '<span class="r-howto-h">How to use</span><ol class="r-howto-l">'+
                steps.map(function(x){return '<li>'+esc(x)+'</li>';}).join('')+'</ol>';
        }
    }

    result.innerHTML =
      '<div class="r-ok">'+
        '<span class="pill ok"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>Authentic ELHOE Product</span>'+
        '<a class="r-product" href="'+esc(cta)+'" target="_blank" rel="noopener">'+
          (p.image_url ? '<img class="r-img" src="'+esc(p.image_url)+'" alt="" loading="lazy" decoding="async">' : '')+
          '<h3 class="r-title">'+esc(p.title||'')+'</h3>'+
        '</a>'+
        (p.description ? '<p class="r-desc">'+esc(p.description)+'</p>' : '')+
        '<div class="r-meta">'+
          (p.batch_number ? '<div class="t"><div class="k">Batch</div><div class="v">'+esc(p.batch_number)+'</div></div>' : '')+
          (p.expiry_date  ? '<div class="t"><div class="k">Expires</div><div class="v">'+esc(p.expiry_date)+'</div></div>' : '')+
        '</div>'+
        howList+
        reused+
        '<a class="r-cta" href="'+esc(cta)+'" target="_blank" rel="noopener">Buy Again \u2014 Restock Now</a>'+
      '</div>';
}

function renderErr(msg){
    result.innerHTML =
      '<div class="r-err">'+
        '<span class="pill err"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>Code Not Recognised</span>'+
        '<p class="r-desc">'+esc(msg||'This code is invalid or may indicate a counterfeit. Please double-check your entry. If the package looks genuine, contact ELHOE support.')+'</p>'+
        '<button type="button" id="retry" class="r-retry">Try again</button>'+
      '</div>';
    var r = document.getElementById("retry");
    if (r) r.addEventListener("click", function(){
        result.innerHTML = ""; codeIn.value = ""; codeIn.focus();
    });
}

function renderRl(){
    result.innerHTML =
      '<div class="r-rl">'+
        '<strong>Too many attempts.</strong>'+
        '<p class="r-desc">For security, please wait a few minutes before trying again.</p>'+
      '</div>';
}

// ---------- Submit ----------
form.addEventListener("submit", function(e){
    e.preventDefault();
    result.innerHTML = "";
    var code = codeIn.value.trim();
    if (!code){ codeIn.focus(); return; }

    var fd = new FormData(form);

    if (TURNSTILE){
        var tok = (fd.get("cf-turnstile-response") || "").toString();
        if (!tok){
            // Force Turnstile to render now if user submitted before it loaded.
            loadTurnstile();
            renderErr("Please complete the security check, then try again.");
            return;
        }
    }

    busy(true);
    fetch(BASE + "/verify_action.php", { method:"POST", headers:{Accept:"application/json"}, body:fd })
        .then(function(r){
            if (r.status === 429){ renderRl(); return null; }
            return r.json().catch(function(){ return {}; });
        })
        .then(function(d){
            if (!d) return;
            if (d.ok && d.product) renderOk(d);
            else renderErr(d.message);
        })
        .catch(function(){ renderErr(); })
        .finally(function(){
            busy(false);
            if (window.turnstile) window.turnstile.reset();
        });
});

// ---------- QR scanner (fully lazy) ----------
var modal     = document.getElementById("modal");
var btnScan   = document.getElementById("scan");
var btnClose  = document.getElementById("modal-close");
var scanner   = null;
var qrLoading = null;

function loadQrLib(){
    if (window.Html5Qrcode) return Promise.resolve();
    if (qrLoading) return qrLoading;
    qrLoading = new Promise(function(res, rej){
        var s = document.createElement("script");
        s.src = "https://unpkg.com/html5-qrcode@2.3.10/html5-qrcode.min.js";
        s.async = true;
        s.onload  = function(){ res(); };
        s.onerror = function(){ qrLoading = null; rej(new Error("script")); };
        document.head.appendChild(s);
    });
    return qrLoading;
}

function openScanner(){
    btnScan.disabled = true;
    var orig = btnScan.querySelector("span");
    if (orig) orig.textContent = "Loading\u2026";
    loadQrLib().then(function(){
        modal.classList.add("open");
        scanner = new Html5Qrcode("qr-reader", { verbose:false });
        return Html5Qrcode.getCameras().then(function(cams){
            var back = cams.find(function(c){ return /back|rear|environment/i.test(c.label); }) || cams[0];
            if (!back) throw new Error("no-camera");
            return scanner.start(back.id,
                { fps:10, qrbox:{ width:240, height:240 }, aspectRatio:1.0 },
                function(decoded){
                    codeIn.value = decoded;
                    closeScanner();
                    form.requestSubmit();
                },
                function(){}
            );
        });
    }).catch(function(){
        closeScanner();
        renderErr("Camera unavailable. Please type the code manually.");
    }).finally(function(){
        btnScan.disabled = false;
        if (orig) orig.textContent = "Scan";
    });
}

function closeScanner(){
    modal.classList.remove("open");
    if (scanner){
        scanner.stop().catch(function(){}).then(function(){
            try{ scanner.clear(); }catch(e){}
            scanner = null;
        });
    }
}

btnScan.addEventListener("click", openScanner);
btnClose.addEventListener("click", closeScanner);
modal.addEventListener("click", function(e){ if (e.target === modal) closeScanner(); });
document.addEventListener("keydown", function(e){ if (e.key === "Escape") closeScanner(); });

})();
</script>
</body>
</html>
