<?php

declare(strict_types=1);

namespace WPRC\Catalog\LeadForms;

use WPRC\Core\InternalApi\Client;
use WPRC\Core\Security\RequestIpResolver;
use WPRC\Core\Security\Turnstile\TurnstileVerifier;
use WPRC\Core\Site\SiteContext;

defined('ABSPATH') || exit;

/** Browser AJAX adapter on www forwarding validated submissions to my. */
final class SubmissionProxy
{
    public function __construct(
        private readonly RemoteFormRepository $forms,
        private readonly TurnstileVerifier $turnstile,
        private readonly Client $client,
        private readonly SiteContext $sites,
        private readonly RequestIpResolver $ipResolver
    ) {
    }

    public function init(): void
    {
        add_action('wp_ajax_wprc_opportunity_form_submit', [$this, 'handle']);
        add_action('wp_ajax_nopriv_wprc_opportunity_form_submit', [$this, 'handle']);
    }

    public function handle(): void
    {
        if (!check_ajax_referer('wprc_opportunity_form', 'nonce', false)
            && !check_ajax_referer('wprc_opportunity_form', 'wprc_opportunity_nonce', false)) {
            wp_send_json_error(['message' => __('Session expirée. Merci de recharger la page.', 'rc-catalog')], 403);
        }

        $slug = isset($_POST['form_slug']) ? sanitize_key(wp_unslash((string) $_POST['form_slug'])) : '';
        $lang = isset($_POST['form_lang']) ? sanitize_key(wp_unslash((string) $_POST['form_lang'])) : '';
        $form = $slug !== '' ? $this->forms->findBySlug($slug, $lang !== '' ? $lang : null) : null;
        if (!$form) {
            wp_send_json_error(['message' => __('Formulaire introuvable.', 'rc-catalog')], 404);
        }

        $definition = is_array($form['definition'] ?? null) ? $form['definition'] : [];
        $turnstileResult = ($definition['turnstile'] ?? true) === false
            ? ['success' => true, 'message' => '']
            : $this->turnstile->verifyRequest('native_form');
        if (!$turnstileResult['success']) {
            wp_send_json_error(['message' => $turnstileResult['message']], 403);
        }

        $data = [];
        foreach ($_POST as $key => $value) {
            $key = sanitize_key((string) $key);
            if (in_array($key, ['action', 'nonce', 'wprc_opportunity_nonce', 'cf-turnstile-response'], true)) {
                continue;
            }
            $data[$key] = is_array($value)
                ? wp_unslash($value)
                : (is_scalar($value) ? wp_unslash((string) $value) : '');
        }

        $server = wp_unslash($_SERVER);
        $ua = isset($server['HTTP_USER_AGENT']) ? sanitize_textarea_field((string) $server['HTTP_USER_AGENT']) : '';
        $context = [
            'source_post_id' => isset($_POST['source_post_id']) ? absint($_POST['source_post_id']) : (get_the_ID() ?: null),
            'referer_url' => isset($server['HTTP_REFERER']) ? esc_url_raw((string) $server['HTTP_REFERER']) : null,
            'visitor_ip' => $this->ipResolver->resolve(),
            'user_agent' => $ua !== '' ? $ua : null,
            'browser' => $this->browserFromUa($ua),
            'utm_source' => $this->requestValue('utm_source'),
            'utm_medium' => $this->requestValue('utm_medium'),
            'utm_campaign' => $this->requestValue('utm_campaign'),
            'utm_term' => $this->requestValue('utm_term'),
            'utm_content' => $this->requestValue('utm_content'),
        ];

        $response = $this->client->request(
            'POST',
            $this->sites->applicationRestUrl(),
            '/rc-leads/v1/internal/submissions',
            [],
            [
                'form_slug' => $slug,
                'form_lang' => $lang,
                'data' => $data,
                'context' => $context,
            ],
            [],
            12
        );

        $payload = is_array($response['decoded']) ? $response['decoded'] : [];
        if ($response['error'] !== null || $response['status'] < 200 || $response['status'] >= 300 || empty($payload['success'])) {
            $message = isset($payload['message']) && is_scalar($payload['message'])
                ? (string) $payload['message']
                : __('La demande n’a pas pu être transmise. Merci de réessayer.', 'rc-catalog');
            wp_send_json_error(['message' => $message], $response['status'] >= 400 ? $response['status'] : 502);
        }

        wp_send_json_success([
            'message' => (string) ($payload['message'] ?? ''),
            'opportunity_id' => (int) ($payload['id'] ?? 0),
            'uuid' => (string) ($payload['uuid'] ?? ''),
            'public_ref' => (string) ($payload['public_ref'] ?? ''),
        ]);
    }

    private function requestValue(string $key): ?string
    {
        if (!isset($_POST[$key]) || !is_scalar($_POST[$key])) {
            return null;
        }
        $value = sanitize_text_field(wp_unslash((string) $_POST[$key]));
        return $value !== '' ? $value : null;
    }

    private function browserFromUa(string $ua): ?string
    {
        if ($ua === '') {
            return null;
        }
        foreach (['Edg' => 'Edge', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari', 'Opera' => 'Opera'] as $needle => $label) {
            if (stripos($ua, $needle) !== false) {
                return $label;
            }
        }
        return 'Other';
    }
}
