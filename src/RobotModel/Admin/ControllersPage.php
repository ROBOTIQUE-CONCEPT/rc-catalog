<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Admin;

use WPRC\Catalog\Security\Capabilities;

use Throwable;
use WPRC\Catalog\RobotModel\Application\Canonicalizer;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;

defined('ABSPATH') || exit;

final class ControllersPage
{
    public const PAGE_SLUG = 'wprc-robot-controllers';

    public function __construct(
        private readonly ControllerRepository $controllers,
        private readonly Canonicalizer $canonicalizer
    ) {
    }

    public function init(): void
    {
        add_action('admin_menu', [$this, 'registerMenu'], 40);
        add_action('admin_post_wprc_save_robot_controller', [$this, 'save']);
        add_action('admin_post_wprc_delete_robot_controller', [$this, 'delete']);
    }

    public function registerMenu(): void
    {
        add_submenu_page(
            'edit.php?post_type=product',
            __('Contrôleurs robots', 'rc-catalog'),
            __('Contrôleurs', 'rc-catalog'),
            'manage_woocommerce',
            self::PAGE_SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!Capabilities::canManageControllers()) {
            wp_die(esc_html__('Vous n’êtes pas autorisé à gérer les contrôleurs.', 'rc-catalog'));
        }

        $editingId = isset($_GET['controller_id']) ? absint(wp_unslash((string) $_GET['controller_id'])) : 0;
        $editing = $editingId > 0 ? $this->controllers->find($editingId) : null;
        $brands = $this->defaultLanguageBrands();
        $notice = isset($_GET['wprc_notice']) ? sanitize_key(wp_unslash((string) $_GET['wprc_notice'])) : '';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Contrôleurs robots', 'rc-catalog'); ?></h1>
            <?php if ($notice === 'saved') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Contrôleur enregistré.', 'rc-catalog'); ?></p></div>
            <?php elseif ($notice === 'deleted') : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Contrôleur supprimé.', 'rc-catalog'); ?></p></div>
            <?php elseif ($notice === 'error') : ?>
                <div class="notice notice-error"><p><?php echo esc_html__('Le contrôleur n’a pas pu être enregistré.', 'rc-catalog'); ?></p></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:minmax(480px,2fr) minmax(320px,1fr);gap:24px;align-items:start;">
                <div>
                    <table class="widefat striped">
                        <thead><tr>
                            <th><?php echo esc_html__('Nom', 'rc-catalog'); ?></th>
                            <th><?php echo esc_html__('Référence', 'rc-catalog'); ?></th>
                            <th><?php echo esc_html__('Marque', 'rc-catalog'); ?></th>
                            <th><?php echo esc_html__('UID', 'rc-catalog'); ?></th>
                            <th><?php echo esc_html__('Statut', 'rc-catalog'); ?></th>
                            <th></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($this->controllers->all(null, false) as $controller) :
                            $brand = get_term($controller->brandTermId, 'product_brand'); ?>
                            <tr>
                                <td><strong><?php echo esc_html($controller->name); ?></strong></td>
                                <td><?php echo esc_html((string) $controller->reference); ?></td>
                                <td><?php echo esc_html($brand instanceof \WP_Term ? $brand->name : ('#' . $controller->brandTermId)); ?></td>
                                <td><code><?php echo esc_html($controller->uid); ?></code></td>
                                <td><?php echo esc_html($controller->status === 'active' ? __('Actif', 'rc-catalog') : __('Inactif', 'rc-catalog')); ?></td>
                                <td style="white-space:nowrap;">
                                    <a class="button button-small" href="<?php echo esc_url(add_query_arg(['post_type' => 'product', 'page' => self::PAGE_SLUG, 'controller_id' => $controller->id], admin_url('edit.php'))); ?>"><?php echo esc_html__('Modifier', 'rc-catalog'); ?></a>
                                    <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'wprc_delete_robot_controller', 'controller_id' => $controller->id], admin_url('admin-post.php')), 'wprc_delete_robot_controller_' . $controller->id)); ?>" onclick="return confirm('<?php echo esc_js(__('Supprimer ce contrôleur et ses compatibilités ?', 'rc-catalog')); ?>');"><?php echo esc_html__('Supprimer', 'rc-catalog'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($this->controllers->all(null, false) === []) : ?>
                            <tr><td colspan="6"><?php echo esc_html__('Aucun contrôleur enregistré.', 'rc-catalog'); ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="postbox" style="padding:16px;">
                    <h2 style="margin-top:0;"><?php echo esc_html($editing ? __('Modifier le contrôleur', 'rc-catalog') : __('Ajouter un contrôleur', 'rc-catalog')); ?></h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="wprc_save_robot_controller">
                        <input type="hidden" name="controller_id" value="<?php echo esc_attr((string) ($editing?->id ?? 0)); ?>">
                        <?php wp_nonce_field('wprc_save_robot_controller'); ?>
                        <p>
                            <label for="wprc-controller-brand"><strong><?php echo esc_html__('Marque', 'rc-catalog'); ?></strong></label><br>
                            <select id="wprc-controller-brand" name="brand_term_id" class="widefat" required>
                                <option value=""><?php echo esc_html__('— Sélectionner —', 'rc-catalog'); ?></option>
                                <?php foreach ($brands as $brand) : ?>
                                    <option value="<?php echo esc_attr((string) $brand->term_id); ?>" <?php selected($editing?->brandTermId ?? 0, $brand->term_id); ?>><?php echo esc_html($brand->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p><label><strong><?php echo esc_html__('Nom', 'rc-catalog'); ?></strong></label><br><input class="widefat" type="text" name="name" value="<?php echo esc_attr($editing?->name ?? ''); ?>" required></p>
                        <p><label><strong><?php echo esc_html__('Référence', 'rc-catalog'); ?></strong></label><br><input class="widefat" type="text" name="reference" value="<?php echo esc_attr((string) ($editing?->reference ?? '')); ?>"></p>
                        <p><label><strong><?php echo esc_html__('Statut', 'rc-catalog'); ?></strong></label><br>
                            <select class="widefat" name="status">
                                <option value="active" <?php selected($editing?->status ?? 'active', 'active'); ?>><?php echo esc_html__('Actif', 'rc-catalog'); ?></option>
                                <option value="inactive" <?php selected($editing?->status ?? 'active', 'inactive'); ?>><?php echo esc_html__('Inactif', 'rc-catalog'); ?></option>
                            </select>
                        </p>
                        <p><label><strong><?php echo esc_html__('Ordre', 'rc-catalog'); ?></strong></label><br><input class="small-text" type="number" min="0" name="sort_order" value="<?php echo esc_attr((string) ($editing?->sortOrder ?? 0)); ?>"></p>
                        <?php submit_button($editing ? __('Mettre à jour', 'rc-catalog') : __('Ajouter', 'rc-catalog'), 'primary', 'submit', false); ?>
                        <?php if ($editing) : ?>&nbsp;<a class="button" href="<?php echo esc_url(add_query_arg(['post_type' => 'product', 'page' => self::PAGE_SLUG], admin_url('edit.php'))); ?>"><?php echo esc_html__('Annuler', 'rc-catalog'); ?></a><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function save(): void
    {
        if (!Capabilities::canManageControllers()) {
            wp_die(esc_html__('Action interdite.', 'rc-catalog'));
        }
        check_admin_referer('wprc_save_robot_controller');

        try {
            $id = isset($_POST['controller_id']) ? absint(wp_unslash((string) $_POST['controller_id'])) : 0;
            $brand = isset($_POST['brand_term_id']) ? $this->canonicalizer->term(absint(wp_unslash((string) $_POST['brand_term_id']))) : 0;
            $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash((string) $_POST['name'])) : '';
            $reference = isset($_POST['reference']) ? sanitize_text_field(wp_unslash((string) $_POST['reference'])) : '';
            $status = isset($_POST['status']) ? sanitize_key(wp_unslash((string) $_POST['status'])) : 'active';
            $sort = isset($_POST['sort_order']) ? max(0, (int) $_POST['sort_order']) : 0;
            $savedId = $this->controllers->save($id > 0 ? $id : null, $brand, $name, $reference, $status, $sort);
            wp_safe_redirect(add_query_arg(['post_type' => 'product', 'page' => self::PAGE_SLUG, 'controller_id' => $savedId, 'wprc_notice' => 'saved'], admin_url('edit.php')));
        } catch (Throwable) {
            wp_safe_redirect(add_query_arg(['post_type' => 'product', 'page' => self::PAGE_SLUG, 'wprc_notice' => 'error'], admin_url('edit.php')));
        }
        exit;
    }

    public function delete(): void
    {
        if (!Capabilities::canManageControllers()) {
            wp_die(esc_html__('Action interdite.', 'rc-catalog'));
        }
        $id = isset($_GET['controller_id']) ? absint(wp_unslash((string) $_GET['controller_id'])) : 0;
        check_admin_referer('wprc_delete_robot_controller_' . $id);
        $this->controllers->delete($id);
        wp_safe_redirect(add_query_arg(['post_type' => 'product', 'page' => self::PAGE_SLUG, 'wprc_notice' => 'deleted'], admin_url('edit.php')));
        exit;
    }

    /** @return \WP_Term[] */
    private function defaultLanguageBrands(): array
    {
        $terms = get_terms(['taxonomy' => 'product_brand', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        if (is_wp_error($terms) || !is_array($terms)) {
            return [];
        }
        $default = $this->canonicalizer->defaultLanguage();
        return array_values(array_filter($terms, static function ($term) use ($default): bool {
            if (!$term instanceof \WP_Term) {
                return false;
            }
            if (!function_exists('pll_get_term_language')) {
                return true;
            }
            $lang = pll_get_term_language($term->term_id, 'slug');
            return !is_string($lang) || $lang === '' || $lang === $default;
        }));
    }
}
