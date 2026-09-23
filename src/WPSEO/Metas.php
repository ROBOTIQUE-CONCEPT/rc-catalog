<?php 


declare(strict_types=1);





namespace WPRC\Catalog\WPSEO;





defined('ABSPATH') || exit;








class Metas {





    public function filterTitle(string $title): string


    {


        return self::wpseo_title($title);


    }





    public function filterMetaDescription(string $desc): string


    {


        return self::wpseo_metadesc($desc);


    }





    /**


     * Modify the document title for a  single product page


     *


     * @param string $title


     * 


     * @return string The filtered title


     */


    public static function wpseo_title( string $title ): string {


        /** Get current product */


        global $product;





        if( $product && is_singular( 'product' ) ) {


            return rc_translate('seo_meta_title_product_' . $product->get_type(), (int) $product->get_id()) . ' '. YoastSEO()->helpers->options->get_title_separator() . ' ' . get_bloginfo( 'name' );


        }





        /** Return default title */


        return $title;


    }





    /**


     * Filter the document metadesc for a single product page


     *


     * @param string $desc


     * 


     * @return string The metadesc


     */


    public static function wpseo_metadesc( string $desc ): string {


        /** Get current product */


        global $product;








        if( $product && is_singular( 'product' ) ) {


            return rc_translate('seo_meta_description_product_' . $product->get_type(), (int) $product->get_id());


        }





        /** Return default desc */


        return $desc;


    }





}


