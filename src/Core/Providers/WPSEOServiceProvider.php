<?php


declare(strict_types=1);





namespace WPRC\Catalog\Core\Providers;








use WPRC\Core\Container;


use WPRC\Core\Runtime\RequestContext;


use WPRC\Catalog\WPSEO\Breadcrumbs;


use WPRC\Catalog\WPSEO\JSONSchemas;


use WPRC\Catalog\WPSEO\Metas;


use WPRC\Catalog\WPSEO\WPSEOIntegration;





defined('ABSPATH') || exit;








final class WPSEOServiceProvider implements \WPRC\Core\Contracts\ServiceProviderInterface


{


    public function register(Container $container): void


    {


        $container->singleton(Metas::class, static fn (): Metas => new Metas());


        $container->singleton(JSONSchemas::class, static fn (): JSONSchemas => new JSONSchemas());


        $container->singleton(Breadcrumbs::class, static fn (): Breadcrumbs => new Breadcrumbs());





        $container->singleton(WPSEOIntegration::class, static function (Container $container): WPSEOIntegration {


            return new WPSEOIntegration(


                $container->get(Metas::class),


                $container->get(JSONSchemas::class),


                $container->get(Breadcrumbs::class),


                $container->get(RequestContext::class)


            );


        });


    }


}


