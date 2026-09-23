<?php


declare(strict_types=1);





namespace WPRC\Catalog\WooCommerce\Product;





use WPRC\Core\Contracts\BootableInterface;


use WPRC\Catalog\WooCommerce\Product\Types\WC_Product_Abstract;
use WPRC\Catalog\RobotModel\Application\RobotModels;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;
use WPRC\Catalog\ERP\DescriptionTemplateRenderer;





defined('ABSPATH') || exit;








final class CustomFields implements BootableInterface


{


    public function __construct(


        private readonly ProductDataLookup $lookup,
        private readonly RobotModels $robotModels,
        private readonly ControllerRepository $controllers,
        private readonly DescriptionTemplateRenderer $descriptionTemplates


    ) {


    }





    public function init(): void


    {


        add_action('admin_head-post.php', [$this, 'removeUnnecessaryFields']);


        add_action('admin_head-post-new.php', [$this, 'removeUnnecessaryFields']);


        add_action('woocommerce_product_options_general_product_data', [$this, 'renderGeneralPanelFields']);


        add_action('woocommerce_product_options_shipping', [$this, 'renderShippingPanelFields']);
        add_action('woocommerce_product_data_panels', [$this, 'renderErpDescriptionPanel']);


    }





    public function removeUnnecessaryFields(): void


    {


        $screen = function_exists('get_current_screen') ? get_current_screen() : null;


        if (!$screen || $screen->post_type !== 'product') {


            return;


        }


        ?>


        <style>


            .options_group.pricing p.form-field._sale_price_field { display:none !important; }


            .sale_price_dates_fields { display:none !important; }


            p.form-field._global_unique_id_field,


            p.form-field.global_unique_id_field,


            #_global_unique_id,


            #global_unique_id { display:none !important; }


            p.form-field.shipping_class_field { display:none !important; }


        </style>


        <?php


    }





    public function renderGeneralPanelFields(): void


    {


        $productId = get_the_ID();


        $product = $productId ? wc_get_product($productId) : null;
        $erpId = $product ? trim((string) $product->get_meta(WC_Product_Abstract::META_ERP_ID, true)) : '';
        $erpLabel = $product ? trim((string) $product->get_meta(WC_Product_Abstract::META_ERP_LABEL, true)) : '';
        $erpOptions = $erpId !== '' ? [$erpId => ($erpLabel !== '' ? $erpLabel : $erpId)] : [];





        echo '<div class="options_group">';





        $this->renderSelectField(


            WC_Product_Abstract::META_ERP_ID,


            __('Produit ERP', 'wprc'),


            $erpId,


            $erpOptions,


            '',


            $erpLabel


        );





        echo '</div>';





        echo '<div class="options_group">';





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_REFERENCE,


            'label' => __('Référence constructeur', 'wprc'),


            'desc_tip' => true,


            'description' => __('Référence article propre au constructeur.', 'wprc'),


            'type' => 'text',


            'wrapper_class' => 'show_if_spare',


        ]);





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_DESIGNATION,


            'label' => __('Désignation', 'wprc'),


            'desc_tip' => true,


            'description' => __('Désignation article propre au constructeur.', 'wprc'),


            'type' => 'text',


            'wrapper_class' => 'show_if_spare',


        ]);


        $modelId = $product ? absint($product->get_meta(WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID, true)) : 0;
        $controllerId = $product ? absint($product->get_meta(WC_Product_Abstract::META_ROBOT_CONTROLLER_ID, true)) : 0;
        $modelOptions = ['' => __('— Sélectionner un modèle —', 'rc-catalog')];
        $language = function_exists('pll_get_post_language') && $productId ? pll_get_post_language($productId, 'slug') : null;
        foreach ($this->robotModels->all(is_string($language) ? $language : null) as $model) {
            $modelOptions[(string) $model->id] = (string) ($model->title ?: $model->reference);
        }
        woocommerce_wp_select([
            'id' => WC_Product_Abstract::META_ROBOT_MODEL_ENTITY_ID,
            'label' => __('Modèle mécanique', 'rc-catalog'),
            'wrapper_class' => 'show_if_robot form-field-wide',
            'class' => 'wc-enhanced-select widefat',
            'options' => $modelOptions,
            'value' => $modelId > 0 ? (string) $modelId : '',
            'desc_tip' => true,
            'description' => __('Modèle technique RC Catalog partagé entre les traductions.', 'rc-catalog'),
        ]);

        $controllerOptions = ['' => __('— Sélectionner un contrôleur —', 'rc-catalog')];
        if ($modelId > 0) {
            foreach ($this->robotModels->compatibleControllers($modelId) as $controller) {
                $controllerOptions[(string) $controller->id] = $controller->name . ($controller->reference ? ' — ' . $controller->reference : '');
            }
        }
        woocommerce_wp_select([
            'id' => WC_Product_Abstract::META_ROBOT_CONTROLLER_ID,
            'label' => __('Contrôleur', 'rc-catalog'),
            'wrapper_class' => 'show_if_robot form-field-wide',
            'class' => 'wc-enhanced-select widefat',
            'options' => $controllerOptions,
            'value' => $controllerId > 0 ? (string) $controllerId : '',
            'desc_tip' => true,
            'description' => __('Seuls les contrôleurs compatibles avec le modèle sélectionné sont disponibles.', 'rc-catalog'),
        ]);





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_PAGE_TITLE,


            'label' => __('Titre de la page', 'wprc'),


            'desc_tip' => true,


            'description' => __('Titre affiché pour l\'annonce.', 'wprc'),


            'type' => 'text',


            'wrapper_class' => 'show_if_cell show_if_manipulator',


        ]);





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_YOM,


            'label' => __('Année du matériel', 'wprc'),


            'desc_tip' => true,


            'description' => __('Année de production du matériel.', 'wprc'),


            'type' => 'number',


            'wrapper_class' => 'show_if_robot show_if_cell show_if_manipulator',


        ]);





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_RUNTIME,


            'label' => __('Heures de service', 'wprc'),


            'desc_tip' => true,


            'description' => __('Nombre d\'heures de fonctionnement du matériel.', 'wprc'),


            'type' => 'number',


            'wrapper_class' => 'show_if_robot show_if_cell show_if_manipulator',


        ]);





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_SW_VERSION,


            'label' => __('Version logicielle', 'wprc'),


            'desc_tip' => true,


            'description' => __('Version du logiciel du matériel.', 'wprc'),


            'type' => 'text',


            'wrapper_class' => 'show_if_robot',


        ]);





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_CABLE_LENGTH,


            'label' => __('Longueur de faisceau', 'wprc'),


            'desc_tip' => true,


            'description' => __('Longueur du faisceau bras <> contrôleur du matériel.', 'wprc'),


            'type' => 'number',


            'wrapper_class' => 'show_if_robot',


        ]);





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_YOUTUBE_URL,


            'label' => __('ID YouTube', 'wprc'),


            'desc_tip' => true,


            'description' => __('ID de la vidéo de démonstration sur YouTube.', 'wprc'),


            'type' => 'text',


            'wrapper_class' => 'show_if_robot show_if_cell show_if_manipulator',


        ]);





        echo '</div>';


        echo '<div class="options_group">';

        $retailerId = $product ? trim((string) $product->get_meta(WC_Product_Abstract::META_RETAILER_ID, true)) : '';
        $retailerLabel = $product ? trim((string) $product->get_meta(WC_Product_Abstract::META_RETAILER_NAME, true)) : '';
        $retailerOptions = $retailerId !== '' ? [$retailerId => ($retailerLabel !== '' ? $retailerLabel : $retailerId)] : [];
        $this->renderSelectField(
            WC_Product_Abstract::META_RETAILER_ID,
            __('Vendeur du matériel', 'wprc'),
            $retailerId,
            $retailerOptions,
            'show_if_robot show_if_cell show_if_manipulator',
            $retailerLabel
        );

        $locationId = $product ? trim((string) $product->get_meta(WC_Product_Abstract::META_LOCATION_ID, true)) : '';
        $locationLabel = $product ? trim((string) $product->get_meta(WC_Product_Abstract::META_LOCATION_LABEL, true)) : '';
        $locationOptions = $locationId !== '' ? [$locationId => ($locationLabel !== '' ? $locationLabel : $locationId)] : [];
        $this->renderSelectField(
            WC_Product_Abstract::META_LOCATION_ID,
            __('Emplacement du matériel', 'wprc'),
            $locationId,
            $locationOptions,
            'show_if_robot show_if_cell show_if_manipulator',
            $locationLabel
        );

        $addressId = $product ? trim((string) $product->get_meta(WC_Product_Abstract::META_LOCATION_ADDRESS_ID, true)) : '';
        $addressLabel = $product ? trim((string) $product->get_meta(WC_Product_Abstract::META_LOCATION_ADDRESS_LABEL, true)) : '';
        $this->renderAddressSelectField(
            WC_Product_Abstract::META_LOCATION_ADDRESS_ID,
            __('Adresse de l\'emplacement', 'wprc'),
            $locationId,
            $addressId,
            $addressLabel,
            'show_if_robot show_if_cell show_if_manipulator'
        );
        if ($product) {
            printf('<input type="hidden" id="%1$s" name="%1$s" value="%2$s">', esc_attr(WC_Product_Abstract::META_LOCATION_ADDRESS_ZIP), esc_attr((string) $product->get_meta(WC_Product_Abstract::META_LOCATION_ADDRESS_ZIP, true)));
            printf('<input type="hidden" id="%1$s" name="%1$s" value="%2$s">', esc_attr(WC_Product_Abstract::META_LOCATION_ADDRESS_COUNTRY), esc_attr((string) $product->get_meta(WC_Product_Abstract::META_LOCATION_ADDRESS_COUNTRY, true)));
            printf('<input type="hidden" id="%1$s" name="%1$s" value="%2$s">', esc_attr(WC_Product_Abstract::META_LOCATION_ADDRESS_COUNTRY_CODE), esc_attr((string) $product->get_meta(WC_Product_Abstract::META_LOCATION_ADDRESS_COUNTRY_CODE, true)));
        }





        echo '</div>';


    }





    public function renderShippingPanelFields(): void


    {


        $product = wc_get_product(get_the_ID());





        echo '<div class="options_group">';





        woocommerce_wp_text_input([


            'id' => WC_Product_Abstract::META_HS_CODE,


            'label' => __('Code SH', 'wprc'),


            'desc_tip' => true,


            'description' => __('Code produit du Système Harmonisé', 'wprc'),


            'type' => 'text',


            'wrapper_class' => 'show_if_spare',


        ]);





        woocommerce_wp_select([


            'id' => WC_Product_Abstract::META_ORIGIN_COUNTRY,


            'label' => __('Pays d\'origine', 'wprc'),


            'desc_tip' => true,


            'description' => __('Pays d\'origine du produit.', 'wprc'),


            'options' => WC()->countries->get_countries(),


            'value' => $product ? $product->get_meta(WC_Product_Abstract::META_ORIGIN_COUNTRY) : '',


            'wrapper_class' => 'show_if_spare',


        ]);

        if ($product) {
            $activeSource = $this->lookup->activeErpSource();

            $robotShippingId = trim((string) $product->get_meta(WC_Product_Abstract::META_SHIPPING_ERP_ID, true));
            $robotSource = trim((string) $product->get_meta(WC_Product_Abstract::META_SHIPPING_ERP_SOURCE, true));
            $robotLabel = trim((string) $product->get_meta(WC_Product_Abstract::META_SHIPPING_ERP_LABEL, true));
            $robotSource = $robotShippingId !== '' && $robotSource !== '' ? $robotSource : $activeSource;
            $this->renderSelectField(
                WC_Product_Abstract::META_SHIPPING_ERP_ID,
                __('Forfait transport ERP', 'rc-catalog'),
                $robotShippingId,
                $robotShippingId !== '' ? [$robotShippingId => ($robotLabel !== '' ? $robotLabel : $robotShippingId)] : [],
                'show_if_robot',
                $robotLabel
            );
            printf('<input type="hidden" id="%1$s" name="%1$s" value="%2$s">', esc_attr(WC_Product_Abstract::META_SHIPPING_ERP_SOURCE), esc_attr($robotShippingId !== '' ? $robotSource : ''));

            foreach ([
                [WC_Product_Abstract::META_SHIPPING_STANDARD_ERP_ID, WC_Product_Abstract::META_SHIPPING_STANDARD_ERP_SOURCE, WC_Product_Abstract::META_SHIPPING_STANDARD_ERP_LABEL, __('Transport standard ERP', 'rc-catalog')],
                [WC_Product_Abstract::META_SHIPPING_EXPRESS_ERP_ID, WC_Product_Abstract::META_SHIPPING_EXPRESS_ERP_SOURCE, WC_Product_Abstract::META_SHIPPING_EXPRESS_ERP_LABEL, __('Transport express ERP', 'rc-catalog')],
            ] as [$idKey, $sourceKey, $labelKey, $label]) {
                $externalId = trim((string) $product->get_meta($idKey, true));
                $storedSource = trim((string) $product->get_meta($sourceKey, true));
                $snapshotLabel = trim((string) $product->get_meta($labelKey, true));
                $storedSource = $externalId !== '' && $storedSource !== '' ? $storedSource : $activeSource;
                $this->renderSelectField(
                    $idKey,
                    $label,
                    $externalId,
                    $externalId !== '' ? [$externalId => ($snapshotLabel !== '' ? $snapshotLabel : $externalId)] : [],
                    'show_if_spare',
                    $snapshotLabel
                );
                printf('<input type="hidden" id="%1$s" name="%1$s" value="%2$s">', esc_attr($sourceKey), esc_attr($externalId !== '' ? $storedSource : ''));
            }
        }

        echo '</div>';


    }





    public function renderErpDescriptionPanel(): void
    {
        $product = wc_get_product(get_the_ID());
        if (!$product) {
            return;
        }
        $template = (string) $product->get_meta(WC_Product_Abstract::META_ERP_DESCRIPTION_TEMPLATE, true);
        $effectiveTemplate = $template !== '' ? $template : $this->descriptionTemplates->templateFor($product);
        $preview = $this->descriptionTemplates->render($product, $effectiveTemplate);
        ?>
        <div id="wprc_erp_description_product_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <?php woocommerce_wp_textarea_input([
                    'id' => WC_Product_Abstract::META_ERP_DESCRIPTION_TEMPLATE,
                    'label' => __('Modèle de description ERP', 'rc-catalog'),
                    'value' => $template,
                    'description' => __('Laissez vide pour utiliser le modèle défini dans WooCommerce > Réglages > ERP.', 'rc-catalog'),
                    'desc_tip' => true,
                    'style' => 'min-height:90px;',
                ]); ?>
                <p class="form-field"><label><?php echo esc_html__('Aperçu', 'rc-catalog'); ?></label><code style="display:inline-block;padding:8px 10px;max-width:70%;white-space:pre-wrap;"><?php echo esc_html($preview); ?></code></p>
                <p class="form-field"><label><?php echo esc_html__('Placeholders', 'rc-catalog'); ?></label><span class="description" style="display:inline-block;max-width:70%;"><?php echo esc_html(implode(' · ', $this->descriptionTemplates->placeholders())); ?></span></p>
                <p class="form-field"><label><?php echo esc_html__('Synchronisation', 'rc-catalog'); ?></label><span class="description"><?php echo esc_html__('Squelette uniquement : aucun envoi Axonaut/Odoo n’est effectué dans cette version.', 'rc-catalog'); ?></span></p>
            </div>
        </div>
        <?php
    }

    /**


     * @param array<string,string> $options


     */


    /**
     * @param array<string,string> $options
     */
    private function renderSelectField(
        string $key,
        string $label,
        string $value,
        array $options,
        string $wrapperClass = '',
        string $selectedText = ''
    ): void {
        $displayText = $selectedText !== '' ? $selectedText : ($value !== '' ? ($options[$value] ?? $value) : '');
        woocommerce_wp_select([
            'id' => $key,
            'label' => $label,
            'wrapper_class' => 'form-field-wide ' . $wrapperClass,
            'class' => 'wc-enhanced-select widefat erp',
            'custom_attributes' => [
                'data-selected' => $value,
                'data-selected-text' => $displayText,
                'data-minimum-results-for-search' => '0',
            ],
            'options' => $options,
            'value' => $value,
        ]);

        printf(
            '<input type="hidden" name="%1$s__label" id="%1$s__label" value="%2$s" class="wprc-select-label" />',
            esc_attr($key),
            esc_attr($displayText)
        );
    }

    private function renderAddressSelectField(
        string $key,
        string $label,
        string $locationId,
        string $selectedValue,
        string $selectedLabel,
        string $wrapperClass = ''
    ): void {
        $style = $locationId === '' ? 'display:none;' : '';
        $displayLabel = $selectedLabel !== '' ? $selectedLabel : $selectedValue;

        printf(
            '<p class="form-field form-field-wide %1$s" id="%2$s_field" data-selected-address="%3$s" style="%4$s">',
            esc_attr($wrapperClass),
            esc_attr($key),
            esc_attr($selectedValue),
            esc_attr($style)
        );
        printf('<label for="%1$s">%2$s</label>', esc_attr($key), esc_html($label));
        printf('<span class="description" id="%1$s_loading" style="display:none;">%2$s</span>', esc_attr($key), esc_html__('Chargement des adresses...', 'wprc'));
        printf('<span class="description" id="%1$s_message" style="display:none;"></span>', esc_attr($key));
        printf('<select id="%1$s" name="%1$s" class="short widefat">', esc_attr($key));
        echo '<option value="">' . esc_html__('Sélectionner une adresse', 'wprc') . '</option>';
        if ($selectedValue !== '') {
            printf('<option value="%1$s" selected>%2$s</option>', esc_attr($selectedValue), esc_html($displayLabel));
        }
        echo '</select>';
        printf('<input type="hidden" name="%1$s__label" id="%1$s__label" value="%2$s" class="wprc-select-label" />', esc_attr($key), esc_attr($displayLabel));
        echo '</p>';
    }
}
