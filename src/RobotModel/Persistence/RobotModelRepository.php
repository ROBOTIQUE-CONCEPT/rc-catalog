<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Persistence;

use RuntimeException;
use WPRC\Catalog\Database\TableNames;
use WPRC\Catalog\RobotModel\Domain\AxisSpecification;
use WPRC\Catalog\RobotModel\Domain\RobotModel;
use WPRC\Core\Contracts\CacheInterface;

defined('ABSPATH') || exit;

final class RobotModelRepository
{
    private const CACHE_GROUP = 'wprc_catalog_robot_models';

    public function __construct(
        private readonly TableNames $tables,
        private readonly CacheInterface $cache
    ) {
    }

    public function findByPostId(int $postId): ?RobotModel
    {
        $modelId = $this->modelIdByPostId($postId);
        return $modelId !== null ? $this->find($modelId, $this->postLanguage($postId)) : null;
    }

    public function find(int $modelId, ?string $language = null): ?RobotModel
    {
        if ($modelId <= 0) {
            return null;
        }

        $language = $language !== null ? sanitize_key($language) : '';
        $cacheKey = 'model:' . $modelId . ':' . ($language !== '' ? $language : 'default');
        $cached = $this->cache->get($cacheKey, self::CACHE_GROUP, '__missing__');
        if ($cached !== '__missing__') {
            return $cached instanceof RobotModel ? $cached : null;
        }

        global $wpdb;
        $models = $this->tables->robotModels();
        $posts = $this->tables->robotModelPosts();

        $mappingOrder = $language !== ''
            ? $wpdb->prepare('CASE WHEN p.language = %s THEN 0 WHEN p.is_primary = 1 THEN 1 ELSE 2 END', $language)
            : 'CASE WHEN p.is_primary = 1 THEN 0 ELSE 1 END';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, p.post_id, p.language
             FROM {$models} m
             LEFT JOIN {$posts} p ON p.model_id = m.id
             WHERE m.id = %d AND m.deleted_at IS NULL
             ORDER BY {$mappingOrder}, p.id ASC
             LIMIT 1",
            $modelId
        ), ARRAY_A);

        if (!is_array($row)) {
            $this->cache->set($cacheKey, null, self::CACHE_GROUP, 300);
            return null;
        }

        $postId = isset($row['post_id']) ? (int) $row['post_id'] : null;
        $post = $postId ? get_post($postId) : null;
        $model = new RobotModel(
            id: (int) $row['id'],
            publicId: (string) $row['public_id'],
            reference: (string) $row['reference'],
            payloadKg: $this->nullableFloat($row['payload_kg'] ?? null),
            reachMm: $this->nullableFloat($row['reach_mm'] ?? null),
            massKg: $this->nullableFloat($row['mass_kg'] ?? null),
            repeatabilityMm: $this->nullableFloat($row['repeatability_mm'] ?? null),
            structure: $this->nullableString($row['structure'] ?? null),
            axesCount: $row['axes_count'] !== null ? (int) $row['axes_count'] : null,
            ipBase: $this->nullableString($row['ip_base'] ?? null),
            ipWrist: $this->nullableString($row['ip_wrist'] ?? null),
            status: (string) ($row['status'] ?? 'active'),
            postId: $postId,
            language: isset($row['language']) ? (string) $row['language'] : null,
            title: $post instanceof \WP_Post ? $post->post_title : null,
            axes: $this->axes($modelId)
        );

        $this->cache->set($cacheKey, $model, self::CACHE_GROUP, 3600);
        return $model;
    }

    /** @return RobotModel[] */
    public function all(?string $language = null): array
    {
        global $wpdb;
        $ids = $wpdb->get_col("SELECT id FROM {$this->tables->robotModels()} WHERE deleted_at IS NULL ORDER BY reference ASC");
        $models = [];
        foreach ($ids as $id) {
            $model = $this->find((int) $id, $language);
            if ($model instanceof RobotModel) {
                $models[] = $model;
            }
        }
        return $models;
    }

    public function modelIdByPostId(int $postId): ?int
    {
        if ($postId <= 0) {
            return null;
        }
        global $wpdb;
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT model_id FROM {$this->tables->robotModelPosts()} WHERE post_id = %d LIMIT 1",
            $postId
        ));
        return $id !== null ? (int) $id : null;
    }

    public function primaryPostId(int $modelId): ?int
    {
        global $wpdb;
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$this->tables->robotModelPosts()} WHERE model_id = %d ORDER BY is_primary DESC, id ASC LIMIT 1",
            $modelId
        ));
        return $id !== null ? (int) $id : null;
    }

    public function postIdForLanguage(int $modelId, string $language): ?int
    {
        global $wpdb;
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$this->tables->robotModelPosts()} WHERE model_id = %d AND language = %s LIMIT 1",
            $modelId,
            sanitize_key($language)
        ));
        return $id !== null ? (int) $id : $this->primaryPostId($modelId);
    }

    /**
     * Synchronize all Polylang translations with one shared technical entity.
     *
     * Existing mappings from the former standalone plugin are deliberately
     * merged here. The default-language post wins when two independent model
     * entities become linked as translations.
     *
     * @param int[] $translationPostIds
     */
    public function ensureForPost(int $postId, string $language, bool $isPrimary, string $reference, array $translationPostIds = []): int
    {
        $postIds = array_values(array_unique(array_filter(array_map('absint', array_merge([$postId], $translationPostIds)))));
        $defaultLanguage = $this->defaultLanguage();
        $currentModelId = $this->modelIdByPostId($postId);
        $candidateModelIds = [];
        $defaultModelId = null;

        foreach ($postIds as $candidatePostId) {
            $candidateModelId = $this->modelIdByPostId($candidatePostId);
            if ($candidateModelId !== null) {
                $candidateModelIds[$candidateModelId] = $candidateModelId;
                if ($this->postLanguage($candidatePostId) === $defaultLanguage) {
                    $defaultModelId = $candidateModelId;
                }
            }
        }

        $modelId = $defaultModelId ?? $currentModelId ?? (reset($candidateModelIds) ?: null);
        if ($modelId === null) {
            $modelId = $this->create($reference);
        }

        foreach ($postIds as $candidatePostId) {
            if (get_post_type($candidatePostId) !== 'rc_robot_model') {
                continue;
            }
            $candidateLanguage = $this->postLanguage($candidatePostId) ?? $defaultLanguage;
            $this->upsertPostMapping(
                $modelId,
                $candidatePostId,
                $candidateLanguage,
                $candidateLanguage === $defaultLanguage
            );
        }

        $this->restore($modelId);

        foreach ($candidateModelIds as $candidateModelId) {
            if ($candidateModelId !== $modelId && $this->countPostMappings($candidateModelId) === 0) {
                $this->markDeleted($candidateModelId);
            }
        }

        $primaryPostId = null;
        foreach ($postIds as $candidatePostId) {
            if ($this->postLanguage($candidatePostId) === $defaultLanguage) {
                $primaryPostId = $candidatePostId;
                break;
            }
        }
        if ($primaryPostId !== null) {
            $primaryPost = get_post($primaryPostId);
            if ($primaryPost instanceof \WP_Post) {
                $this->updateReference($modelId, trim($primaryPost->post_title));
            }
        } elseif ($isPrimary || $language === $defaultLanguage) {
            $this->updateReference($modelId, $reference);
        }

        return $modelId;
    }

    public function countPostMappings(int $modelId): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->robotModelPosts()} WHERE model_id = %d",
            $modelId
        ));
    }

    public function markDeleted(int $modelId): void
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $wpdb->update($this->tables->robotModels(), [
            'deleted_at' => $now,
            'updated_at' => $now,
        ], ['id' => $modelId], ['%s', '%s'], ['%d']);
        $this->flush($modelId);
    }

    public function restore(int $modelId): void
    {
        global $wpdb;
        $wpdb->update($this->tables->robotModels(), [
            'deleted_at' => null,
            'updated_at' => current_time('mysql', true),
        ], ['id' => $modelId]);
        $this->flush($modelId);
    }

    public function create(string $reference): int
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $ok = $wpdb->insert($this->tables->robotModels(), [
            'public_id' => wp_generate_uuid4(),
            'reference' => sanitize_text_field($reference),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%s', '%s', '%s', '%s', '%s']);
        if ($ok === false) {
            throw new RuntimeException('Unable to create robot model entity.');
        }
        return (int) $wpdb->insert_id;
    }

    public function upsertPostMapping(int $modelId, int $postId, string $language, bool $isPrimary): void
    {
        global $wpdb;
        $now = current_time('mysql', true);
        $table = $this->tables->robotModelPosts();

        if ($isPrimary) {
            $wpdb->update($table, ['is_primary' => 0, 'updated_at' => $now], ['model_id' => $modelId], ['%d', '%s'], ['%d']);
        }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE post_id = %d", $postId));
        $data = [
            'model_id' => $modelId,
            'post_id' => $postId,
            'language' => sanitize_key($language) ?: 'und',
            'is_primary' => $isPrimary ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($existing !== null) {
            $wpdb->update($table, $data, ['id' => (int) $existing]);
        } else {
            $data['created_at'] = $now;
            $wpdb->insert($table, $data);
        }
        $this->flush($modelId);
    }

    public function updateReference(int $modelId, string $reference): void
    {
        global $wpdb;
        $wpdb->update($this->tables->robotModels(), [
            'reference' => sanitize_text_field($reference),
            'updated_at' => current_time('mysql', true),
        ], ['id' => $modelId], ['%s', '%s'], ['%d']);
        $this->flush($modelId);
    }

    /** @param array<string,mixed> $data */
    public function updateTechnicalData(int $modelId, array $data): void
    {
        global $wpdb;
        $allowed = ['payload_kg', 'reach_mm', 'mass_kg', 'repeatability_mm', 'structure', 'axes_count', 'ip_base', 'ip_wrist', 'status'];
        $payload = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }
        $payload['updated_at'] = current_time('mysql', true);
        $wpdb->update($this->tables->robotModels(), $payload, ['id' => $modelId]);
        $this->flush($modelId);
    }

    /** @param AxisSpecification[] $axes */
    public function replaceAxes(int $modelId, array $axes): void
    {
        global $wpdb;
        $table = $this->tables->robotModelAxes();
        $wpdb->delete($table, ['model_id' => $modelId], ['%d']);
        $now = current_time('mysql', true);
        foreach ($axes as $axis) {
            if (!$axis instanceof AxisSpecification) {
                continue;
            }
            $wpdb->insert($table, [
                'model_id' => $modelId,
                'axis_number' => $axis->axisNumber,
                'motion_type' => $axis->motionType,
                'range_min' => $axis->rangeMin,
                'range_max' => $axis->rangeMax,
                'range_unit' => $axis->rangeUnit,
                'max_velocity' => $axis->maxVelocity,
                'velocity_unit' => $axis->velocityUnit,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->flush($modelId);
    }

    /** @return AxisSpecification[] */
    public function axes(int $modelId): array
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables->robotModelAxes()} WHERE model_id = %d ORDER BY axis_number ASC",
            $modelId
        ), ARRAY_A);
        $axes = [];
        foreach ($rows as $row) {
            $axes[] = new AxisSpecification(
                (int) $row['axis_number'],
                (string) $row['motion_type'],
                $this->nullableFloat($row['range_min']),
                $this->nullableFloat($row['range_max']),
                'deg',
                $this->nullableFloat($row['max_velocity']),
                'deg_s'
            );
        }
        return $axes;
    }

    public function removePostMapping(int $postId): void
    {
        global $wpdb;
        $modelId = $this->modelIdByPostId($postId);
        if ($modelId === null) {
            return;
        }
        $wpdb->delete($this->tables->robotModelPosts(), ['post_id' => $postId], ['%d']);
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables->robotModelPosts()} WHERE model_id = %d",
            $modelId
        ));
        if ($count === 0) {
            $wpdb->update($this->tables->robotModels(), [
                'deleted_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true),
            ], ['id' => $modelId]);
        }
        $this->flush($modelId);
    }

    public function flush(?int $modelId = null): void
    {
        if ($modelId !== null) {
            // Languages are not enumerable cheaply; flush the dedicated group.
        }
        $this->cache->flushGroup(self::CACHE_GROUP);
    }

    private function defaultLanguage(): string
    {
        if (function_exists('pll_default_language')) {
            $language = pll_default_language('slug');
            if (is_string($language) && $language !== '') {
                return $language;
            }
        }
        return strtolower(substr((string) get_locale(), 0, 2)) ?: 'und';
    }

    private function postLanguage(int $postId): ?string
    {
        if (function_exists('pll_get_post_language')) {
            $language = pll_get_post_language($postId, 'slug');
            return is_string($language) && $language !== '' ? $language : null;
        }
        return null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }
}
