<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Persistence;

use RuntimeException;
use WPRC\Catalog\Database\TableNames;
use WPRC\Core\Contracts\CacheInterface;

defined('ABSPATH') || exit;

/**
 * Persists the language-independent secondary gallery of a robot model.
 */
final class RobotModelMediaRepository
{
    private const CACHE_GROUP = 'wprc_catalog_robot_model_media';

    public function __construct(
        private readonly TableNames $tables,
        private readonly CacheInterface $cache
    ) {
    }

    /** @return int[] */
    public function attachmentIds(int $modelId): array
    {
        if ($modelId <= 0) {
            return [];
        }

        $key = 'model:' . $modelId;
        $cached = $this->cache->get($key, self::CACHE_GROUP, null);
        if (is_array($cached)) {
            return array_values(array_map('intval', $cached));
        }

        global $wpdb;
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT attachment_id FROM {$this->tables->robotModelMedia()} WHERE model_id = %d ORDER BY sort_order ASC, id ASC",
            $modelId
        ));
        $ids = array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
        $this->cache->set($key, $ids, self::CACHE_GROUP, 3600);
        return $ids;
    }

    /** @param int[] $attachmentIds */
    public function replace(int $modelId, array $attachmentIds): void
    {
        if ($modelId <= 0) {
            return;
        }

        $attachmentIds = array_values(array_unique(array_filter(array_map('absint', $attachmentIds), static function (int $id): bool {
            return $id > 0 && get_post_type($id) === 'attachment';
        })));

        global $wpdb;
        $table = $this->tables->robotModelMedia();
        $now = current_time('mysql', true);
        $wpdb->query('START TRANSACTION');

        try {
            if ($wpdb->delete($table, ['model_id' => $modelId], ['%d']) === false) {
                throw new RuntimeException('Unable to clear robot model media.');
            }

            foreach ($attachmentIds as $sortOrder => $attachmentId) {
                if ($wpdb->insert($table, [
                    'model_id' => $modelId,
                    'attachment_id' => $attachmentId,
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], ['%d', '%d', '%d', '%s', '%s']) === false) {
                    throw new RuntimeException('Unable to save robot model media.');
                }
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $exception) {
            $wpdb->query('ROLLBACK');
            throw $exception;
        }

        $this->cache->flushGroup(self::CACHE_GROUP);
    }

    public function deleteAttachment(int $attachmentId): void
    {
        if ($attachmentId <= 0) {
            return;
        }
        global $wpdb;
        $wpdb->delete($this->tables->robotModelMedia(), ['attachment_id' => $attachmentId], ['%d']);
        $this->cache->flushGroup(self::CACHE_GROUP);
    }
}
