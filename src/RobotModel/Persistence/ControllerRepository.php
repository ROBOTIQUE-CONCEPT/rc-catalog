<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Persistence;

use RuntimeException;
use WPRC\Catalog\Database\TableNames;
use WPRC\Catalog\RobotModel\Domain\Controller;
use WPRC\Core\Contracts\CacheInterface;
use WPRC\Core\Support\UidGenerator;

defined('ABSPATH') || exit;

final class ControllerRepository
{
    private const CACHE_GROUP = 'wprc_catalog_robot_controllers';

    public function __construct(
        private readonly TableNames $tables,
        private readonly CacheInterface $cache,
        private readonly UidGenerator $uid
    ) {
    }

    public function find(int $id): ?Controller
    {
        if ($id <= 0) {
            return null;
        }

        $key = 'controller:' . $id;
        $cached = $this->cache->get($key, self::CACHE_GROUP, '__missing__');
        if ($cached !== '__missing__') {
            return $cached instanceof Controller ? $cached : null;
        }

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables->robotControllers()} WHERE id = %d LIMIT 1",
            $id
        ), ARRAY_A);

        if (!is_array($row)) {
            $this->cache->set($key, null, self::CACHE_GROUP, 300);
            return null;
        }

        $controller = $this->hydrate($row);
        $this->cache->set($key, $controller, self::CACHE_GROUP, 3600);
        return $controller;
    }

    /** @return Controller[] */
    public function all(?int $brandTermId = null, bool $activeOnly = true): array
    {
        global $wpdb;
        $where = ['1=1'];
        $params = [];
        if ($brandTermId !== null && $brandTermId > 0) {
            $where[] = 'brand_term_id = %d';
            $params[] = $brandTermId;
        }
        if ($activeOnly) {
            $where[] = "status = 'active'";
        }

        $sql = "SELECT * FROM {$this->tables->robotControllers()} WHERE " . implode(' AND ', $where) . ' ORDER BY sort_order ASC, name ASC';
        if ($params !== []) {
            $sql = $wpdb->prepare($sql, ...$params);
        }
        $rows = $wpdb->get_results($sql, ARRAY_A);
        return array_map(fn (array $row): Controller => $this->hydrate($row), $rows);
    }

    public function save(?int $id, int $brandTermId, string $name, ?string $reference, string $status = 'active', int $sortOrder = 0): int
    {
        global $wpdb;
        $name = sanitize_text_field($name);
        $reference = $reference !== null ? sanitize_text_field($reference) : null;
        $status = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
        $brand = $brandTermId > 0 ? get_term($brandTermId, 'product_brand') : null;
        if (!$brand instanceof \WP_Term || $name === '') {
            throw new RuntimeException('Controller brand and name are required.');
        }

        $now = current_time('mysql', true);
        $data = [
            'brand_term_id' => $brandTermId,
            'name' => $name,
            'reference' => $reference,
            'status' => $status,
            'sort_order' => max(0, $sortOrder),
            'updated_at' => $now,
        ];

        if ($id !== null && $id > 0) {
            $ok = $wpdb->update($this->tables->robotControllers(), $data, ['id' => $id]);
            if ($ok === false) {
                throw new RuntimeException('Unable to update controller.');
            }
            $controllerId = $id;
        } else {
            $data['uid'] = $this->uid->generateUnique(function (string $uid): bool {
                global $wpdb;
                return (bool) $wpdb->get_var($wpdb->prepare(
                    "SELECT 1 FROM {$this->tables->robotControllers()} WHERE uid = %s LIMIT 1",
                    $uid
                ));
            });
            $data['created_at'] = $now;
            $ok = $wpdb->insert($this->tables->robotControllers(), $data);
            if ($ok === false) {
                throw new RuntimeException('Unable to create controller.');
            }
            $controllerId = (int) $wpdb->insert_id;
        }

        $this->cache->flushGroup(self::CACHE_GROUP);
        return $controllerId;
    }

    public function delete(int $id): void
    {
        global $wpdb;
        if ($id <= 0) {
            return;
        }
        $wpdb->delete($this->tables->robotModelControllers(), ['controller_id' => $id], ['%d']);
        $wpdb->delete($this->tables->robotControllers(), ['id' => $id], ['%d']);
        $this->cache->flushGroup(self::CACHE_GROUP);
    }

    private function hydrate(array $row): Controller
    {
        return new Controller(
            id: (int) $row['id'],
            uid: (string) $row['uid'],
            brandTermId: (int) $row['brand_term_id'],
            name: (string) $row['name'],
            reference: isset($row['reference']) && trim((string) $row['reference']) !== '' ? (string) $row['reference'] : null,
            status: (string) $row['status'],
            sortOrder: (int) $row['sort_order']
        );
    }
}
