<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

use App\Core\Helpers;

$csrf      = Helpers::csrfToken();
$logoFile  = APP_ROOT . '/assets/logo.png';
$hasLogo   = is_file($logoFile);
$logoUrl   = BASE_PATH . '/assets/logo.png?v=' . ($hasLogo ? filemtime($logoFile) : '1');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#f3eee2">
<meta name="base-path" content="<?= Helpers::e(BASE_PATH) ?>">
<title>ELHOE — Authenticity Verification</title>
<meta name="description" content="Verify the authenticity of your ELHOE luxury skincare product.">

<?php if ($hasLogo): ?>
<link rel="icon" type="image/png" href="<?= Helpers::e($logoUrl) ?>">
<?php endif; ?>

<!-- Tailwind via CDN with luxury theme tokens -->
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ivory:     '#faf6ee',
          cream:     '#f3eee2',
          stone:     '#e8e0d2',
          ink:       '#1c1815',
          gold:      '#b08d57',
          'gold-deep': '#8a6d3f',
          sage:      '#9eb087',
          'sage-deep': '#7c9168',
        },
        fontFamily: {
          sans:  ['Inter', 'system-ui', 'sans-serif'],
          serif: ['"Cormorant Garamond"', 'serif'],
        }
      }
    }
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= Helpers::e(BASE_PATH) ?>/assets/css/site.css">

<!-- html5-qrcode camera scanner -->
<script src="https://unpkg.com/html5-qrcode@2.3.10/html5-qrcode.min.js" defer></script>

<?php if (TURNSTILE_SITE_KEY !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
</head>
<body>

<!-- ============================================================
     ANIMATED BACKGROUND
     orbs (slow-breathing gradient blobs) + drifting botanical leaves
============================================================ -->
<div class="bg-canvas" aria-hidden="true">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Leaf shape sprite. Defined once, reused via <use>. -->
    <svg class="leaf-defs" width="0" height="0" aria-hidden="true">
        <defs>
            <!-- A: rounded eucalyptus leaf with center vein -->
            <symbol id="leaf-a" viewBox="0 0 100 100">
                <path d="M50 6 C72 18, 86 50, 50 94 C14 50, 28 18, 50 6 Z" fill="currentColor"/>
                <path d="M50 8 L50 92" stroke="rgba(0,0,0,0.18)" stroke-width="1" fill="none"/>
            </symbol>
            <!-- B: slim olive leaf -->
            <symbol id="leaf-b" viewBox="0 0 100 100">
                <path d="M50 4 Q63 50 50 96 Q37 50 50 4 Z" fill="currentColor"/>
            </symbol>
            <!-- C: petal/teardrop -->
            <symbol id="leaf-c" viewBox="0 0 100 100">
                <path d="M50 92 C18 72, 18 28, 50 10 C82 28, 82 72, 50 92 Z" fill="currentColor"/>
            </symbol>
            <!-- D: pointed sage leaf -->
            <symbol id="leaf-d" viewBox="0 0 100 100">
                <path d="M50 5 C75 25, 75 55, 50 95 C25 55, 25 25, 50 5 Z" fill="currentColor"/>
                <path d="M50 8 L50 92" stroke="rgba(0,0,0,0.15)" stroke-width="1" fill="none"/>
            </symbol>
        </defs>
    </svg>

    <!-- 16 leaves: varied positions, scales, shapes, durations, delays, colors.
         Negative animation-delays mean some leaves are mid-fall on load. -->
    <svg class="leaf" style="--x:6%;  --scale:1.0; --dur:24s; --delay:-2s;  --leaf-color:#9eb087; --max-op:0.50;"><use href="#leaf-a"/></svg>
    <svg class="leaf" style="--x:14%; --scale:1.6; --dur:28s; --delay:-9s;  --leaf-color:#7c9168; --max-op:0.40;"><use href="#leaf-b"/></svg>
    <svg class="leaf" style="--x:22%; --scale:0.9; --dur:20s; --delay:-15s; --leaf-color:#a8b87b; --max-op:0.55;"><use href="#leaf-c"/></svg>
    <svg class="leaf" style="--x:30%; --scale:1.3; --dur:26s; --delay:-4s;  --leaf-color:#8a9a5b; --max-op:0.42;"><use href="#leaf-d"/></svg>
    <svg class="leaf" style="--x:38%; --scale:1.1; --dur:22s; --delay:-18s; --leaf-color:#9eb087; --max-op:0.48;"><use href="#leaf-a"/></svg>
    <svg class="leaf" style="--x:46%; --scale:0.8; --dur:30s; --delay:-7s;  --leaf-color:#b8c8a3; --max-op:0.52;"><use href="#leaf-b"/></svg>
    <svg class="leaf" style="--x:54%; --scale:1.5; --dur:25s; --delay:-12s; --leaf-color:#7c9168; --max-op:0.40;"><use href="#leaf-c"/></svg>
    <svg class="leaf" style="--x:62%; --scale:1.0; --dur:21s; --delay:-3s;  --leaf-color:#b08d57; --max-op:0.32;"><use href="#leaf-d"/></svg>
    <svg class="leaf" style="--x:70%; --scale:1.2; --dur:27s; --delay:-20s; --leaf-color:#9eb087; --max-op:0.50;"><use href="#leaf-a"/></svg>
    <svg class="leaf" style="--x:78%; --scale:0.9; --dur:23s; --delay:-6s;  --leaf-color:#a8b87b; --max-op:0.55;"><use href="#leaf-b"/></svg>
    <svg class="leaf" style="--x:86%; --scale:1.4; --dur:29s; --delay:-14s; --leaf-color:#8a9a5b; --max-op:0.42;"><use href="#leaf-c"/></svg>
    <svg class="leaf" style="--x:93%; --scale:1.0; --dur:24s; --delay:-1s;  --leaf-color:#9eb087; --max-op:0.50;"><use href="#leaf-d"/></svg>
    <svg class="leaf" style="--x:18%; --scale:0.7; --dur:32s; --delay:-22s; --leaf-color:#d8c3a0; --max-op:0.30;"><use href="#leaf-c"/></svg>
    <svg class="leaf" style="--x:50%; --scale:0.8; --dur:34s; --delay:-11s; --leaf-color:#b08d57; --max-op:0.28;"><use href="#leaf-b"/></svg>
    <svg class="leaf" style="--x:74%; --scale:0.7; --dur:33s; --delay:-19s; --leaf-color:#d8c3a0; --max-op:0.30;"><use href="#leaf-a"/></svg>
    <svg class="leaf" style="--x:34%; --scale:0.6; --dur:36s; --delay:-25s; --leaf-color:#b08d57; --max-op:0.25;"><use href="#leaf-d"/></svg>
</div>

<!-- ============================================================
     MAIN
============================================================ -->
<main class="relative z-10 max-w-xl mx-auto px-5 py-10 sm:py-14">

    <!-- Hero -->
    <header class="text-center">
        <?php if ($hasLogo): ?>
            <img src="<?= Helpers::e($logoUrl) ?>" alt="ELHOE" class="brand-logo mx-auto">
        <?php else: ?>
            <!-- SVG fallback wordmark - shown until /checker/assets/logo.png is uploaded -->
            <svg class="brand-logo mx-auto" viewBox="0 0 320 80" aria-label="ELHOE" role="img">
                <text x="160" y="56" text-anchor="middle"
                      font-family="'Cormorant Garamond', serif"
                      font-size="56" font-weight="500"
                      letter-spacing="14" fill="#7a6e5e">ELHOE</text>
            </svg>
        <?php endif; ?>

        <div class="hero-divider">
            <span class="line"></span>
            <span class="ornament">✶</span>
            <span class="line"></span>
        </div>

        <p class="kicker">Authenticity Assured</p>

        <h1 class="hero-title">
            Verify your <em>ELHOE</em><br>skincare ritual.
        </h1>

        <p class="hero-sub">
            Each authentic ELHOE product carries a unique authentication seal.
            Enter the code printed beneath the seal — or scan the QR — to
            confirm yours.
        </p>
    </header>

    <!-- Verification card -->
    <section id="verify-card" class="verify-card mt-10">

        <form id="verify-form" autocomplete="off" novalidate>
            <input type="hidden" name="csrf" value="<?= Helpers::e($csrf) ?>">

            <label for="code" class="field-label">Verification Code</label>

            <div class="field-row">
                <input id="code" name="code" type="text" required maxlength="100"
                       inputmode="text" autocapitalize="characters" spellcheck="false"
                       placeholder="ELH-XXXX-XXXX-XXXX"
                       class="field-input">

                <button type="button" id="btn-scan" class="field-scan" aria-label="Scan QR code with camera">
                    <!-- Elegant viewfinder icon -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 8V5a2 2 0 0 1 2-2h3"/>
                        <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
                        <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
                        <path d="M21 16v3a2 2 0 0 1-2 2h-3"/>
                        <rect x="8" y="8" width="8" height="8" rx="1.5"/>
                    </svg>
                    <span class="hidden sm:inline">Scan</span>
                </button>
            </div>

            <?php if (TURNSTILE_SITE_KEY !== ''): ?>
            <div class="cf-turnstile mb-4"
                 data-sitekey="<?= Helpers::e(TURNSTILE_SITE_KEY) ?>"
                 data-theme="light" data-size="flexible"></div>
            <?php endif; ?>

            <button type="submit" id="btn-verify" class="cta-verify">
                <span class="cta-shine" aria-hidden="true"></span>
                <span class="cta-text">Verify Authenticity</span>
            </button>

            <p class="card-trust">Protected by Cloudflare. We never store personal data.</p>
        </form>

        <!-- Result region -->
        <div id="result" class="mt-6" aria-live="polite"></div>
    </section>

    <!-- Help -->
    <footer class="help">
        <p>Can't find your code? Check beneath the scratch-off panel on the carton.</p>
        <p>
            Spotted a counterfeit?
            <a href="mailto:support@elhoe.com">support@elhoe.com</a>
        </p>
    </footer>

</main>

<!-- ============================================================
     SCANNER MODAL
============================================================ -->
<div id="scanner-modal" class="scanner-modal" role="dialog" aria-modal="true" aria-labelledby="scanner-title">
    <div class="scanner-card">
        <div class="scanner-head">
            <h2 id="scanner-title">Scan the QR Code</h2>
            <button id="btn-scan-close" class="scanner-close" aria-label="Close">×</button>
        </div>
        <div id="qr-reader"></div>
        <p class="scanner-hint">Hold the QR steady inside the frame.</p>
    </div>
</div>

<script src="<?= Helpers::e(BASE_PATH) ?>/assets/js/site.js" defer></script>
</body>
</html>
