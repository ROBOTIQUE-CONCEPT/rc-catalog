<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce;

use WPRC\Core\Runtime\RequestContext as CoreRequestContext;

defined('ABSPATH') || exit;

/**
 * WooCommerce-specific runtime predicates layered on top of RC Core context.
 *
 * Generic WordPress request detection is owned by RC Core. Catalog only keeps
 * the WooCommerce query predicates that are specific to this module.
 */
final class RequestContext
{
    public function __construct(private readonly CoreRequestContext $core)
    {
    }

    public function isAdmin(): bool
    {
        return $this->core->isAdmin();
    }

    public function isAjax(): bool
    {
        return $this->core->isAjax();
    }

    public function isRest(): bool
    {
        if ($this->core->isRest()) {
            return true;
        }

        // Compatibility guard for early plugin bootstrap before REST_REQUEST is
        // defined. This may disappear once Core owns URI-level REST detection.
        if (!isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $requestUri = (string) $_SERVER['REQUEST_URI'];
        $restPrefix = function_exists('rest_get_url_prefix') ? '/' . rest_get_url_prefix() . '/' : '/wp-json/';

        return str_contains($requestUri, $restPrefix);
    }

    public function isCli(): bool
    {
        return $this->core->isCli();
    }

    public function isFront(): bool
    {
        return !$this->isAdmin()
            && !$this->isAjax()
            && !$this->isRest()
            && !$this->isCli()
            && !$this->core->isCron();
    }

    public function isWooShop(): bool
    {
        return $this->isFrontQueryReady() && function_exists('is_shop') && is_shop();
    }

    public function isWooSingleProduct(): bool
    {
        return $this->isFrontQueryReady() && function_exists('is_product') && is_product();
    }

    public function isWooTaxonomy(): bool
    {
        return $this->isFrontQueryReady() && function_exists('is_product_taxonomy') && is_product_taxonomy();
    }

    public function isWooArchive(): bool
    {
        return $this->isWooShop() || $this->isWooTaxonomy();
    }

    public function isWooCart(): bool
    {
        return $this->isFrontQueryReady() && function_exists('is_cart') && is_cart();
    }

    public function isWooCheckout(): bool
    {
        return $this->isFrontQueryReady() && function_exists('is_checkout') && is_checkout();
    }

    public function isWooAccount(): bool
    {
        return $this->isFrontQueryReady() && function_exists('is_account_page') && is_account_page();
    }

    public function isWooContext(): bool
    {
        if ($this->isFrontQueryReady() && function_exists('is_woocommerce') && is_woocommerce()) {
            return true;
        }

        return $this->isWooSingleProduct()
            || $this->isWooArchive()
            || $this->isWooCart()
            || $this->isWooCheckout()
            || $this->isWooAccount();
    }

    private function isFrontQueryReady(): bool
    {
        return did_action('wp') > 0;
    }
}
