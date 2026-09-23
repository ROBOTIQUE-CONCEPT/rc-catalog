<?php


declare(strict_types=1);





namespace WPRC\Catalog\WooCommerce\Product;

use WPRC\Catalog\Security\Capabilities;

use WPRC\Catalog\RobotModel\Application\RobotModels;





use WPRC\Core\Contracts\BootableInterface;





defined('ABSPATH') || exit;








final class Ajax implements BootableInterface


{


    public function __construct(


        private readonly ProductDataLookup $lookup,
        private readonly RobotModels $robotModels


    ) {


    }





    public function init(): void


    {


        add_action('admin_enqueue_scripts', [$this, 'loadAssets'], 10, 1);


        add_action('wp_ajax_wprc_search_erp_addresses', [$this, 'searchAddresses']);


        add_action('wp_ajax_wprc_search_erp_companies', [$this, 'searchCompanies']);


        add_action('wp_ajax_wprc_search_erp_products', [$this, 'searchErpProducts']);
        add_action('wp_ajax_wprc_robot_model_controllers', [$this, 'robotModelControllers']);


    }





    public function loadAssets(string $hook): void


    {


        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {


            return;


        }





        $screen = function_exists('get_current_screen') ? get_current_screen() : null;


        if (!$screen || ($screen->post_type ?? '') !== 'product') {


            return;


        }





        wp_enqueue_script('selectWoo');


        wp_enqueue_style('selectWoo');





        $assetPath = WPRC_CATALOG_PATH . 'src/WooCommerce/assets/js/wprc-woo-product-search.js';





        wp_enqueue_script(


            'wprc-woo-search',


            plugins_url('assets/js/wprc-woo-product-search.js', __DIR__),


            ['jquery', 'selectWoo'],


            is_readable($assetPath) ? (string) filemtime($assetPath) : RC_CATALOG_VERSION,


            true


        );





        wp_localize_script('wprc-woo-search', 'wprc_admin_params', [


            'ajax_url' => admin_url('admin-ajax.php'),


            'nonce' => wp_create_nonce('wprc_woo_ajax'),


            'erp_source' => $this->lookup->activeErpSource(),

            'actions' => [


                'products' => 'wprc_search_erp_products',


                'companies' => 'wprc_search_erp_companies',


                'addresses' => 'wprc_search_erp_addresses',
                'robotControllers' => 'wprc_robot_model_controllers',


            ],


            'placeholders' => [


                'products' => __('Rechercher un produit ERP ...', 'wprc'),


                'companies' => __('Rechercher une entreprise ...', 'wprc'),


                'addresses' => __('Rechercher une addresse ...', 'wprc'),


            ],


        ]);


    }





    public function searchErpProducts(): void


    {


        $this->assertAuthorized();


        $term = isset($_GET['q']) ? sanitize_text_field(wp_unslash((string) $_GET['q'])) : '';





        wp_send_json($this->lookup->searchErpProducts($term));


    }

















    public function searchCompanies(): void


    {


        $this->assertAuthorized();


        $term = isset($_GET['q']) ? sanitize_text_field(wp_unslash((string) $_GET['q'])) : '';





        wp_send_json($this->lookup->searchCompanies($term));


    }





    public function searchAddresses(): void


    {


        $this->assertAuthorized();


        $locationId = isset($_GET['location_id']) ? sanitize_text_field(wp_unslash((string) $_GET['location_id'])) : '';





        wp_send_json($this->lookup->searchAddresses($locationId));


    }





    public function robotModelControllers(): void
    {
        $this->assertAuthorized();
        $modelId = isset($_GET['model_id']) ? absint(wp_unslash((string) $_GET['model_id'])) : 0;
        $items = [];
        foreach ($this->robotModels->compatibleControllers($modelId) as $controller) {
            $items[] = [
                'id' => (string) $controller->id,
                'text' => $controller->name . ($controller->reference ? ' — ' . $controller->reference : ''),
            ];
        }
        wp_send_json($items);
    }

    private function assertAuthorized(): void


    {


        check_ajax_referer('wprc_woo_ajax');





        if (!Capabilities::canLookupErpData()) {


            wp_send_json_error([


                'message' => __('Forbidden', 'wprc'),


            ], 403);


        }


    }


}


