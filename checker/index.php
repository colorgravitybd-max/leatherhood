<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Helpers;

$csrf = Helpers::csrfToken();
?>
<!doctype html>
<html lang="en" data-lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#faf6f1">
<meta name="base-path" content="<?= Helpers::e(BASE_PATH) ?>">
<title>ELHOE — Authenticity Verification</title>
<meta name="description" content="Verify the authenticity of your ELHOE luxury skincare product.">

<!-- Tailwind via CDN (config inline) -->
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          elhoe: { cream:'#faf6f1', canvas:'#f3eee7', ink:'#1a1614', gold:'#b08d57' }
        },
        fontFamily: {
          sans: ['Inter','system-ui','sans-serif'],
          serif: ['"Cormorant Garamond"','serif'],
          bn: ['"Hind Siliguri"','"Noto Sans Bengali"','system-ui','sans-serif'],
        }
      }
    }
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600&family=Hind+Siliguri:wght@400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= Helpers::e(BASE_PATH) ?>/assets/css/site.css">

<!-- html5-qrcode camera scanner (~50KB gz) -->
<script src="https://unpkg.com/html5-qrcode@2.3.10/html5-qrcode.min.js" defer></script>

<?php if (TURNSTILE_SITE_KEY !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
</head>
<body class="min-h-screen antialiased selection:bg-elhoe-gold/30">

<!-- ============ HEADER ============ -->
<header class="max-w-xl mx-auto px-5 pt-6 pb-3 flex items-center justify-between">
    <a href="<?= Helpers::e(BASE_PATH) ?>/" class="flex items-center gap-2" aria-label="ELHOE">
        <!-- Inline minimalist logo: a thin gold ring + brand wordmark -->
        <svg width="26" height="26" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <circle cx="16" cy="16" r="13" stroke="#b08d57" stroke-width="1.5"/>
            <circle cx="16" cy="16" r="5" stroke="#1a1614" stroke-width="1.5"/>
        </svg>
        <span class="font-serif text-[1.35rem] tracking-[0.18em] text-elhoe-ink">ELHOE</span>
    </a>

    <!-- Language toggle -->
    <div class="inline-flex items-center text-xs font-medium bg-white/60 border border-white/80 backdrop-blur-md rounded-full p-0.5"
         role="tablist" aria-label="Language">
        <button data-lang="en" class="lang-btn px-3 py-1.5 rounded-full transition" aria-pressed="true">EN</button>
        <button data-lang="bn" class="lang-btn px-3 py-1.5 rounded-full transition font-bn" aria-pressed="false">বাংলা</button>
    </div>
</header>

<main class="max-w-xl mx-auto px-5 pb-16">

    <!-- ============ HERO ============ -->
    <section class="text-center mt-4 mb-6">
        <p data-i18n="kicker"
           class="uppercase tracking-[0.3em] text-[10px] text-elhoe-gold mb-2">Authenticity Portal</p>
        <h1 data-i18n="title"
            class="font-serif text-3xl sm:text-4xl text-elhoe-ink leading-tight">
            Confirm your ELHOE is genuine.
        </h1>
        <p data-i18n="subtitle"
           class="text-sm text-elhoe-ink/70 mt-2">
            Enter the code printed under the seal, or scan the QR.
        </p>
    </section>

    <!-- ============ VERIFICATION CARD ============ -->
    <section id="verify-card"
             class="elhoe-glass rounded-3xl p-5 sm:p-7 relative">

        <form id="verify-form" class="space-y-4" autocomplete="off" novalidate>
            <input type="hidden" name="csrf" value="<?= Helpers::e($csrf) ?>">

            <label for="code" class="block text-xs font-medium tracking-wide text-elhoe-ink/70"
                   data-i18n="label_code">Verification Code</label>

            <div class="flex gap-2">
                <input id="code" name="code" type="text" inputmode="text" autocapitalize="characters"
                       spellcheck="false" required maxlength="100"
                       data-i18n-attr="placeholder=ph_code"
                       placeholder="ELH-XXXX-XXXX-XXXX"
                       class="flex-1 rounded-xl border-elhoe-ink/15 bg-white/70 placeholder:text-elhoe-ink/30
                              focus:border-elhoe-gold focus:ring-elhoe-gold/30 text-elhoe-ink tracking-wider">

                <button type="button" id="btn-scan"
                        class="elhoe-btn-ghost rounded-xl px-3 inline-flex items-center gap-1.5 text-sm font-medium"
                        aria-label="Scan QR or barcode" data-i18n-attr="title=scan_btn"
                        title="Scan QR / Barcode">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" aria-hidden="true">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2M21 7V5a2 2 0 0 0-2-2h-2M3 17v2a2 2 0 0 0 2 2h2M21 17v2a2 2 0 0 1-2 2h-2M7 12h10"/>
                    </svg>
                    <span class="hidden sm:inline" data-i18n="scan_btn">Scan</span>
                </button>
            </div>

            <?php if (TURNSTILE_SITE_KEY !== ''): ?>
            <div class="cf-turnstile pt-1"
                 data-sitekey="<?= Helpers::e(TURNSTILE_SITE_KEY) ?>"
                 data-theme="light" data-size="flexible"></div>
            <?php endif; ?>

            <button type="submit" id="btn-verify"
                    class="elhoe-btn w-full rounded-xl py-3 font-medium tracking-wide"
                    data-i18n="verify_btn">
                Verify Authenticity
            </button>

            <p class="text-[11px] text-center text-elhoe-ink/50" data-i18n="trust_note">
                Protected by Cloudflare. We do not store personal data.
            </p>
        </form>

        <!-- Result region: server-rendered into here -->
        <div id="result" class="mt-5" aria-live="polite"></div>
    </section>

    <!-- ============ FAQ MICRO-COPY ============ -->
    <section class="text-center text-xs text-elhoe-ink/55 mt-8 space-y-1">
        <p data-i18n="help_1">Can't find your code? Check beneath the scratch-off panel on the carton.</p>
        <p data-i18n="help_2">
            Suspect a counterfeit?
            <a href="mailto:support@elhoe.com" class="underline decoration-elhoe-gold/50 hover:text-elhoe-ink">
                support@elhoe.com
            </a>
        </p>
    </section>

</main>

<!-- ============ SCANNER MODAL ============ -->
<div id="scanner-modal" class="fixed inset-0 z-50 hidden items-center justify-center elhoe-modal-backdrop p-4"
     role="dialog" aria-modal="true" aria-labelledby="scanner-title">
    <div class="elhoe-glass rounded-3xl w-full max-w-md p-5 relative">
        <div class="flex items-center justify-between mb-3">
            <h2 id="scanner-title" class="font-serif text-xl" data-i18n="scan_title">Scan QR / Barcode</h2>
            <button id="btn-scan-close" class="rounded-full p-2 hover:bg-black/5" aria-label="Close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <div id="qr-reader" class="aspect-square"></div>
        <p class="text-[11px] text-center text-elhoe-ink/55 mt-3" data-i18n="scan_hint">
            Hold the QR steady inside the frame.
        </p>
    </div>
</div>

<!-- i18n strings injected for JS -->
<script id="i18n-data" type="application/json"><?= json_encode([
    'en' => [
        'kicker'      => 'Authenticity Portal',
        'title'       => 'Confirm your ELHOE is genuine.',
        'subtitle'    => 'Enter the code printed under the seal, or scan the QR.',
        'label_code'  => 'Verification Code',
        'ph_code'     => 'ELH-XXXX-XXXX-XXXX',
        'scan_btn'    => 'Scan',
        'verify_btn'  => 'Verify Authenticity',
        'verifying'   => 'Verifying…',
        'trust_note'  => 'Protected by Cloudflare. We do not store personal data.',
        'help_1'      => "Can't find your code? Check beneath the scratch-off panel on the carton.",
        'help_2'      => 'Suspect a counterfeit?',
        'scan_title'  => 'Scan QR / Barcode',
        'scan_hint'   => 'Hold the QR steady inside the frame.',
        'genuine'     => 'Authentic ELHOE Product',
        'expires'     => 'Expires',
        'batch'       => 'Batch',
        'how_to_use'  => 'How to use',
        'ingredients' => 'Ingredients',
        'cta'         => 'Buy Again / Restock Now',
        'reused_pre'  => 'This code is authentic but has been verified',
        'reused_mid'  => 'times before. If you just unsealed this scratch-off panel, please contact ELHOE support immediately.',
        'reused_times'=> 'times',
        'fail_title'  => 'Code not recognised',
        'fail_body'   => 'This code is invalid or may indicate a counterfeit. Please double-check your entry. If the package looks genuine, contact ELHOE support.',
        'fail_cta'    => 'Try again',
        'rate_title'  => 'Too many attempts',
        'rate_body'   => 'For security, please wait a few minutes before trying again.',
        'turnstile_required' => 'Please complete the security check.',
    ],
    'bn' => [
        'kicker'      => 'অরিজিনালিটি পোর্টাল',
        'title'       => 'আপনার এলহো পণ্যটি আসল কিনা যাচাই করুন।',
        'subtitle'    => 'সিলের নিচে থাকা কোডটি লিখুন, অথবা QR স্ক্যান করুন।',
        'label_code'  => 'ভেরিফিকেশন কোড',
        'ph_code'     => 'ELH-XXXX-XXXX-XXXX',
        'scan_btn'    => 'স্ক্যান',
        'verify_btn'  => 'যাচাই করুন',
        'verifying'   => 'যাচাই করা হচ্ছে…',
        'trust_note'  => 'Cloudflare দ্বারা সুরক্ষিত। আমরা কোনো ব্যক্তিগত তথ্য সংরক্ষণ করি না।',
        'help_1'      => 'কোড খুঁজে পাচ্ছেন না? বক্সের স্ক্র্যাচ-অফ অংশের নিচে দেখুন।',
        'help_2'      => 'সন্দেহ হচ্ছে নকল?',
        'scan_title'  => 'QR / বারকোড স্ক্যান',
        'scan_hint'   => 'QR কোডটি ফ্রেমের মধ্যে স্থির রাখুন।',
        'genuine'     => 'আসল এলহো পণ্য',
        'expires'     => 'মেয়াদ',
        'batch'       => 'ব্যাচ',
        'how_to_use'  => 'ব্যবহার বিধি',
        'ingredients' => 'উপাদান',
        'cta'         => 'আবার অর্ডার করুন',
        'reused_pre'  => 'এই কোডটি আসল, কিন্তু এর আগে',
        'reused_mid'  => 'বার যাচাই করা হয়েছে। যদি আপনি এইমাত্র সিল খুলে থাকেন, অনুগ্রহ করে দ্রুত এলহো সাপোর্টে যোগাযোগ করুন।',
        'reused_times'=> 'বার',
        'fail_title'  => 'কোড সঠিক নয়',
        'fail_body'   => 'এই কোডটি অবৈধ অথবা নকল পণ্যের ইঙ্গিত হতে পারে। অনুগ্রহ করে আবার দেখুন। প্যাকেজ আসল মনে হলে এলহো সাপোর্টে জানান।',
        'fail_cta'    => 'আবার চেষ্টা',
        'rate_title'  => 'অনেকবার চেষ্টা হয়েছে',
        'rate_body'   => 'নিরাপত্তার জন্য কিছুক্ষণ পর আবার চেষ্টা করুন।',
        'turnstile_required' => 'অনুগ্রহ করে সিকিউরিটি চেক সম্পন্ন করুন।',
    ],
], JSON_UNESCAPED_UNICODE) ?></script>

<script src="<?= Helpers::e(BASE_PATH) ?>/assets/js/site.js" defer></script>
</body>
</html>
