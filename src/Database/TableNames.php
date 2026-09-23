<?php

declare(strict_types=1);

namespace WPRC\Catalog\Database;

defined('ABSPATH') || exit;

final class TableNames
{
    private function table(string $suffix): string
    {
        global $wpdb;

        return $wpdb->prefix . $suffix;
    }

    /**
     * Robot-model table names intentionally keep the former RC Robot Models
     * names. Existing technical data therefore survives the move into Catalog
     * without an intermediate copy/migration step.
     */
    public function robotModels(): string { return $this->table('rc_robot_models'); }
    public function robotModelPosts(): string { return $this->table('rc_robot_model_posts'); }
    public function robotModelAxes(): string { return $this->table('rc_robot_model_axes'); }
    public function robotModelAliases(): string { return $this->table('rc_robot_model_aliases'); }
    public function robotModelErpProducts(): string { return $this->table('rc_robot_model_erp_products'); }
    public function robotModelMedia(): string { return $this->table('rc_robot_model_media'); }
    public function robotModelLubrication(): string { return $this->table('rc_robot_model_service_lubrication'); }
    public function robotModelBeltTensions(): string { return $this->table('rc_robot_model_service_belt_tensions'); }
    public function robotModelBalancers(): string { return $this->table('rc_robot_model_service_balancers'); }
    public function robotControllers(): string { return $this->table('rc_robot_controllers'); }
    public function robotModelControllers(): string { return $this->table('rc_robot_model_controllers'); }
    public function robotModelPages(): string { return $this->table('rc_robot_model_pages'); }
}
