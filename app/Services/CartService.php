<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;

/**
 * Server-side cart kept in PHP session. We never trust prices/stock from
 * the client; everything is recomputed against the DB.
 */
final class CartService
{
    private const KEY = 'lh_cart';

    public static function items(): array
    {
        return $_SESSION[self::KEY] ?? [];
    }

    public static function count(): int
    {
        $n = 0;
        foreach (self::items() as $it) $n += (int)$it['qty'];
        return $n;
    }

    public static function add(int $productId, ?int $variationId, int $qty = 1): void
    {
        $key = $productId.'-'.((int)$variationId);
        $cart = self::items();
        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qty;
        } else {
            $cart[$key] = ['product_id'=>$productId,'variation_id'=>$variationId,'qty'=>$qty];
        }
        $_SESSION[self::KEY] = $cart;
    }

    public static function update(string $key, int $qty): void
    {
        $cart = self::items();
        if (!isset($cart[$key])) return;
        if ($qty <= 0) unset($cart[$key]);
        else $cart[$key]['qty'] = $qty;
        $_SESSION[self::KEY] = $cart;
    }

    public static function remove(string $key): void
    {
        $cart = self::items();
        unset($cart[$key]);
        $_SESSION[self::KEY] = $cart;
    }

    public static function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }

    /**
     * Hydrate cart with live pricing/stock and return a structured snapshot.
     * @return array{items: array<int,array<string,mixed>>, subtotal: float}
     */
    public static function hydrate(): array
    {
        $items = [];
        $subtotal = 0.0;
        foreach (self::items() as $key => $row) {
            $product = Database::i()->one(
                'SELECT id, name, slug, sku, price, compare_at_price, has_variations,
                        track_inventory, stock_qty, status, featured_image
                   FROM products WHERE id = ? LIMIT 1', [$row['product_id']]);
            if (!$product || $product['status'] !== 'active') continue;

            $variation = null;
            if (!empty($row['variation_id'])) {
                $variation = Database::i()->one(
                    'SELECT id, sku, option_summary, price, stock_qty, image, is_active
                       FROM product_variations WHERE id = ? LIMIT 1', [$row['variation_id']]);
                if (!$variation || !$variation['is_active']) continue;
            }

            $unitPrice = (float)($variation['price'] ?? $product['price']);
            $qty       = (int)$row['qty'];
            $line      = $unitPrice * $qty;
            $subtotal += $line;

            $items[$key] = [
                'key'           => $key,
                'product_id'    => (int)$product['id'],
                'variation_id'  => $variation ? (int)$variation['id'] : null,
                'sku'           => $variation['sku'] ?? $product['sku'],
                'name'          => $product['name'],
                'slug'          => $product['slug'],
                'option_summary'=> $variation['option_summary'] ?? null,
                'image'         => $variation['image'] ?? $product['featured_image'],
                'unit_price'    => $unitPrice,
                'compare_at_price' => $product['compare_at_price'] ? (float)$product['compare_at_price'] : null,
                'qty'           => $qty,
                'line_total'    => $line,
                'stock_qty'     => $variation ? (int)$variation['stock_qty'] : (int)$product['stock_qty'],
                'track_inventory' => (int)$product['track_inventory'],
            ];
        }
        return ['items' => $items, 'subtotal' => $subtotal];
    }
}
