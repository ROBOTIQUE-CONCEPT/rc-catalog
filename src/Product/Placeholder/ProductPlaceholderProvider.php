<?php

declare(strict_types=1);

namespace WPRC\Catalog\Product\Placeholder;

use WPRC\Catalog\Product\CatalogProduct;
use WPRC\Catalog\Product\ProductRepository;
use WPRC\Core\Contracts\PlaceholderProviderInterface;
use WPRC\Core\Data\Content\PlaceholderContext;

defined('ABSPATH') || exit;

/**
 * Exposes a small, stable set of product placeholders to RC Core.
 *
 * The provider resolves against the product being viewed first, then the
 * current loop/post product. It never performs an ERP request.
 */
final class ProductPlaceholderProvider implements PlaceholderProviderInterface
{
    private const PLACEHOLDERS = [
        'product_id',
        'product_name',
        'product_sku',
        'product_reference',
        'product_designation',
        'product_type',
        'product_brand',
        'product_category',
        'product_url',
        'product_image_url',
        'product_external_id',
        'product_robot_mecanical_unit',
        'product_robot_mechanical_unit',
        'product_robot_electrical_unit',
        'product_robot_yom',
        'product_robot_runtime',
        'product_location_country',
        'product_process',
        'product_last_updated_at',
        'product_robot_payload',
        'product_robot_reach',
        'product_robot_repeatability',
    ];

    /** @var array<int,CatalogProduct|null> */
    private array $resolvedProducts = [];

    public function __construct(private readonly ProductRepository $products)
    {
    }

    /** @return list<string> */
    public function placeholders(): array
    {
        return self::PLACEHOLDERS;
    }

    public function resolve(string $placeholder, PlaceholderContext $context): string|int|float|bool|null
    {
        $product = $this->resolveProduct($context);
        if (!$product instanceof CatalogProduct) {
            return '';
        }

        return match ($placeholder) {
            'product_id' => $product->id(),
            'product_name' => $product->name(),
            'product_sku' => $product->sku(),
            'product_reference' => $product->reference(),
            'product_designation' => $product->designation(),
            'product_type' => $product->type(),
            'product_brand' => $product->brandName(),
            'product_category' => $product->categoryName(),
            'product_url' => $product->permalink(),
            'product_image_url' => $product->imageUrl('full'),
            'product_external_id' => $product->externalId(),
            'product_robot_mecanical_unit',
            'product_robot_mechanical_unit' => $product->robotMechanicalUnit(),
            'product_robot_electrical_unit' => $product->robotElectricalUnit(),
            'product_robot_yom' => $product->robotYearOfManufacture(),
            'product_robot_runtime' => $product->robotRuntime(),
            'product_location_country' => $product->locationCountry(),
            'product_process' => $product->processName(),
            'product_last_updated_at' => $product->lastUpdatedLabel(),
            'product_robot_payload' => $product->robotPayload(),
            'product_robot_reach' => $product->robotReach(),
            'product_robot_repeatability' => $product->robotRepeatability(),
            default => '',
        };
    }

    private function resolveProduct(PlaceholderContext $context): ?CatalogProduct
    {
        $productId = $this->productIdFromContext($context);
        if ($productId <= 0) {
            return null;
        }

        if (!array_key_exists($productId, $this->resolvedProducts)) {
            $this->resolvedProducts[$productId] = $this->products->find($productId);
        }

        return $this->resolvedProducts[$productId];
    }

    private function productIdFromContext(PlaceholderContext $context): int
    {
        // Explicit contexts (for example rc_translate(..., $productId)) are
        // authoritative. This prevents a surrounding Woo loop/global product
        // from leaking into a translation rendered for another product.
        if ($context->postId !== null && $context->postId > 0 && get_post_type($context->postId) === 'product') {
            return $context->postId;
        }

        if (function_exists('is_product') && is_product()) {
            $queriedId = (int) get_queried_object_id();
            if ($queriedId > 0) {
                return $queriedId;
            }
        }

        global $product;
        if ($product instanceof \WC_Product && $product->get_id() > 0) {
            return (int) $product->get_id();
        }

        if ($context->queriedObjectId !== null && $context->queriedObjectId > 0 && get_post_type($context->queriedObjectId) === 'product') {
            return $context->queriedObjectId;
        }

        return 0;
    }
}
