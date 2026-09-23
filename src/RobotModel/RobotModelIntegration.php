<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel;

use WPRC\Catalog\RobotModel\Admin\ControllersPage;
use WPRC\Catalog\RobotModel\Admin\RobotModelEditor;
use WPRC\Catalog\RobotModel\Admin\RobotModelGalleryEditor;
use WPRC\Catalog\RobotModel\WordPress\ContentTypes;
use WPRC\Catalog\RobotModel\WordPress\Permalinks;
use WPRC\Catalog\RobotModel\WordPress\PostSynchronizer;

defined('ABSPATH') || exit;

final class RobotModelIntegration
{
    public function __construct(
        private readonly ContentTypes $contentTypes,
        private readonly PostSynchronizer $postSynchronizer,
        private readonly Permalinks $permalinks,
        private readonly RobotModelEditor $editor,
        private readonly ControllersPage $controllersPage,
        private readonly RobotModelGalleryEditor $galleryEditor
    ) {
    }

    public function init(): void
    {
        // The former standalone plugin must be disabled before Catalog takes
        // ownership of the same CPT/tables. We avoid double registrations.
        if (defined('RC_ROBOT_MODELS_VERSION')) {
            add_action('admin_notices', static function (): void {
                if (current_user_can('activate_plugins')) {
                    echo '<div class="notice notice-error"><p><strong>RC Catalog :</strong> ' . esc_html__('désactivez le plugin autonome RC Robot Models. Ses données sont reprises directement par RC Catalog.', 'rc-catalog') . '</p></div>';
                }
            });
            return;
        }

        add_filter('use_block_editor_for_post_type', [$this->contentTypes, 'useBlockEditor'], 20, 2);
        add_action('init', [$this->contentTypes, 'register'], 25);
        $this->postSynchronizer->registerHooks();
        $this->permalinks->registerHooks();

        if (is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax())) {
            $this->editor->init();
            $this->controllersPage->init();
            $this->galleryEditor->init();
            add_action('admin_menu', [$this, 'registerFamilyMenu'], 45);
        }
    }

    public function registerFamilyMenu(): void
    {
        add_submenu_page(
            'edit.php?post_type=product',
            __('Familles / séries de robots', 'rc-catalog'),
            __('Familles / séries', 'rc-catalog'),
            'manage_product_terms',
            'edit-tags.php?taxonomy=' . ContentTypes::FAMILY_TAXONOMY . '&post_type=' . ContentTypes::POST_TYPE
        );
    }
}
