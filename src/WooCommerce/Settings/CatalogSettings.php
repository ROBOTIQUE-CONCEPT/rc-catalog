<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Settings;

use WPRC\Catalog\RobotModel\Application\Canonicalizer;

defined('ABSPATH') || exit;

final class CatalogSettings extends \WC_Settings_Page
{
    public function __construct()
    {
        $this->id = 'rc_catalog';
        $this->label = __('Catalogue', 'rc-catalog');
        parent::__construct();
    }

    public function get_settings_for_default_section(): array
    {
        return [
            [
                'title' => __('Référentiel Catalogue', 'rc-catalog'),
                'type' => 'title',
                'desc' => __('Réglages structurants utilisés par RC Catalog. Les relations sont enregistrées dans la langue Polylang par défaut.', 'rc-catalog'),
                'id' => 'wprc_catalog_reference_settings',
            ],
            [
                'title' => __('Page parente des applications robotisées', 'rc-catalog'),
                'desc' => __('Les pages descendantes de cette page seront proposées comme applications compatibles sur les modèles de robots.', 'rc-catalog'),
                'id' => CatalogOptions::OPTION_APPLICATIONS_PARENT,
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width:350px;',
                'options' => self::pageOptions(),
                'default' => '',
                'autoload' => false,
            ],
            [
                'title' => __('Page parente des logiciels', 'rc-catalog'),
                'desc' => __('Les pages descendantes de cette page seront proposées sur les marques comme logiciels compatibles.', 'rc-catalog'),
                'id' => CatalogOptions::OPTION_SOFTWARE_PARENT,
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width:350px;',
                'options' => self::pageOptions(),
                'default' => '',
                'autoload' => false,
            ],
            [
                'type' => 'sectionend',
                'id' => 'wprc_catalog_reference_settings',
            ],
        ];
    }

    public function save(): void
    {
        parent::save();

        $canonicalizer = new Canonicalizer();
        foreach ([CatalogOptions::OPTION_APPLICATIONS_PARENT, CatalogOptions::OPTION_SOFTWARE_PARENT] as $option) {
            $value = absint(get_option($option, 0));
            if ($value > 0) {
                update_option($option, $canonicalizer->post($value), false);
            }
        }
    }

    /** @return array<string,string> */
    private static function pageOptions(): array
    {
        $options = ['' => __('— Sélectionner une page —', 'rc-catalog')];
        $defaultLanguage = function_exists('pll_default_language') ? pll_default_language('slug') : null;
        $args = [
            'post_type' => 'page',
            'post_status' => ['publish', 'private', 'draft'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'suppress_filters' => false,
        ];
        if (is_string($defaultLanguage) && $defaultLanguage !== '') {
            $args['lang'] = $defaultLanguage;
        }

        foreach (get_posts($args) as $page) {
            if ($page instanceof \WP_Post) {
                $options[(string) $page->ID] = $page->post_title !== '' ? $page->post_title : ('#' . $page->ID);
            }
        }
        return $options;
    }
}
