<?php

declare(strict_types=1);

namespace WPRC\Catalog\Polylang;

use WPRC\Catalog\RobotModel\WordPress\ContentTypes;
use WPRC\Core\Contracts\BootableInterface;

defined('ABSPATH') || exit;

/**
 * Catalog integration with Polylang Pro.
 *
 * RC Catalog treats Polylang as a required dependency. Editorial robot-model
 * posts and their family/series taxonomy are translated, while technical data
 * is stored once in Catalog tables and shared by all translations.
 */
final class PolylangIntegration implements BootableInterface
{
    public function init(): void
    {
        add_filter('pll_get_post_types', [$this, 'postTypes'], 10, 2);
        add_filter('pll_get_taxonomies', [$this, 'taxonomies'], 10, 2);
    }

    /** @param array<string,string> $postTypes */
    public function postTypes(array $postTypes, bool $isSettings): array
    {
        if ($isSettings) {
            unset($postTypes[ContentTypes::POST_TYPE]);
            return $postTypes;
        }

        $postTypes[ContentTypes::POST_TYPE] = ContentTypes::POST_TYPE;
        return $postTypes;
    }

    /** @param array<string,string> $taxonomies */
    public function taxonomies(array $taxonomies, bool $isSettings): array
    {
        if ($isSettings) {
            unset($taxonomies[ContentTypes::FAMILY_TAXONOMY]);
            return $taxonomies;
        }

        $taxonomies[ContentTypes::FAMILY_TAXONOMY] = ContentTypes::FAMILY_TAXONOMY;
        return $taxonomies;
    }
}
