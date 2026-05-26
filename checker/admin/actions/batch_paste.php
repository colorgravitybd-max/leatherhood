<?php
declare(strict_types=1);

/**
 * POST /admin/?route=codes/paste
 *
 * Insert a list of CUSTOM, user-supplied codes (existing legacy codes,
 * pre-printed codes, etc.) for a given product + batch. Codes can be
 * pasted into a textarea separated by newlines, commas, semicolons,
 * tabs, or spaces.
 *
 * Each code is normalised (uppercased, non-alphanumeric stripped except
 * dashes) so it round-trips with the public verifier. Duplicates are
 * silently skipped via INSERT IGNORE on the UNIQUE(code) index.
 */

use App\Core\Database;
use App\Core\Helpers;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    || !Helpers::csrfCheck($_POST['csrf'] ?? '')) {
    Helpers::flash('error', 'Bad request.');
    Helpers::redirect('/admin/?route=codes/generate');
}

$productId = (int) ($_POST['product_id'] ?? 0);
$type      = ($_POST['code_type'] ?? '') === 'universal' ? 'universal' : 'unique';
$batch     = Helpers::clean($_POST['batch_number'] ?? '') ?? '';
$expiry    = trim((string) ($_POST['expiry_date'] ?? '')) ?: null;
$rawCodes  = (string) ($_POST['codes'] ?? '');

if ($productId <= 0 || $batch === '') {
    Helpers::flash('error', 'Product and batch identifier are required.');
    Helpers::redirect('/admin/?route=codes/generate');
}
if ($expiry !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry)) {
    Helpers::flash('error', 'Invalid expiry date.');
    Helpers::redirect('/admin/?route=codes/generate');
}

// Make sure the product exists.
$exists = (int) Database::scalar('SELECT COUNT(*) FROM products WHERE id = ?', [$productId]);
if ($exists === 0) {
    Helpers::flash('error', 'Selected product does not exist.');
    Helpers::redirect('/admin/?route=codes/generate');
}

// Universal codes are by definition shared; only ever insert one per batch.
// If the user pastes multiple "universal" codes we still insert each unique
// one, but warn that this is unusual.
$tokens = preg_split('/[\s,;]+/', $rawCodes) ?: [];
$codes  = [];
$invalid = 0;
foreach ($tokens as $tok) {
    $code = Helpers::normalizeCode((string) $tok);
    if ($code === '') {
        if (trim((string) $tok) !== '') { $invalid++; }
        continue;
    }
    if (strlen($code) > 100) { $invalid++; continue; }
    $codes[$code] = true;
}
$codes = array_keys($codes); // dedupe

if (empty($codes)) {
    Helpers::flash('error', 'No valid codes were found in the pasted text.');
    Helpers::redirect('/admin/?route=codes/generate');
}
if (count($codes) > 100000) {
    Helpers::flash('error', 'Too many codes in one paste (max 100,000). Use CSV import instead.');
    Helpers::redirect('/admin/?route=codes/generate');
}

$pdo = Database::pdo();
$pdo->beginTransaction();
$inserted = 0;
$skipped  = 0;
try {
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO verification_codes
            (product_id, code, code_type, batch_number, expiry_date)
         VALUES (?,?,?,?,?)'
    );
    foreach ($codes as $code) {
        $stmt->execute([$productId, $code, $type, $batch, $expiry]);
        if ($stmt->rowCount() === 0) {
            $skipped++;        // already exists in the codes table
        } else {
            $inserted++;
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[ELHOE] paste codes failed: ' . $e->getMessage());
    Helpers::flash('error', 'Failed to save pasted codes.');
    Helpers::redirect('/admin/?route=codes/generate');
}

$msg = sprintf(
    'Saved %d code(s) into batch "%s" (%s).',
    $inserted, $batch, $type
);
if ($skipped > 0) {
    $msg .= sprintf(' Skipped %d already-existing code(s).', $skipped);
}
if ($invalid > 0) {
    $msg .= sprintf(' Ignored %d unparseable token(s).', $invalid);
}

Helpers::flash('success', $msg);
Helpers::redirect('/admin/?route=codes&batch=' . urlencode($batch));
