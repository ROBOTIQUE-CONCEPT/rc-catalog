<?php

declare(strict_types=1);

namespace WPRC\Catalog\Product;

use WPRC\Core\Contracts\Product\ProductContextProviderInterface;
use WPRC\Core\Data\Product\ProductContext;

defined('ABSPATH') || exit;

final class WordPressProductContextProvider implements ProductContextProviderInterface
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function getCurrent(): ?ProductContext
    {
        $product = $this->products->current();

        return $product !== null ? $this->toContext($product) : null;
    }

    public function getById(int $productId): ?ProductContext
    {
        $product = $this->products->find($productId);

        return $product !== null ? $this->toContext($product) : null;
    }

    private function toContext(CatalogProduct $product): ProductContext
    {
        return new ProductContext(
            $product->id(),
            $product->sku(),
            $product->name(),
            $product->externalId() !== '' ? $product->externalId() : null,
            $product->imageUrl() !== '' ? $product->imageUrl() : null,
            $product->permalink() !== '' ? $product->permalink() : null,
            $product->externalSource() !== '' ? $product->externalSource() : null,
            $product->type() !== '' ? $product->type() : null
        );
    }
}
