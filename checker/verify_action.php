<?php
declare(strict_types=1);

/**
 * POST /verify_action.php
 *
 * Body (form-data):
 *   - code        : string, required
 *   - csrf        : string, required (matches $_SESSION['_csrf'])
 *   - cf-turnstile-response : string, required when Turnstile is enabled
 *
 * Response (JSON):
 *   200 OK ok=true                → authentic, includes product payload
 *   200 OK ok=false               → invalid / unknown code
 *   400 Bad Request               → missing fields or CSRF/Turnstile failure
 *   429 Too Many Requests         → rate limited by IP
 */

require_once __DIR__ . '/config/config.php';

use App\Core\Database;
use App\Core\GeoIP;
use App\Core\Helpers;
use App\Core\RateLimiter;
use App\Services\Turnstile;

// Only POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Helpers::json(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$ip       = Helpers::clientIp();
$rawCode  = (string) ($_POST['code'] ?? '');
$csrf     = (string) ($_POST['csrf'] ?? '');
$tsToken  = (string) ($_POST['cf-turnstile-response'] ?? '');
$ua       = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

// 1) CSRF
if (!Helpers::csrfCheck($csrf)) {
    Helpers::json(['ok' => false, 'message' => 'Session expired. Please refresh and try again.'], 400);
}

// 2) Cloudflare Turnstile (no-op if disabled)
if (Turnstile::enabled() && !Turnstile::verify($tsToken, $ip)) {
    Helpers::json(['ok' => false, 'message' => 'Security check failed. Please try again.'], 400);
}

// 3) Rate limit (IP-based, invalid attempts only)
$rl = RateLimiter::check($ip);
if ($rl['blocked']) {
    header('Retry-After: ' . $rl['retry_after']);
    Helpers::json([
        'ok'          => false,
        'rate_limited'=> true,
        'message'     => 'Too many invalid attempts. Please try again later.',
    ], 429);
}

// 4) Normalise + validate input
$code = Helpers::normalizeCode($rawCode);
if ($code === '' || strlen($code) > 100) {
    logScan($code ?: $rawCode, false, null, $ip, $ua);
    Helpers::json(['ok' => false, 'message' => 'Please enter a valid code.'], 200);
}

// 5) Lookup
$row = Database::one(
    'SELECT vc.id, vc.product_id, vc.code_type, vc.batch_number, vc.expiry_date,
            vc.scan_count, vc.is_disabled,
            p.id   AS p_id,
            p.title, p.title_bn,
            p.description, p.description_bn,
            p.ingredients,
            p.how_to_use, p.how_to_use_bn,
            p.image_url, p.wordpress_url, p.is_active
       FROM verification_codes vc
       JOIN products p ON p.id = vc.product_id
      WHERE vc.code = ?
      LIMIT 1',
    [$code]
);

if (!$row || (int) $row['is_disabled'] === 1 || (int) $row['is_active'] === 0) {
    logScan($code, false, $row['p_id'] ?? null, $ip, $ua);
    Helpers::json(['ok' => false, 'message' => null], 200); // null → frontend shows default failure copy
}

// 6) Increment scan stats (atomic)
Database::run(
    'UPDATE verification_codes
        SET scan_count = scan_count + 1,
            first_scan_at = COALESCE(first_scan_at, NOW()),
            last_scan_at  = NOW()
      WHERE id = ?',
    [(int) $row['id']]
);
$newScanCount = (int) $row['scan_count'] + 1;

logScan($code, true, (int) $row['p_id'], $ip, $ua);

// 7) Build product payload (with UTM-stamped wordpress_url for the marketing funnel)
$wpUrl = Helpers::withUtm((string) ($row['wordpress_url'] ?? ''), (int) $row['p_id']);
$image = (string) ($row['image_url'] ?? '');
if ($image !== '' && !preg_match('~^https?://~i', $image)) {
    // Make uploads absolute when the frontend lives on a subdomain.
    $image = APP_BASE_URL . $image;
}

Helpers::json([
    'ok'         => true,
    'code_type'  => $row['code_type'],
    'scan_count' => $newScanCount,
    'product'    => [
        'id'             => (int) $row['p_id'],
        'title'          => $row['title'],
        'title_bn'       => $row['title_bn'],
        'description'    => $row['description'],
        'description_bn' => $row['description_bn'],
        'ingredients'    => $row['ingredients'],
        'how_to_use'     => $row['how_to_use'],
        'how_to_use_bn'  => $row['how_to_use_bn'],
        'image_url'      => $image,
        'wordpress_url'  => $wpUrl,
        'batch_number'   => $row['batch_number'],
        'expiry_date'    => $row['expiry_date'],
    ],
]);

// ---------- helpers ----------
function logScan(string $code, bool $valid, ?int $productId, string $ip, string $ua): void
{
    $geo = GeoIP::lookup($ip);
    Database::run(
        'INSERT INTO scan_logs
            (code_searched, is_valid, product_id, ip_address, country, region, district, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            substr($code, 0, 100),
            $valid ? 1 : 0,
            $productId,
            $ip,
            $geo['country'],
            $geo['region'],
            $geo['district'],
            $ua,
        ]
    );
}
