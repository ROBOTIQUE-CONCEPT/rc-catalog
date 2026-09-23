<?php

declare(strict_types=1);

namespace WPRC\Catalog\Core;

use WPRC\Catalog\Core\Providers\CoreServiceProvider;
use WPRC\Catalog\Core\Providers\PolylangServiceProvider;
use WPRC\Catalog\Core\Providers\WooCommerceServiceProvider;
use WPRC\Catalog\Core\Providers\WPSEOServiceProvider;
use WPRC\Catalog\Security\Capabilities;
use WPRC\Core\Container;
use WPRC\Core\Contracts\ModuleInterface;
use WPRC\Core\Security\Capabilities\CapabilityRegistry;

defined('ABSPATH') || exit;

/**
 * RC Catalog module entry point registered through the RC Core lifecycle.
 */
final class Module implements ModuleInterface
{
    private ?Container $container = null;

    public function id(): string
    {
        return 'catalog';
    }

    public function version(): string
    {
        return RC_CATALOG_VERSION;
    }

    public function minimumCoreVersion(): string
    {
        return RC_CATALOG_MIN_CORE_VERSION;
    }

    public function register(Container $container): void
    {
        $this->container = $container;

        $container->registerProvider(new CoreServiceProvider());
        $container->registerProvider(new WooCommerceServiceProvider());
        $container->registerProvider(new WPSEOServiceProvider());
        $container->registerProvider(new PolylangServiceProvider());

        Capabilities::register($container->get(CapabilityRegistry::class));
    }

    public function boot(): void
    {
        if (!$this->container instanceof Container) {
            return;
        }

        $bootRuntime = function (): void {
            if ($this->container instanceof Container) {
                $this->container->get(Plugin::class)->boot();
            }
        };

        // Core registers and boots modules from its early plugins_loaded hook.
        // Catalog historically initialized at priority 10, after WooCommerce
        // and Polylang plugin files are available. Keep that runtime timing to
        // avoid changing dependency initialization semantics in this release.
        if (function_exists('doing_action') && doing_action('plugins_loaded')) {
            add_action('plugins_loaded', $bootRuntime, 10);
            return;
        }

        if (did_action('plugins_loaded') > 0) {
            $bootRuntime();
            return;
        }

        add_action('plugins_loaded', $bootRuntime, 10);
    }
}
