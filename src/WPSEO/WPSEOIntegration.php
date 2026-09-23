<?php


declare(strict_types=1);





namespace WPRC\Catalog\WPSEO;





use WPRC\Core\Contracts\BootableInterface;


use WPRC\Core\Runtime\RequestContext;





defined('ABSPATH') || exit;








final class WPSEOIntegration implements BootableInterface


{


    private bool $registered = false;





    public function __construct(


        private readonly Metas $metas,


        private readonly JSONSchemas $jsonSchemas,


        private readonly Breadcrumbs $breadcrumbs,


        private readonly RequestContext $context


    ) {


    }





    public function init(): void


    {


        if ($this->context->isAdmin() || $this->context->isAjax() || $this->context->isRest()) {


            return;


        }





        add_action('wp', [$this, 'registerFrontHooks'], 20);


    }





    public function registerFrontHooks(): void


    {


        if ($this->registered) {


            return;


        }










        if ($this->isProductContext()) {


            add_filter('wpseo_title', [$this->metas, 'filterTitle'], 10, 1);


            add_filter('wpseo_metadesc', [$this->metas, 'filterMetaDescription'], 10, 1);


            add_filter('wpseo_schema_product', [$this->jsonSchemas, 'filterProductSchema'], 10, 1);


            add_filter('wpseo_schema_product', [$this->jsonSchemas, 'enforceHttpsSchema'], 20, 1);


        }





        if ($this->isSeoFrontContext()) {


            add_filter('wpseo_schema_graph', [$this->jsonSchemas, 'mutateSchemaGraph'], 20, 2);


            add_filter('wpseo_breadcrumb_links', [$this->breadcrumbs, 'filterLinks'], 10, 1);


        }





        $this->registered = true;


    }





    private function isProductContext(): bool


    {


        return function_exists('is_product') && is_product();


    }





    private function isSeoFrontContext(): bool


    {


        if ($this->isProductContext()) {


            return true;


        }





        if (function_exists('is_woocommerce') && is_woocommerce()) {


            return true;


        }





        return is_singular() || is_archive();


    }


}


