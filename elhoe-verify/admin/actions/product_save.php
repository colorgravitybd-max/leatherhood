<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Helpers;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    || !Helpers::csrfCheck($_POST['csrf'] ?? '')) {
    Helpers::flash('error', 'Bad request.');
    Helpers::redirect('/admin/?route=products');
}

$id = (int) ($_POST['id'] ?? 0);

$data = [
    'title'          => Helpers::clean($_POST['title']          ?? '') ?? '',
    'title_bn'       => Helpers::clean($_POST['title_bn']       ?? null),
    'description'    => Helpers::clean($_POST['description']    ?? null),
    'description_bn' => Helpers::clean($_POST['description_bn'] ?? null),
    'ingredients'    => Helpers::clean($_POST['ingredients']    ?? null),
    'how_to_use'     => trim((string) ($_POST['how_to_use']     ?? '')) ?: null,
    'how_to_use_bn'  => trim((string) ($_POST['how_to_use_bn']  ?? '')) ?: null,
    'wordpress_url'  => Helpers::clean($_POST['wordpress_url']  ?? null),
    'is_active'      => (int) ($_POST['is_active'] ?? 1) === 1 ? 1 : 0,
];

if ($data['title'] === '') {
    Helpers::flash('error', 'Title is required.');
    Helpers::redirect('/admin/?route=products/edit' . ($id > 0 ? '&id=' . $id : ''));
}
if ($data['wordpress_url'] !== null
    && !filter_var($data['wordpress_url'], FILTER_VALIDATE_URL)) {
    Helpers::flash('error', 'Invalid WordPress URL.');
    Helpers::redirect('/admin/?route=products/edit' . ($id > 0 ? '&id=' . $id : ''));
}

// ---------- Image upload (optional) ----------
$imagePath = null; // only set when a new image is uploaded
if (!empty($_FILES['image']['name']) && (int) $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['image'];
    if ($f['size'] > 4 * 1024 * 1024) {
        Helpers::flash('error', 'Image too large (max 4 MB).');
        Helpers::redirect('/admin/?route=products/edit' . ($id > 0 ? '&id=' . $id : ''));
    }
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        Helpers::flash('error', 'Unsupported image type.');
        Helpers::redirect('/admin/?route=products/edit' . ($id > 0 ? '&id=' . $id : ''));
    }
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0775, true);
    }
    $fname = 'p_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest  = UPLOAD_DIR . '/' . $fname;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        Helpers::flash('error', 'Failed to save uploaded image.');
        Helpers::redirect('/admin/?route=products/edit' . ($id > 0 ? '&id=' . $id : ''));
    }
    $imagePath = UPLOAD_URL . '/' . $fname;
}

// ---------- Persist ----------
if ($id > 0) {
    $sql = 'UPDATE products SET
              title = ?, title_bn = ?, description = ?, description_bn = ?,
              ingredients = ?, how_to_use = ?, how_to_use_bn = ?,
              wordpress_url = ?, is_active = ?
              ' . ($imagePath ? ', image_url = ?' : '') . '
            WHERE id = ?';
    $params = [
        $data['title'], $data['title_bn'], $data['description'], $data['description_bn'],
        $data['ingredients'], $data['how_to_use'], $data['how_to_use_bn'],
        $data['wordpress_url'], $data['is_active'],
    ];
    if ($imagePath) { $params[] = $imagePath; }
    $params[] = $id;

    Database::run($sql, $params);
    Helpers::flash('success', 'Product updated.');
} else {
    Database::run(
        'INSERT INTO products
            (title, title_bn, description, description_bn, ingredients,
             how_to_use, how_to_use_bn, image_url, wordpress_url, is_active)
         VALUES (?,?,?,?,?,?,?,?,?,?)',
        [
            $data['title'], $data['title_bn'], $data['description'], $data['description_bn'],
            $data['ingredients'], $data['how_to_use'], $data['how_to_use_bn'],
            $imagePath, $data['wordpress_url'], $data['is_active'],
        ]
    );
    $id = (int) Database::lastId();
    Helpers::flash('success', 'Product created.');
}

Helpers::redirect('/admin/?route=products/edit&id=' . $id);
