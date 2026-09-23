<?php 


declare(strict_types=1);





namespace WPRC\Catalog\WPSEO;





defined('ABSPATH') || exit;








/** WooCommerce */


use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Robot as WC_Product_Robot;


use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Spare as WC_Product_Spare;


use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Cell as WC_Product_Cell;





class JSONSchemas {





    public function filterProductSchema(array $data): array


    {


        return self::wpseo_schema_product($data);


    }





    public function enforceHttpsSchema(array $data): array


    {


        return self::wpseo_schema_https($data);


    }





    public function mutateSchemaGraph(array $graph, $context): array


    {


        return self::wpseo_schema_graph_mutation($graph, $context);


    }





    /**


     * Filter the application/ld+json schema output


     * 


     * @Hook : wpseo_schema_product


     *


     * @param array $data


     * @return array


     */


    public static function wpseo_schema_product( array $data ): array 


    {


        if ( ! is_product() || empty( $data['@type'] ) || 'Product' !== $data['@type'] ) {


            return $data;


        }





        global $product;


        





        /** Common overrides  */


        $data['name'] = rc_translate('seo_meta_title_product_' . $product->get_type(), (int) $product->get_id());


        $data['description'] = rc_translate('seo_meta_description_product_' . $product->get_type(), (int) $product->get_id());


        $data['inLanguage'] = str_replace( '_', '-', get_locale() );


        $data['priceValidUntil'] = gmdate( 'Y-m-d', strtotime( '+' . 30 . ' days' ) );





        /** Product type specifics  */


        switch( $product->get_type() ) {


            case 'robot':


                return self::generate_robot_product_schema( $data, $product );


            


            case 'spare':


                return self::generate_spare_product_schema( $data, $product );


            


            case 'cell':


                return self::generate_cell_product_schema( $data, $product );





            default: 


                return $data;


        }


  


    }





    /**


     * Force HTTPS on all application/ld+json schema


     * 


     * @Hook : wpseo_schema_product


     * 


     *


     * @param array $data


     * @return array


     */


    public static function wpseo_schema_https( array $data ): array 


    {


        $json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES  );


        if ( $json && is_string( $json ) ) {


            $json = str_replace( 'http://schema.org/', 'https://schema.org/', $json );


            $data = json_decode( $json, true );


        }


        return $data;


    }








    public static function wpseo_schema_graph_mutation( array $graph, $context ) : array 


    {


        if ( empty( $graph ) || ! is_array( $graph ) ) {


            return $graph;


        }





        $address = self::generate_company_postal_address();


        if ( ! $address ) {


            return $graph;


        }





        foreach ( $graph as &$node ) {


            if ( ! is_array( $node ) || empty( $node['@type'] ) ) {


                continue;


            }


            $types = (array) $node['@type'];





            // Yoast te sort 'Organization' : on lui ajoute 'address'


            if ( in_array( 'Organization', $types, true ) ) {


                $node['address'] = $address;


                // Si tu veux “forcer” LocalBusiness, tu peux ajouter le type :


                // if ( ! in_array('LocalBusiness', $types, true) ) $node['@type'][] = 'LocalBusiness';


            }


        }


        unset($node);





        return $graph;


    }





    protected static function generate_company_postal_address() : ?array 


    {


        // Exemple : récup via options WP (mets-les où tu gères tes infos société)


        $street  = trim( (string) get_option( 'wprc_company_street',  '' ) );


        $zip     = trim( (string) get_option( 'wprc_company_postal',  '' ) );


        $city    = trim( (string) get_option( 'wprc_company_city',    '' ) );


        $country = trim( (string) get_option( 'wprc_company_country', 'FR' ) );





        if ( ! $street || ! $zip || ! $city ) {


            return null; // on ne met rien si incomplet


        }





        return [


            '@type'           => 'PostalAddress',


            'streetAddress'   => $street,


            'postalCode'      => $zip,


            'addressLocality' => $city,


            'addressCountry'  => $country,


        ];


    }








    /**


     * Filter the "robot" product type LD+JSON Schema data


     *


     * @param array $data


     * @param WC_Product_Robot $product


     * @return array


     */


    protected static function generate_robot_product_schema( array $data, WC_Product_Robot $product ): array 


    {





        $data['model']          = $product->get_brand_name() . ' ' . $product->get_robot_model_name() . ' ' . $product->get_cabinet_model_name();


        $data['productionDate'] = strval( $product->get_yom() );


        $data['itemCondition']  = 'https://schema.org/UsedCondition';


        $data['priceValidUntil'] = gmdate( 'Y-m-d', strtotime( '+' . 30 . ' days' ) );


        $data['additionalProperty'] = [


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_technical_mass'),


                'value' => "{$product->get_robot_model_mass()} kg",


            ],


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_technical_payload'),


                'value' => "{$product->get_robot_model_payload()} kg",


            ],


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_technical_reach'),


                'value' => "{$product->get_robot_model_reach()} mm",


            ],


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_technical_repeatability'),


                'value' => "±{$product->get_robot_model_repeatability()} mm",


            ],


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_meta_label_cabinet_model'),


                'value' => "{$product->get_cabinet_model_name()}",


            ],


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_meta_label_runtime'),


                'value' => "{$product->get_runtime()}",


            ],


        ];





        return $data;   


    }





    /**


     * Filter the "spare" product type LD+JSON Schema data


     *


     * @param array $data


     * @param WC_Product_Spare $product


     * @return array


     */


    protected static function generate_spare_product_schema( array $data, WC_Product_Spare $product ): array 


    {


        


        $data['model']          = $product->get_brand_name() . ' | ' . $product->get_reference() . ' | ' . $product->get_designation();


        $data['itemCondition']  = 'https://schema.org/UsedCondition';


        $data['priceValidUntil'] = gmdate( 'Y-m-d', strtotime( '+' . 30 . ' days' ) );


        $data['additionalProperty'] = [


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_meta_label_reference'),


                'value' => "{$product->get_reference()}",


            ],


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_meta_label_designation'),


                'value' => "{$product->get_designation()}",


            ],


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_meta_label_sh_code'),


                'value' => "{$product->get_hs_code()}",


            ],


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_meta_label_origin_country'),


                'value' => "{$product->get_origin_country()}",


            ],


        ];





        return $data;   


    }





    /**


     * Filter the "cell" product type LD+JSON Schema data


     *


     * @param array $data


     * @param WC_Product_Cell $product


     * @return array


     */


    protected static function generate_cell_product_schema( array $data, WC_Product_Cell $product ): array 


    {





        $data['model']          = $product->get_brand_name();


        $data['productionDate'] = strval( $product->get_yom() );


        $data['itemCondition']  = 'https://schema.org/UsedCondition';


        $data['priceValidUntil'] = gmdate( 'Y-m-d', strtotime( '+' . 30 . ' days' ) );


        $data['additionalProperty'] = [


            [


                '@type' => 'PropertyValue',


                'name'  => rc_translate('product_meta_label_runtime'),


                'value' => "{$product->get_runtime()}",


            ],


        ];





        return $data;   


    }





}


