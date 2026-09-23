<?php

declare(strict_types=1);

namespace WPRC\Catalog\Core\Providers;

use WPRC\Catalog\Polylang\PolylangIntegration;
use WPRC\Core\Container;
use WPRC\Core\Contracts\ServiceProviderInterface;

defined('ABSPATH') || exit;

final class PolylangServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(
            PolylangIntegration::class,
            static fn (): PolylangIntegration => new PolylangIntegration()
        );
    }
}
