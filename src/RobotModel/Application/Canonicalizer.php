<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Application;

defined('ABSPATH') || exit;

/**
 * Canonicalizes multilingual WordPress objects to the Polylang default language.
 *
 * RC Catalog stores all content relationships against default-language objects
 * (French on the Robotique Concept site) and resolves translations only when
 * data is consumed.
 */
final class Canonicalizer
{
    public function defaultLanguage(): string
    {
        if (function_exists('pll_default_language')) {
            $language = pll_default_language('slug');
            if (is_string($language) && $language !== '') {
                return $language;
            }
        }

        return strtolower(substr((string) get_locale(), 0, 2)) ?: 'fr';
    }

    public function currentLanguage(): string
    {
        if (function_exists('pll_current_language')) {
            $language = pll_current_language('slug');
            if (is_string($language) && $language !== '') {
                return $language;
            }
        }

        return $this->defaultLanguage();
    }

    public function post(int $postId): int
    {
        if ($postId <= 0) {
            return 0;
        }

        if (function_exists('pll_get_post')) {
            $translated = (int) pll_get_post($postId, $this->defaultLanguage());
            if ($translated > 0) {
                return $translated;
            }
        }

        return $postId;
    }

    public function term(int $termId): int
    {
        if ($termId <= 0) {
            return 0;
        }

        if (function_exists('pll_get_term')) {
            $translated = (int) pll_get_term($termId, $this->defaultLanguage());
            if ($translated > 0) {
                return $translated;
            }
        }

        return $termId;
    }

    public function translatedPost(int $canonicalPostId, ?string $language = null): int
    {
        $canonicalPostId = $this->post($canonicalPostId);
        $language = $language !== null ? sanitize_key($language) : '';

        if ($language !== '' && function_exists('pll_get_post')) {
            $translated = (int) pll_get_post($canonicalPostId, $language);
            if ($translated > 0) {
                return $translated;
            }
        }

        return $canonicalPostId;
    }

    public function translatedTerm(int $canonicalTermId, ?string $language = null): int
    {
        $canonicalTermId = $this->term($canonicalTermId);
        $language = $language !== null ? sanitize_key($language) : '';

        if ($language !== '' && function_exists('pll_get_term')) {
            $translated = (int) pll_get_term($canonicalTermId, $language);
            if ($translated > 0) {
                return $translated;
            }
        }

        return $canonicalTermId;
    }

    public function postLanguage(int $postId): string
    {
        if (function_exists('pll_get_post_language')) {
            $language = pll_get_post_language($postId, 'slug');
            if (is_string($language) && $language !== '') {
                return $language;
            }
        }

        return $this->defaultLanguage();
    }
}
