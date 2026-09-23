<?php

declare(strict_types=1);

namespace WPRC\Catalog\LeadForms;

use WPRC\Core\Contracts\LoggerInterface;
use WPRC\Core\InternalApi\Client;
use WPRC\Core\Site\SiteContext;

defined('ABSPATH') || exit;

/**
 * Local materialized projection of public lead form definitions owned by my.
 *
 * Public rendering reads the local option snapshot only. Network calls happen
 * on explicit/cron synchronization, or once when a requested snapshot does not
 * exist yet.
 */
final class RemoteFormRepository
{
    private const OPTION = 'rc_catalog_lead_form_projection';
    private const CRON_HOOK = 'rc_catalog_sync_lead_forms';

    public function __construct(
        private readonly Client $client,
        private readonly SiteContext $sites,
        private readonly LoggerInterface $logger
    ) {
    }

    public function init(): void
    {
        add_action(self::CRON_HOOK, [$this, 'syncKnownLanguages']);
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'hourly', self::CRON_HOOK);
        }
    }

    public function currentLanguage(): string
    {
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language('slug');
            if (is_string($lang) && $lang !== '') {
                return $this->normalizeLanguage($lang);
            }
        }

        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        return $this->normalizeLanguage(substr((string) $locale, 0, 2));
    }

    /** @return array<int,array<string,mixed>> */
    public function all(?string $language = null, bool $refresh = false): array
    {
        $language = $this->normalizeLanguage($language ?: $this->currentLanguage());
        if ($refresh) {
            $this->syncLanguage($language);
        }

        $projection = $this->projection();
        $forms = isset($projection['languages'][$language]['forms']) && is_array($projection['languages'][$language]['forms'])
            ? $projection['languages'][$language]['forms']
            : [];

        // Rendering is projection-only. A frontend request must never depend on
        // the availability of my; refreshes happen through CLI/cron explicitly.
        return array_values(array_filter($forms, 'is_array'));
    }

    /** @return array<string,mixed>|null */
    public function findBySlug(string $slug, ?string $language = null): ?array
    {
        $slug = sanitize_key($slug);
        $language = $this->normalizeLanguage($language ?: $this->currentLanguage());
        if ($slug === '') {
            return null;
        }

        $forms = $this->all($language);
        foreach ($forms as $form) {
            if (sanitize_key((string) ($form['slug'] ?? '')) === $slug) {
                return $form;
            }
        }

        return null;
    }

    public function syncKnownLanguages(): void
    {
        foreach ($this->knownLanguages() as $language) {
            $this->syncLanguage($language);
        }
    }

    public function syncLanguage(string $language): bool
    {
        $language = $this->normalizeLanguage($language);
        $response = $this->client->request(
            'GET',
            $this->sites->applicationRestUrl(),
            '/rc-leads/v1/internal/forms',
            ['lang' => $language],
            null,
            [],
            8
        );

        if ($response['error'] !== null || $response['status'] !== 200 || !is_array($response['decoded'])) {
            $this->logger->warning(
                'Unable to synchronize public lead form projection.',
                'catalog',
                ['lang' => $language, 'status' => $response['status'], 'error' => $response['error']?->get_error_message()],
                'lead_forms_sync_failed'
            );
            return false;
        }

        $forms = isset($response['decoded']['forms']) && is_array($response['decoded']['forms'])
            ? array_values(array_filter($response['decoded']['forms'], 'is_array'))
            : [];

        $projection = $this->projection();
        $projection['languages'][$language] = [
            'forms' => $forms,
            'synced_at' => gmdate('c'),
        ];
        update_option(self::OPTION, $projection, false);

        $this->logger->info(
            'Public lead form projection synchronized.',
            'catalog',
            ['lang' => $language, 'count' => count($forms)],
            'lead_forms_synced'
        );
        return true;
    }

    /** @return string[] */
    public function knownLanguages(): array
    {
        if (function_exists('pll_languages_list')) {
            $languages = pll_languages_list(['fields' => 'slug']);
            if (is_array($languages) && $languages !== []) {
                return array_values(array_unique(array_map(fn ($lang): string => $this->normalizeLanguage((string) $lang), $languages)));
            }
        }
        return [$this->currentLanguage()];
    }

    /** @return array<string,mixed> */
    private function projection(): array
    {
        $projection = get_option(self::OPTION, []);
        if (!is_array($projection)) {
            $projection = [];
        }
        if (!isset($projection['languages']) || !is_array($projection['languages'])) {
            $projection['languages'] = [];
        }
        return $projection;
    }

    private function normalizeLanguage(string $language): string
    {
        $language = strtolower(sanitize_key(str_replace('_', '-', $language)));
        return $language !== '' ? $language : 'fr';
    }
}
