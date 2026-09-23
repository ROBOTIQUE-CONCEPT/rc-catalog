<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Settings;

defined('ABSPATH') || exit;

/**
 * Front-safe access to RC Catalog settings.
 *
 * This class deliberately does not extend WC_Settings_Page so it can be used
 * from public requests where WooCommerce admin settings classes are not loaded.
 */
final class CatalogOptions
{
    public const OPTION_APPLICATIONS_PARENT = 'wprc_catalog_applications_parent_page_id';
    public const OPTION_SOFTWARE_PARENT = 'wprc_catalog_software_parent_page_id';

    public static function applicationsParentId(): int
    {
        return absint(get_option(self::OPTION_APPLICATIONS_PARENT, 0));
    }

    public static function softwareParentId(): int
    {
        return absint(get_option(self::OPTION_SOFTWARE_PARENT, 0));
    }
}
