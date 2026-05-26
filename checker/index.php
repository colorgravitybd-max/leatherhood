<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

use App\Core\Helpers;

$csrf      = Helpers::csrfToken();
$logoFile  = APP_ROOT . '/assets/logo.png';
$hasLogo   = is_file($logoFile);
$logoUrl   = BASE_PATH . '/assets/logo.png?v=' . ($hasLogo ? filemtime($logoFile) : '1');

// Distributor / WhatsApp constants - if you ever need to change them,
// just edit these four lines.
$DIST_ADDRESS = 'ELHOE Inc. Uttara, Dhaka, 1230, Dhaka - North, Dhaka, Bangladesh';
$DIST_PHONE   = '+8801641096067';            // for tel: link (no spaces)
$DIST_WA      = '8801641096067';             // for wa.me URL (no plus, no spaces)
$DIST_PHONE_DISPLAY = '+880 1641-096067';    // pretty version shown on screen

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
<title>ELHOE - Verify Authenticity</title>

<?php if ($hasLogo): ?>
<link rel="icon" type="image/png" href="<?= Helpers::e($logoUrl) ?>">
<link rel="preload" as="image" href="<?= Helpers::e($logoUrl) ?>">
<?php endif; ?>

<?php if (TURNSTILE_SITE_KEY !== ''): ?>
<link rel="dns-prefetch"  href="//challenges.cloudflare.com">
<link rel="preconnect"    href="https://challenges.cloudflare.com" crossorigin>
<?php endif; ?>

<style>
/* ============================================================
   ELHOE Verify - inline critical CSS.
   No Tailwind, no external sheet, no web fonts -> 1-RTT render.
   ============================================================ */
*,*::before,*::after{box-sizing:border-box}
html,body{margin:0;padding:0}
:root{
    --bg:#f8f7f2; --paper:#ffffff; --ink:#1d1c19;
    --muted:#6e6a62; --hair:#e8e5dd;
    --sage:#8a9d7a; --sage-dp:#5d6e4f;
    --emerald:#0f7a55; --crimson:#b62929; --amber:#a96a08;
    --wa:#25d366; --wa-dp:#1ebb5b;
    --radius:14px;
    --serif:Georgia,"Iowan Old Style","Apple Garamond",Cambria,"Times New Roman",serif;
    --sans:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,system-ui,sans-serif;
}
body{
    background:var(--bg); color:var(--ink);
    font:15px/1.55 var(--sans);
    -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    min-height:100vh; display:flex; flex-direction:column;
}

/* ---------- Layout ---------- */
.shell{
    width:100%; max-width:480px; margin:0 auto;
    padding:36px 20px 32px; flex:1; position:relative; z-index:1;
}
@media (min-width:520px){ .shell{ padding-top:60px; } }

/* ---------- Logo + heading ---------- */
.logo{
    display:block; margin:0 auto 22px;
    max-height:54px; max-width:180px; width:auto; height:auto;
}
.kicker-row{
    display:flex; align-items:center; justify-content:center; gap:10px;
    color:var(--sage-dp); font-size:10px; letter-spacing:.34em;
    text-transform:uppercase; margin-bottom:14px;
}
.kicker-row .line{
    height:1px; width:36px;
    background:linear-gradient(90deg,transparent,var(--sage),transparent);
}
h1{
    font:500 clamp(26px,6vw,32px)/1.15 var(--serif);
    text-align:center; letter-spacing:-.005em; margin:0 0 12px;
}
h1 em{font-style:italic; color:var(--sage-dp); font-weight:500}
.lede{
    color:var(--muted); font-size:14px; line-height:1.6;
    text-align:center; max-width:380px; margin:0 auto 28px;
}

/* ---------- Card (verification + distributor share these tokens) ---------- */
.card{
    background:var(--paper); border:1px solid var(--hair);
    border-radius:var(--radius); padding:22px 20px;
    box-shadow:0 1px 2px rgba(60,50,30,.04),0 8px 24px -16px rgba(60,50,30,.12);
}
@media (min-width:520px){ .card{ padding:28px 26px; } }

label{
    display:block; font-size:10px; letter-spacing:.24em;
    text-transform:uppercase; color:var(--sage-dp); margin-bottom:9px;
}
.input{
    display:block; width:100%; padding:13px 14px;
    font:inherit; font-size:15px; letter-spacing:.04em;
    font-variant-numeric:tabular-nums;
    border:1px solid var(--hair); border-radius:10px;
    background:#fff; color:var(--ink);
    -webkit-appearance:none; appearance:none;
    transition:border-color .15s,box-shadow .15s;
    margin-bottom:18px;
}
.input::placeholder{color:#b8b3a7;letter-spacing:.06em}
.input:focus{
    outline:0; border-color:var(--sage);
    box-shadow:0 0 0 3px rgba(138,157,122,.18);
}

#ts-mount{margin-bottom:16px;min-height:0}
#ts-mount:not(:empty){min-height:65px}

.cta{
    display:block; width:100%;
    padding:14px 16px;
    font:500 12px/1 var(--sans); letter-spacing:.22em; text-transform:uppercase;
    color:#fff; background:var(--ink);
    border:1px solid var(--ink); border-radius:10px; cursor:pointer;
    transition:transform .12s,background .12s;
}
.cta:hover{background:#2d2a25;transform:translateY(-1px)}
.cta:active{transform:translateY(0)}
.cta:disabled{opacity:.55;cursor:not-allowed;transform:none}

.trust{
    margin:14px 0 0; text-align:center;
    font-size:11px; color:#8c8678; letter-spacing:.04em;
}

/* Verifying state - thin sage shimmer line */
.card.is-verifying{position:relative;overflow:hidden}
.card.is-verifying::after{
    content:""; position:absolute; left:0; right:0; top:0; height:2px;
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
.r-warn{
    background:#fcf5e8;border:1px solid #e9d8b3;margin-top:14px;
    font-size:13px;color:#7a4a08;display:flex;gap:10px;
}
.r-warn svg{flex:0 0 auto;width:18px;height:18px;color:var(--amber)}

.pill{
    display:inline-flex;align-items:center;gap:6px;
    font-size:11px;font-weight:600;letter-spacing:.18em;text-transform:uppercase;
}
.pill.ok{color:var(--emerald)}
.pill.err{color:var(--crimson)}
.pill svg{width:15px;height:15px}

.r-product{display:block;color:inherit;margin-top:14px}
.r-product[href]{text-decoration:none}
.r-product[href] .r-title{transition:color .15s}
.r-product[href]:hover .r-title{color:var(--sage-dp)}
.r-img{
    width:100%; aspect-ratio:16/10; object-fit:cover;
    border-radius:10px; border:1px solid var(--hair); background:#f3f2ec;
}
.r-title{
    font:500 22px/1.2 var(--serif);
    margin:12px 0 4px; letter-spacing:-.005em;
}
.r-desc{font-size:14px;line-height:1.6;color:var(--muted);margin:8px 0 0}

.r-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}
.r-meta .t{
    background:#fff; border:1px solid var(--hair);
    border-radius:10px; padding:9px 12px;
}
.r-meta .k{font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--sage-dp)}
.r-meta .v{font-size:13px;font-weight:500;margin-top:2px;font-variant-numeric:tabular-nums}

.r-howto-h{
    display:block; font-size:9px; letter-spacing:.24em;
    text-transform:uppercase; color:var(--sage-dp); margin:18px 0 6px;
}
.r-howto-l{margin:0;padding-left:18px;color:var(--muted);font-size:14px;line-height:1.7}

.r-cta{
    display:block; text-align:center; width:100%;
    margin-top:20px; padding:13px 14px;
    background:var(--ink); color:#fff; border-radius:10px;
    font:500 11px/1 var(--sans); letter-spacing:.22em;
    text-transform:uppercase; text-decoration:none;
    transition:transform .12s,background .12s;
}
.r-cta:hover{background:#2d2a25;transform:translateY(-1px)}

.r-retry{
    width:100%; margin-top:14px; padding:11px 14px;
    background:#fff; color:var(--ink);
    border:1px solid var(--hair); border-radius:10px; cursor:pointer;
    font:500 11px/1 var(--sans); letter-spacing:.18em; text-transform:uppercase;
}
.r-retry:hover{background:#fafafa}

/* ============================================================
   Distributor card + WhatsApp button
   ============================================================ */
.section-head{
    display:flex; align-items:center; justify-content:center; gap:10px;
    color:var(--sage-dp); font-size:10px; letter-spacing:.34em;
    text-transform:uppercase; margin:36px 0 14px;
}
.section-head .line{
    height:1px;width:36px;
    background:linear-gradient(90deg,transparent,var(--sage),transparent);
}
.dist-title{
    font:500 22px/1.25 var(--serif);
    text-align:center; margin:0 0 18px; letter-spacing:-.005em;
}
.dist-rows{display:grid;gap:14px;margin-bottom:18px}
.dist-row{
    display:flex; gap:12px; align-items:flex-start;
    padding:14px 14px;
    background:#fbfaf6; border:1px solid var(--hair); border-radius:10px;
}
.dist-icon{
    flex:0 0 auto; width:34px; height:34px;
    border-radius:9px; background:#fff; border:1px solid var(--hair);
    color:var(--sage-dp);
    display:inline-flex; align-items:center; justify-content:center;
}
.dist-icon svg{width:17px;height:17px}
.dist-body{min-width:0;flex:1}
.dist-k{
    font-size:9px; letter-spacing:.24em; text-transform:uppercase;
    color:var(--sage-dp); margin:0 0 3px;
}
.dist-v{font-size:14px;line-height:1.55;color:var(--ink);word-break:break-word}
.dist-v a{color:var(--ink);text-decoration:none;border-bottom:1px solid rgba(138,157,122,.45)}
.dist-v a:hover{color:var(--sage-dp);border-bottom-color:var(--sage-dp)}

.wa-btn{
    display:flex; align-items:center; justify-content:center; gap:9px;
    width:100%; padding:14px 16px;
    background:var(--wa); color:#fff; border-radius:10px;
    text-decoration:none;
    font:600 13px/1 var(--sans); letter-spacing:.04em;
    box-shadow:0 6px 18px -10px rgba(37,211,102,.7);
    transition:background .15s, transform .12s;
}
.wa-btn:hover{background:var(--wa-dp); transform:translateY(-1px)}
.wa-btn:active{transform:translateY(0)}
.wa-btn svg{width:20px;height:20px;fill:currentColor}

/* ---------- Footer ---------- */
.footer{
    text-align:center; color:#8c8678; font-size:12px; line-height:1.7;
    margin-top:28px; padding:0 8px;
}
.footer a{
    color:var(--ink); text-decoration:underline;
    text-decoration-color:rgba(138,157,122,.55); text-underline-offset:3px;
}
.footer a:hover{color:var(--sage-dp)}
.footer .eco{
    display:inline-flex; align-items:center; gap:5px;
    color:var(--sage-dp); margin-top:10px; font-size:11px; letter-spacing:.04em;
}
.footer .eco svg{width:12px;height:12px}

/* ---------- Decorative botanical sprig ---------- */
.sprig{
    position:fixed; right:-30px; bottom:-30px; width:200px; height:200px;
    color:var(--sage); opacity:.18; pointer-events:none; z-index:0;
}
@media (max-width:520px){ .sprig{ width:140px;height:140px;right:-40px;bottom:-40px; } }

@media (prefers-reduced-motion:reduce){
    .card.is-verifying::after,.cta,.r-cta,.wa-btn{animation:none!important;transition:none!important}
}
</style>
</head>
<body>

<!-- Decorative botanical sprig in the corner. ~600 bytes inline. -->
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
        Enter yours below to confirm it.
    </p>

    <!-- ============ Verification card ============ -->
    <section id="card" class="card">
        <form id="form" autocomplete="off" novalidate>
            <input type="hidden" name="csrf" value="<?= Helpers::e($csrf) ?>">

            <label for="code">Verification Code</label>
            <input id="code" name="code" class="input" type="text"
                   inputmode="text" autocapitalize="characters" spellcheck="false"
                   required maxlength="100"
                   placeholder="ELH-XXXX-XXXX-XXXX">

            <div id="ts-mount" data-sitekey="<?= Helpers::e(TURNSTILE_SITE_KEY) ?>"></div>

            <button type="submit" id="verify" class="cta">Verify Authenticity</button>

            <p class="trust">Protected . No personal data stored</p>
        </form>

        <div id="result" class="result" aria-live="polite"></div>
    </section>

    <!-- ============ Distributor + WhatsApp ============ -->
    <div class="section-head">
        <span class="line"></span>
        <span>Local Distributor</span>
        <span class="line"></span>
    </div>

    <section class="card" aria-labelledby="dist-title">
        <h2 id="dist-title" class="dist-title">Contact our local distributor</h2>

        <div class="dist-rows">
            <div class="dist-row">
                <span class="dist-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s-7-7.58-7-13a7 7 0 0 1 14 0c0 5.42-7 13-7 13z"/>
                        <circle cx="12" cy="9" r="2.5"/>
                    </svg>
                </span>
                <div class="dist-body">
                    <div class="dist-k">Address</div>
                    <div class="dist-v"><?= Helpers::e($DIST_ADDRESS) ?></div>
                </div>
            </div>

            <div class="dist-row">
                <span class="dist-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7a2 2 0 0 1 1.72 2.03z"/>
                    </svg>
                </span>
                <div class="dist-body">
                    <div class="dist-k">Mobile</div>
                    <div class="dist-v">
                        <a href="tel:<?= Helpers::e($DIST_PHONE) ?>"><?= Helpers::e($DIST_PHONE_DISPLAY) ?></a>
                    </div>
                </div>
            </div>
        </div>

        <a class="wa-btn"
           href="https://wa.me/<?= Helpers::e($DIST_WA) ?>?text=<?= rawurlencode('Hello ELHOE, I would like to ask about a product.') ?>"
           target="_blank" rel="noopener"
           aria-label="Chat on WhatsApp">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.82 11.82 0 0 1 8.413 3.488 11.82 11.82 0 0 1 3.48 8.414c-.003 6.555-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.512 5.26l.6.954-1.005 3.667 3.762-.99.62.41zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.521.074-.793.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.71.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.247-.694.247-1.289.173-1.413z"/>
            </svg>
            Chat on WhatsApp
        </a>
    </section>

    <footer class="footer">
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

<script>
/* =================================================================
   ELHOE Verify - inline JS (~1.5 KB).
   - Form submit -> /checker/verify_action.php
   - Turnstile is lazy-loaded after first idle (or on input focus).
   - QR scanner removed; manual entry only.
   ================================================================= */
(function(){
"use strict";

var BASE      = "<?= Helpers::e(BASE_PATH) ?>";
var TURNSTILE = <?= TURNSTILE_SITE_KEY !== '' ? 'true' : 'false' ?>;

var form    = document.getElementById("form");
var card    = document.getElementById("card");
var codeIn  = document.getElementById("code");
var btn     = document.getElementById("verify");
var result  = document.getElementById("result");
var tsMount = document.getElementById("ts-mount");

function esc(s){ return String(s==null?"":s)
    .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
    .replace(/"/g,"&quot;").replace(/'/g,"&#39;"); }

function busy(on){
    btn.disabled = on;
    btn.textContent = on ? "Verifying\u2026" : "Verify Authenticity";
    card.classList.toggle("is-verifying", on);
}

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
    var p   = d.product || {};
    // Treat empty / whitespace-only URLs as "no link".
    var url = (typeof p.wordpress_url === "string" && p.wordpress_url.trim()) ? p.wordpress_url.trim() : "";

    // Product block: <a> when there's a URL, plain <div> otherwise.
    var productOpen  = url ? '<a class="r-product" href="'+esc(url)+'" target="_blank" rel="noopener">' : '<div class="r-product">';
    var productClose = url ? '</a>' : '</div>';

    var img   = p.image_url ? '<img class="r-img" src="'+esc(p.image_url)+'" alt="" loading="lazy" decoding="async">' : '';
    var title = '<h3 class="r-title">'+esc(p.title||'')+'</h3>';

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

    // CTA only renders when wordpress_url is set.
    var ctaBlock = url
        ? '<a class="r-cta" href="'+esc(url)+'" target="_blank" rel="noopener">Buy Again \u2014 Restock Now</a>'
        : '';

    result.innerHTML =
      '<div class="r-ok">'+
        '<span class="pill ok"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>Authentic ELHOE Product</span>'+
        productOpen + img + title + productClose +
        (p.description ? '<p class="r-desc">'+esc(p.description)+'</p>' : '')+
        '<div class="r-meta">'+
          (p.batch_number ? '<div class="t"><div class="k">Batch</div><div class="v">'+esc(p.batch_number)+'</div></div>' : '')+
          (p.expiry_date  ? '<div class="t"><div class="k">Expires</div><div class="v">'+esc(p.expiry_date)+'</div></div>' : '')+
        '</div>'+
        howList +
        reused +
        ctaBlock +
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

})();
</script>
</body>
</html>
