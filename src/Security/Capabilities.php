<?php

declare(strict_types=1);

namespace WPRC\Catalog\Security;

use WPRC\Core\Security\Capabilities\CapabilityRegistry;

defined('ABSPATH') || exit;

/**
 * Catalog-owned capability declarations and temporary legacy authorization
 * bridge.
 *
 * New RC roles will receive the `rc_catalog_*` capabilities through RC Core.
 * Existing WooCommerce capabilities remain accepted during the monolithic
 * migration so RC Catalog 1.7 does not alter production access rights.
 */
final class Capabilities
{
    public const MANAGE = 'rc_catalog_manage';
    public const MANAGE_PRODUCTS = 'rc_catalog_manage_products';
    public const MANAGE_ROBOT_MODELS = 'rc_catalog_manage_robot_models';
    public const MANAGE_CONTROLLERS = 'rc_catalog_manage_controllers';

    /** @return string[] */
    public static function all(): array
    {
        return [
            self::MANAGE,
            self::MANAGE_PRODUCTS,
            self::MANAGE_ROBOT_MODELS,
            self::MANAGE_CONTROLLERS,
        ];
    }

    public static function register(CapabilityRegistry $registry): void
    {
        $registry->register('catalog', self::all());
    }

    public static function canManageControllers(): bool
    {
        return current_user_can(self::MANAGE_CONTROLLERS)
            || current_user_can(self::MANAGE)
            || current_user_can('manage_woocommerce');
    }

    public static function canEditRobotModels(): bool
    {
        return current_user_can(self::MANAGE_ROBOT_MODELS)
            || current_user_can(self::MANAGE)
            || current_user_can('edit_products');
    }

    public static function canLookupErpData(): bool
    {
        return current_user_can(self::MANAGE_PRODUCTS)
            || current_user_can(self::MANAGE)
            || current_user_can('edit_products')
            || current_user_can('manage_woocommerce');
    }

    public static function canManageProductTerms(): bool
    {
        return current_user_can(self::MANAGE_PRODUCTS)
            || current_user_can(self::MANAGE)
            || current_user_can('manage_product_terms')
            || current_user_can('manage_woocommerce');
    }
}
