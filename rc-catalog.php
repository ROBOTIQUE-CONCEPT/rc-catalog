<?php
/**
 * Plugin Name: RC Catalog
 * Plugin URI: https://www.robotiqueconcept.com/
 * Description: Catalog normalization, WooCommerce business rules, SEO and translation logic for Robotique Concept.
 * Version: 1.7.0-alpha2
 * Requires at least: 6.8
 * Requires PHP: 8.1
 * Requires Plugins: rc-core, woocommerce, polylang-pro
 * Author: Robotique Concept
 * License: GPL-2.0-or-later
 * Text Domain: rc-catalog
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('RC_CATALOG_VERSION', '1.7.0-alpha2');
define('RC_CATALOG_MIN_CORE_VERSION', '0.6.0-alpha2');
define('WPRC_CATALOG_FILE', __FILE__);
define('WPRC_CATALOG_PATH', plugin_dir_path(__FILE__));
define('WPRC_CATALOG_URL', plugin_dir_url(__FILE__));

require_once WPRC_CATALOG_PATH . 'src/Autoloader.php';
\WPRC\Catalog\Autoloader::register();
require_once WPRC_CATALOG_PATH . 'src/global-functions.php';

/**
 * Whether the installed RC Core version satisfies the Catalog contract.
 */
function rc_catalog_core_is_compatible(): bool
{
    return function_exists('rc_core')
        && defined('RC_CORE_VERSION')
        && version_compare(RC_CORE_VERSION, RC_CATALOG_MIN_CORE_VERSION, '>=');
}

/**
 * Backward-compatible access to the shared RC Core container.
 *
 * @deprecated 1.7.0 Use rc_core()->container() directly.
 */
function rc_catalog_bootstrap(): ?\WPRC\Core\Container
{
    return rc_catalog_core_is_compatible() ? rc_core()->container() : null;
}

/**
 * Register Catalog through the canonical RC Core module lifecycle.
 */
add_action('wprc/core/register_modules', static function (\WPRC\Core\Module\ModuleRegistry $modules): void {
    if (!rc_catalog_core_is_compatible()) {
        return;
    }

    if (!$modules->has('catalog')) {
        $modules->register(new \WPRC\Catalog\Core\Module());
    }
}, 10, 1);

register_activation_hook(__FILE__, static function (): void {
    if (!rc_catalog_core_is_compatible()) {
        wp_die(sprintf(
            esc_html__('RC Catalog requires RC Core %s or newer.', 'rc-catalog'),
            esc_html(RC_CATALOG_MIN_CORE_VERSION)
        ));
    }

    \WPRC\Catalog\Core\Installer::activate();
});

register_deactivation_hook(__FILE__, static function (): void {
    \WPRC\Catalog\Core\Installer::deactivate();
});

add_action('plugins_loaded', static function (): void {
    if (rc_catalog_core_is_compatible()) {
        return;
    }

    add_action('admin_notices', static function (): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p><strong>RC Catalog</strong> requires RC Core %s or newer.</p></div>',
            esc_html(RC_CATALOG_MIN_CORE_VERSION)
        );
    });
}, 20);

if (!function_exists('wprc')) {
    /**
     * Historical Core container helper kept for third-party compatibility.
     *
     * @deprecated 1.7.0 Use rc_core()->container().
     */
    function wprc(): \WPRC\Core\Container
    {
        return rc_core()->container();
    }
}
