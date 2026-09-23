<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce;

use WPRC\Catalog\WooCommerce\Product\Ajax as ProductAjax;
use WPRC\Catalog\WooCommerce\Product\CustomFields;
use WPRC\Catalog\WooCommerce\Product\ProductSaveHandler;
use WPRC\Catalog\WooCommerce\Product\Search as ProductSearch;
use WPRC\Catalog\WooCommerce\Product\Types as ProductTypes;
use WPRC\Catalog\WooCommerce\Settings\SettingsIntegration;
use WPRC\Catalog\WooCommerce\Taxonomy\ProductBrand;
use WPRC\Catalog\WooCommerce\Taxonomy\ProductCat;
use WPRC\Core\Contracts\BootableInterface;

defined('ABSPATH') || exit;

final class WooCommerceIntegration implements BootableInterface
{
    public function __construct(
        private readonly ProductSaveHandler $productSaveHandler,
        private readonly ProductAjax $productAjax,
        private readonly CustomFields $customFields,
        private readonly ProductSearch $productSearch,
        private readonly CatalogMode $catalogMode,
        private readonly SettingsIntegration $settings
    ) {
    }

    public function init(): void
    {
        $this->registerCommonHooks();

        if ($this->isAdminContext()) {
            $this->registerAdminHooks();
        }

        if ($this->isFrontContext()) {
            $this->registerFrontHooks();
        }
    }

    private function registerCommonHooks(): void
    {
        add_action('generate_rewrite_rules', [Rewrite::class, 'generate_rewrite_rules'], 10, 1);
        add_filter('term_link', [Rewrite::class, 'term_link'], 10, 3);
        add_action('created_product_cat', [Rewrite::class, 'flush_rewrite_rules'], 10, 1);
        add_action('edited_product_cat', [Rewrite::class, 'flush_rewrite_rules'], 10, 1);
        add_action('delete_product_cat', [Rewrite::class, 'flush_rewrite_rules'], 10, 1);
        add_filter('post_type_link', [Rewrite::class, 'post_type_link'], 10, 2);

        add_filter('woocommerce_product_class', [ProductTypes::class, 'filter_product_class'], 5, 2);
        $this->catalogMode->init();
        $this->settings->init();
    }

    private function registerAdminHooks(): void
    {
        add_action('admin_menu', [Unbloat::class, 'cleanup_admin_menu'], 150);
        add_filter('woocommerce_admin_features', [Unbloat::class, 'cleanup_admin_features'], 10, 1);

        $this->customFields->init();
        $this->productAjax->init();
        add_action('woocommerce_admin_process_product_object', [$this->productSaveHandler, 'save'], 50);

        add_action('product_cat_add_form_fields', [ProductCat::class, 'render_menu_title_add'], 10, 2);
        add_action('product_cat_edit_form_fields', [ProductCat::class, 'render_menu_title_edit'], 10, 2);
        add_action('created_product_cat', [ProductCat::class, 'save_metas'], 10, 2);
        add_action('edited_product_cat', [ProductCat::class, 'save_metas'], 10, 2);

        add_action('product_brand_add_form_fields', [ProductBrand::class, 'render_olp_softwares_add'], 10, 2);
        add_action('product_brand_edit_form_fields', [ProductBrand::class, 'render_olp_softwares_edit'], 10, 2);
        add_action('product_brand_edit_form_fields', [ProductBrand::class, 'render_additional_services_edit'], 10, 2);
        add_action('product_brand_edit_form_fields', [ProductBrand::class, 'render_controllers_edit'], 20, 2);
        add_action('created_product_brand', [ProductBrand::class, 'save_metas'], 10, 2);
        add_action('edited_product_brand', [ProductBrand::class, 'save_metas'], 10, 2);

        add_filter('product_type_selector', [ProductTypes::class, 'filter_product_type_selector'], 10, 1);
        add_action('woocommerce_product_data_panels', [ProductTypes::class, 'enqueue_scripts'], 10);
        add_filter('woocommerce_product_data_tabs', [ProductTypes::class, 'filter_product_tabs'], 20, 1);
    }

    private function registerFrontHooks(): void
    {
        // Business behavior remains in Catalog; presentation is owned by the theme.
        $this->productSearch->init();
    }

    private function isAdminContext(): bool
    {
        return is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax());
    }

    private function isFrontContext(): bool
    {
        return !is_admin() && !(function_exists('wp_doing_ajax') && wp_doing_ajax());
    }
}
