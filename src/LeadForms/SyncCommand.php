<?php

declare(strict_types=1);

namespace WPRC\Catalog\LeadForms;

defined('ABSPATH') || exit;

final class SyncCommand
{
    public static function register(RemoteFormRepository $forms): void
    {
        if (!class_exists('WP_CLI')) {
            return;
        }
        \WP_CLI::add_command('rc catalog sync-lead-forms', static function () use ($forms): void {
            $ok = true;
            foreach ($forms->knownLanguages() as $language) {
                \WP_CLI::log('Sync lead forms: ' . $language);
                $ok = $forms->syncLanguage($language) && $ok;
            }
            $ok ? \WP_CLI::success('Lead form projections synchronized.') : \WP_CLI::error('One or more lead form projections failed to synchronize.');
        });
    }
}
