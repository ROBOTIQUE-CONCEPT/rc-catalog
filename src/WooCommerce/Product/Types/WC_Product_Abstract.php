<?php


namespace WPRC\Catalog\WooCommerce\Product\Types;





defined('ABSPATH') || exit;














/** WordPress */


use WP_Term;


use WPRC\Catalog\WooCommerce\Taxonomy\ProductBrand;





class WC_Product_Abstract extends \WC_Product_Simple {





    /** 


     * Functional constants 


     */


    const INTERNAL_RETAILER_ID          = 15223947;


    const TERM_BRAND                    = 'product_brand';


    const TERM_CATEGORY                 = 'product_cat';


    const INSTOCK_LABEL                 = 'instock';


    const OUTOFSTOCK_LABEL              = 'onbackorder';


    


    /** 


     * Default metakeys 


     */


    const META_CABLE_LENGTH             = 'wprc_product_cable_length';  


    const META_CONDITION                = 'wprc_product_condition';


    const META_DESIGNATION              = 'wprc_product_designation';


    const META_PAGE_TITLE               = 'wprc_product_page_title';


    const META_PROCESS_ID               = 'wprc_product_process_id';


    const META_REFERENCE                = 'wprc_product_reference';


    const META_RUNTIME                  = 'wprc_product_runtime';


    const META_SW_VERSION               = 'wprc_product_software_version';


    const META_WARRANTY                 = 'wprc_product_warranty';


    const META_YOM                      = 'wprc_product_yom';


    const META_YOUTUBE_URL              = 'wprc_product_youtube_url';

    /** External ERP product link. */
    const META_ERP_SOURCE = 'wprc_product_erp_source';
    const META_ERP_ID = 'wprc_product_erp_id';
    const META_ERP_LABEL = '_wprc_product_erp_label';
    const META_ERP_PUBLIC_IMG = '_wprc_product_erp_image_url';

    /** Catalog-native robot-model/controller links. */
    const META_ROBOT_MODEL_ENTITY_ID = 'wprc_product_robot_model_entity_id';
    const META_ROBOT_MODEL_PUBLIC_ID = 'wprc_product_robot_model_public_id';
    const META_ROBOT_CONTROLLER_ID = 'wprc_product_robot_controller_id';
    const META_ROBOT_CONTROLLER_UID = 'wprc_product_robot_controller_uid';

    /** ERP reference-template override and shipping product links. */
    const META_ERP_DESCRIPTION_TEMPLATE = 'wprc_product_erp_description_template';
    const META_SHIPPING_ERP_SOURCE = 'wprc_product_shipping_erp_source';
    const META_SHIPPING_ERP_ID = 'wprc_product_shipping_erp_id';
    const META_SHIPPING_ERP_LABEL = '_wprc_product_shipping_erp_label';
    const META_SHIPPING_STANDARD_ERP_SOURCE = 'wprc_product_shipping_standard_erp_source';
    const META_SHIPPING_STANDARD_ERP_ID = 'wprc_product_shipping_standard_erp_id';
    const META_SHIPPING_STANDARD_ERP_LABEL = '_wprc_product_shipping_standard_erp_label';
    const META_SHIPPING_EXPRESS_ERP_SOURCE = 'wprc_product_shipping_express_erp_source';
    const META_SHIPPING_EXPRESS_ERP_ID = 'wprc_product_shipping_express_erp_id';
    const META_SHIPPING_EXPRESS_ERP_LABEL = '_wprc_product_shipping_express_erp_label';





    /** 


     * Metakeys for product's robot model 


     */


    const META_ROBOT_ID                 = 'wprc_product_robot_id';


    const META_ROBOT_BRAND              = '_wprc_product_robot_brand';


    const META_ROBOT_MODEL              = '_wprc_product_robot_model';


    const META_ROBOT_PAYLOAD            = '_wprc_product_robot_payload';


    const META_ROBOT_REACH              = '_wprc_product_robot_reach';


    const META_ROBOT_REPEATABILITY      = '_wprc_product_robot_repeatability';


    const META_ROBOT_MASS               = '_wprc_product_robot_mass';


    const META_ROBOT_RANGES             = '_wprc_product_robot_ranges';


    const META_ROBOT_VELOCITIES         = '_wprc_product_robot_velocities';


    const META_ROBOT_IP_GRADE_BASE      = '_wprc_product_robot_ip_grade_base';


    const META_ROBOT_IP_GRADE_WRIST     = '_wprc_product_robot_ip_grade_wrist';


    const META_ROBOT_PROCESSES          = '_wprc_product_robot_processes';


    


    /** 


     * Metakeys for product's cabinet model 


     */


    const META_CABINET_ID               = 'wprc_product_cabinet_id';


    const META_CABINET_BRAND            = '_wprc_product_cabinet_brand';


    const META_CABINET_MODEL            = '_wprc_product_cabinet_model';


    


    /** 


     * Meta key for product's retailer company 


     */


    const META_RETAILER_ID              = 'wprc_product_retailer_id';


    const META_RETAILER_NAME            = '_wprc_product_retailer_name';


    const META_RETAILER_STATUS          = '_wprc_product_retailer_status';





    /** 


     * Meta key for product's location address 


     */


    const META_LOCATION_ID                      = 'wprc_product_location_id';
    const META_LOCATION_LABEL                   = '_wprc_product_location_label';


    const META_LOCATION_ADDRESS_ID              = 'wprc_product_location_address_id';
    const META_LOCATION_ADDRESS_LABEL           = '_wprc_product_location_address_label';


    const META_LOCATION_ADDRESS_ZIP             = '_wprc_product_location_address_zip';


    const META_LOCATION_ADDRESS_COUNTRY         = '_wprc_product_location_address_country';


    const META_LOCATION_ADDRESS_COUNTRY_CODE    = '_wprc_product_location_address_country_code';





    /**


     * Canonical product metadata synchronized from the active ERP provider


     */


    const META_TARIFF_CODE              = '_wprc_product_tariff_code';


    const META_COUNTRY_OF_ORIGIN        = '_wprc_product_country_of_origin';


    const META_HS_CODE                  = 'wprc_product_hs_code';               // REMOVE


    const META_ORIGIN_COUNTRY           = 'wprc_product_origin_country';        // REMOVE





    /** 


     * System metas 


     */


    const META_SYS_LAST_SYNC_TIMESTAMP      = '_wprc_product_last_sync';


    const META_SYS_LAST_COMPUTE_TIMESTAMP   = '_wprc_product_last_compute';





    /** @var string Product type */    


    protected ?string $product_type = null;





    /**


     * Lazy loading


     */


    protected array $loaded             = [];       /** @var array Product's loaded meta properties */





    /**


     * System updates


     */


    protected ?int $last_update_time    = null;     /** @var int|null Product's last remote sync timestamp */


    protected ?int $last_render_time    = null;     /** @var int|null Product's last front meta rendering timestamp */

    /** External ERP data. */
    protected ?int $erp_product_id = null;
    protected ?string $public_image_path = null;





    /**


     * Brand Term data


     */


    protected ?WP_Term $brand                   = null;     /** @var WP_Term|null Product's brand */


    protected ?int $brand_id                    = null;     /** @var int|null Product's brand ID */


    protected ?string $brand_name               = null;     /** @var string|null Product's brand name */


    protected ?string $brand_slug               = null;     /** @var string|null Product's brand slug */


    protected mixed $brand_related_services     = null;     


    protected mixed $brand_related_softwares    = null;     





    /**


     * Category Term data


     */


    protected ?WP_Term $category        = null;     /** @var WP_Term|null Product's category */


    protected ?int $category_id         = null;     /** @var int|null Product's category ID */


    protected ?string $category_name    = null;     /** @var string|null Product's category name */


    protected ?string $category_slug    = null;     /** @var string|null Product's category slug */





    /**


     * Retailer properties


     */


    protected ?int $retailer_id         = null;     /** @var int|null Product's retailer company ID */          


    protected ?object $retailer_data    = null;     /** @var CompanyDto|null Product's retailer company DTO */


    protected ?string $retailer_name    = null;     /** @var string|null Product's retailer company name */


    protected ?string $retailer_status  = null;     /** @var string|null Product's retailer company status (internal|external) */





    /**


     * Location data


     */


    protected ?int $location_id         = null;     /** @var int|null Product's retailer company ID */


    protected ?object $location_data    = null;     /** @var object|null Product's retailer company DTO */


    protected ?string $location_zip     = null;     /** @var string|null Product's location company zip */


    protected ?string $location_country = null;     /** @var string|null Product's location company country */


    protected ?string $location_country_code = null;     /** @var string|null Product's location company country */





    /** @var string|null Product's designation */    


    protected ?string $designation = null;





    


    protected ?string $presentation_video_id = null;    /** @var ?string Product's YouTube presentation video ID */


    protected ?int $yom                 = null;     /** @var int|null Product's YoM */


    protected ?float $runtime           = null;     /** @var float|null Product's runtime */








    /**


     * Default WC_Product_Simple overrides


     */





    public function __construct( $product ) { parent::__construct( $product ); } 


    public function get_type(): string { return (string) $this->product_type; }


    public function managing_stock(): bool { return true; }





    /**


     * Lazy loading methods


     */





    /**


     * Mark a meta property loaded


     */


    protected function markLoaded( string $property ): void 


    {


        $this->loaded[ $property ] = true;


    }





    /**


     * Check if a meta property is already loaded


     */


    protected function isLoaded( string $property ): bool 


    {


        return isset( $this->loaded[ $property ] );


    }





    /**


     * Return a lazy load of a meta property from cache or callable


     */


    protected function lazy(string $key, callable $callback, mixed $default = null ) 


    {





        if ( ! $this->isLoaded( $key ) ) {


            $this->$key = $callback();


            $this->markLoaded( $key );


        }





        if( $default && ( !$this->$key || '' === $this->$key) ) {


            return $default;


        }





        return $this->$key;


        


    }





    /**


     * System updates timestamps


     */





    /**


     * Get product's last update timestamp


     * 


     * @return string


     */


    public function get_last_updated_at(): ?int


    {





        return (int) $this->lazy( 


            'last_update_time',  


            fn() => (int) $this->get_meta( self::META_SYS_LAST_SYNC_TIMESTAMP ),


        ); 





    }





    /**


     * Get product's last render timestamp


     * 


     * @return string


     */


    public function get_last_rendered_at(): ?int


    {





        return (int) $this->lazy( 


            'last_render_time',  


            fn() => (int) $this->get_meta( self::META_SYS_LAST_RENDER_TIMESTAMP ),


        ); 





    }





    /**


     * Product's brand


     */





    /**


     * Get product's brand


     * 


     * @return WP_Term|null


     */


    public function get_brand(): ?WP_Term 


    {





        return $this->lazy( 


            'brand',  


            function() {


                $terms = get_the_terms( $this->get_id(), self::TERM_BRAND );





                return $terms && !is_wp_error( $terms ) ? reset( $terms ) : null;


            },


            null


        );





    }





    /**


     * Get product's brand name


     * 


     * @return int|null


     */


    public function get_brand_id(): ?int 


    {





        return (int) $this->lazy( 


            'brand_id',  


            fn() => (int) $this->get_brand()->term_id ?? null,


            null


        ); 





    }





    /**


     * Get product's brand name


     * 


     * @return string|null


     */


    public function get_brand_name(): ?string 


    {





        return (string) $this->lazy( 


            'brand_name',  


            fn() => (string) $this->get_brand()->name ?? null,


            null


        ); 





    }





    /**


     * Get product's brand slug


     * 


     * @return string|null


     */


    public function get_brand_slug(): ?string 


    {





        return (string) $this->lazy( 


            'brand_slug',  


            fn() => (string) $this->get_brand()->slug ?? null,


            null


        ); 





    }





    /**


     * Get product's brand related services


     * 


     * @return string|null


     */


    public function get_brand_related_services()


    {


        return $this->lazy( 


            'brand_related_services',


            fn() => get_term_meta( $this->get_brand_id(), ProductBrand::META_ADDITIONAL_SERVICES, true )


        );





    }





    /**


     * Get product's brand related softwares


     * 


     * @return string|null


     */


    public function get_brand_related_softwares()


    {


        return $this->lazy(
            'brand_related_softwares',
            function() {
                $brandId = $this->get_brand_id();
                if ($brandId <= 0) {
                    return [];
                }

                // Software relations live only on the canonical product_brand
                // term (Polylang default language). Resolve translated brand
                // terms back to that canonical term before reading metadata.
                if (function_exists('pll_default_language') && function_exists('pll_get_term')) {
                    $defaultLanguage = pll_default_language('slug');
                    if (is_string($defaultLanguage) && $defaultLanguage !== '') {
                        $canonicalBrandId = pll_get_term($brandId, $defaultLanguage);
                        if (is_int($canonicalBrandId) && $canonicalBrandId > 0) {
                            $brandId = $canonicalBrandId;
                        }
                    }
                }

                $pages = get_term_meta($brandId, ProductBrand::META_OLP_SOFTWARE, true);
                return is_array($pages) ? array_values(array_filter(array_map('absint', $pages))) : [];
            }
        );





    }





    /**


     * Product's category


     */





    /**


     * Get product's category


     * 


     * @return WP_Term|null


     */


    public function get_category(): ?WP_Term 


    {





        return $this->lazy( 


            'category',  


            function() {


                $terms = get_the_terms( $this->get_id(), self::TERM_CATEGORY );


                return $terms && !is_wp_error( $terms ) ? reset( $terms ) : null;


            },


        );





    }





    /**


     * Get product's brand name


     * 


     * @return int|null


     */


    public function get_category_id(): ?int 


    {





        return (int) $this->lazy( 


            'category_id',  


            fn() => (int) $this->get_category()->ID ?? null,


        ); 





    }





    /**


     * Get product's category name


     * 


     * @return string|null


     */


    public function get_category_name(): ?string 


    {





        return (string) $this->lazy( 


            'category_name',  


            fn() => (string) $this->get_category()->name ?? null,


        ); 





    }





    /**


     * Get product's category slug


     * 


     * @return string|null


     */


    public function get_category_slug(): ?string 


    {





        return (string) $this->lazy( 


            'category_slug',  


            fn() => (string) $this->get_category()->slug ?? null,


        ); 





    }





    /** Product external ERP entity information. */


    


    /**


     * Get the product external ERP ID


     * 


     * @return string|null


     */


    public function get_erp_product_id(): ?int 


    {





        return (int) $this->lazy( 


            'erp_product_id',  


            fn() => (int) $this->get_meta( self::META_ERP_ID ),


        );


    


    }





    public function get_public_image_path(): ?string 


    {





        return (string) $this->lazy( 


            'public_image_path',  


            fn() => (string) $this->get_meta( self::META_ERP_PUBLIC_IMG ),


        );


    


    }





    /**


     * Get product's designation


     * 


     * @return string|null


     */


    public function get_designation(): ?string 


    {





        return (string) $this->lazy( 


            'designation', 


            fn() => (string) $this->get_meta( self::META_DESIGNATION ) 


        );





    }








    /**


     * Product's retailer information 


     */





    /**


     * Get product's retailer company ID


     * 


     * @return int|null


     */


    public function get_retailer_id( ): ?int  


    { 





        return (int) $this->lazy( 


            'retailer_id', 


            fn() => (int) $this->get_meta( self::META_RETAILER_ID ) 


        );





    }





    /**


     * Get product's retailer's company data


     * 


     * @return float|null


     */


    public function get_retailer_data( ): ?object 


    { 





        return (object) $this->lazy( 


            'retailer_data',  


            fn() => (object) json_decode( $this->get_meta( self::META_RETAILER_DATA ), true ) 


        );





    }





    /**


     * Get product's retailer company name


     * 


     * @return float|null


     */


    public function get_retailer_name( ): ?string 


    { 





        return (string) $this->lazy( 


            'retailer_name',  


            fn() => (string) $this->get_meta( self::META_RETAILER_NAME ) 


        );





    }





    /**


     * Get product's retailer status (internal or external)


     * 


     * @return float|null


     */


    public function get_retailer_status( ): ?string 


    { 





        return (string) $this->lazy( 


            'retailer_status',  


            fn() => (string) $this->get_meta( self::META_RETAILER_STATUS ) 


        );





    }





    /** 


     * Product's location information 


     */


    


    /**


     * Get product's location ID


     * 


     * @return int|null


     */


    public function get_location_id( ): ?int  


    { 





        return (int) $this->lazy( 


            'location_id', 


            fn() => (int) $this->get_meta( self::META_LOCATION_ID ) 


        );





    }





    /**


     * Get product's location data


     * 


     * @return object|null


     */


    public function get_location_data( ): ?object 


    { 





        return (object) $this->lazy( 


            'location_data', 


            fn() => (object) json_decode( $this->get_meta( self::META_LOCATION_DATA ), true )


        );


    }





    /**


     * Get product's location zip code


     * 


     * @return string|null


     */


    public function get_location_zip( ): ?string 


    { 





        return (string) $this->lazy( 


            'location_zip', 


            fn() => (string) $this->get_meta( self::META_LOCATION_ADDRESS_ZIP ) 


        );





    }





    /**


     * Get product's location country


     * 


     * @return string|null


     */


    public function get_location_country( ): ?string 


    { 





        return (string) $this->lazy( 


            'location_country', 


            fn() => (string) $this->get_meta( self::META_LOCATION_ADDRESS_COUNTRY ) 


        );





    }





    /**


     * Get product's location country code


     * 


     * @return string|null


     */


    public function get_location_country_code( ): ?string 


    { 





        return (string) $this->lazy( 


            'location_country_code', 


            fn() => (string) $this->get_meta( self::META_LOCATION_ADDRESS_COUNTRY_CODE ) 


        );





    }





    





    /**


     * Get product's last update datetime


     * 


     * @return string


     */


    public function get_product_video_id(): ?string


    {





        return (string) $this->lazy( 


            'presentation_video_id',  


            fn() => (string) $this->get_meta( self::META_YOUTUBE_URL ),


        ); 





    }





    /**


     * Get product's YoM


     * 


     * @return ?int


     */


    public function get_yom( ): ?int 


    { 


        


        return (int) $this->lazy( 


            'yom', 


            fn() => (int) $this->get_meta( self::META_YOM ) 


        );





    }





    /**


     * Get product's runtime


     * 


     * @return float|null


     */


    public function get_runtime( ): mixed


    { 





        return  $this->lazy( 


            'runtime', 


            fn() => (float) $this->get_meta( self::META_RUNTIME ) 


        );





    }





    /**


     * Get the product stock status from synchronized ERP stock


     *


     * @param string $context


     * @return string


     */


    public function get_stock_status( $context = 'view' ): string 


    {





        return ( $this->get_stock_quantity() > 0 ) ? static::INSTOCK_LABEL : static::OUTOFSTOCK_LABEL;





    }





    // TO BE ADDRESSED //


    /**


     * Filter the product title


     *


     * @param [type] $title


     * @param [type] $post_id


     * @return void


     */


    public static function filter_product_title( $title, $post_id ) {





        if ( is_admin() ) return $title;





        if ( get_post_type( $post_id ) === 'product' ) {





            global $product;





            if ( $product ) {


                if( is_archive() || is_page() ) {


                    $title = rc_translate('product_loop_title_' . $product->get_type(), (int) $product->get_id());


                }


                if( is_single() ) {


                    $title = rc_translate('product_single_title_' . $product->get_type(), (int) $product->get_id());


                }


            }


        }





        return $title;





    }





    public function supports____( $feature ): bool {


        $supported = [


            // 'ajax_add_to_cart',


            'stock',


            'sku',


            // 'taxes',


            'shipping',


            // 'multiple_shipping_addresses', // optionnel


        ];





        // Woo s’attend souvent à 'simple' support aussi pour certains comportements admin


        if ( $feature === 'simple' ) {


            return true;


        }





        if ( in_array( $feature, $supported, true ) ) {


            return true;


        }





        return parent::supports( $feature );


    }


        


}


