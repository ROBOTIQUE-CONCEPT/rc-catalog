<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\WordPress;

use WPRC\Catalog\RobotModel\Application\Canonicalizer;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRepository;
use WP_Post;

defined('ABSPATH') || exit;

final class PostSynchronizer
{
    public function __construct(
        private readonly RobotModelRepository $models,
        private readonly Canonicalizer $canonicalizer
    ) {
    }

    public function registerHooks(): void
    {
        add_action('save_post_' . ContentTypes::POST_TYPE, [$this, 'synchronize'], 20, 3);
        add_action('before_delete_post', [$this, 'removeMapping'], 20, 2);
    }

    public function synchronize(int $postId, WP_Post $post, bool $update): void
    {
        unset($update);
        if ($post->post_type !== ContentTypes::POST_TYPE || $post->post_status === 'auto-draft') {
            return;
        }
        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        $language = $this->canonicalizer->postLanguage($postId);
        $defaultLanguage = $this->canonicalizer->defaultLanguage();
        $translations = [$postId];
        if (function_exists('pll_get_post_translations')) {
            $found = pll_get_post_translations($postId);
            if (is_array($found)) {
                $translations = array_values(array_map('intval', $found));
            }
        }

        $this->models->ensureForPost(
            $postId,
            $language,
            $language === $defaultLanguage,
            trim($post->post_title),
            $translations
        );
    }

    public function removeMapping(int $postId, WP_Post $post): void
    {
        if ($post->post_type === ContentTypes::POST_TYPE) {
            $this->models->removePostMapping($postId);
        }
    }
}
