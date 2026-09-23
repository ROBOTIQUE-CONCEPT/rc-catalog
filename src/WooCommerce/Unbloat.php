<?php


declare(strict_types=1);





namespace WPRC\Catalog\WooCommerce;





defined('ABSPATH') || exit;








class Unbloat {





    /**


     * Clean unnecessary admin menu entries


     */


    public static function cleanup_admin_menu(): void {





        /** Menu pages */


        remove_menu_page( 'admin.php?page=wc-admin' );


        remove_menu_page( 'admin.php?page=wc-settings&tab=checkout' );


        remove_menu_page( 'admin.php?page=wc-admin&path=/wc-pay-welcome-page' ); 


        remove_menu_page( 'admin.php?page=wc-admin&path=/marketing' ); 


        remove_menu_page( 'admin.php?page=wc-admin&path=/extensions' ); 


        remove_menu_page( 'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM' );





        /** Submenu pages */


        remove_submenu_page( 'edit.php?post_type=product', 'product_attributes' );


        remove_submenu_page( 'edit.php?post_type=product', 'product-reviews' );


	    remove_submenu_page( 'edit.php?post_type=product', 'edit-tags.php?taxonomy=product_tag&post_type=product' );


        remove_submenu_page( 'woocommerce', 'edit.php?post_type=shop_order' );


        remove_submenu_page( 'woocommerce', 'wc-orders' );


        remove_submenu_page( 'woocommerce', 'wc-coupons' );


        remove_submenu_page( 'woocommerce', 'wc-reports' );





    }





    /**


     * Unload unnecessary WooCommerce admin features


     *


     * @return array


     */


    public static function cleanup_admin_features( array $features ): array {


        return array_values(


            array_filter( $features, function( $feature ) {


                return $feature !== 'marketing';


            } ) 


        );      


    }


}


