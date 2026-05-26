<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;
use App\Services\CodeGenerator;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    || !Helpers::csrfCheck($_POST['csrf'] ?? '')) {
    Helpers::flash('error', 'Bad request.');
    Helpers::redirect('/admin/?route=codes/generate');
}

$productId = (int) ($_POST['product_id'] ?? 0);
$type      = ($_POST['code_type'] ?? '') === 'universal' ? 'universal' : 'unique';
$batch     = Helpers::clean($_POST['batch_number'] ?? '') ?? '';
$expiry    = trim((string) ($_POST['expiry_date'] ?? '')) ?: null;
$quantity  = max(1, min((int) ($_POST['quantity'] ?? 1), 100000));
$prefix    = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($_POST['prefix'] ?? 'ELH')) ?? 'ELH');
if ($prefix === '') { $prefix = 'ELH'; }

if ($productId <= 0 || $batch === '') {
    Helpers::flash('error', 'Product and batch identifier are required.');
    Helpers::redirect('/admin/?route=codes/generate');
}
if ($expiry !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry)) {
    Helpers::flash('error', 'Invalid expiry date.');
    Helpers::redirect('/admin/?route=codes/generate');
}

$exists = Database::scalar('SELECT COUNT(*) FROM products WHERE id = ?', [$productId]);
if ((int) $exists === 0) {
    Helpers::flash('error', 'Selected product does not exist.');
    Helpers::redirect('/admin/?route=codes/generate');
}

// Universal codes are by definition shared, so we only ever insert ONE of those.
if ($type === 'universal') {
    $quantity = 1;
}

$codes = CodeGenerator::batch($quantity, $prefix);

// Insert in chunks to keep the transaction tight and the lock window short.
$pdo = Database::pdo();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO verification_codes
            (product_id, code, code_type, batch_number, expiry_date)
         VALUES (?,?,?,?,?)'
    );

    $inserted = 0;
    foreach ($codes as $code) {
        $stmt->execute([$productId, $code, $type, $batch, $expiry]);
        $inserted += $stmt->rowCount();
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[ELHOE] batch generate failed: ' . $e->getMessage());
    Helpers::flash('error', 'Failed to generate batch.');
    Helpers::redirect('/admin/?route=codes/generate');
}

Helpers::flash('success',
    sprintf('Generated %d %s code(s) for batch "%s".', $inserted, $type, $batch)
);
Helpers::redirect('/admin/?route=codes&batch=' . urlencode($batch));
