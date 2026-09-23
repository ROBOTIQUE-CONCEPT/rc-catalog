<?php

declare(strict_types=1);

namespace WPRC\Catalog\Product;

use WPRC\Catalog\RobotModel\Application\RobotModels;
use WPRC\Catalog\RobotModel\Domain\Controller;
use WPRC\Catalog\RobotModel\Domain\RobotModel;
use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Abstract;

defined('ABSPATH') || exit;

final class CatalogProduct
{
    public function __construct(
        private readonly \WC_Product $product,
        private readonly RobotModels $robotModels
    ) {
    }

    public function woo(): \WC_Product
    {
        return $this->product;
    }

    public function id(): int
    {
        return $this->product->get_id();
    }

    public function type(): string
    {
        return $this->product->get_type();
    }

    public function sku(): string
    {
        return (string) $this->product->get_sku();
    }

    public function name(): string
    {
        return (string) $this->product->get_name();
    }

    public function reference(): string
    {
        if (is_callable([$this->product, 'get_reference'])) {
            return trim((string) $this->product->get_reference());
        }

        return trim((string) $this->meta('wprc_product_reference', ''));
    }

    public function designation(): string
    {
        if (is_callable([$this->product, 'get_designation'])) {
            return trim((string) $this->product->get_designation());
        }

        return trim((string) $this->meta('wprc_product_designation', ''));
    }

    public function permalink(): string
    {
        $url = get_permalink($this->id());

        return is_string($url) ? $url : '';
    }

    public function imageUrl(string $size = 'medium'): string
    {
        $imageId = (int) $this->product->get_image_id();
        if ($imageId <= 0) {
            return '';
        }

        $url = wp_get_attachment_image_url($imageId, $size);

        return is_string($url) ? $url : '';
    }

    public function externalSource(): string
    {
        $source = trim((string) $this->product->get_meta(WC_Product_Abstract::META_ERP_SOURCE, true));

        if ($source !== '') {
            return sanitize_key($source);
        }

        // Compatibility fallback for products created before Catalog 1.7.
        $externalId = $this->externalId();
        return $externalId !== '' ? sanitize_key(rc_core()->erp()->activeSource()) : '';
    }

    public function externalId(): string
    {
        return trim((string) $this->product->get_meta(WC_Product_Abstract::META_ERP_ID, true));
    }

    public function identity(): ?ProductIdentity
    {
        $identity = new ProductIdentity($this->externalSource(), $this->externalId());

        return $identity->isValid() ? $identity : null;
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        $value = $this->product->get_meta($key, true);

        return ($value === '' || $value === null) ? $default : $value;
    }

    public function brandName(): string
    {
        if (is_callable([$this->product, 'get_brand_name'])) {
            return trim((string) $this->product->get_brand_name());
        }

        $terms = get_the_terms($this->id(), 'product_brand');
        if (!$terms || is_wp_error($terms)) {
            return '';
        }

        return (string) reset($terms)->name;
    }

    public function categoryName(): string
    {
        if (is_callable([$this->product, 'get_category_name'])) {
            return trim((string) $this->product->get_category_name());
        }

        $terms = get_the_terms($this->id(), 'product_cat');
        if (!$terms || is_wp_error($terms)) {
            return '';
        }

        return (string) reset($terms)->name;
    }

    /**
     * Mechanical unit model for robot products only.
     */
    public function robotMechanicalUnit(): string
    {
        if ($this->type() !== 'robot') {
            return '';
        }

        $model = $this->robotModel();
        if ($model) {
            return trim((string) ($model->title ?: $model->reference));
        }
        if (is_callable([$this->product, 'get_robot_model_name'])) {
            return trim((string) $this->product->get_robot_model_name());
        }
        return trim((string) $this->meta('_wprc_product_robot_model', ''));
    }

    /**
     * Electrical/controller unit model for robot products only.
     */
    public function robotElectricalUnit(): string
    {
        if ($this->type() !== 'robot') {
            return '';
        }

        $controller = $this->robotController();
        if ($controller) {
            return trim($controller->name . ($controller->reference ? ' — ' . $controller->reference : ''));
        }
        if (is_callable([$this->product, 'get_cabinet_model_name'])) {
            return trim((string) $this->product->get_cabinet_model_name());
        }
        return trim((string) $this->meta('_wprc_product_cabinet_model', ''));
    }


    public function robotYearOfManufacture(): ?int
    {
        if ($this->type() !== 'robot') {
            return null;
        }

        if (is_callable([$this->product, 'get_yom'])) {
            $year = (int) $this->product->get_yom();
            return $year > 0 ? $year : null;
        }

        $year = (int) $this->meta('wprc_product_yom', 0);
        return $year > 0 ? $year : null;
    }

    public function robotRuntime(): ?float
    {
        if ($this->type() !== 'robot') {
            return null;
        }

        if (is_callable([$this->product, 'get_runtime'])) {
            $runtime = (float) $this->product->get_runtime();
            return $runtime > 0 ? $runtime : null;
        }

        $runtime = (float) $this->meta('wprc_product_runtime', 0);
        return $runtime > 0 ? $runtime : null;
    }

    public function locationCountry(): string
    {
        if (is_callable([$this->product, 'get_location_country'])) {
            return trim((string) $this->product->get_location_country());
        }

        return trim((string) $this->meta('wprc_product_location_country', ''));
    }

    public function processName(): string
    {
        $processId = absint($this->meta('wprc_product_process_id', 0));
        if ($processId <= 0) {
            return '';
        }

        $title = get_the_title($processId);
        return is_string($title) ? trim($title) : '';
    }

    public function lastUpdatedLabel(): string
    {
        $timestamp = 0;
        if (is_callable([$this->product, 'get_last_updated_at'])) {
            $timestamp = (int) $this->product->get_last_updated_at();
        }

        if ($timestamp <= 0) {
            return '';
        }

        $format = trim((string) get_option('date_format') . ' ' . (string) get_option('time_format'));
        return wp_date($format !== '' ? $format : 'Y-m-d H:i', $timestamp);
    }

    public function robotPayload(): ?float
    {
        $model = $this->type() === 'robot' ? $this->robotModel() : null;
        return $model?->payloadKg !== null ? (float) $model->payloadKg : null;
    }

    public function robotReach(): ?float
    {
        $model = $this->type() === 'robot' ? $this->robotModel() : null;
        return $model?->reachMm !== null ? (float) $model->reachMm : null;
    }

    public function robotRepeatability(): ?float
    {
        $model = $this->type() === 'robot' ? $this->robotModel() : null;
        return $model?->repeatabilityMm !== null ? (float) $model->repeatabilityMm : null;
    }

    public function robotModelId(): int
    {
        return $this->type() === 'robot' ? absint($this->meta(WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID, 0)) : 0;
    }

    public function robotModelPublicId(): string
    {
        if ($this->type() !== 'robot') {
            return '';
        }

        $publicId = trim((string) $this->meta(WC_Product_Abstract::META_ROBOT_MODEL_PUBLIC_ID, ''));
        if ($publicId !== '') {
            return $publicId;
        }

        return $this->robotModel()?->publicId ?? '';
    }

    public function robotControllerId(): int
    {
        return $this->type() === 'robot' ? absint($this->meta(WC_Product_Abstract::META_ROBOT_CONTROLLER_ID, 0)) : 0;
    }

    public function robotControllerUid(): string
    {
        if ($this->type() !== 'robot') {
            return '';
        }

        $uid = trim((string) $this->meta(WC_Product_Abstract::META_ROBOT_CONTROLLER_UID, ''));
        if ($uid !== '') {
            return $uid;
        }

        return $this->robotController()?->uid ?? '';
    }

    public function robotModel(): ?RobotModel
    {
        $modelId = $this->robotModelId();
        return $modelId > 0 ? $this->robotModels->find($modelId, $this->language()) : null;
    }

    public function robotController(): ?Controller
    {
        $controllerId = $this->robotControllerId();
        $modelId = $this->robotModelId();
        if ($controllerId <= 0 || $modelId <= 0) {
            return null;
        }
        foreach ($this->robotModels->compatibleControllers($modelId) as $controller) {
            if ($controller->id === $controllerId) {
                return $controller;
            }
        }
        return null;
    }

    /** @return \WP_Post[] */
    public function robotApplications(): array
    {
        $modelId = $this->robotModelId();
        return $modelId > 0 ? $this->robotModels->applications($modelId, $this->language()) : [];
    }

    /** @return \WP_Post[] */
    public function robotSoftwarePages(): array
    {
        if ($this->type() !== 'robot') {
            return [];
        }
        $terms = get_the_terms($this->id(), 'product_brand');
        if (!is_array($terms) || !$terms || !($terms[0] instanceof \WP_Term)) {
            return [];
        }
        return $this->robotModels->softwarePagesForBrand((int) $terms[0]->term_id, $this->language());
    }

    /** @return int[] */
    public function robotGalleryAttachmentIds(): array
    {
        $modelId = $this->robotModelId();
        return $modelId > 0 ? $this->robotModels->galleryAttachmentIds($modelId) : [];
    }

    /** @return array<string,mixed> */
    public function robotMaintenance(): array
    {
        $modelId = $this->robotModelId();
        return $modelId > 0 ? $this->robotModels->maintenance($modelId) : [];
    }

    /** @return array<int,array<string,mixed>> */
    public function robotErpProducts(?string $relationType = null): array
    {
        $modelId = $this->robotModelId();
        return $modelId > 0 ? $this->robotModels->erpProducts($modelId, $relationType) : [];
    }

    private function language(): ?string
    {
        if (!function_exists('pll_get_post_language')) {
            return null;
        }
        $language = pll_get_post_language($this->id(), 'slug');
        return is_string($language) && $language !== '' ? $language : null;
    }

    public function displayTitle(string $context = 'single'): string
    {
        if (!function_exists('rc_translate')) {
            return $this->name();
        }

        $key = $context === 'loop'
            ? 'product_loop_title_' . $this->type()
            : 'product_single_title_' . $this->type();
        $translated = rc_translate($key, $this->id());

        return $translated !== '' ? $translated : $this->name();
    }

    public function availabilityLabel(): string
    {
        if (!function_exists('rc_translate')) {
            return wc_get_stock_html($this->product);
        }

        return rc_translate('product_stock_status_' . $this->product->get_stock_status(), $this->id());
    }

    public function loopSubtitle(): string
    {
        if (!function_exists('rc_translate')) {
            return '';
        }

        return rc_translate('product_loop_subtitle_' . $this->type(), $this->id());
    }

}
