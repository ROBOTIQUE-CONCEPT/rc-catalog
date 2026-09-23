<?php

declare(strict_types=1);

namespace WPRC\Catalog\Product;

use WPRC\Catalog\RobotModel\Application\RobotModels;

use function get_post_type;
use function sanitize_key;

defined('ABSPATH') || exit;

final class ProductRepository
{
    public function __construct(
        private readonly RobotModels $robotModels
    ) {
    }

    public function find(int $productId): ?CatalogProduct
    {
        if ($productId <= 0 || !function_exists('wc_get_product')) {
            return null;
        }

        $product = wc_get_product($productId);
        if (!$product instanceof \WC_Product) {
            return null;
        }

        return new CatalogProduct($product, $this->robotModels);
    }

    public function current(): ?CatalogProduct
    {
        if (!function_exists('is_product') || !is_product()) {
            return null;
        }

        return $this->find((int) get_queried_object_id());
    }

    /**
     * Resolve the canonical RC WooCommerce product type for a product post.
     *
     * The product object is authoritative here. We deliberately do not inspect
     * the `product_type` taxonomy directly: the concrete RC product class is
     * checked first, then the product object's `get_type()` value is used as a
     * validated fallback. This keeps consumers decoupled from taxonomy storage.
     */
    public function typeById(int $productId): string
    {
        if (
            $productId <= 0
            || get_post_type($productId) !== 'product'
            || !function_exists('wc_get_product')
        ) {
            return '';
        }

        $product = wc_get_product($productId);
        if (!$product instanceof \WC_Product) {
            return '';
        }

        $classMap = [
            \WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Robot::class       => 'robot',
            \WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Spare::class       => 'spare',
            \WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Cell::class        => 'cell',
            \WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Manipulator::class => 'manipulator',
        ];

        foreach ($classMap as $className => $type) {
            if ($product instanceof $className) {
                return $type;
            }
        }

        $type = sanitize_key((string) $product->get_type());

        return ProductTypes::exists($type) ? $type : '';
    }
}
