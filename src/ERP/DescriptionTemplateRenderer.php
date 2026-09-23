<?php

declare(strict_types=1);

namespace WPRC\Catalog\ERP;

use WPRC\Catalog\RobotModel\Application\RobotModels;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;
use WPRC\Catalog\RobotModel\WordPress\ContentTypes;
use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Abstract;
use WPRC\Catalog\WooCommerce\Settings\ErpOptions;

defined('ABSPATH') || exit;

/**
 * Explicit, side-effect free placeholder renderer for ERP description templates.
 *
 * This is deliberately not a shortcode/eval engine. Only placeholders listed
 * by values() can be expanded.
 */
final class DescriptionTemplateRenderer
{
    public function __construct(
        private readonly RobotModels $robotModels,
        private readonly ControllerRepository $controllers
    ) {
    }

    public function templateFor(\WC_Product $product): string
    {
        $override = trim((string) $product->get_meta(WC_Product_Abstract::META_ERP_DESCRIPTION_TEMPLATE, true));
        return $override !== '' ? $override : ErpOptions::templateForType($product->get_type());
    }

    public function render(\WC_Product $product, ?string $template = null): string
    {
        $template ??= $this->templateFor($product);
        $values = $this->values($product);

        return preg_replace_callback('/\{([a-z0-9_.-]+)\}/i', static function (array $match) use ($values): string {
            $key = strtolower((string) ($match[1] ?? ''));
            return array_key_exists($key, $values) ? (string) $values[$key] : $match[0];
        }, $template) ?? $template;
    }

    /** @return array<string,string|int|float> */
    public function values(\WC_Product $product): array
    {
        $brandName = '';
        $brandSlug = '';
        $brandTermId = 0;
        $brands = get_the_terms($product->get_id(), 'product_brand');
        if (is_array($brands) && isset($brands[0]) && $brands[0] instanceof \WP_Term) {
            $brandName = $brands[0]->name;
            $brandSlug = $brands[0]->slug;
            $brandTermId = (int) $brands[0]->term_id;
        }

        $values = [
            'product.id' => $product->get_id(),
            'product.name' => (string) $product->get_name(),
            'product.sku' => (string) $product->get_sku(),
            'product.reference' => (string) $product->get_meta(WC_Product_Abstract::META_REFERENCE, true),
            'product.designation' => (string) $product->get_meta(WC_Product_Abstract::META_DESIGNATION, true),
            'product.year' => (string) $product->get_meta(WC_Product_Abstract::META_YOM, true),
            'product.runtime' => (string) $product->get_meta(WC_Product_Abstract::META_RUNTIME, true),
            'product.software_version' => (string) $product->get_meta(WC_Product_Abstract::META_SW_VERSION, true),
            'product.cable_length' => (string) $product->get_meta(WC_Product_Abstract::META_CABLE_LENGTH, true),
            'product.erp_id' => (string) $product->get_meta(WC_Product_Abstract::META_ERP_ID, true),
            'product.serial_number' => (string) $product->get_sku(),
            'brand.name' => $brandName,
            'brand.slug' => $brandSlug,
            'brand.software' => implode(', ', array_map(static fn (\WP_Post $page): string => $page->post_title, $this->robotModels->softwarePagesForBrand($brandTermId, function_exists('pll_get_post_language') ? (string) pll_get_post_language($product->get_id(), 'slug') : null))),
        ];

        $modelId = absint($product->get_meta(WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID, true));
        $language = function_exists('pll_get_post_language') ? pll_get_post_language($product->get_id(), 'slug') : null;
        $model = $modelId > 0 ? $this->robotModels->find($modelId, is_string($language) ? $language : null) : null;
        if ($model !== null) {
            [$modelBrand, $family, $series] = $this->modelClassification($model->postId);
            $values += [
                'robot_model.id' => $model->id,
                'robot_model.reference' => $model->reference,
                'robot_model.name' => $model->title ?? $model->reference,
                'robot_model.brand' => $modelBrand,
                'robot_model.family' => $family,
                'robot_model.series' => $series,
                'robot_model.payload' => $model->payloadKg ?? '',
                'robot_model.reach' => $model->reachMm ?? '',
                'robot_model.mass' => $model->massKg ?? '',
                'robot_model.repeatability' => $model->repeatabilityMm ?? '',
                'robot_model.structure' => $model->structure ?? '',
                'robot_model.axes' => $model->axesCount ?? '',
                'robot_model.ip_base' => $model->ipBase ?? '',
                'robot_model.ip_wrist' => $model->ipWrist ?? '',
                'robot_model.applications' => implode(', ', array_map(static fn (\WP_Post $page): string => $page->post_title, $this->robotModels->applications($model->id, is_string($language) ? $language : null))),
            ];
        }

        $controllerId = absint($product->get_meta(WC_Product_Abstract::META_ROBOT_CONTROLLER_ID, true));
        $controller = $controllerId > 0 ? $this->controllers->find($controllerId) : null;
        if ($controller !== null) {
            $values += [
                'controller.id' => $controller->id,
                'controller.uid' => $controller->uid,
                'controller.name' => $controller->name,
                'controller.reference' => $controller->reference ?? '',
            ];
        }

        return $values;
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function modelClassification(?int $postId): array
    {
        if (!$postId) {
            return ['', '', ''];
        }

        $brand = '';
        $brands = get_the_terms($postId, 'product_brand');
        if (is_array($brands) && isset($brands[0]) && $brands[0] instanceof \WP_Term) {
            $brand = $brands[0]->name;
        }

        $family = '';
        $series = '';
        $terms = get_the_terms($postId, ContentTypes::FAMILY_TAXONOMY);
        if (is_array($terms) && $terms !== []) {
            $deepest = null;
            $depth = -1;
            foreach ($terms as $term) {
                if (!$term instanceof \WP_Term) {
                    continue;
                }
                $currentDepth = count(get_ancestors($term->term_id, ContentTypes::FAMILY_TAXONOMY, 'taxonomy'));
                if ($currentDepth > $depth) {
                    $deepest = $term;
                    $depth = $currentDepth;
                }
            }

            if ($deepest instanceof \WP_Term) {
                if ((int) $deepest->parent === 0) {
                    $family = $deepest->name;
                } else {
                    $series = $deepest->name;
                    $ancestors = array_reverse(get_ancestors($deepest->term_id, ContentTypes::FAMILY_TAXONOMY, 'taxonomy'));
                    if ($ancestors !== []) {
                        $root = get_term((int) $ancestors[0], ContentTypes::FAMILY_TAXONOMY);
                        if ($root instanceof \WP_Term) {
                            $family = $root->name;
                        }
                    }
                }
            }
        }

        return [$brand, $family, $series];
    }

    /** @return string[] */
    public function placeholders(): array
    {
        return [
            '{product.id}', '{product.name}', '{product.sku}', '{product.reference}', '{product.designation}',
            '{product.year}', '{product.runtime}', '{product.software_version}', '{product.cable_length}',
            '{product.erp_id}', '{product.serial_number}', '{brand.name}', '{brand.slug}', '{brand.software}',
            '{robot_model.id}', '{robot_model.reference}', '{robot_model.name}', '{robot_model.brand}',
            '{robot_model.family}', '{robot_model.series}', '{robot_model.payload}',
            '{robot_model.reach}', '{robot_model.mass}', '{robot_model.repeatability}', '{robot_model.structure}', '{robot_model.axes}',
            '{robot_model.ip_base}', '{robot_model.ip_wrist}', '{robot_model.applications}', '{controller.id}', '{controller.uid}',
            '{controller.name}', '{controller.reference}',
        ];
    }
}
