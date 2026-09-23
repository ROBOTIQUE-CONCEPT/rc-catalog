<?php

declare(strict_types=1);

namespace WPRC\Catalog\Core;

use WPRC\Catalog\Database\TableNames;

defined('ABSPATH') || exit;

final class Installer
{
    private const DB_VERSION = '2026.09.11.2';
    private const DB_VERSION_OPTION = 'wprc_catalog_db_version';

    public static function activate(): void
    {
        if (!function_exists('rc_core')) {
            wp_die(esc_html__('RC Catalog nécessite RC Core.', 'rc-catalog'));
        }

        self::assertRequiredPlugins();
        self::install();
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('rc_catalog_sync_lead_forms');
    }

    public static function maybeUpgrade(): void
    {
        if ((string) get_option(self::DB_VERSION_OPTION, '') !== self::DB_VERSION) {
            self::install();
        }
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = new TableNames();
        $charsetCollate = $wpdb->get_charset_collate();

        $queries = [
            "CREATE TABLE {$tables->robotModels()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                public_id char(36) NOT NULL,
                reference varchar(191) NOT NULL,
                payload_kg decimal(10,3) DEFAULT NULL,
                reach_mm decimal(10,3) DEFAULT NULL,
                mass_kg decimal(10,3) DEFAULT NULL,
                repeatability_mm decimal(10,4) DEFAULT NULL,
                structure varchar(100) DEFAULT NULL,
                axes_count tinyint(3) unsigned DEFAULT NULL,
                ip_base varchar(32) DEFAULT NULL,
                ip_wrist varchar(32) DEFAULT NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                deleted_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY public_id (public_id),
                KEY reference (reference),
                KEY status_deleted (status,deleted_at),
                KEY payload_kg (payload_kg),
                KEY reach_mm (reach_mm)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelPosts()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                post_id bigint(20) unsigned NOT NULL,
                language varchar(20) NOT NULL DEFAULT 'und',
                is_primary tinyint(1) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY post_id (post_id),
                UNIQUE KEY model_language (model_id,language),
                KEY model_primary (model_id,is_primary),
                KEY language (language)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelAxes()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                axis_number tinyint(3) unsigned NOT NULL,
                motion_type varchar(20) NOT NULL DEFAULT 'rotary',
                range_min decimal(12,4) DEFAULT NULL,
                range_max decimal(12,4) DEFAULT NULL,
                range_unit varchar(16) NOT NULL DEFAULT 'deg',
                max_velocity decimal(12,4) DEFAULT NULL,
                velocity_unit varchar(16) NOT NULL DEFAULT 'deg_s',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY model_axis (model_id,axis_number),
                KEY axis_number (axis_number)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelAliases()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                alias varchar(191) NOT NULL,
                normalized_alias varchar(191) NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY model_alias (model_id,normalized_alias),
                KEY normalized_alias (normalized_alias)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelErpProducts()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                erp_source varchar(32) NOT NULL,
                external_product_id varchar(128) NOT NULL,
                product_name varchar(255) DEFAULT NULL,
                product_reference varchar(191) DEFAULT NULL,
                relation_type varchar(32) NOT NULL,
                sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
                quantity decimal(12,3) NOT NULL DEFAULT 1.000,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY model_erp_product_relation (model_id,erp_source,external_product_id,relation_type),
                KEY model_relation_sort (model_id,relation_type,sort_order),
                KEY external_product (erp_source,external_product_id)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelMedia()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                attachment_id bigint(20) unsigned NOT NULL,
                sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY model_attachment (model_id,attachment_id),
                KEY model_sort (model_id,sort_order),
                KEY attachment_id (attachment_id)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelLubrication()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                target_type varchar(32) NOT NULL DEFAULT 'axis',
                axis_number tinyint(3) unsigned DEFAULT NULL,
                erp_relation_id bigint(20) unsigned DEFAULT NULL,
                quantity decimal(10,3) DEFAULT NULL,
                quantity_unit varchar(16) NOT NULL DEFAULT 'l',
                sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY model_sort (model_id,sort_order),
                KEY erp_relation_id (erp_relation_id),
                KEY target_axis (target_type,axis_number)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelBeltTensions()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                section varchar(20) NOT NULL DEFAULT 'wrist',
                axis_number tinyint(3) unsigned NOT NULL,
                erp_relation_id bigint(20) unsigned DEFAULT NULL,
                tension_nominal decimal(10,3) DEFAULT NULL,
                tension_delta decimal(10,3) DEFAULT NULL,
                tension_unit varchar(16) NOT NULL DEFAULT 'Hz',
                sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY model_sort (model_id,sort_order),
                KEY erp_relation_id (erp_relation_id),
                KEY section_axis (section,axis_number)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelBalancers()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                erp_relation_id bigint(20) unsigned DEFAULT NULL,
                balancer_label varchar(100) DEFAULT NULL,
                balancer_reference varchar(100) DEFAULT NULL,
                pressure_min decimal(10,3) DEFAULT NULL,
                pressure_nominal decimal(10,3) DEFAULT NULL,
                pressure_unit varchar(16) NOT NULL DEFAULT 'bar',
                sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY model_sort (model_id,sort_order),
                KEY erp_relation_id (erp_relation_id)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotControllers()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                uid varchar(32) NOT NULL,
                brand_term_id bigint(20) unsigned NOT NULL,
                name varchar(191) NOT NULL,
                reference varchar(191) DEFAULT NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY uid (uid),
                KEY brand_status_sort (brand_term_id,status,sort_order),
                KEY name (name)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelControllers()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                controller_id bigint(20) unsigned NOT NULL,
                sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY model_controller (model_id,controller_id),
                KEY controller_id (controller_id),
                KEY model_sort (model_id,sort_order)
            ) {$charsetCollate};",
            "CREATE TABLE {$tables->robotModelPages()} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                model_id bigint(20) unsigned NOT NULL,
                page_id bigint(20) unsigned NOT NULL,
                relation_type varchar(32) NOT NULL,
                sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY model_page_relation (model_id,page_id,relation_type),
                KEY model_relation_sort (model_id,relation_type,sort_order),
                KEY page_id (page_id)
            ) {$charsetCollate};",
        ];

        dbDelta($queries);
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION, false);
        flush_rewrite_rules(false);
    }

    private static function assertRequiredPlugins(): void
    {
        if (!class_exists('WooCommerce')) {
            wp_die(esc_html__('RC Catalog nécessite WooCommerce.', 'rc-catalog'));
        }

        if (!function_exists('pll_default_language')) {
            wp_die(esc_html__('RC Catalog nécessite Polylang Pro.', 'rc-catalog'));
        }
    }
}
