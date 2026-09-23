<?php


declare(strict_types=1);





namespace WPRC\Catalog\WooCommerce\Product;





use WPRC\Core\Contracts\BootableInterface;


use WPRC\Catalog\WooCommerce\RequestContext;





defined('ABSPATH') || exit;








final class Search implements BootableInterface


{


    public function __construct(private readonly RequestContext $context)


    {


    }





    public function init(): void


    {


        if ($this->context->isAdmin() && !$this->context->isAjax() && !$this->context->isRest()) {


            return;


        }





        if ($this->context->isFront()) {


            add_action('wp', [$this, 'registerFrontHooks'], 20);


        }





        add_filter('query_loop_block_query_vars', [$this, 'enableIncludeChildrenProductCollectionBlock'], 11, 3);


        add_filter('rest_product_query', [$this, 'restIncludeChildrenProductCollectionBlock'], 11, 2);


    }





    public function registerFrontHooks(): void


    {


        if (!$this->context->isWooArchive()) {


            return;


        }





        if (!$this->hasActiveArchiveFilters()) {


            return;


        }





        add_filter('woocommerce_product_query_meta_query', [$this, 'woocommerceProductQueryMetaQuery'], 10, 2);


    }





    public function woocommerceProductQueryMetaQuery(array $metaQuery, \WC_Query $wcQuery): array


    {


        if (is_admin() || !is_main_query()) {


            return $metaQuery;


        }


        if (!($this->context->isWooArchive())) {


            return $metaQuery;


        }





        if (!empty($_GET['reference_search'])) {


            $ref = sanitize_text_field(wp_unslash((string) $_GET['reference_search']));


            $metaQuery[] = [


                'relation' => 'OR',


                [


                    'key' => 'wprc_product_designation',


                    'value' => $ref,


                    'compare' => 'LIKE',


                ],


                [


                    'key' => 'wprc_product_reference',


                    'value' => $ref,


                    'compare' => 'LIKE',


                ],


            ];


        }





        if (!empty($_GET['payload_range']) && preg_match('/^(\d+)-(\d+)$/', sanitize_text_field(wp_unslash((string) $_GET['payload_range'])), $matches)) {


            $metaQuery[] = [


                'key' => 'wprc_product_robot_payload',


                'value' => [(int) $matches[1], (int) $matches[2]],


                'compare' => 'BETWEEN',


                'type' => 'NUMERIC',


            ];


        }





        if (!empty($_GET['reach_range']) && preg_match('/^(\d+)-(\d+)$/', sanitize_text_field(wp_unslash((string) $_GET['reach_range'])), $matches)) {


            $metaQuery[] = [


                'key' => 'wprc_product_robot_reach',


                'value' => [(int) $matches[1], (int) $matches[2]],


                'compare' => 'BETWEEN',


                'type' => 'NUMERIC',


            ];


        }





        return $metaQuery;


    }





    public function enableIncludeChildrenProductCollectionBlock(array $query, \WP_Block $block, int $page): array


    {


        $isProductCollection = $block->context['query']['isProductCollectionBlock'] ?? false;


        if (!$isProductCollection) {


            return $query;


        }





        if (!empty($query['tax_query']) && is_array($query['tax_query'])) {


            foreach ($query['tax_query'] as $index => $taxClause) {


                if (!is_array($taxClause)) {


                    continue;


                }





                if (($taxClause['taxonomy'] ?? '') === 'product_cat') {


                    $query['tax_query'][$index]['include_children'] = true;


                }


            }


        }





        return $query;


    }





    public function restIncludeChildrenProductCollectionBlock(array $args, \WP_REST_Request $request): array


    {


        if (!(bool) $request->get_param('isProductCollectionBlock')) {


            return $args;


        }





        if (!empty($args['tax_query']) && is_array($args['tax_query'])) {


            foreach ($args['tax_query'] as $index => $taxClause) {


                if (!is_array($taxClause)) {


                    continue;


                }





                if (($taxClause['taxonomy'] ?? '') === 'product_cat') {


                    $args['tax_query'][$index]['include_children'] = true;


                }


            }


        }





        return $args;


    }





    private function hasActiveArchiveFilters(): bool


    {


        return !empty($_GET['reference_search']) || !empty($_GET['payload_range']) || !empty($_GET['reach_range']);


    }





}


