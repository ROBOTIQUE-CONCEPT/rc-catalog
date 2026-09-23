<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce;

defined('ABSPATH') || exit;

/**
 * Enforce the public WooCommerce installation as a non-transactional catalog.
 *
 * Product browsing and presentation remain native WooCommerce concerns, while
 * cart, checkout and purchasing capabilities are disabled at the business-rule
 * layer instead of merely being hidden by the theme.
 */
final class CatalogMode
{
    /**
     * Register catalog-mode hooks before WooCommerce loads the cart session.
     */
    public function init(): void
    {
        add_filter('woocommerce_is_purchasable', [$this, 'disablePurchases'], PHP_INT_MAX, 2);
        add_filter('woocommerce_variation_is_purchasable', [$this, 'disablePurchases'], PHP_INT_MAX, 2);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'rejectAddToCart'], PHP_INT_MAX, 1);
        add_filter('woocommerce_coupons_enabled', '__return_false', PHP_INT_MAX);
        add_filter('woocommerce_cart_session_initialize', [$this, 'filterCartSessionInitialization'], PHP_INT_MAX, 2);

        add_action('template_redirect', [$this, 'redirectTransactionalPages'], 1);
        add_action('wp_enqueue_scripts', [$this, 'dequeueTransactionalAssets'], 999);
    }

    /**
     * WooCommerce products are never purchasable on the public RC catalog.
     */
    public function disablePurchases(bool $purchasable, \WC_Product $product): bool
    {
        return false;
    }

    /**
     * Final server-side guard for classic, AJAX and compatible add-to-cart flows.
     */
    public function rejectAddToCart(bool $valid): bool
    {
        return false;
    }

    /**
     * Do not initialize the WooCommerce cart persistence layer on catalog pages.
     *
     * My Account is deliberately excluded from this optimization: it remains a
     * native WooCommerce surface and must not be altered by RC Catalog.
     *
     * @param mixed $session WC_Cart_Session instance supplied by WooCommerce.
     */
    public function filterCartSessionInitialization(bool $initialize, mixed $session = null): bool
    {
        if ($this->mustPreserveNativeAccountRuntime()) {
            return $initialize;
        }

        return false;
    }

    /**
     * Redirect obsolete transactional pages to the product catalog.
     */
    public function redirectTransactionalPages(): void
    {
        if ($this->mustPreserveNativeAccountRuntime()) {
            return;
        }

        if (isset($_GET['add-to-cart'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only cleanup of an obsolete public commerce URL.
            $productId = absint(wp_unslash((string) $_GET['add-to-cart'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $target = $productId > 0 ? get_permalink($productId) : '';
            if (!is_string($target) || $target === '') {
                $target = remove_query_arg('add-to-cart');
            }

            wp_safe_redirect($target, 302, 'RC Catalog');
            exit;
        }

        $isCart = function_exists('is_cart') && is_cart();
        $isCheckout = function_exists('is_checkout') && is_checkout();

        if (!$isCart && !$isCheckout) {
            return;
        }

        $target = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '';
        if (!is_string($target) || $target === '') {
            $target = home_url('/');
        }

        wp_safe_redirect($target, 302, 'RC Catalog');
        exit;
    }

    /**
     * Remove only transaction-specific scripts. Product/gallery/filter assets
     * remain untouched to preserve the browsing experience.
     */
    public function dequeueTransactionalAssets(): void
    {
        if ($this->mustPreserveNativeAccountRuntime()) {
            return;
        }

        foreach (['wc-cart-fragments', 'wc-add-to-cart', 'wc-cart', 'wc-checkout'] as $handle) {
            wp_dequeue_script($handle);
        }
    }

    /**
     * Keep WooCommerce's My Account runtime strictly native.
     */
    private function mustPreserveNativeAccountRuntime(): bool
    {
        if (is_admin()) {
            return true;
        }

        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return true;
        }

        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return true;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        if (function_exists('is_account_page') && did_action('wp') > 0 && is_account_page()) {
            return true;
        }

        return $this->requestMatchesMyAccountPage();
    }

    /**
     * Detect My Account before the main query exists (cart session initializes
     * early during WordPress init). Polylang translations are included when
     * available.
     */
    private function requestMatchesMyAccountPage(): bool
    {
        if (!function_exists('wc_get_page_id')) {
            return false;
        }

        $pageId = (int) wc_get_page_id('myaccount');
        if ($pageId <= 0 || empty($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $pageIds = [$pageId];
        if (function_exists('pll_get_post_translations')) {
            $translations = pll_get_post_translations($pageId);
            if (is_array($translations)) {
                foreach ($translations as $translationId) {
                    $translationId = (int) $translationId;
                    if ($translationId > 0) {
                        $pageIds[] = $translationId;
                    }
                }
            }
        }

        $requestUri = wp_unslash((string) $_SERVER['REQUEST_URI']);
        $requestPath = wp_parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($requestPath) || $requestPath === '') {
            return false;
        }

        $requestPath = trailingslashit('/' . ltrim($requestPath, '/'));

        foreach (array_unique($pageIds) as $candidateId) {
            $permalink = get_permalink($candidateId);
            if (!is_string($permalink) || $permalink === '') {
                continue;
            }

            $accountPath = wp_parse_url($permalink, PHP_URL_PATH);
            if (!is_string($accountPath) || $accountPath === '' || $accountPath === '/') {
                continue;
            }

            $accountPath = trailingslashit('/' . ltrim($accountPath, '/'));
            if (str_starts_with($requestPath, $accountPath)) {
                return true;
            }
        }

        return false;
    }
}
