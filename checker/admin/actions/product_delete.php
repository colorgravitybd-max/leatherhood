<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    || !Helpers::csrfCheck($_POST['csrf'] ?? '')) {
    Helpers::flash('error', 'Bad request.');
    Helpers::redirect('/admin/?route=products');
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    Helpers::redirect('/admin/?route=products');
}

// Best-effort: remove uploaded image file from disk.
$row = Database::one('SELECT image_url FROM products WHERE id = ?', [$id]);
if ($row && !empty($row['image_url']) && str_starts_with($row['image_url'], UPLOAD_URL . '/')) {
    $filename = basename($row['image_url']);
    $abs = UPLOAD_DIR . '/' . $filename;
    if (is_file($abs)) {
        @unlink($abs);
    }
}

// FK is ON DELETE CASCADE → verification_codes for this product are removed automatically.
Database::run('DELETE FROM products WHERE id = ?', [$id]);

Helpers::flash('success', 'Product deleted.');
Helpers::redirect('/admin/?route=products');
