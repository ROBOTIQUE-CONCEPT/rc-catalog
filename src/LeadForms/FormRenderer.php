<?php


declare(strict_types=1);





namespace WPRC\Catalog\LeadForms;





use WPRC\Core\Contracts\Product\ProductContextProviderInterface;
use WPRC\Core\Security\Turnstile\TurnstileRenderer;





defined('ABSPATH') || exit;








final class FormRenderer


{


    private bool $assetsQueued = false;





    public function __construct(
        private readonly RemoteFormRepository $forms,
        private readonly TurnstileRenderer $turnstile,
        private readonly ProductContextProviderInterface $products
    ) {
    }


    public function init(): void


    {


        add_shortcode('wprc_form', [$this, 'shortcode']);
        add_action('init', [$this, 'registerBlock']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets']);

        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);





    }





    public function registerBlock(): void
    {
        $assetPath = WPRC_CATALOG_PATH . 'assets/admin/lead-form-block.js';
        wp_register_script(
            'wprc-leads-form-block',
            WPRC_CATALOG_URL . 'assets/admin/lead-form-block.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n'],
            is_readable($assetPath) ? (string) filemtime($assetPath) : RC_CATALOG_VERSION,
            true
        );

        register_block_type('wprc-leads/form', [
            'api_version' => 3,
            'editor_script' => 'wprc-leads-form-block',
            'attributes' => [
                'formSlug' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
            'render_callback' => [$this, 'renderBlock'],
        ]);
    }

    public function enqueueBlockEditorAssets(): void
    {
        wp_enqueue_script('wprc-leads-form-block');

        $options = [
            ['value' => '', 'label' => __('Sélectionner un formulaire…', 'wprc')],
        ];
        foreach ($this->forms->all($this->forms->currentLanguage()) as $form) {
            if ((string) ($form['status'] ?? '') !== 'active') {
                continue;
            }
            $slug = sanitize_key((string) ($form['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $options[] = [
                'value' => $slug,
                'label' => (string) ($form['title'] ?? $slug),
            ];
        }

        wp_localize_script('wprc-leads-form-block', 'WPRCLeadsFormBlock', [
            'forms' => $options,
            'title' => __('Formulaire RConcept', 'wprc'),
            'description' => __('Sélectionnez le formulaire RC Leads à afficher.', 'wprc'),
        ]);
    }

    /** @param array<string,mixed> $attributes */
    public function renderBlock(array $attributes = []): string
    {
        $slug = sanitize_key((string) ($attributes['formSlug'] ?? ''));
        if ($slug === '') {
            return current_user_can('edit_pages')
                ? '<p class="wprc-form-error">' . esc_html__('Sélectionnez un formulaire dans les réglages du bloc.', 'wprc') . '</p>'
                : '';
        }

        return $this->shortcode(['id' => $slug]);
    }

    public function enqueueAssets(): void


    {


        if (!$this->assetsQueued && !$this->shouldLoadAssets()) {


            return;


        }





        wp_enqueue_style(


            'wprc-opportunity-forms',


            WPRC_CATALOG_URL . 'assets/public/lead-forms.css',


            [],


            $this->assetVersion('public/lead-forms.css')


        );





        wp_enqueue_script(


            'wprc-opportunity-forms',


            WPRC_CATALOG_URL . 'assets/public/lead-forms.js',


            [],


            $this->assetVersion('public/lead-forms.js'),


            true


        );





        wp_localize_script('wprc-opportunity-forms', 'WPRCOpportunityForms', [


            'ajaxUrl' => admin_url('admin-ajax.php'),


            'nonce' => wp_create_nonce('wprc_opportunity_form'),


            'i18n' => [


                'sending' => __('Envoi en cours…', 'wprc'),


                'error' => __('La demande n’a pas pu être envoyée. Merci de réessayer.', 'wprc'),


            ],


            'lang' => $this->forms->currentLanguage(),


        ]);


    }





    /** @param array<string,mixed>|string $atts */


    public function shortcode($atts = []): string


    {


        $atts = shortcode_atts(['id' => ''], is_array($atts) ? $atts : [], 'wprc_form');


        $slug = sanitize_key((string) $atts['id']);


        if ($slug === '') {


            return '';


        }





        $form = $this->forms->findBySlug($slug, $this->forms->currentLanguage());


        if (!$form) {


            return current_user_can('edit_pages') ? '<p class="wprc-form-error">' . esc_html__('Formulaire WPRC introuvable.', 'wprc') . '</p>' : '';


        }





        $this->assetsQueued = true;




        $definition = is_array($form['definition'] ?? null) ? $form['definition'] : [];


        $fields = is_array($definition['fields'] ?? null) ? $definition['fields'] : [];


        $formId = 'wprc-form-' . sanitize_html_class((string) $form['slug']);


        $classes = trim('wprc-form ' . sanitize_html_class((string) ($definition['class'] ?? '')));





        $productContext = $this->products->getCurrent();
        $productAttributes = '';
        if ($productContext !== null) {
            $productAttributes = ' data-product-id="' . esc_attr((string) $productContext->id) . '"'
                . ' data-product-sku="' . esc_attr($productContext->sku) . '"'
                . ' data-product-external-id="' . esc_attr((string) ($productContext->externalId ?? '')) . '"';
        }

        ob_start();


        echo '<form id="' . esc_attr($formId) . '" class="' . esc_attr($classes) . '" data-wprc-opportunity-form data-form-slug="' . esc_attr((string) $form['slug']) . '"' . $productAttributes . '>';


        echo '<input type="hidden" name="form_slug" value="' . esc_attr((string) $form['slug']) . '" />';


        echo '<input type="hidden" name="form_lang" value="' . esc_attr((string) ($form['render_lang'] ?? $this->forms->currentLanguage())) . '" />';


        wp_nonce_field('wprc_opportunity_form', 'wprc_opportunity_nonce');

        $declaredFields = array_map('sanitize_key', array_map('strval', array_keys($fields)));
        if (!in_array('source_post_id', $declaredFields, true)) {
            $sourcePostId = get_queried_object_id();
            if ($sourcePostId > 0) {
                echo '<input type="hidden" name="source_post_id" value="' . esc_attr((string) $sourcePostId) . '" />';
            }
        }

        if ($productContext !== null) {
            echo $this->renderProductContextInputs($productContext, $declaredFields);
        }





        foreach ($fields as $name => $field) {


            if (!is_array($field) || !$this->isHiddenField($field)) {


                continue;


            }





            echo $this->renderHiddenField((string) $name, $field);


        }





        echo '<div class="wprc-form__body" data-wprc-form-body>';





        if (!empty($definition['title'])) {


            echo '<h2 class="wprc-form__title">' . esc_html((string) $definition['title']) . '</h2>';


        }


        if (!empty($definition['description'])) {


            echo '<p class="wprc-form__description">' . esc_html((string) $definition['description']) . '</p>';


        }





        echo '<div class="wprc-form__grid">';


        foreach ($fields as $name => $field) {


            if (!is_array($field) || $this->isHiddenField($field)) {


                continue;


            }





            echo $this->renderField((string) $name, $field);


        }


        echo '</div>';





        if (($definition['turnstile'] ?? true) !== false) {


            echo $this->turnstile->render('native_form', ['class' => 'wprc-form__turnstile']);


        }





        $button = (string) ($definition['submit_label'] ?? __('Envoyer ma demande', 'wprc'));


        echo '<div class="wprc-form__actions"><button type="submit" class="wprc-form__submit">' . esc_html($button) . '</button></div>';


        echo '</div>';





        echo '<div class="woocommerce-thankyou-order-received" data-wprc-form-notice role="message" aria-live="polite" hidden></div>';


        echo '</form>';





        return (string) ob_get_clean();


    }





    /** @param array<string,mixed> $field */


    private function isHiddenField(array $field): bool


    {


        return sanitize_key((string) ($field['type'] ?? 'text')) === 'hidden';


    }





    /** @param array<string,mixed> $field */


    private function renderHiddenField(string $name, array $field): string


    {


        $key = sanitize_key($name);


        if ($key === '') {


            return '';


        }





        $id = sanitize_html_class((string) ($field['id'] ?? 'wprc-field-' . $key));


        $value = $this->resolveHiddenFieldValue($key, $field);





        return '<input type="hidden" id="' . esc_attr($id) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';


    }





    /**


     * Resolve hidden field values from explicit definition or from the current


     * WordPress/WooCommerce context. This keeps technical fields out of the


     * visual grid while still allowing product forms injected dynamically by


     * Blocksy hooks to carry their product metadata.


     *


     * @param array<string,mixed> $field


     */


    private function resolveHiddenFieldValue(string $name, array $field): string


    {


        if (array_key_exists('value', $field) && is_scalar($field['value'])) {


            return (string) $field['value'];


        }





        $source = sanitize_key((string) ($field['value_source'] ?? $field['source'] ?? ''));


        if ($source === '') {


            $source = $name;


        }





        $product = $this->products->getCurrent();





        return match ($source) {


            'current_product_id', 'product_id' => $product ? (string) $product->id : '',


            'current_product_sku', 'product_sku' => $product ? $product->sku : '',


            'current_product_name', 'product_name', 'product_title' => $product ? $product->name : '',


            'current_product_url', 'product_url', 'product_permalink' => $product ? (string) ($product->url ?? '') : '',
            'current_product_external_id', 'product_external_id' => $product ? (string) ($product->externalId ?? '') : '',
            'current_product_image', 'product_image', 'product_image_url' => $product ? (string) ($product->imageUrl ?? '') : '',


            'current_post_id', 'post_id' => is_singular() ? (string) get_queried_object_id() : '',


            'current_url', 'request_url' => $this->currentRequestUrl(),


            default => '',


        };


    }





    /**
     * @param array<int,string> $declaredFields
     */
    private function renderProductContextInputs(\WPRC\Core\Data\Product\ProductContext $product, array $declaredFields): string
    {
        $values = [
            'product_id' => (string) $product->id,
            'product_sku' => $product->sku,
            'product_external_id' => (string) ($product->externalId ?? ''),
            'product_external_source' => (string) ($product->externalSource ?? ''),
            'product_type' => (string) ($product->type ?? ''),
            'product_name' => $product->name,
            'product_image' => (string) ($product->imageUrl ?? ''),
            'product_url' => (string) ($product->url ?? ''),
        ];

        $html = '';
        foreach ($values as $name => $value) {
            if (in_array($name, $declaredFields, true)) {
                continue;
            }
            $html .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
        }

        return $html;
    }

    private function currentRequestUrl(): string


    {


        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';


        if ($uri === '') {


            return '';


        }





        return home_url($uri);


    }





    /** @param array<string,mixed> $field */


    private function renderField(string $name, array $field): string


    {


        $type = sanitize_key((string) ($field['type'] ?? 'text'));


        $id = sanitize_html_class((string) ($field['id'] ?? 'wprc-field-' . $name));


        $label = (string) ($field['label'] ?? $name);


        $placeholder = (string) ($field['placeholder'] ?? '');


        $required = !empty($field['required']);


        $autocomplete = (string) ($field['autocomplete'] ?? '');


        $class = trim('wprc-field wprc-field--' . $type . ' ' . sanitize_html_class((string) ($field['class'] ?? '')));


        $style = !empty($field['style']) ? ' style="' . esc_attr((string) $field['style']) . '"' : '';


        $screenReader = !empty($field['screenreader']);


        $width = !empty($field['width']) ? ' wprc-field--width-' . sanitize_html_class((string) $field['width']) : '';





        ob_start();


        $htmlType = in_array($type, ['text', 'email', 'tel', 'number', 'url', 'hidden'], true) ? $type : 'text';


        $supportsFloatingLabel = !$screenReader && in_array($type, ['text', 'email', 'tel', 'number', 'url', 'textarea', 'select'], true);


        $class .= $supportsFloatingLabel ? ' wprc-field--floating' : '';





        echo '<div class="' . esc_attr($class . $width) . '"' . $style . '>';





        $attrs = ' id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"';


        $attrs .= $required ? ' required aria-required="true"' : '';


        $attrs .= $autocomplete !== '' ? ' autocomplete="' . esc_attr($autocomplete) . '"' : '';





        if (in_array($type, ['text', 'email', 'tel', 'number', 'url', 'textarea'], true)) {


            if ($supportsFloatingLabel) {


                // The visible placeholder is the floating label. Keep the native placeholder empty


                // to avoid duplicate text rendered by browser/theme placeholder styles.


                $attrs .= ' placeholder=""';


            } else {


                $fieldPlaceholder = $placeholder !== '' ? $placeholder : $label;


                $attrs .= ' placeholder="' . esc_attr($fieldPlaceholder) . '"';


            }


        } elseif ($placeholder !== '' && !$supportsFloatingLabel) {


            $attrs .= ' placeholder="' . esc_attr($placeholder) . '"';


        }





        if ($type === 'textarea') {


            $rows = max(2, absint($field['rows'] ?? 5));


            echo '<textarea' . $attrs . ' rows="' . esc_attr((string) $rows) . '"></textarea>';


            echo '<label for="' . esc_attr($id) . '" class="' . esc_attr($screenReader ? 'screen-reader-text' : 'wprc-field__label') . '">' . esc_html($label) . '</label>';


        } elseif ($type === 'select') {


            echo '<select' . $attrs . '>';


            foreach ((array) ($field['options'] ?? []) as $value => $optionLabel) {


                echo '<option value="' . esc_attr((string) $value) . '">' . esc_html((string) $optionLabel) . '</option>';


            }


            echo '</select>';


            echo '<label for="' . esc_attr($id) . '" class="' . esc_attr($screenReader ? 'screen-reader-text' : 'wprc-field__label') . '">' . esc_html($label) . '</label>';


        } elseif ($type === 'checkbox') {


            echo '<label class="wprc-field__checkbox"><input type="checkbox" name="' . esc_attr($name) . '" value="1"' . ($required ? ' required aria-required="true"' : '') . ' /> ' . esc_html($label) . '</label>';


        } else {


            echo '<input type="' . esc_attr($htmlType) . '"' . $attrs . ' />';


            if ($htmlType !== 'hidden') {


                echo '<label for="' . esc_attr($id) . '" class="' . esc_attr($screenReader ? 'screen-reader-text' : 'wprc-field__label') . '">' . esc_html($label) . '</label>';


            }


        }





        if (!empty($field['help'])) {


            echo '<p class="wprc-field__help">' . esc_html((string) $field['help']) . '</p>';


        }


        echo '</div>';





        return (string) ob_get_clean();


    }





    private function shouldLoadAssets(): bool


    {


        if (is_admin()) {


            return false;


        }





        $shouldLoad = false;





        /*


         * First pass: standard WordPress content.


         * This covers the classic use case where [wprc_form] is stored directly


         * in the queried post content.


         */


        $queriedObject = get_queried_object();


        if ($queriedObject instanceof \WP_Post) {


            $content = (string) $queriedObject->post_content;
            $shouldLoad = has_shortcode($content, 'wprc_form') || has_block('wprc-leads/form', $content);


        }





        /*


         * Blocksy Content Blocks and theme hooks can render the shortcode outside


         * of the queried post_content. In that case the previous check cannot see


         * the shortcode before wp_enqueue_scripts runs.


         *


         * Product pages are a first-class target for WPRC forms, especially the


         * mono-product inquiry form injected through Blocksy.


         */


        if (!$shouldLoad && ((function_exists('is_product') && is_product()) || is_singular('product'))) {


            $shouldLoad = true;


        }





        /*


         * Generic fallback for singular pages.


         * Dynamic builders/hooks often inject the shortcode too late for a pure


         * post_content scan. The assets are lightweight and self-scoped, so loading


         * them on singular views is safer than missing styling/JS on rendered forms.


         */


        if (!$shouldLoad && is_singular()) {


            $shouldLoad = true;


        }





        /**


         * Filters whether native WPRC form assets should be loaded on the current request.


         *


         * @param bool $shouldLoad Whether assets should be enqueued.


         */


        return (bool) apply_filters('wprc_opportunity_forms_should_load_assets', $shouldLoad);


    }





    private function assetVersion(string $relativePath): string


    {


        $path = WPRC_CATALOG_PATH . 'assets/' . ltrim($relativePath, '/');





        return is_readable($path) ? (string) filemtime($path) : RC_CATALOG_VERSION;


    }


}


