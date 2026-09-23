<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Settings;

use WPRC\Catalog\Product\ProductTypes;

defined('ABSPATH') || exit;

/**
 * Front-safe access to ERP formatting templates.
 */
final class ErpOptions
{
    public const OPTION_PREFIX = 'wprc_catalog_erp_description_template_';

    public static function templateForType(string $type): string
    {
        $type = sanitize_key($type);
        $value = get_option(self::OPTION_PREFIX . $type, null);

        return is_string($value) ? $value : self::defaultTemplate($type);
    }

    public static function defaultTemplate(string $type): string
    {
        return match ($type) {
            'spare' => '{brand.name} {product.reference} - {product.designation}',
            'robot' => '{brand.name} {robot_model.name} - {controller.name} - S/N {product.serial_number}',
            'cell' => '{product.name}',
            'manipulator' => '{product.name}',
            default => '{product.name}',
        };
    }

    /** @return array<string,string> */
    public static function productTypes(): array
    {
        return ProductTypes::all();
    }
}
