<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Legacy bootstrap shim.
 *
 * RC Catalog is registered through RC Core's ModuleRegistry since 1.7.0.
 * This file remains available for compatibility with tooling that required it
 * directly in earlier releases.
 */
return rc_core()->container();
