<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\WordPress;

defined('ABSPATH') || exit;

final class ContentTypes
{
    public const POST_TYPE = 'rc_robot_model';
    public const FAMILY_TAXONOMY = 'rc_robot_family';

    public function register(): void
    {
        $this->registerFamilyTaxonomy();
        $this->registerRobotModel();

        // product_brand is owned by WooCommerce. Catalog only extends its
        // object types instead of creating a second brand vocabulary.
        if (taxonomy_exists('product_brand')) {
            register_taxonomy_for_object_type('product_brand', self::POST_TYPE);
        }
    }

    public function useBlockEditor(bool $useBlockEditor, string $postType): bool
    {
        return $postType === self::POST_TYPE ? false : $useBlockEditor;
    }

    private function registerRobotModel(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Modèles de robots', 'rc-catalog'),
                'singular_name' => __('Modèle de robot', 'rc-catalog'),
                'add_new_item' => __('Ajouter un modèle de robot', 'rc-catalog'),
                'edit_item' => __('Modifier le modèle de robot', 'rc-catalog'),
                'new_item' => __('Nouveau modèle de robot', 'rc-catalog'),
                'view_item' => __('Voir le modèle de robot', 'rc-catalog'),
                'search_items' => __('Rechercher des modèles de robots', 'rc-catalog'),
                'not_found' => __('Aucun modèle de robot trouvé.', 'rc-catalog'),
                'menu_name' => __('Modèles de robots', 'rc-catalog'),
            ],
            'public' => true,
            'query_var' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=product',
            'show_in_rest' => true,
            'rest_base' => 'robot-models',
            'has_archive' => 'robots/modeles',
            'hierarchical' => false,
            'rewrite' => [
                'slug' => 'robots/modeles',
                'with_front' => false,
            ],
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
            'taxonomies' => [self::FAMILY_TAXONOMY, 'product_brand'],
            'menu_icon' => 'dashicons-superhero-alt',
            'map_meta_cap' => true,
            'capabilities' => [
                'edit_post' => 'edit_product',
                'read_post' => 'read_product',
                'delete_post' => 'delete_product',
                'edit_posts' => 'edit_products',
                'edit_others_posts' => 'edit_others_products',
                'publish_posts' => 'publish_products',
                'read_private_posts' => 'read_private_products',
                'delete_posts' => 'delete_products',
                'delete_private_posts' => 'delete_private_products',
                'delete_published_posts' => 'delete_published_products',
                'delete_others_posts' => 'delete_others_products',
                'edit_private_posts' => 'edit_private_products',
                'edit_published_posts' => 'edit_published_products',
                'create_posts' => 'edit_products',
            ],
            'delete_with_user' => false,
        ]);
    }

    private function registerFamilyTaxonomy(): void
    {
        register_taxonomy(self::FAMILY_TAXONOMY, [self::POST_TYPE], [
            'labels' => [
                'name' => __('Familles / séries', 'rc-catalog'),
                'singular_name' => __('Famille / série', 'rc-catalog'),
                'search_items' => __('Rechercher une famille ou série', 'rc-catalog'),
                'all_items' => __('Toutes les familles / séries', 'rc-catalog'),
                'parent_item' => __('Famille parente', 'rc-catalog'),
                'edit_item' => __('Modifier la famille / série', 'rc-catalog'),
                'add_new_item' => __('Ajouter une famille / série', 'rc-catalog'),
                'menu_name' => __('Familles / séries', 'rc-catalog'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'robots/modeles/famille',
                'with_front' => false,
                'hierarchical' => true,
            ],
            'capabilities' => [
                'manage_terms' => 'manage_product_terms',
                'edit_terms' => 'edit_product_terms',
                'delete_terms' => 'delete_product_terms',
                'assign_terms' => 'assign_product_terms',
            ],
        ]);
    }
}
