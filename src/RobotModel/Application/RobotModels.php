<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Application;

use WPRC\Catalog\RobotModel\Domain\Controller;
use WPRC\Catalog\RobotModel\Domain\RobotModel;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRelationRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelMediaRepository;
use WPRC\Catalog\RobotModel\WordPress\ContentTypes;
use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Abstract;

defined('ABSPATH') || exit;

/**
 * Stable Catalog-owned facade for robot-model technical data.
 */
final class RobotModels
{
    /** @var array<string,array<int,int>> */
    private array $availableProductCache = [];

    public function __construct(
        private readonly RobotModelRepository $models,
        private readonly ControllerRepository $controllers,
        private readonly RobotModelRelationRepository $relations,
        private readonly Canonicalizer $canonicalizer,
        private readonly RobotModelMediaRepository $media
    ) {
    }

    public function find(int $modelId, ?string $language = null): ?RobotModel
    {
        return $this->models->find($modelId, $language);
    }

    public function findByPostId(int $postId): ?RobotModel
    {
        return $this->models->findByPostId($postId);
    }

    /** @return RobotModel[] */
    public function all(?string $language = null): array
    {
        return $this->models->all($language);
    }

    /** @return Controller[] */
    public function compatibleControllers(int $modelId): array
    {
        $ids = $this->relations->controllerIds($modelId);
        $result = [];
        foreach ($ids as $id) {
            $controller = $this->controllers->find($id);
            if ($controller instanceof Controller && $controller->status === 'active') {
                $result[] = $controller;
            }
        }
        return $result;
    }

    /** @return int[] Default-language page IDs. */
    public function applicationPageIds(int $modelId): array
    {
        return $this->relations->pageIds($modelId, 'application');
    }

    /** @return \WP_Post[] */
    public function applications(int $modelId, ?string $language = null): array
    {
        $language ??= $this->canonicalizer->currentLanguage();
        $posts = [];
        foreach ($this->applicationPageIds($modelId) as $canonicalId) {
            $post = get_post($this->canonicalizer->translatedPost($canonicalId, $language));
            if ($post instanceof \WP_Post) {
                $posts[] = $post;
            }
        }
        return $posts;
    }

    /** @return array<int,array<string,mixed>> */
    public function erpProducts(int $modelId, ?string $relationType = null): array
    {
        return $this->relations->erpProducts($modelId, $relationType);
    }

    /**
     * Backward-compatible facade name. Robot-model technical products are now
     * ERP relations and no longer imply WooCommerce posts.
     *
     * @return array<int,array<string,mixed>>
     */
    public function products(int $modelId, ?string $relationType = null, ?string $language = null): array
    {
        unset($language);
        return $this->erpProducts($modelId, $relationType);
    }

    /** @return int[] */
    public function galleryAttachmentIds(int $modelId): array
    {
        return $this->media->attachmentIds($modelId);
    }

    /** @return array<string,mixed> */
    public function maintenance(int $modelId, ?string $language = null): array
    {
        unset($language);

        return [
            'lubrication' => $this->relations->lubrication($modelId),
            'belts' => $this->relations->belts($modelId),
            'balancers' => $this->relations->balancers($modelId),
        ];
    }

    /** @return \WP_Post[] */
    public function softwarePagesForBrand(int $brandTermId, ?string $language = null): array
    {
        if ($brandTermId <= 0 || !taxonomy_exists('product_brand')) {
            return [];
        }

        $canonicalBrand = $this->canonicalizer->term($brandTermId);
        $ids = get_term_meta($canonicalBrand, \WPRC\Catalog\WooCommerce\Taxonomy\ProductBrand::META_OLP_SOFTWARE, true);
        if (!is_array($ids)) {
            return [];
        }

        $language ??= $this->canonicalizer->currentLanguage();
        $pages = [];
        foreach ($ids as $pageId) {
            $canonicalId = $this->canonicalizer->post((int) $pageId);
            if ($canonicalId <= 0) {
                continue;
            }
            $post = get_post($this->canonicalizer->translatedPost($canonicalId, $language));
            if ($post instanceof \WP_Post) {
                $pages[] = $post;
            }
        }

        return $pages;
    }

    /**
     * Resolve the translated product_brand term attached to a robot model.
     */
    public function brand(int $modelId, ?string $language = null): ?\WP_Term
    {
        $model = $this->find($modelId, $language);
        if (!$model || !$model->postId || !taxonomy_exists('product_brand')) {
            return null;
        }

        $terms = get_the_terms($model->postId, 'product_brand');
        if (!is_array($terms) || $terms === []) {
            return null;
        }

        $term = reset($terms);
        return $term instanceof \WP_Term ? $term : null;
    }

    /**
     * Resolve the model's translated family and optional series.
     *
     * @return array{family:?\WP_Term,series:?\WP_Term}
     */
    public function classification(int $modelId, ?string $language = null): array
    {
        $result = ['family' => null, 'series' => null];
        $model = $this->find($modelId, $language);
        if (!$model || !$model->postId || !taxonomy_exists(ContentTypes::FAMILY_TAXONOMY)) {
            return $result;
        }

        $terms = get_the_terms($model->postId, ContentTypes::FAMILY_TAXONOMY);
        if (!is_array($terms) || $terms === []) {
            return $result;
        }

        usort($terms, static function (\WP_Term $a, \WP_Term $b): int {
            return count(get_ancestors($a->term_id, ContentTypes::FAMILY_TAXONOMY, 'taxonomy')) <=> count(get_ancestors($b->term_id, ContentTypes::FAMILY_TAXONOMY, 'taxonomy'));
        });

        $deepest = end($terms);
        if (!$deepest instanceof \WP_Term) {
            return $result;
        }

        $ancestors = array_reverse(array_map('intval', get_ancestors($deepest->term_id, ContentTypes::FAMILY_TAXONOMY, 'taxonomy')));
        if ($ancestors !== []) {
            $family = get_term($ancestors[0], ContentTypes::FAMILY_TAXONOMY);
            $result['family'] = $family instanceof \WP_Term ? $family : null;
            $result['series'] = $deepest;
            return $result;
        }

        $result['family'] = $deepest;
        return $result;
    }

    /** @return \WP_Post[] */
    public function softwarePages(int $modelId, ?string $language = null): array
    {
        $brand = $this->brand($modelId, $language);
        return $brand instanceof \WP_Term
            ? $this->softwarePagesForBrand($brand->term_id, $language)
            : [];
    }

    /**
     * Published Robot products linked to this technical model in the requested
     * Polylang language. Products explicitly marked out of stock are excluded.
     *
     * @return int[]
     */
    public function availableProductIds(int $modelId, ?string $language = null, int $limit = 12): array
    {
        if ($modelId <= 0 || !post_type_exists('product')) {
            return [];
        }

        $language ??= $this->canonicalizer->currentLanguage();
        $limit = max(1, min(48, $limit));
        $cacheKey = $modelId . ':' . sanitize_key($language) . ':' . $limit;
        if (isset($this->availableProductCache[$cacheKey])) {
            return $this->availableProductCache[$cacheKey];
        }

        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => $limit,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'tax_query' => [
                [
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => ['robot'],
                ],
            ],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID,
                    'value' => $modelId,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_stock_status',
                        'value' => 'outofstock',
                        'compare' => '!=',
                    ],
                    [
                        'key' => '_stock_status',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ],
        ];

        if ($language !== '' && function_exists('pll_current_language')) {
            $args['lang'] = sanitize_key($language);
        }

        /** @var \WP_Query $query */
        $query = new \WP_Query($args);
        $ids = array_values(array_filter(array_map('intval', is_array($query->posts) ? $query->posts : [])));

        /** @var int[] $filtered */
        $filtered = apply_filters('wprc/catalog/robot_model/available_product_ids', $ids, $modelId, $language, $limit);
        $result = array_values(array_unique(array_filter(array_map('intval', is_array($filtered) ? $filtered : $ids))));
        $this->availableProductCache[$cacheKey] = $result;
        return $result;
    }

}
