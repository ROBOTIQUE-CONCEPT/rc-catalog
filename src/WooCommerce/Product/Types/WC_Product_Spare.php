<?php


namespace WPRC\Catalog\WooCommerce\Product\Types;





defined('ABSPATH') || exit;











/** Intergations */


use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Abstract as WC_Product_Abstract;





final class WC_Product_Spare extends WC_Product_Abstract {





    /** Instock label */


    const INSTOCK_LABEL = 'instock';





    /** Out of stock label */


    const OUTOFSTOCK_LABEL = 'onbackorder';





    /** @var string Product's type */    


    protected ?string $product_type = 'spare';





    /** @var string|null Product's reference */    


    protected ?string $reference = null;





    /** @var string|null Product's designation */    


    protected ?string $designation = null;





    /** @var string|null Product's country of origin */    


    protected ?string $country_of_origin = null;





    /** @var string|null Product's HS code */    


    protected ?string $hs_code = null;


    protected ?string $tariff_code = null;








    /**


     * Default WC_Product_Simple overrides


     */


    public function __construct( $product ) { parent::__construct( $product ); }    


    public function managing_stock(): bool  { return true; }








    /**


     * Spare product type methods


     */





    /**


     * Get product's reference


     * 


     * @return string|null


     */


    public function get_reference(): ?string 


    { 





        return $this->lazy( 


            'reference', 


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_REFERENCE ) 


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


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_DESIGNATION ) 


        );





    }


        


    /**


     * Get product's country of origin


     * 


     * @return string|null


     */


    public function get_origin_country(): ?string 


    { 


        


        return (string) $this->lazy( 


            'country_of_origin', 


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_ORIGIN_COUNTRY ) 


        );


    


    }





    /**


     * Get product's country of origin


     * 


     * @return string|null


     */


    public function get_country_of_origin(): ?string 


    { 


        


        return (string) $this->lazy( 


            'country_of_origin', 


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_COUNTRY_OF_ORIGIN ) 


        );


    


    }





    /**


     * Get product's HS code


     * 


     * @return string|null


     */


    public function get_hs_code(): ?string 


    { 


    


        return (string) $this->lazy( 


            'hs_code', 


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_TARIFF_CODE ) 


        );


            


    }





    /**


     * Get product's tariff code


     * 


     * @return string|null


     */


    public function get_tariff_code(): ?string 


    { 


    


        return (string) $this->lazy( 


            'tariff_code', 


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_TARIFF_CODE ) 


        );


            


    }


    


}


