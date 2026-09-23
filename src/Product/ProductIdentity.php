<?php

declare(strict_types=1);

namespace WPRC\Catalog\Product;

defined('ABSPATH') || exit;

/**
 * Stable upstream identity for a projected public product.
 *
 * WordPress post IDs are deliberately excluded: this identity is safe to use
 * across the future `my` / `www` application boundary.
 */
final class ProductIdentity
{
    public function __construct(
        public readonly string $source,
        public readonly string $externalId
    ) {
    }

    public function isValid(): bool
    {
        return $this->source !== '' && $this->externalId !== '';
    }

    public function key(): string
    {
        return $this->isValid() ? $this->source . ':' . $this->externalId : '';
    }
}
