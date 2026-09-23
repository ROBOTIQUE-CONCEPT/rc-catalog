<?php

declare(strict_types=1);

namespace WPRC\Catalog\Product;

defined('ABSPATH') || exit;

/**
 * Canonical list of Robotique Concept WooCommerce product types.
 *
 * This registry belongs to RC Catalog because product types are business data.
 * Theme integrations must consume it through the public Catalog API.
 */
final class ProductTypes
{
    /**
     * @return array<string,string> Product type slug => French UI label.
     */
    public static function all(): array
    {
        return [
            'robot'       => 'Robot industriel',
            'spare'       => 'Pièce détachée',
            'cell'        => 'Cellule robotisée',
            'manipulator' => 'Manipulateur',
        ];
    }

    public static function exists(string $type): bool
    {
        return array_key_exists($type, self::all());
    }

    public static function label(string $type): string
    {
        return self::all()[$type] ?? $type;
    }
}
