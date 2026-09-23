<?php

declare(strict_types=1);

namespace WPRC\Catalog\Core\Providers;

use WPRC\Catalog\WooCommerce\RequestContext;
use WPRC\Catalog\ERP\DescriptionTemplateRenderer;
use WPRC\Catalog\RobotModel\Application\RobotModels;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRelationRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRepository;
use WPRC\Catalog\WooCommerce\CatalogMode;
use WPRC\Catalog\WooCommerce\Product\Ajax as ProductAjax;
use WPRC\Catalog\WooCommerce\Product\CustomFields;
use WPRC\Catalog\WooCommerce\Product\ProductDataLookup;
use WPRC\Catalog\WooCommerce\Product\ProductSaveHandler;
use WPRC\Catalog\WooCommerce\Product\Search as ProductSearch;
use WPRC\Catalog\WooCommerce\Settings\SettingsIntegration;
use WPRC\Catalog\WooCommerce\WooCommerceIntegration;
use WPRC\Core\Container;
use WPRC\Core\ERP\ProviderRegistry;

defined('ABSPATH') || exit;

final class WooCommerceServiceProvider implements \WPRC\Core\Contracts\ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(ProductDataLookup::class, static fn (Container $container): ProductDataLookup => new ProductDataLookup(
            $container->get(ProviderRegistry::class)
        ));

        $container->singleton(ProductSaveHandler::class, static fn (Container $c): ProductSaveHandler => new ProductSaveHandler(
            $c->get(RobotModelRepository::class),
            $c->get(ControllerRepository::class),
            $c->get(RobotModelRelationRepository::class),
            $c->get(ProviderRegistry::class)
        ));

        $container->singleton(ProductAjax::class, static fn (Container $c): ProductAjax => new ProductAjax(
            $c->get(ProductDataLookup::class),
            $c->get(RobotModels::class)
        ));
        $container->singleton(CustomFields::class, static fn (Container $c): CustomFields => new CustomFields(
            $c->get(ProductDataLookup::class),
            $c->get(RobotModels::class),
            $c->get(ControllerRepository::class),
            $c->get(DescriptionTemplateRenderer::class)
        ));
        $container->singleton(ProductSearch::class, static fn (Container $c): ProductSearch => new ProductSearch($c->get(RequestContext::class)));
        $container->singleton(CatalogMode::class, static fn (): CatalogMode => new CatalogMode());
        $container->singleton(SettingsIntegration::class, static fn (): SettingsIntegration => new SettingsIntegration());

        $container->singleton(WooCommerceIntegration::class, static function (Container $container): WooCommerceIntegration {
            return new WooCommerceIntegration(
                $container->get(ProductSaveHandler::class),
                $container->get(ProductAjax::class),
                $container->get(CustomFields::class),
                $container->get(ProductSearch::class),
                $container->get(CatalogMode::class),
                $container->get(SettingsIntegration::class)
            );
        });
    }
}
