<?php

declare(strict_types=1);

namespace WPRC\Catalog\Helpers;

use WP_Post;
use WPRC\Catalog\RobotModel\Application\Canonicalizer;
use WPRC\Catalog\WooCommerce\Settings\CatalogOptions;

defined('ABSPATH') || exit;

/**
 * Resolve a configured robot-application child page by slug.
 *
 * The historical process-taxonomy fallback was removed. Application
 * pages now live exclusively below the parent configured in WooCommerce >
 * Settings > Catalogue.
 */
final class RobotProcessPageResolver
{
    /** @var array<string,array<string,WP_Post>> */
    private static array $maps = [];

    public static function find_page_by_process_slug(string $processSlug, array $args = []): ?WP_Post
    {
        unset($args);

        $processSlug = sanitize_title($processSlug);
        if ($processSlug === '') {
            return null;
        }

        $parentId = CatalogOptions::applicationsParentId();
        if ($parentId <= 0) {
            return null;
        }

        $canonicalizer = new Canonicalizer();
        $language = $canonicalizer->currentLanguage();
        $translatedParent = $canonicalizer->translatedPost($parentId, $language);
        if ($translatedParent <= 0) {
            return null;
        }

        $cacheKey = $language . ':' . $translatedParent;
        if (!isset(self::$maps[$cacheKey])) {
            self::$maps[$cacheKey] = [];
            $pages = get_pages([
                'child_of' => $translatedParent,
                'post_status' => 'publish',
                'sort_column' => 'menu_order,post_title',
            ]);

            foreach ($pages as $page) {
                if ($page instanceof WP_Post && $page->post_name !== '') {
                    self::$maps[$cacheKey][$page->post_name] = $page;
                }
            }
        }

        return self::$maps[$cacheKey][$processSlug] ?? null;
    }
}
