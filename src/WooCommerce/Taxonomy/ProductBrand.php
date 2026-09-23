<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Taxonomy;

use WPRC\Catalog\Security\Capabilities;

use WPRC\Catalog\RobotModel\Admin\ControllersPage;
use WPRC\Catalog\RobotModel\Application\Canonicalizer;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;
use WPRC\Catalog\WooCommerce\Settings\CatalogOptions;

defined('ABSPATH') || exit;

final class ProductBrand
{
    public const META_OLP_SOFTWARE = 'related_software_pages';
    public const META_ADDITIONAL_SERVICES = 'wprc_additional_brand_services';

    public static function render_olp_softwares_add($taxonomy): void
    {
        unset($taxonomy);
        ?>
        <div class="form-field term-group">
            <label><?php echo esc_html__('Logiciels compatibles', 'rc-catalog'); ?></label>
            <?php self::renderSoftwareChecklist([], null); ?>
        </div>
        <?php
    }

    public static function render_olp_softwares_edit($term, $taxonomy): void
    {
        unset($taxonomy);
        $termId = $term instanceof \WP_Term ? (int) $term->term_id : 0;
        $canonicalizer = new Canonicalizer();
        $canonicalTermId = $canonicalizer->term($termId);
        $current = self::normalizeIntList(get_term_meta($canonicalTermId, self::META_OLP_SOFTWARE, true));
        ?>
        <tr class="form-field">
            <th scope="row"><label><?php echo esc_html__('Logiciels compatibles', 'rc-catalog'); ?></label></th>
            <td><?php self::renderSoftwareChecklist($current, $termId); ?></td>
        </tr>
        <?php
    }

    public static function render_controllers_edit($term, $taxonomy): void
    {
        unset($taxonomy);
        if (!$term instanceof \WP_Term || !function_exists('rc_core')) {
            return;
        }
        $canonicalizer = new Canonicalizer();
        $brandId = $canonicalizer->term((int) $term->term_id);
        try {
            /** @var ControllerRepository $repository */
            $repository = rc_core()->container()->get(ControllerRepository::class);
            $controllers = $repository->all($brandId, false);
        } catch (\Throwable) {
            $controllers = [];
        }
        ?>
        <tr class="form-field">
            <th scope="row"><?php echo esc_html__('Contrôleurs', 'rc-catalog'); ?></th>
            <td>
                <?php if ($controllers === []) : ?>
                    <p><?php echo esc_html__('Aucun contrôleur enregistré pour cette marque.', 'rc-catalog'); ?></p>
                <?php else : ?>
                    <ul style="margin-top:0;">
                        <?php foreach ($controllers as $controller) : ?>
                            <li><strong><?php echo esc_html($controller->name); ?></strong><?php echo $controller->reference ? ' — ' . esc_html($controller->reference) : ''; ?><?php echo $controller->status !== 'active' ? ' (' . esc_html__('inactif', 'rc-catalog') . ')' : ''; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <p><a class="button" href="<?php echo esc_url(add_query_arg(['post_type' => 'product', 'page' => ControllersPage::PAGE_SLUG], admin_url('edit.php'))); ?>"><?php echo esc_html__('Gérer les contrôleurs', 'rc-catalog'); ?></a></p>
            </td>
        </tr>
        <?php
    }

    public static function render_additional_services_edit($term, $taxonomy): void
    {
        unset($taxonomy);
        $metaKey = self::META_ADDITIONAL_SERVICES;
        $current = self::normalizeTextList(get_term_meta((int) $term->term_id, $metaKey, true));
        ?>
        <tr class="form-field">
            <th scope="row"><label for="<?php echo esc_attr($metaKey); ?>"><?php echo esc_html__('Services additionnels associés', 'rc-catalog'); ?></label></th>
            <td>
                <select class="postform" name="<?php echo esc_attr($metaKey); ?>[]" id="<?php echo esc_attr($metaKey); ?>" multiple>
                    <?php foreach (self::getAdditionalServices() as $service => $label) : ?>
                        <option value="<?php echo esc_attr($service); ?>" <?php selected(in_array($service, $current, true)); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php echo esc_html__('Associez un ou plusieurs services à cette marque.', 'rc-catalog'); ?></p>
            </td>
        </tr>
        <?php
    }

    public static function save_metas($term_id, $tt_id): void
    {
        unset($tt_id);
        if (!Capabilities::canManageProductTerms()) {
            return;
        }
        $termId = absint($term_id);
        if ($termId <= 0) {
            return;
        }

        $canonicalizer = new Canonicalizer();
        $canonicalTermId = $canonicalizer->term($termId);

        // Only mutate the software relation when the Catalog field is actually
        // part of the submitted term form. REST/programmatic term updates must
        // not silently erase an existing relation.
        if (isset($_POST[self::META_OLP_SOFTWARE])) {
            $software = self::normalizeIntList(wp_unslash($_POST[self::META_OLP_SOFTWARE]));
            $software = array_values(array_unique(array_filter(array_map(
                static fn (int $pageId): int => $canonicalizer->post($pageId),
                $software
            ))));
            $software = array_values(array_intersect($software, self::allowedSoftwareCanonicalIds($termId)));
            update_term_meta($canonicalTermId, self::META_OLP_SOFTWARE, $software);
        }

        if (isset($_POST[self::META_ADDITIONAL_SERVICES])) {
            $allowed = array_keys(self::getAdditionalServices());
            $services = array_values(array_intersect(self::normalizeTextList(wp_unslash($_POST[self::META_ADDITIONAL_SERVICES])), $allowed));
            update_term_meta($termId, self::META_ADDITIONAL_SERVICES, $services);
        }
    }

    /** @param int[] $current */
    private static function renderSoftwareChecklist(array $current, ?int $termId): void
    {
        echo '<input type="hidden" name="' . esc_attr(self::META_OLP_SOFTWARE) . '[]" value="0">';
        $parentId = CatalogOptions::softwareParentId();
        if ($parentId <= 0) {
            echo '<p class="description">' . esc_html__('Configurez la page parente des logiciels dans WooCommerce > Réglages > Catalogue.', 'rc-catalog') . '</p>';
            return;
        }

        $canonicalizer = new Canonicalizer();
        $language = $canonicalizer->defaultLanguage();
        if ($termId && function_exists('pll_get_term_language')) {
            $candidate = pll_get_term_language($termId, 'slug');
            if (is_string($candidate) && $candidate !== '') {
                $language = $candidate;
            }
        }
        $translatedParent = $canonicalizer->translatedPost($parentId, $language);
        $pages = get_pages([
            'child_of' => $translatedParent,
            'sort_column' => 'menu_order,post_title',
            'sort_order' => 'ASC',
            'post_status' => ['publish', 'private', 'draft'],
        ]);
        if ($pages === []) {
            echo '<p class="description">' . esc_html__('Aucune page logiciel enfant trouvée.', 'rc-catalog') . '</p>';
            return;
        }
        echo '<div style="max-height:220px;overflow:auto;border:1px solid #dcdcde;padding:8px 10px;background:#fff;">';
        foreach ($pages as $page) {
            $canonicalPageId = $canonicalizer->post((int) $page->ID);
            echo '<label style="display:block;padding:3px 0;"><input type="checkbox" name="' . esc_attr(self::META_OLP_SOFTWARE) . '[]" value="' . esc_attr((string) $canonicalPageId) . '" ' . checked(in_array($canonicalPageId, $current, true), true, false) . '> ' . esc_html($page->post_title) . '</label>';
        }
        echo '</div><p class="description">' . esc_html__('Relations stockées vers les pages de la langue Polylang par défaut.', 'rc-catalog') . '</p>';
    }

    /** @return int[] */
    private static function allowedSoftwareCanonicalIds(int $termId): array
    {
        $parentId = CatalogOptions::softwareParentId();
        if ($parentId <= 0) {
            return [];
        }

        $canonicalizer = new Canonicalizer();
        $language = $canonicalizer->defaultLanguage();
        if ($termId > 0 && function_exists('pll_get_term_language')) {
            $candidate = pll_get_term_language($termId, 'slug');
            if (is_string($candidate) && $candidate !== '') {
                $language = $candidate;
            }
        }

        $translatedParent = $canonicalizer->translatedPost($parentId, $language);
        $ids = [];
        foreach (get_pages([
            'child_of' => $translatedParent,
            'sort_column' => 'menu_order,post_title',
            'sort_order' => 'ASC',
            'post_status' => ['publish', 'private', 'draft'],
        ]) as $page) {
            if (!$page instanceof \WP_Post) {
                continue;
            }
            $canonicalId = $canonicalizer->post((int) $page->ID);
            if ($canonicalId > 0) {
                $ids[$canonicalId] = $canonicalId;
            }
        }

        return array_values($ids);
    }

    /** @return array<string,string> */
    private static function getAdditionalServices(): array
    {
        return [
            'warranty' => __('Warranty', 'rc-catalog'),
            'shipping' => __('Shipping', 'rc-catalog'),
            'customization' => __('Customization', 'rc-catalog'),
            'programming' => __('Programming', 'rc-catalog'),
            'preventive_maintenance' => __('Preventive maintenance', 'rc-catalog'),
            'contract_maintenance' => __('Contract maintenance', 'rc-catalog'),
            'support' => __('Support', 'rc-catalog'),
            'transfer' => __('Transfer', 'rc-catalog'),
        ];
    }

    /** @return int[] */
    private static function normalizeIntList(mixed $value): array
    {
        $items = is_array($value) ? $value : ($value === '' || $value === null ? [] : [$value]);
        return array_values(array_filter(array_map('absint', $items)));
    }

    /** @return string[] */
    private static function normalizeTextList(mixed $value): array
    {
        $items = is_array($value) ? $value : ($value === '' || $value === null ? [] : [$value]);
        return array_values(array_filter(array_map('sanitize_key', $items)));
    }
}
