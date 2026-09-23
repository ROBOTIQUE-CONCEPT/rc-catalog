<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Settings;

defined('ABSPATH') || exit;

final class ErpSettings extends \WC_Settings_Page
{
    public function __construct()
    {
        $this->id = 'rc_erp';
        $this->label = __('ERP', 'rc-catalog');
        parent::__construct();
    }

    public function get_settings_for_default_section(): array
    {
        $settings = [
            [
                'title' => __('Normalisation ERP', 'rc-catalog'),
                'type' => 'title',
                'desc' => __('Définissez un modèle de description ERP par type de produit. Chaque produit pourra le surcharger. Les placeholders sont résolus par RC Catalog, sans appel direct à l’ERP.', 'rc-catalog'),
                'id' => 'wprc_catalog_erp_templates',
            ],
        ];

        foreach (ErpOptions::productTypes() as $type => $label) {
            $settings[] = [
                'title' => sprintf(__('Description — %s', 'rc-catalog'), $label),
                'id' => ErpOptions::OPTION_PREFIX . sanitize_key($type),
                'type' => 'textarea',
                'css' => 'width:520px;min-height:72px;',
                'default' => ErpOptions::defaultTemplate($type),
                'desc' => __('Exemples : {brand.name}, {brand.software}, {product.reference}, {robot_model.reference}, {robot_model.applications}, {controller.name}.', 'rc-catalog'),
            ];
        }

        $settings[] = [
            'type' => 'sectionend',
            'id' => 'wprc_catalog_erp_templates',
        ];

        return $settings;
    }
}
