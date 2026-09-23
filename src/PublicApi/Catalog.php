<?php

declare(strict_types=1);

namespace WPRC\Catalog\PublicApi;

use WPRC\Catalog\Helpers\RobotProcessPageResolver;

use WPRC\Catalog\Product\CatalogProduct;
use WPRC\Catalog\Product\ProductRepository;
use WPRC\Catalog\Product\ProductTypes;
use WPRC\Catalog\RobotModel\Application\RobotModels;

defined('ABSPATH') || exit;

/**
 * Stable API exposed by RC Catalog to the active theme and independent plugins.
 *
 * Consumers should prefer this facade over reading WPRC post meta directly.
 */
final class Catalog
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly RobotModels $robotModels
    ) {
    }

    public function product(int $productId): ?CatalogProduct
    {
        return $this->products->find($productId);
    }

    public function currentProduct(): ?CatalogProduct
    {
        return $this->products->current();
    }

    public function products(): ProductRepository
    {
        return $this->products;
    }

    /**
     * Return the canonical Robotique Concept product types.
     *
     * @return array<string,string> Product type slug => French UI label.
     */
    public function productTypes(): array
    {
        return ProductTypes::all();
    }

    /**
     * Resolve the canonical RC product type for a WordPress product post ID.
     *
     * Returns an empty string when the post is not a product or when its type
     * is not one of the RC Catalog product types.
     */
    public function productTypeById(int $productId): string
    {
        return $this->products->typeById($productId);
    }


    /**
     * Resolve an application page for a robot-process slug in the current language.
     */
    public function processPageBySlug(string $processSlug): ?\WP_Post
    {
        return RobotProcessPageResolver::find_page_by_process_slug($processSlug);
    }

    /**
     * Robot-model technical repository facade owned by RC Catalog.
     */
    public function robotModels(): RobotModels
    {
        return $this->robotModels;
    }

}
