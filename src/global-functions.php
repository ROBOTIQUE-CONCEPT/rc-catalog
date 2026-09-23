<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Return the stable RC Catalog public API.
 */
if (!function_exists('rc_catalog')) {
    function rc_catalog(): \WPRC\Catalog\PublicApi\Catalog
    {
        /** @var \WPRC\Catalog\PublicApi\Catalog $catalog */
        $catalog = rc_core()->container()->get(\WPRC\Catalog\PublicApi\Catalog::class);

        return $catalog;
    }
}
