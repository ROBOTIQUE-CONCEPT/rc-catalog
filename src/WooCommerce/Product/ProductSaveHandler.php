<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Product;

use WC_Product;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRelationRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRepository;
use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Abstract;
use WPRC\Core\ERP\ProviderRegistry;

defined('ABSPATH') || exit;

/**
 * Persist only Catalog-owned, locally edited product data.
 *
 * This handler deliberately performs no ERP reads, no remote hydration and no
 * computed synchronization. External entities are selected through AJAX in the
 * editor and only their stable external IDs plus lightweight display labels are
 * stored locally.
 */
final class ProductSaveHandler
{
    public function __construct(
        private readonly RobotModelRepository $models,
        private readonly ControllerRepository $controllers,
        private readonly RobotModelRelationRepository $relations,
        private readonly ProviderRegistry $erp
    ) {
    }

    public function save(WC_Product $product): void
    {
        if (!is_admin() || !current_user_can('edit_product', $product->get_id())) {
            return;
        }

        $this->saveLocalFields($product);
        $this->saveErpIdentity($product);
        $this->saveRelationLabels($product);
        $this->saveRobotSelection($product);
        $this->projectLocalSpareFields($product);
    }

    private function saveLocalFields(WC_Product $product): void
    {
        $map = [
            WC_Product_Abstract::META_ERP_ID => ['sanitize_text_field', ['robot', 'spare', 'cell', 'manipulator']],
            WC_Product_Abstract::META_YOM => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_RUNTIME => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_SW_VERSION => ['sanitize_text_field', ['robot']],
            WC_Product_Abstract::META_CABLE_LENGTH => ['sanitize_text_field', ['robot']],
            WC_Product_Abstract::META_RETAILER_ID => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_LOCATION_ID => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_LOCATION_ADDRESS_ID => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_LOCATION_ADDRESS_ZIP => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_LOCATION_ADDRESS_COUNTRY => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_LOCATION_ADDRESS_COUNTRY_CODE => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_ORIGIN_COUNTRY => ['sanitize_text_field', ['spare']],
            WC_Product_Abstract::META_HS_CODE => ['sanitize_text_field', ['spare']],
            WC_Product_Abstract::META_DESIGNATION => ['sanitize_text_field', ['spare']],
            WC_Product_Abstract::META_REFERENCE => ['sanitize_text_field', ['spare']],
            WC_Product_Abstract::META_YOUTUBE_URL => ['sanitize_text_field', ['robot', 'cell', 'manipulator']],
            WC_Product_Abstract::META_PAGE_TITLE => ['sanitize_text_field', ['cell', 'manipulator']],
            WC_Product_Abstract::META_ERP_DESCRIPTION_TEMPLATE => ['sanitize_textarea_field', ['robot', 'spare', 'cell', 'manipulator']],
            WC_Product_Abstract::META_SHIPPING_ERP_SOURCE => ['sanitize_key', ['robot']],
            WC_Product_Abstract::META_SHIPPING_ERP_ID => ['sanitize_text_field', ['robot']],
            WC_Product_Abstract::META_SHIPPING_STANDARD_ERP_SOURCE => ['sanitize_key', ['spare']],
            WC_Product_Abstract::META_SHIPPING_STANDARD_ERP_ID => ['sanitize_text_field', ['spare']],
            WC_Product_Abstract::META_SHIPPING_EXPRESS_ERP_SOURCE => ['sanitize_key', ['spare']],
            WC_Product_Abstract::META_SHIPPING_EXPRESS_ERP_ID => ['sanitize_text_field', ['spare']],
        ];

        foreach ($map as $metaKey => [$sanitizer, $productTypes]) {
            if (!isset($_POST[$metaKey]) || !in_array($product->get_type(), $productTypes, true)) {
                continue;
            }

            $raw = wp_unslash($_POST[$metaKey]);
            $value = is_callable($sanitizer) ? call_user_func($sanitizer, $raw) : $raw;
            $product->update_meta_data($metaKey, $value);
        }
    }


    /**
     * Persist the provider alongside the external product ID.
     *
     * Earlier Catalog versions stored only the external ID because the site had
     * a single ERP. The explicit source makes the mapping safe for Axonaut →
     * Odoo migration and for future cross-site projections.
     */
    private function saveErpIdentity(WC_Product $product): void
    {
        if (!isset($_POST[WC_Product_Abstract::META_ERP_ID])) {
            return;
        }

        $externalId = trim(sanitize_text_field(wp_unslash((string) $_POST[WC_Product_Abstract::META_ERP_ID])));
        $product->update_meta_data(
            WC_Product_Abstract::META_ERP_SOURCE,
            $externalId !== '' ? sanitize_key($this->erp->activeSource()) : ''
        );
    }

    private function saveRelationLabels(WC_Product $product): void
    {
        $labels = [
            WC_Product_Abstract::META_ERP_ID => WC_Product_Abstract::META_ERP_LABEL,
            WC_Product_Abstract::META_RETAILER_ID => WC_Product_Abstract::META_RETAILER_NAME,
            WC_Product_Abstract::META_LOCATION_ID => WC_Product_Abstract::META_LOCATION_LABEL,
            WC_Product_Abstract::META_LOCATION_ADDRESS_ID => WC_Product_Abstract::META_LOCATION_ADDRESS_LABEL,
            WC_Product_Abstract::META_SHIPPING_ERP_ID => WC_Product_Abstract::META_SHIPPING_ERP_LABEL,
            WC_Product_Abstract::META_SHIPPING_STANDARD_ERP_ID => WC_Product_Abstract::META_SHIPPING_STANDARD_ERP_LABEL,
            WC_Product_Abstract::META_SHIPPING_EXPRESS_ERP_ID => WC_Product_Abstract::META_SHIPPING_EXPRESS_ERP_LABEL,
        ];

        foreach ($labels as $idKey => $labelKey) {
            if (!isset($_POST[$idKey])) {
                continue;
            }

            $id = trim(sanitize_text_field(wp_unslash((string) $_POST[$idKey])));
            $postedLabel = isset($_POST[$idKey . '__label'])
                ? sanitize_text_field(wp_unslash((string) $_POST[$idKey . '__label']))
                : '';

            $product->update_meta_data($labelKey, $id !== '' ? $postedLabel : '');
        }
    }

    private function saveRobotSelection(WC_Product $product): void
    {
        if ($product->get_type() !== 'robot') {
            return;
        }

        $modelId = isset($_POST[WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID])
            ? absint(wp_unslash((string) $_POST[WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID]))
            : 0;
        $controllerId = isset($_POST[WC_Product_Abstract::META_ROBOT_CONTROLLER_ID])
            ? absint(wp_unslash((string) $_POST[WC_Product_Abstract::META_ROBOT_CONTROLLER_ID]))
            : 0;

        $model = $modelId > 0 ? $this->models->find($modelId) : null;
        if ($model === null) {
            $modelId = 0;
        }

        $controller = $controllerId > 0 ? $this->controllers->find($controllerId) : null;
        if ($controllerId > 0 && (
            $modelId <= 0
            || $controller === null
            || !$this->relations->controllerIsCompatible($modelId, $controllerId)
        )) {
            $controllerId = 0;
            $controller = null;
            if (class_exists('WC_Admin_Meta_Boxes') && is_callable(['WC_Admin_Meta_Boxes', 'add_error'])) {
                \WC_Admin_Meta_Boxes::add_error(__('Le contrôleur sélectionné n’est pas compatible avec le modèle de robot.', 'rc-catalog'));
            }
        }

        $product->update_meta_data(WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID, $modelId > 0 ? $modelId : '');
        $product->update_meta_data(WC_Product_Abstract::META_ROBOT_MODEL_PUBLIC_ID, $model?->publicId ?? '');
        $product->update_meta_data(WC_Product_Abstract::META_ROBOT_CONTROLLER_ID, $controllerId > 0 ? $controllerId : '');
        $product->update_meta_data(WC_Product_Abstract::META_ROBOT_CONTROLLER_UID, $controller?->uid ?? '');
    }

    /**
     * Keep the current public getters coherent until the dedicated post-meta
     * cleanup pass replaces historical aliases with canonical local fields.
     */
    private function projectLocalSpareFields(WC_Product $product): void
    {
        if ($product->get_type() !== 'spare') {
            return;
        }

        if (isset($_POST[WC_Product_Abstract::META_HS_CODE])) {
            $product->update_meta_data(
                WC_Product_Abstract::META_TARIFF_CODE,
                sanitize_text_field(wp_unslash((string) $_POST[WC_Product_Abstract::META_HS_CODE]))
            );
        }

        if (isset($_POST[WC_Product_Abstract::META_ORIGIN_COUNTRY])) {
            $product->update_meta_data(
                WC_Product_Abstract::META_COUNTRY_OF_ORIGIN,
                sanitize_text_field(wp_unslash((string) $_POST[WC_Product_Abstract::META_ORIGIN_COUNTRY]))
            );
        }
    }
}
