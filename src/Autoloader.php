<?php

declare(strict_types=1);

namespace WPRC\Catalog;

defined('ABSPATH') || exit;

/**
 * Lightweight PSR-4 style autoloader for RC Catalog.
 *
 * It is registered from the plugin file before `plugins_loaded` so RC Core can
 * discover and register the Catalog module during its own lifecycle bootstrap.
 */
final class Autoloader
{
    private const PREFIX = 'WPRC\\Catalog\\';

    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        spl_autoload_register([self::class, 'autoload']);
    }

    private static function autoload(string $class): void
    {
        if (!str_starts_with($class, self::PREFIX)) {
            return;
        }

        $relative = substr($class, strlen(self::PREFIX));
        $path = WPRC_CATALOG_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($path)) {
            require_once $path;
        }
    }
}
