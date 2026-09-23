<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Product;

use WPRC\Catalog\Product\ProductTypes;

defined('ABSPATH') || exit;

final class Types
{
    /** @var array<string,class-string<\WC_Product>> */
    private const PRODUCT_CLASSES = [
        'robot'       => \WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Robot::class,
        'spare'       => \WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Spare::class,
        'cell'        => \WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Cell::class,
        'manipulator' => \WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Manipulator::class,
    ];

    /**
     * Replace the WooCommerce selector with RC Catalog product types.
     *
     * @param array<string,string> $types Existing WooCommerce product types.
     * @return array<string,string>
     */
    public static function filter_product_type_selector(array $types): array
    {
        unset($types);
        return ProductTypes::all();
    }

    public static function filter_product_class(string $classname, string $productType): string
    {
        return self::PRODUCT_CLASSES[$productType] ?? $classname;
    }

    /**
     * Keep the deliberately small RC product-data UI and append the ERP helper.
     *
     * @param array<string,array<string,mixed>> $tabs
     * @return array<string,array<string,mixed>>
     */
    public static function filter_product_tabs(array $tabs): array
    {
        $newTabs = [];

        foreach (['general', 'inventory', 'shipping'] as $key) {
            if (isset($tabs[$key]) && is_array($tabs[$key])) {
                $newTabs[$key] = $tabs[$key];
            }
        }

        $newTabs['wprc_erp_description'] = [
            'label' => __('Description Axonaut', 'rc-catalog'),
            'target' => 'wprc_erp_description_product_data',
            'class' => ['show_if_spare', 'show_if_robot', 'show_if_cell', 'show_if_manipulator'],
            'priority' => 80,
        ];

        return $newTabs;
    }

    /**
     * WooCommerce hides native panels for unknown custom types. Restore only the
     * panels intentionally retained by RC Catalog.
     */
    public static function enqueue_scripts(): void
    {
        $handle = 'wprc-custom-admin-product-tabs';
        wp_register_script($handle, '', ['jquery'], RC_CATALOG_VERSION, false);
        wp_enqueue_script($handle);

        $inline = <<<'JS'
(function($){
    function restoreRcPanels(type) {
        if (['spare', 'robot', 'cell', 'manipulator'].indexOf(type) === -1) {
            return;
        }
        $('.general_tab, .inventory_tab, .shipping_tab').show();
        $('.pricing').show();
    }

    $(document.body).on('woocommerce-product-type-change', function(event, type){
        restoreRcPanels(type);
    });

    restoreRcPanels($('#product-type').val());
})(jQuery);
JS;

        wp_add_inline_script($handle, $inline, 'after');
    }
}
