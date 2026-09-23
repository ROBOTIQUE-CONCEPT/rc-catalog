<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Settings;

defined('ABSPATH') || exit;

final class SettingsIntegration
{
    public function init(): void
    {
        // WooCommerce admin settings base classes are not loaded on ordinary
        // frontend requests. Keep settings-page registration strictly admin.
        if (!is_admin()) {
            return;
        }

        add_filter('woocommerce_get_settings_pages', [$this, 'registerPages'], 20);
    }

    /** @param array<int,object> $pages */
    public function registerPages(array $pages): array
    {
        $pages[] = new CatalogSettings();
        $pages[] = new ErpSettings();
        return $pages;
    }
}
