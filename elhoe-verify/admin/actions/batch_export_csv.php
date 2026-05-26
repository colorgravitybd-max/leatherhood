<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

$batch = trim((string) ($_GET['batch'] ?? ''));
if ($batch === '') {
    Helpers::flash('error', 'Missing batch number.');
    Helpers::redirect('/admin/?route=codes');
}

$rows = Database::all(
    'SELECT vc.code, p.title AS product_title, vc.product_id, vc.code_type,
            vc.batch_number, vc.expiry_date, vc.created_at
       FROM verification_codes vc
       JOIN products p ON p.id = vc.product_id
      WHERE vc.batch_number = ?
      ORDER BY vc.id ASC',
    [$batch]
);

if (empty($rows)) {
    Helpers::flash('error', 'No codes found for that batch.');
    Helpers::redirect('/admin/?route=codes');
}

$filename = sprintf('elhoe-batch-%s-%s.csv',
    preg_replace('/[^A-Za-z0-9_-]+/', '_', $batch),
    date('Ymd-His')
);

// Stream directly to the browser - efficient for big batches.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$out = fopen('php://output', 'w');
// UTF-8 BOM so Excel opens Bengali product titles cleanly.
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['code', 'product_id', 'product_title', 'code_type', 'batch_number', 'expiry_date', 'created_at']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['code'],
        $r['product_id'],
        $r['product_title'],
        $r['code_type'],
        $r['batch_number'],
        $r['expiry_date'],
        $r['created_at'],
    ]);
}
fclose($out);
exit;
