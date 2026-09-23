<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Admin;

use WPRC\Catalog\RobotModel\Persistence\RobotModelMediaRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRepository;
use WPRC\Catalog\RobotModel\WordPress\ContentTypes;
use WPRC\Core\Contracts\LoggerInterface;
use WP_Post;

defined('ABSPATH') || exit;

final class RobotModelGalleryEditor
{
    private const NONCE_ACTION = 'wprc_robot_model_gallery_save';
    private const NONCE_NAME = '_wprc_robot_model_gallery_nonce';

    public function __construct(
        private readonly RobotModelRepository $models,
        private readonly RobotModelMediaRepository $media,
        private readonly LoggerInterface $logger
    ) {
    }

    public function init(): void
    {
        add_action('add_meta_boxes_' . ContentTypes::POST_TYPE, [$this, 'addMetaBox']);
        add_action('save_post_' . ContentTypes::POST_TYPE, [$this, 'save'], 80, 3);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('delete_attachment', [$this->media, 'deleteAttachment']);
    }

    public function addMetaBox(WP_Post $post): void
    {
        add_meta_box(
            'wprc_robot_model_gallery',
            __('Galerie du modèle', 'rc-catalog'),
            [$this, 'render'],
            ContentTypes::POST_TYPE,
            'side',
            'default'
        );
    }

    public function render(WP_Post $post): void
    {
        $model = $this->models->findByPostId($post->ID);
        $ids = $model ? $this->media->attachmentIds($model->id) : [];
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        ?>
        <div class="wprc-rm-gallery" data-wprc-rm-gallery>
            <p class="description"><?php echo esc_html__('L’image mise en avant reste le visuel principal. Cette galerie secondaire est commune à toutes les traductions Polylang.', 'rc-catalog'); ?></p>
            <ul class="wprc-rm-gallery__list" data-wprc-rm-gallery-list>
                <?php foreach ($ids as $attachmentId) : $image = wp_get_attachment_image($attachmentId, 'thumbnail', false, ['alt' => '']); ?>
                    <?php if ($image !== '') : ?>
                        <li class="wprc-rm-gallery__item" data-attachment-id="<?php echo esc_attr((string) $attachmentId); ?>">
                            <?php echo wp_kses_post($image); ?>
                            <div><button type="button" class="button-link" data-gallery-up>↑</button> <button type="button" class="button-link" data-gallery-down>↓</button> <button type="button" class="button-link-delete" data-gallery-remove><?php echo esc_html__('Retirer', 'rc-catalog'); ?></button></div>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <input type="hidden" name="wprc_robot_model_gallery_ids" value="<?php echo esc_attr(implode(',', $ids)); ?>" data-wprc-rm-gallery-input>
            <p><button type="button" class="button" data-wprc-rm-gallery-add><?php echo esc_html__('Ajouter des images', 'rc-catalog'); ?></button></p>
        </div>
        <?php
    }

    public function save(int $postId, WP_Post $post, bool $update): void
    {
        unset($update);
        if ($post->post_type !== ContentTypes::POST_TYPE || $post->post_status === 'auto-draft' || wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $model = $this->models->findByPostId($postId);
        if (!$model) {
            return;
        }

        $raw = isset($_POST['wprc_robot_model_gallery_ids']) ? sanitize_text_field(wp_unslash((string) $_POST['wprc_robot_model_gallery_ids'])) : '';
        $ids = array_values(array_unique(array_filter(array_map('absint', explode(',', $raw)))));
        try {
            $this->media->replace($model->id, $ids);
        } catch (\Throwable $exception) {
            $this->logger->error('Robot model gallery save failed.', 'catalog', [
                'post_id' => $postId,
                'model_id' => $model->id,
                'error' => $exception->getMessage(),
            ], 'catalog.robot_model.gallery_save_failed');
        }
    }

    public function enqueueAssets(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== ContentTypes::POST_TYPE) {
            return;
        }
        wp_enqueue_media();
    }
}
