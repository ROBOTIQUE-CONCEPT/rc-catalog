<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Persistence;

use WPRC\Catalog\Database\TableNames;
use WPRC\Catalog\RobotModel\Application\Canonicalizer;
use WPRC\Core\Contracts\CacheInterface;

defined('ABSPATH') || exit;

final class RobotModelRelationRepository
{
    private const CACHE_GROUP = 'wprc_catalog_robot_model_relations';

    public function __construct(
        private readonly TableNames $tables,
        private readonly Canonicalizer $canonicalizer,
        private readonly CacheInterface $cache
    ) {
    }

    /** @return int[] */
    public function controllerIds(int $modelId): array
    {
        global $wpdb;

        return array_map('intval', $wpdb->get_col($wpdb->prepare(
            "SELECT controller_id FROM {$this->tables->robotModelControllers()} WHERE model_id = %d ORDER BY sort_order ASC, id ASC",
            $modelId
        )));
    }

    /** @param int[] $controllerIds */
    public function replaceControllers(int $modelId, array $controllerIds): void
    {
        global $wpdb;

        $table = $this->tables->robotModelControllers();
        $wpdb->delete($table, ['model_id' => $modelId], ['%d']);
        $now = current_time('mysql', true);

        foreach (array_values(array_unique(array_filter(array_map('intval', $controllerIds)))) as $sort => $controllerId) {
            $wpdb->insert($table, [
                'model_id' => $modelId,
                'controller_id' => $controllerId,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->flush();
    }

    public function controllerIsCompatible(int $modelId, int $controllerId): bool
    {
        global $wpdb;

        if ($modelId <= 0 || $controllerId <= 0) {
            return false;
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$this->tables->robotModelControllers()} WHERE model_id = %d AND controller_id = %d LIMIT 1",
            $modelId,
            $controllerId
        ));
    }

    /** @return int[] */
    public function pageIds(int $modelId, string $relationType): array
    {
        global $wpdb;

        return array_map('intval', $wpdb->get_col($wpdb->prepare(
            "SELECT page_id FROM {$this->tables->robotModelPages()} WHERE model_id = %d AND relation_type = %s ORDER BY sort_order ASC, id ASC",
            $modelId,
            sanitize_key($relationType)
        )));
    }

    /** @param int[] $pageIds */
    public function replacePages(int $modelId, string $relationType, array $pageIds): void
    {
        global $wpdb;

        $relationType = sanitize_key($relationType);
        $table = $this->tables->robotModelPages();
        $wpdb->delete($table, ['model_id' => $modelId, 'relation_type' => $relationType], ['%d', '%s']);
        $now = current_time('mysql', true);
        $canonical = [];

        foreach ($pageIds as $pageId) {
            $pageId = $this->canonicalizer->post((int) $pageId);
            if ($pageId > 0 && get_post_type($pageId) === 'page') {
                $canonical[$pageId] = $pageId;
            }
        }

        foreach (array_values($canonical) as $sort => $pageId) {
            $wpdb->insert($table, [
                'model_id' => $modelId,
                'page_id' => $pageId,
                'relation_type' => $relationType,
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->flush();
    }

    /**
     * Technical parts/consumables/services are ERP entities, not WooCommerce
     * content. The relation stores the provider source + external identifier and
     * a small display snapshot so the technical repository stays readable when
     * the ERP is temporarily unavailable.
     *
     * @return array<int,array<string,mixed>>
     */
    public function erpProducts(int $modelId, ?string $relationType = null): array
    {
        global $wpdb;

        $table = $this->tables->robotModelErpProducts();
        if ($relationType !== null) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE model_id = %d AND relation_type = %s ORDER BY sort_order ASC, id ASC",
                $modelId,
                sanitize_key($relationType)
            ), ARRAY_A) ?: [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE model_id = %d ORDER BY relation_type ASC, sort_order ASC, id ASC",
            $modelId
        ), ARRAY_A) ?: [];
    }

    /**
     * @param array<int,array{erp_source:string,external_product_id:string,product_name?:string,product_reference?:string,quantity?:float|int|string}> $items
     */
    public function replaceErpProducts(int $modelId, string $relationType, array $items): void
    {
        global $wpdb;

        $relationType = sanitize_key($relationType);
        $table = $this->tables->robotModelErpProducts();
        $wpdb->delete($table, ['model_id' => $modelId, 'relation_type' => $relationType], ['%d', '%s']);

        foreach (array_values($items) as $sort => $item) {
            $this->insertErpProduct($modelId, $relationType, $item, $sort);
        }

        $this->flush();
    }

    /** @return array<int,array<string,mixed>> */
    public function lubrication(int $modelId): array
    {
        return $this->maintenanceRows($this->tables->robotModelLubrication(), $modelId);
    }

    /** @param array<int,array<string,mixed>> $rows */
    public function replaceLubrication(int $modelId, array $rows): void
    {
        global $wpdb;

        $table = $this->tables->robotModelLubrication();
        $wpdb->delete($table, ['model_id' => $modelId], ['%d']);
        $wpdb->delete($this->tables->robotModelErpProducts(), ['model_id' => $modelId, 'relation_type' => 'lubricant'], ['%d', '%s']);
        $now = current_time('mysql', true);

        foreach ($rows as $sort => $row) {
            if (!is_array($row)) {
                continue;
            }

            $relationId = $this->upsertMaintenanceErpProduct($modelId, 'lubricant', $row, $sort);
            if ($relationId <= 0) {
                continue;
            }

            [$targetType, $axisNumber] = $this->parseLubricationTarget($row);
            $wpdb->insert($table, [
                'model_id' => $modelId,
                'target_type' => $targetType,
                'axis_number' => $axisNumber,
                'erp_relation_id' => $relationId,
                'quantity' => $this->nullableDecimal($row['quantity'] ?? null),
                'quantity_unit' => sanitize_key((string) ($row['quantity_unit'] ?? 'l')) ?: 'l',
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->flush();
    }

    /** @return array<int,array<string,mixed>> */
    public function belts(int $modelId): array
    {
        return $this->maintenanceRows($this->tables->robotModelBeltTensions(), $modelId);
    }

    /** @param array<int,array<string,mixed>> $rows */
    public function replaceBelts(int $modelId, array $rows): void
    {
        global $wpdb;

        $table = $this->tables->robotModelBeltTensions();
        $wpdb->delete($table, ['model_id' => $modelId], ['%d']);
        $wpdb->delete($this->tables->robotModelErpProducts(), ['model_id' => $modelId, 'relation_type' => 'belt'], ['%d', '%s']);
        $now = current_time('mysql', true);

        foreach ($rows as $sort => $row) {
            if (!is_array($row)) {
                continue;
            }

            $relationId = $this->upsertMaintenanceErpProduct($modelId, 'belt', $row, $sort);
            if ($relationId <= 0) {
                continue;
            }

            $section = sanitize_key((string) ($row['section'] ?? 'wrist'));
            if (!in_array($section, ['wrist', 'motor'], true)) {
                $section = 'wrist';
            }

            $wpdb->insert($table, [
                'model_id' => $modelId,
                'section' => $section,
                'axis_number' => max(1, min(7, (int) ($row['axis_number'] ?? 1))),
                'erp_relation_id' => $relationId,
                'tension_nominal' => $this->nullableDecimal($row['tension_nominal'] ?? null),
                'tension_delta' => $this->nullableDecimal($row['tension_delta'] ?? null),
                'tension_unit' => sanitize_text_field((string) ($row['tension_unit'] ?? 'Hz')) ?: 'Hz',
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->flush();
    }

    /** @return array<int,array<string,mixed>> */
    public function balancers(int $modelId): array
    {
        $rows = $this->maintenanceRows($this->tables->robotModelBalancers(), $modelId);
        foreach ($rows as &$row) {
            // Legacy column kept physically for non-destructive upgrades, but
            // the balancing-group reference is no longer part of Catalog's
            // supported domain model.
            unset($row['balancer_reference']);
        }
        unset($row);

        return $rows;
    }

    /** @param array<int,array<string,mixed>> $rows */
    public function replaceBalancers(int $modelId, array $rows): void
    {
        global $wpdb;

        $table = $this->tables->robotModelBalancers();
        $wpdb->delete($table, ['model_id' => $modelId], ['%d']);
        $wpdb->delete($this->tables->robotModelErpProducts(), ['model_id' => $modelId, 'relation_type' => 'balancer'], ['%d', '%s']);
        $now = current_time('mysql', true);

        foreach ($rows as $sort => $row) {
            if (!is_array($row)) {
                continue;
            }

            $relationId = $this->upsertMaintenanceErpProduct($modelId, 'balancer', $row, $sort);
            $label = sanitize_text_field((string) ($row['label'] ?? ''));
            if ($relationId <= 0 && $label === '') {
                continue;
            }

            $wpdb->insert($table, [
                'model_id' => $modelId,
                'erp_relation_id' => $relationId > 0 ? $relationId : null,
                'balancer_label' => $label,
                'balancer_reference' => null,
                'pressure_min' => $this->nullableDecimal($row['pressure_min'] ?? null),
                'pressure_nominal' => $this->nullableDecimal($row['pressure_nominal'] ?? null),
                'pressure_unit' => sanitize_text_field((string) ($row['pressure_unit'] ?? 'bar')) ?: 'bar',
                'sort_order' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->flush();
    }

    /** @return array<int,array<string,mixed>> */
    private function maintenanceRows(string $table, int $modelId): array
    {
        global $wpdb;

        $erpTable = $this->tables->robotModelErpProducts();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, e.erp_source, e.external_product_id, e.product_name, e.product_reference
             FROM {$table} m
             LEFT JOIN {$erpTable} e ON e.id = m.erp_relation_id
             WHERE m.model_id = %d
             ORDER BY m.sort_order ASC, m.id ASC",
            $modelId
        ), ARRAY_A) ?: [];
    }

    /** @param array<string,mixed> $row */
    private function upsertMaintenanceErpProduct(int $modelId, string $relationType, array $row, int $sort): int
    {
        $item = [
            'erp_source' => sanitize_key((string) ($row['erp_source'] ?? '')),
            'external_product_id' => sanitize_text_field((string) ($row['external_product_id'] ?? '')),
            'product_name' => sanitize_text_field((string) ($row['product_name'] ?? '')),
            'product_reference' => sanitize_text_field((string) ($row['product_reference'] ?? '')),
            'quantity' => 1,
        ];

        return $this->insertErpProduct($modelId, $relationType, $item, $sort);
    }

    /**
     * @param array{erp_source:string,external_product_id:string,product_name?:string,product_reference?:string,quantity?:float|int|string} $item
     */
    private function insertErpProduct(int $modelId, string $relationType, array $item, int $sort): int
    {
        global $wpdb;

        $source = sanitize_key((string) ($item['erp_source'] ?? ''));
        $externalId = sanitize_text_field((string) ($item['external_product_id'] ?? ''));
        if ($source === '' || $externalId === '') {
            return 0;
        }

        $table = $this->tables->robotModelErpProducts();
        $relationType = sanitize_key($relationType);
        $externalId = mb_substr($externalId, 0, 128);
        $now = current_time('mysql', true);
        $existingId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE model_id = %d AND erp_source = %s AND external_product_id = %s AND relation_type = %s LIMIT 1",
            $modelId,
            $source,
            $externalId,
            $relationType
        ));
        $data = [
            'product_name' => $this->nullableText($item['product_name'] ?? null, 255),
            'product_reference' => $this->nullableText($item['product_reference'] ?? null, 191),
            'sort_order' => $sort,
            'quantity' => $this->nullableDecimal($item['quantity'] ?? 1) ?? 1.0,
            'updated_at' => $now,
        ];
        if ($existingId > 0) {
            $wpdb->update($table, $data, ['id' => $existingId]);
            return $existingId;
        }

        $wpdb->insert($table, $data + [
            'model_id' => $modelId,
            'erp_source' => $source,
            'external_product_id' => $externalId,
            'relation_type' => $relationType,
            'created_at' => $now,
        ]);

        return (int) $wpdb->insert_id;
    }

    /** @param array<string,mixed> $row @return array{0:string,1:?int} */
    private function parseLubricationTarget(array $row): array
    {
        $target = sanitize_key((string) ($row['target'] ?? ''));
        if (preg_match('/^axis_([1-7])$/', $target, $matches) === 1) {
            return ['axis', (int) $matches[1]];
        }
        if ($target === 'axis_5_6') {
            return ['axis_5_6', null];
        }

        return ['axis', 1];
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = str_replace(',', '.', trim((string) $value));
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function nullableText(mixed $value, int $maxLength): ?string
    {
        $value = sanitize_text_field((string) ($value ?? ''));
        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }

    private function flush(): void
    {
        $this->cache->flushGroup(self::CACHE_GROUP);
    }
}
