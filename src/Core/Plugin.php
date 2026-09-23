<?php

declare(strict_types=1);

namespace WPRC\Catalog\Core;

use WPRC\Catalog\Polylang\PolylangIntegration;
use WPRC\Catalog\LeadForms\Integration as LeadFormsIntegration;
use WPRC\Catalog\LeadForms\RemoteFormRepository;
use WPRC\Catalog\LeadForms\SyncCommand;
use WPRC\Catalog\Product\Placeholder\ProductPlaceholderProvider;
use WPRC\Catalog\RobotModel\RobotModelIntegration;
use WPRC\Catalog\RobotModel\Placeholder\RobotModelPlaceholderProvider;
use WPRC\Catalog\WooCommerce\WooCommerceIntegration;
use WPRC\Catalog\WPSEO\WPSEOIntegration;
use WPRC\Core\Contracts\Product\ProductContextProviderInterface;

defined('ABSPATH') || exit;

final class Plugin
{
    public function __construct(
        private readonly WooCommerceIntegration $wooCommerce,
        private readonly WPSEOIntegration $wpseo,
        private readonly PolylangIntegration $polylang,
        private readonly ProductContextProviderInterface $productContext,
        private readonly ProductPlaceholderProvider $productPlaceholders,
        private readonly RobotModelPlaceholderProvider $robotModelPlaceholders,
        private readonly RobotModelIntegration $robotModels,
        private readonly LeadFormsIntegration $leadForms,
        private readonly RemoteFormRepository $leadFormRepository
    ) {
    }

    public function boot(): void
    {
        if (is_multisite() && !rc_core()->sites()->isPublicSite()) {
            if (is_admin() && current_user_can('activate_plugins')) {
                add_action('admin_notices', static function (): void {
                    echo '<div class="notice notice-warning"><p><strong>RC Catalog :</strong> ' . esc_html__('ce plugin doit être actif uniquement sur le site public configuré dans RC Core.', 'rc-catalog') . '</p></div>';
                });
            }
            return;
        }

        Installer::maybeUpgrade();

        $translations = require WPRC_CATALOG_PATH . 'resources/translations/catalog.php';
        if (is_array($translations)) {
            rc_register_translations('rc-catalog', $translations);
        }

        if (!class_exists('WooCommerce') || !function_exists('pll_default_language')) {
            add_action('admin_notices', static function (): void {
                if (current_user_can('activate_plugins')) {
                    echo '<div class="notice notice-error"><p><strong>RC Catalog :</strong> ' . esc_html__('WooCommerce et Polylang Pro doivent être actifs.', 'rc-catalog') . '</p></div>';
                }
            });
            return;
        }

        $this->polylang->init();
        $this->robotModels->init();
        $this->wooCommerce->init();
        $this->leadForms->init();

        if (defined('WP_CLI') && WP_CLI) {
            SyncCommand::register($this->leadFormRepository);
        }

        if (class_exists('WPSEO_Premium') || defined('WPSEO_VERSION')) {
            $this->wpseo->init();
        }

        // Public product context exposed through the Core contract; no module-specific dependency.
        rc_core()->services()->instance(ProductContextProviderInterface::class, $this->productContext, true);

        // Atomic Catalog placeholders are registered into the generic Core engine.
        rc_core()->placeholders()->registerProvider($this->productPlaceholders, true);
        rc_core()->placeholders()->registerProvider($this->robotModelPlaceholders, true);
        do_action('wprc/catalog/ready', $this);
    }
}
