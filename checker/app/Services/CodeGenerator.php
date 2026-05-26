<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Cryptographically-random alphanumeric code generator.
 *
 * Uses Crockford-style alphabet (no I, O, 0, 1, U) so printed codes
 * are unambiguous even with bad scanners or smudged thermal labels.
 */
final class CodeGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTVWXYZ23456789';
    private const ALPHA_LEN = 30;

    /**
     * Generate one code of N alphanumeric characters, optionally prefixed.
     * Default: ELH-XXXX-XXXX-XXXX  (4x4-char groups)
     */
    public static function one(string $prefix = 'ELH', int $groups = 3, int $size = 4): string
    {
        $parts = [];
        for ($g = 0; $g < $groups; $g++) {
            $chunk = '';
            for ($i = 0; $i < $size; $i++) {
                $chunk .= self::ALPHABET[random_int(0, self::ALPHA_LEN - 1)];
            }
            $parts[] = $chunk;
        }
        return ($prefix !== '' ? $prefix . '-' : '') . implode('-', $parts);
    }

    /**
     * Generate $count unique codes. Returns the array (callers handle DB writes).
     */
    public static function batch(int $count, string $prefix = 'ELH', int $groups = 3, int $size = 4): array
    {
        $count = max(1, min($count, 100000)); // hard ceiling
        $set = [];
        while (count($set) < $count) {
            $set[self::one($prefix, $groups, $size)] = true;
        }
        return array_keys($set);
    }
}
