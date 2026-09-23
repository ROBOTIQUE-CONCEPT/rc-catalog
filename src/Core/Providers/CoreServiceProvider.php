<?php

declare(strict_types=1);

namespace WPRC\Catalog\Core\Providers;

use WPRC\Catalog\Core\Plugin;
use WPRC\Catalog\Database\TableNames;
use WPRC\Catalog\ERP\DescriptionTemplateRenderer;
use WPRC\Catalog\LeadForms\FormRenderer;
use WPRC\Catalog\LeadForms\Integration as LeadFormsIntegration;
use WPRC\Catalog\LeadForms\RemoteFormRepository;
use WPRC\Catalog\LeadForms\SubmissionProxy;
use WPRC\Catalog\LeadForms\SyncCommand;
use WPRC\Catalog\Polylang\PolylangIntegration;
use WPRC\Catalog\Product\Placeholder\ProductPlaceholderProvider;
use WPRC\Catalog\Product\ProductRepository;
use WPRC\Catalog\Product\WordPressProductContextProvider;
use WPRC\Catalog\PublicApi\Catalog;
use WPRC\Catalog\RobotModel\Admin\ControllersPage;
use WPRC\Catalog\RobotModel\Admin\RobotModelEditor;
use WPRC\Catalog\RobotModel\Admin\RobotModelGalleryEditor;
use WPRC\Catalog\RobotModel\Application\Canonicalizer;
use WPRC\Catalog\RobotModel\Application\RobotModels;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRelationRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelMediaRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRepository;
use WPRC\Catalog\RobotModel\Placeholder\RobotModelPlaceholderProvider;
use WPRC\Catalog\RobotModel\RobotModelIntegration;
use WPRC\Catalog\RobotModel\WordPress\ContentTypes;
use WPRC\Catalog\RobotModel\WordPress\Permalinks;
use WPRC\Catalog\RobotModel\WordPress\PostSynchronizer;
use WPRC\Catalog\WooCommerce\WooCommerceIntegration;
use WPRC\Catalog\WooCommerce\Product\ProductDataLookup;
use WPRC\Catalog\WPSEO\WPSEOIntegration;
use WPRC\Core\Container;
use WPRC\Core\Contracts\CacheInterface;
use WPRC\Core\Contracts\LoggerInterface;
use WPRC\Core\Contracts\Product\ProductContextProviderInterface;
use WPRC\Core\Runtime\RequestContext as CoreRequestContext;
use WPRC\Core\Security\RequestIpResolver;
use WPRC\Core\Security\Turnstile\TurnstileRenderer;
use WPRC\Core\Security\Turnstile\TurnstileVerifier;
use WPRC\Core\InternalApi\Client as InternalApiClient;
use WPRC\Core\Site\SiteContext;
use WPRC\Core\Support\UidGenerator;
use WPRC\Catalog\WooCommerce\RequestContext as WooRequestContext;

defined('ABSPATH') || exit;

final class CoreServiceProvider implements \WPRC\Core\Contracts\ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(WooRequestContext::class, static fn (Container $c): WooRequestContext => new WooRequestContext(
            $c->get(CoreRequestContext::class)
        ));
        $container->singleton(TableNames::class, static fn (): TableNames => new TableNames());
        $container->singleton(Canonicalizer::class, static fn (): Canonicalizer => new Canonicalizer());

        $container->singleton(ProductRepository::class, static fn (Container $c): ProductRepository => new ProductRepository(
            $c->get(RobotModels::class)
        ));
        $container->singleton(ProductPlaceholderProvider::class, static fn (Container $c): ProductPlaceholderProvider => new ProductPlaceholderProvider($c->get(ProductRepository::class)));
        $container->singleton(WordPressProductContextProvider::class, static fn (Container $c): WordPressProductContextProvider => new WordPressProductContextProvider($c->get(ProductRepository::class)));

        $container->singleton(RemoteFormRepository::class, static fn (Container $c): RemoteFormRepository => new RemoteFormRepository(
            $c->get(InternalApiClient::class),
            $c->get(SiteContext::class),
            $c->get(LoggerInterface::class)
        ));
        $container->singleton(FormRenderer::class, static fn (Container $c): FormRenderer => new FormRenderer(
            $c->get(RemoteFormRepository::class),
            $c->get(TurnstileRenderer::class),
            $c->get(WordPressProductContextProvider::class)
        ));
        $container->singleton(SubmissionProxy::class, static fn (Container $c): SubmissionProxy => new SubmissionProxy(
            $c->get(RemoteFormRepository::class),
            $c->get(TurnstileVerifier::class),
            $c->get(InternalApiClient::class),
            $c->get(SiteContext::class),
            $c->get(RequestIpResolver::class)
        ));
        $container->singleton(LeadFormsIntegration::class, static fn (Container $c): LeadFormsIntegration => new LeadFormsIntegration(
            $c->get(RemoteFormRepository::class),
            $c->get(FormRenderer::class),
            $c->get(SubmissionProxy::class)
        ));

        $container->singleton(RobotModelRepository::class, static fn (Container $c): RobotModelRepository => new RobotModelRepository(
            $c->get(TableNames::class),
            $c->get(CacheInterface::class)
        ));
        $container->singleton(ControllerRepository::class, static fn (Container $c): ControllerRepository => new ControllerRepository(
            $c->get(TableNames::class),
            $c->get(CacheInterface::class),
            $c->get(UidGenerator::class)
        ));
        $container->singleton(RobotModelRelationRepository::class, static fn (Container $c): RobotModelRelationRepository => new RobotModelRelationRepository(
            $c->get(TableNames::class),
            $c->get(Canonicalizer::class),
            $c->get(CacheInterface::class)
        ));
        $container->singleton(RobotModelMediaRepository::class, static fn (Container $c): RobotModelMediaRepository => new RobotModelMediaRepository(
            $c->get(TableNames::class),
            $c->get(CacheInterface::class)
        ));
        $container->singleton(RobotModels::class, static fn (Container $c): RobotModels => new RobotModels(
            $c->get(RobotModelRepository::class),
            $c->get(ControllerRepository::class),
            $c->get(RobotModelRelationRepository::class),
            $c->get(Canonicalizer::class),
            $c->get(RobotModelMediaRepository::class)
        ));
        $container->singleton(DescriptionTemplateRenderer::class, static fn (Container $c): DescriptionTemplateRenderer => new DescriptionTemplateRenderer(
            $c->get(RobotModels::class),
            $c->get(ControllerRepository::class)
        ));
        $container->singleton(RobotModelPlaceholderProvider::class, static fn (Container $c): RobotModelPlaceholderProvider => new RobotModelPlaceholderProvider($c->get(RobotModels::class)));

        $container->singleton(ContentTypes::class, static fn (): ContentTypes => new ContentTypes());
        $container->singleton(PostSynchronizer::class, static fn (Container $c): PostSynchronizer => new PostSynchronizer(
            $c->get(RobotModelRepository::class),
            $c->get(Canonicalizer::class)
        ));
        $container->singleton(Permalinks::class, static fn (): Permalinks => new Permalinks());
        $container->singleton(RobotModelEditor::class, static fn (Container $c): RobotModelEditor => new RobotModelEditor(
            $c->get(RobotModelRepository::class),
            $c->get(ControllerRepository::class),
            $c->get(RobotModelRelationRepository::class),
            $c->get(Canonicalizer::class),
            $c->get(ProductDataLookup::class),
            $c->get(LoggerInterface::class)
        ));
        $container->singleton(ControllersPage::class, static fn (Container $c): ControllersPage => new ControllersPage(
            $c->get(ControllerRepository::class),
            $c->get(Canonicalizer::class)
        ));
        $container->singleton(RobotModelGalleryEditor::class, static fn (Container $c): RobotModelGalleryEditor => new RobotModelGalleryEditor(
            $c->get(RobotModelRepository::class),
            $c->get(RobotModelMediaRepository::class),
            $c->get(LoggerInterface::class)
        ));
        $container->singleton(RobotModelIntegration::class, static fn (Container $c): RobotModelIntegration => new RobotModelIntegration(
            $c->get(ContentTypes::class),
            $c->get(PostSynchronizer::class),
            $c->get(Permalinks::class),
            $c->get(RobotModelEditor::class),
            $c->get(ControllersPage::class),
            $c->get(RobotModelGalleryEditor::class)
        ));

        $container->singleton(Catalog::class, static fn (Container $c): Catalog => new Catalog(
            $c->get(ProductRepository::class),
            $c->get(RobotModels::class)
        ));

        $container->alias('catalog.logger', LoggerInterface::class, true);
        $container->alias('catalog.api', Catalog::class, true);

        $container->singleton(Plugin::class, static fn (Container $c): Plugin => new Plugin(
            $c->get(WooCommerceIntegration::class),
            $c->get(WPSEOIntegration::class),
            $c->get(PolylangIntegration::class),
            $c->get(WordPressProductContextProvider::class),
            $c->get(ProductPlaceholderProvider::class),
            $c->get(RobotModelPlaceholderProvider::class),
            $c->get(RobotModelIntegration::class),
            $c->get(LeadFormsIntegration::class),
            $c->get(RemoteFormRepository::class)
        ));
        $container->alias('catalog.plugin', Plugin::class, true);
    }
}
