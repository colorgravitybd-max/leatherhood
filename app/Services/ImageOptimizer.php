<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;

/**
 * Auto-WebP Optimization Engine.
 *
 * • Accepts a $_FILES upload entry.
 * • Detects format, scales to max width, converts to .webp.
 * • Writes a "@thumb" version for grid views.
 * • Deletes the original raw upload (Hostinger inode protection).
 * • Records the row in `media` and returns:
 *     [path, original_bytes, webp_bytes, ratio, dimensions]
 */
final class ImageOptimizer
{
    /** Process a single $_FILES entry. */
    public static function processUpload(array $file, string $subdir = 'products', ?int $userId = null): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload error: '.($file['error'] ?? 'unknown'));
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Tampered upload.');
        }

        $cfg     = $GLOBALS['LH_CFG']['images'];
        $maxW    = (int)$cfg['max_w'];
        $thumbW  = (int)$cfg['thumb_w'];
        $quality = (int)$cfg['quality'];

        $info = @getimagesize($file['tmp_name']);
        if (!$info) throw new \RuntimeException('Unsupported image.');
        [$w, $h] = $info;
        $mime    = $info['mime'];
        $orig_sz = (int) filesize($file['tmp_name']);

        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Unsupported format: '.$mime);
        }

        // Build target paths
        $year   = date('Y'); $month = date('m');
        $relDir = "/uploads/$subdir/$year/$month";
        $absDir = $cfg['upload_dir'] . "/$subdir/$year/$month";
        if (!is_dir($absDir)) mkdir($absDir, 0775, true);

        $hash    = substr(hash('sha1', $file['tmp_name'].microtime(true)), 0, 12);
        $base    = self::slug(pathinfo($file['name'] ?? 'img', PATHINFO_FILENAME)) ?: 'img';
        $relName = "$base-$hash.webp";
        $absMain = "$absDir/$relName";
        $relMain = "$relDir/$relName";

        // Process via best available driver
        if ($cfg['driver'] === 'imagick' && extension_loaded('imagick')) {
            self::processWithImagick($file['tmp_name'], $absMain, $maxW, $quality);
            self::processWithImagick($file['tmp_name'], self::thumbPath($absMain), $thumbW, $quality);
        } else {
            self::processWithGd($file['tmp_name'], $absMain, $maxW, $quality);
            self::processWithGd($file['tmp_name'], self::thumbPath($absMain), $thumbW, $quality);
        }

        // Remove the raw upload
        @unlink($file['tmp_name']);

        $webp_sz = (int) filesize($absMain);
        $ratio   = $orig_sz > 0 ? round(100 - ($webp_sz / $orig_sz) * 100, 2) : 0.0;
        $saved   = max(0, $orig_sz - $webp_sz);

        // Re-read final dimensions
        $newInfo = @getimagesize($absMain);

        $mediaId = Database::i()->insert('media', [
            'path'                => '/assets'.$relMain,
            'original_name'       => substr($file['name'] ?? '', 0, 250),
            'mime'                => 'image/webp',
            'width'               => $newInfo[0] ?? null,
            'height'              => $newInfo[1] ?? null,
            'size_original_bytes' => $orig_sz,
            'size_webp_bytes'     => $webp_sz,
            'compression_ratio'   => $ratio,
            'uploaded_by'         => $userId,
        ]);

        return [
            'media_id'    => (int)$mediaId,
            'path'        => '/assets'.$relMain,
            'thumb_path'  => '/assets'.self::thumbPath($relMain),
            'orig_bytes'  => $orig_sz,
            'webp_bytes'  => $webp_sz,
            'saved_bytes' => $saved,
            'saved_human' => self::humanBytes($saved),
            'ratio'       => $ratio,
            'width'       => $newInfo[0] ?? null,
            'height'      => $newInfo[1] ?? null,
        ];
    }

    private static function processWithGd(string $src, string $dest, int $maxW, int $quality): void
    {
        $info = getimagesize($src);
        [$w, $h] = $info;
        $mime = $info['mime'];

        $im = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($src),
            'image/png'  => imagecreatefrompng($src),
            'image/webp' => imagecreatefromwebp($src),
            'image/gif'  => imagecreatefromgif($src),
            default      => throw new \RuntimeException('Unsupported'),
        };

        if ($w > $maxW) {
            $newW = $maxW;
            $newH = (int)round($h * ($maxW / $w));
            $dst  = imagecreatetruecolor($newW, $newH);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            $tr = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $tr);
            imagecopyresampled($dst, $im, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($im);
            $im = $dst;
        }

        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
        imagewebp($im, $dest, $quality);
        imagedestroy($im);
    }

    private static function processWithImagick(string $src, string $dest, int $maxW, int $quality): void
    {
        $im = new \Imagick($src);
        if ($im->getImageWidth() > $maxW) {
            $im->thumbnailImage($maxW, 0);
        }
        $im->setImageFormat('webp');
        $im->setImageCompressionQuality($quality);
        $im->setOption('webp:method', '6');
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
        $im->writeImage($dest);
        $im->clear();
        $im->destroy();
    }

    private static function thumbPath(string $path): string
    {
        return preg_replace('/\.webp$/', '@thumb.webp', $path) ?? $path;
    }

    private static function humanBytes(int $b): string
    {
        $u = ['B','KB','MB','GB']; $i = 0;
        while ($b >= 1024 && $i < count($u) - 1) { $b /= 1024; $i++; }
        return number_format($b, $b >= 10 || $i === 0 ? 0 : 1).' '.$u[$i];
    }

    private static function slug(string $t): string
    {
        $t = preg_replace('~[^\w\d]+~', '-', $t);
        return trim(strtolower((string)$t), '-');
    }
}
