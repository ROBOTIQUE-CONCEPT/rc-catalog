<?php

declare(strict_types=1);

namespace WPRC\Catalog\WPSEO;

defined('ABSPATH') || exit;

/**
 * Catalog-specific breadcrumb data rules.
 *
 * Rendering and placement are deliberately owned by the active theme.
 */
final class Breadcrumbs
{
    /** @param array<int,array<string,mixed>> $links @return array<int,array<string,mixed>> */
    public function filterLinks(array $links): array
    {
        if (function_exists('is_woocommerce') && is_woocommerce() && is_tax('product_cat')) {
            foreach ($links as $key => $link) {
                if (isset($link['term_id'])) {
                    $label = (string) get_term_meta((int) $link['term_id'], 'category_menu_title', true);
                    if ($label !== '') {
                        $links[$key]['text'] = $label;
                    }
                }
            }

            if (isset($links[1])) {
                unset($links[1]);
                $links = array_values($links);
            }

            return $links;
        }

        if (!(function_exists('is_product') && is_product())) {
            return $links;
        }

        global $product;
        if (!$product instanceof \WC_Product || $links === []) {
            return $links;
        }

        $home = $links[0] ?? null;
        $page = $links[array_key_last($links)] ?? null;
        if (!is_array($home) || !is_array($page)) {
            return $links;
        }

        $terms = wp_get_post_terms($product->get_id(), 'product_cat');
        if (is_wp_error($terms) || $terms === []) {
            return $links;
        }

        usort($terms, static function (\WP_Term $a, \WP_Term $b): int {
            return count(get_ancestors($b->term_id, 'product_cat')) <=> count(get_ancestors($a->term_id, 'product_cat'));
        });

        $deepest = $terms[0];
        $ancestorIds = array_reverse(get_ancestors($deepest->term_id, 'product_cat'));
        $ancestorIds[] = $deepest->term_id;

        $result = [$home];
        foreach ($ancestorIds as $termId) {
            $term = get_term((int) $termId, 'product_cat');
            if (!$term instanceof \WP_Term) {
                continue;
            }

            $url = get_term_link($term);
            if (is_wp_error($url)) {
                continue;
            }

            $label = (string) get_term_meta($term->term_id, 'category_menu_title', true);
            $result[] = [
                'url' => $url,
                'text' => $label !== '' ? $label : $term->name,
                'term_id' => $term->term_id,
                'taxonomy' => 'product_cat',
            ];
        }

        if (function_exists('rc_translate')) {
            $page['text'] = rc_translate(
                'seo_breadcrumb_single_product_' . $product->get_type(),
                (int) $product->get_id()
            );
        }
        $result[] = $page;

        return $result;
    }
}
