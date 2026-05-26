<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    || !Helpers::csrfCheck($_POST['csrf'] ?? '')) {
    Helpers::flash('error', 'Bad request.');
    Helpers::redirect('/admin/?route=codes/generate');
}

if (empty($_FILES['file']['tmp_name']) || (int) $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    Helpers::flash('error', 'Please choose a CSV file.');
    Helpers::redirect('/admin/?route=codes/generate');
}
if ($_FILES['file']['size'] > 20 * 1024 * 1024) {
    Helpers::flash('error', 'File too large (max 20 MB).');
    Helpers::redirect('/admin/?route=codes/generate');
}

$fh = fopen($_FILES['file']['tmp_name'], 'r');
if (!$fh) {
    Helpers::flash('error', 'Could not open uploaded file.');
    Helpers::redirect('/admin/?route=codes/generate');
}

// Strip optional UTF-8 BOM
$peek = fread($fh, 3);
if ($peek !== "\xEF\xBB\xBF") { rewind($fh); }

$header = fgetcsv($fh);
if (!$header) {
    fclose($fh);
    Helpers::flash('error', 'CSV is empty.');
    Helpers::redirect('/admin/?route=codes/generate');
}
$header = array_map(static fn($h) => strtolower(trim((string) $h)), $header);

$required = ['code', 'product_id', 'code_type', 'batch_number', 'expiry_date'];
$idx = [];
foreach ($required as $col) {
    $i = array_search($col, $header, true);
    if ($i === false) {
        fclose($fh);
        Helpers::flash('error', 'CSV missing required column: ' . $col);
        Helpers::redirect('/admin/?route=codes/generate');
    }
    $idx[$col] = $i;
}

$pdo = Database::pdo();
$pdo->beginTransaction();
$inserted = 0;
$skipped  = 0;
$rowNum   = 1;

try {
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO verification_codes
            (product_id, code, code_type, batch_number, expiry_date)
         VALUES (?,?,?,?,?)'
    );

    while (($row = fgetcsv($fh)) !== false) {
        $rowNum++;
        if (count($row) === 1 && trim((string) $row[0]) === '') { continue; } // blank line

        $code      = Helpers::normalizeCode((string) ($row[$idx['code']] ?? ''));
        $productId = (int) ($row[$idx['product_id']] ?? 0);
        $type      = strtolower(trim((string) ($row[$idx['code_type']] ?? '')));
        $batch     = trim((string) ($row[$idx['batch_number']] ?? '')) ?: null;
        $expiry    = trim((string) ($row[$idx['expiry_date']] ?? ''))   ?: null;

        if ($code === '' || $productId <= 0
            || !in_array($type, ['unique','universal'], true)) {
            $skipped++; continue;
        }
        if ($expiry !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry)) {
            $expiry = null;
        }

        $stmt->execute([$productId, $code, $type, $batch, $expiry]);
        $rc = $stmt->rowCount();
        if ($rc === 0) { $skipped++; } else { $inserted += $rc; }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fclose($fh);
    error_log('[ELHOE] CSV import failed at row ' . $rowNum . ': ' . $e->getMessage());
    Helpers::flash('error', 'Import failed near row ' . $rowNum . '. No rows inserted.');
    Helpers::redirect('/admin/?route=codes/generate');
}

fclose($fh);
Helpers::flash('success', sprintf('Imported %d code(s). Skipped %d.', $inserted, $skipped));
Helpers::redirect('/admin/?route=codes');
