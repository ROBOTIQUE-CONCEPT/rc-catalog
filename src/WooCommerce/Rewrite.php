<?php


declare(strict_types=1);





namespace WPRC\Catalog\WooCommerce;





defined('ABSPATH') || exit;








/** WordPress */


use WP_Rewrite;


use WP_Term;


use WP_Post;





/** WPSEO (Yoast) */


use WPSEO_Primary_Term;





class Rewrite {





    /**


     * Generate the custom product_cat term rewrite rule


     * 


     * @param WP_Rewrite $wp_rewrite


     * @return void


     */


    public static function generate_rewrite_rules_backup( WP_Rewrite $wp_rewrite ): void {


        if ( ! taxonomy_exists( 'product_cat' ) ) {


            return;


        }





        // 1) Récupère tous les termes (toutes langues confondues)


        $terms = get_terms([


            'taxonomy'   => 'product_cat',


            'hide_empty' => false,


            'lang'       => 'all',


        ]);


        if ( is_wp_error( $terms ) || empty( $terms ) ) {


            return;


        }





        // 2) Calcule la profondeur pour chaque terme et trie DESC


        usort( $terms, function( $a, $b ) {


            $depth = function( $term ) {


                $d = 0;


                while ( $term->parent ) {


                    $term = get_term( $term->parent, 'product_cat' );


                    if ( is_wp_error( $term ) ) {


                        break;


                    }


                    $d++;


                }


                return $d;


            };


            return $depth( $b ) - $depth( $a );


        } );





        // 3) Prépare le tableau des nouvelles règles


        $new_rules = [];


        // Langues pour Polylang


        $langs = function_exists( 'pll_languages_list' )


            ? pll_languages_list( [ 'fields' => 'slug' ] )


            : [ '' ];





        foreach ( $terms as $term ) {


            // a) reconstruire la hiérarchie “à plat”


            $segments = [];


            $current  = $term;


            while ( true ) {


                $slug = $current->slug;


                if ( $current->parent ) {


                    $parent = get_term( $current->parent, 'product_cat' );


                    $prefix = $parent->slug . '-';


                    if ( 0 === strpos( $slug, $prefix ) ) {


                        $segments[] = substr( $slug, strlen( $prefix ) );


                    } else {


                        $segments[] = $slug;


                    }


                    $current = $parent;


                    continue;


                }


                $segments[] = $slug;


                break;


            }


            $segments = array_reverse( $segments );


            $path     = implode( '/', $segments );





            // b) pour CHAQUE langue, on ajoute les 3 règles


            foreach ( $langs as $lang ) {


                $lang_prefix = $lang ? $lang . '/' : '';





                // 1) pagination


                $new_rules["^{$lang_prefix}{$path}/page/([0-9]+)/?$"] =


                    'index.php?product_cat=' . $term->slug . '&paged=$matches[1]';





                // 2) archive terme (page 1)


                $new_rules["^{$lang_prefix}{$path}/?$"] =


                    'index.php?product_cat=' . $term->slug;





                // 3) single-product sous ce chemin


                $new_rules["^{$lang_prefix}{$path}/([^/]+)/?$"] =


                    'index.php?post_type=product&name=$matches[1]';


            }


        }





        // 4) Injecte en HEAD pour priorité absolue


        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;


    }





    public static function generate_rewrite_rules( WP_Rewrite $wp_rewrite ): void {


        if ( ! taxonomy_exists( 'product_cat' ) ) {


            return;


        }





        $new_rules = [];





        $pll_active = function_exists( 'pll_default_language' ) && function_exists( 'pll_get_term_translations' );





        // Always query terms in a SAFE language to avoid Polylang edge-cases in admin/flush context.


        $safe_lang = $pll_active ? ( pll_default_language( 'slug' ) ?: '' ) : '';





        $terms = get_terms([


            'taxonomy'   => 'product_cat',


            'hide_empty' => false,


            // IMPORTANT: never use 'all' here (can trigger Polylang bug when curlang is not set)


            // We query a safe language, then expand via translations.


            'lang'       => $pll_active ? $safe_lang : null,


        ]);





        if ( is_wp_error( $terms ) || empty( $terms ) ) {


            return;


        }





        // Sort by depth DESC (ensure deeper paths match first)


        usort( $terms, function( $a, $b ) {


            $depth = function( $term ) {


                $d = 0;


                while ( ! empty( $term->parent ) ) {


                    $term = get_term( (int) $term->parent, 'product_cat' );


                    if ( is_wp_error( $term ) || ! $term ) {


                        break;


                    }


                    $d++;


                }


                return $d;


            };


            return $depth( $b ) - $depth( $a );


        } );





        foreach ( $terms as $base_term ) {


            // Expand term translations (lang_slug => term_id)


            $translations = $pll_active


                ? ( pll_get_term_translations( (int) $base_term->term_id ) ?: [] )


                : [ '' => (int) $base_term->term_id ];





            foreach ( $translations as $lang_slug => $term_id ) {


                $term = get_term( (int) $term_id, 'product_cat' );


                if ( is_wp_error( $term ) || ! $term ) {


                    continue;


                }





                // Build the hierarchical path using translated parents


                $segments = [];


                $current  = $term;





                while ( true ) {


                    $slug = $current->slug;





                    if ( ! empty( $current->parent ) ) {


                        $parent = get_term( (int) $current->parent, 'product_cat' );


                        if ( is_wp_error( $parent ) || ! $parent ) {


                            $segments[] = $slug;


                            break;


                        }





                        $prefix = $parent->slug . '-';


                        $segments[] = ( 0 === strpos( $slug, $prefix ) )


                            ? substr( $slug, strlen( $prefix ) )


                            : $slug;





                        $current = $parent;


                        continue;


                    }





                    $segments[] = $slug;


                    break;


                }





                $segments = array_reverse( $segments );


                $path     = implode( '/', $segments );





                $lang_prefix = $lang_slug ? $lang_slug . '/' : '';





                // 1) pagination


                $new_rules["^{$lang_prefix}{$path}/page/([0-9]+)/?$"] =


                    'index.php?product_cat=' . $term->slug . '&paged=$matches[1]';





                // 2) term archive


                $new_rules["^{$lang_prefix}{$path}/?$"] =


                    'index.php?product_cat=' . $term->slug;





                // 3) product under this path


                $new_rules["^{$lang_prefix}{$path}/([^/]+)/?$"] =


                    'index.php?post_type=product&name=$matches[1]';


            }


        }





        // Prepend for priority


        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;


    }





    /** 


     * Filter the product_cat term link


     * 


     * @param string $termlink


     * @param WP_Term $term


     * @param string $taxonomy


     * @return string


     */


    public static function term_link( string $termlink, WP_Term $term, string $taxonomy ): string {


        // 1) On ne s’applique qu’aux product_cat


        if ( 'product_cat' !== $taxonomy ) {


            return $termlink;


        }





        // 2) Remonter la hiérarchie, en retirant à chaque niveau le préfixe "parent-slug-"


        $segments = [];


        $current  = $term;


        while ( true ) {


            $full_slug = $current->slug;





            if ( $current->parent ) {


                $parent = get_term( $current->parent, $taxonomy );


                if ( $parent && ! is_wp_error( $parent ) ) {


                    $prefix = $parent->slug . '-';





                    // si le slug commence bien par "parent-slug-"


                    if ( 0 === strpos( $full_slug, $prefix ) ) {


                        // on n’en garde que la partie qui suit


                        $segments[] = substr( $full_slug, strlen( $prefix ) );


                    } else {


                        // en fallback, si pas de correspondance, on prend tout


                        $segments[] = $full_slug;


                    }





                    // et on remonte d’un cran


                    $current = $parent;


                    continue;


                }


            }





            // si plus de parent (niveau racine), on ajoute son slug complet (pouvant contenir des hyphens)


            $segments[] = $full_slug;


            break;


        }





        // 3) Reconstruire le chemin dans le bon ordre


        $segments = array_reverse( $segments );


        $path     = implode( '/', $segments ) . '/';





        // 4) Recomposer l’URL en gardant le protocole, domaine et éventuelle langue


        //    Adapté ici pour un site en /fr/, modifiez si besoin


        $home = untrailingslashit( home_url() );


        $lang = function_exists('pll_get_term_language')


            ? (string) pll_get_term_language( $current->term_id, 'slug' )


            : '';





        return $home . ($lang !== '' ? '/' . $lang : '') . '/' . $path;


    }





    /**


     * Filter the main request


     * 


     * @param array $vars


     * @return array 


     */


    public static function request( array $vars ): array {


        if ( ! empty( $vars['product_cat'] ) && false !== strpos( $vars['product_cat'], '/' ) ) {


            $vars['product_cat'] = str_replace( '/', '-', $vars['product_cat'] );


        }





        /** Return the $vars array */


        return $vars;


    }








    public static function post_type_link( string $permalink, \WP_Post $post ): string {


        if ( 'product' !== $post->post_type || 'publish' !== $post->post_status ) {


            return $permalink;


        }

// 1) récupérer la "primary" product_cat


        $terms = get_the_terms( $post->ID, 'product_cat' );


        if ( empty( $terms ) || is_wp_error( $terms ) ) {


            return $permalink;


        }


        // priorité à Yoast Primary Term si disponible


        if ( class_exists( 'WPSEO_Primary_Term' ) ) {


            $primary_id = (int) ( new WPSEO_Primary_Term( 'product_cat', $post->ID ) )->get_primary_term();


            foreach ( $terms as $t ) {


                if ( $t->term_id === $primary_id ) {


                    $primary = $t;


                    break;


                }


            }


        }


        // sinon, catégorie la plus haute


        if ( empty( $primary ) ) {


            $primary = array_reduce( $terms, function( $carry, $term ) {


                return $carry === null || $term->parent < $carry->parent ? $term : $carry;


            }, null );


        }





        // 2) get_term_link() renvoie déjà /fr/.../votre/catégorie/


        $term_link = get_term_link( $primary, 'product_cat' );


        if ( is_wp_error( $term_link ) ) {


            return $permalink;


        }





        $final = trailingslashit( untrailingslashit( $term_link ) . '/' . $post->post_name );






        return $final;


    }





    /**


     * Flush WP rewrites rules


     * 


     * @return void


     */


    public static function flush_rewrite_rules( ): void {


        flush_rewrite_rules( false );


    }








}


