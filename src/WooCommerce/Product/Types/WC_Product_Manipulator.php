<?php





namespace WPRC\Catalog\WooCommerce\Product\Types;





defined('ABSPATH') || exit;











/** WooCommerce */


use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Abstract as WC_Product_Abstract;





class WC_Product_Manipulator extends WC_Product_Abstract {





    const INSTOCK_LABEL = 'instock';





    const OUTOFSTOCK_LABEL = 'outofstock';





    /** @var string|null Product's type */    


    public ?string $product_type = 'manipulator';





    /** @var string|null Product's designation */    


    public ?string $designation = 'designation';


    


    public function __construct( $product ) { parent::__construct( $product ); }    





    public function get_type(): string { return (string) $this->product_type; }








    /**


     * Get product's designation


     * 


     * @return string|null


     */


    public function get_designation(): ?string 


    {





        return $this->lazy( 


            'designation', 


            fn() => (string) $this->get_meta( WC_Product_Abstract::META_PAGE_TITLE )


        );





    }








}


