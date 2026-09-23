<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Admin;

use WPRC\Catalog\Security\Capabilities;

use Throwable;
use WPRC\Catalog\RobotModel\Application\Canonicalizer;
use WPRC\Catalog\RobotModel\Domain\AxisSpecification;
use WPRC\Catalog\RobotModel\Persistence\ControllerRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRelationRepository;
use WPRC\Catalog\RobotModel\Persistence\RobotModelRepository;
use WPRC\Catalog\RobotModel\WordPress\ContentTypes;
use WPRC\Catalog\WooCommerce\Settings\CatalogOptions;
use WPRC\Catalog\WooCommerce\Product\ProductDataLookup;
use WPRC\Core\Contracts\LoggerInterface;
use WP_Post;
use WP_Term;

defined('ABSPATH') || exit;

final class RobotModelEditor
{
    private const NONCE_ACTION = 'wprc_robot_model_save';
    private const NONCE_NAME = '_wprc_robot_model_nonce';

    public function __construct(
        private readonly RobotModelRepository $models,
        private readonly ControllerRepository $controllers,
        private readonly RobotModelRelationRepository $relations,
        private readonly Canonicalizer $canonicalizer,
        private readonly ProductDataLookup $lookup,
        private readonly LoggerInterface $logger
    ) {
    }

    public function init(): void
    {
        add_action('add_meta_boxes_' . ContentTypes::POST_TYPE, [$this, 'addMetaBoxes']);
        add_action('save_post_' . ContentTypes::POST_TYPE, [$this, 'save'], 40, 3);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wprc_robot_model_controllers_by_brand', [$this, 'ajaxControllersByBrand']);
        add_action('admin_head-post.php', [$this, 'removeNativeBrandBox']);
        add_action('admin_head-post-new.php', [$this, 'removeNativeBrandBox']);
    }

    public function addMetaBoxes(WP_Post $post): void
    {
        add_meta_box('wprc_robot_model_classification', __('Classification', 'rc-catalog'), [$this, 'renderClassification'], ContentTypes::POST_TYPE, 'side', 'high');
        add_meta_box('wprc_robot_model_data', __('Référentiel technique', 'rc-catalog'), [$this, 'renderDataPanel'], ContentTypes::POST_TYPE, 'normal', 'high');
    }

    public function renderDataPanel(WP_Post $post): void
    {
        ?>
        <div class="wprc-rm-tabs" data-wprc-rm-tabs>
            <div class="wprc-rm-tabs__nav" role="tablist" aria-label="<?php echo esc_attr__('Sections du modèle', 'rc-catalog'); ?>">
                <button type="button" class="button wprc-rm-tab is-active" data-rm-tab="technical"><?php echo esc_html__('Technique', 'rc-catalog'); ?></button>
                <button type="button" class="button wprc-rm-tab" data-rm-tab="compatibility"><?php echo esc_html__('Compatibilités', 'rc-catalog'); ?></button>
                <button type="button" class="button wprc-rm-tab" data-rm-tab="products"><?php echo esc_html__('Pièces & services', 'rc-catalog'); ?></button>
                <button type="button" class="button wprc-rm-tab" data-rm-tab="maintenance"><?php echo esc_html__('Maintenance', 'rc-catalog'); ?></button>
            </div>
            <div class="wprc-rm-tabs__panel is-active" data-rm-panel="technical"><?php $this->renderTechnical($post); ?></div>
            <div class="wprc-rm-tabs__panel" data-rm-panel="compatibility"><?php $this->renderCompatibility($post); ?></div>
            <div class="wprc-rm-tabs__panel" data-rm-panel="products"><?php $this->renderProducts($post); ?></div>
            <div class="wprc-rm-tabs__panel" data-rm-panel="maintenance"><?php $this->renderMaintenance($post); ?></div>
        </div>
        <?php
    }

    public function removeNativeBrandBox(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->post_type === ContentTypes::POST_TYPE) {
            remove_meta_box('product_branddiv', ContentTypes::POST_TYPE, 'side');
            remove_meta_box(ContentTypes::FAMILY_TAXONOMY . 'div', ContentTypes::POST_TYPE, 'side');
        }
    }

    public function renderClassification(WP_Post $post): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        $brandId = $this->canonicalBrandForPost($post->ID);
        [$familyId, $seriesId] = $this->canonicalFamilySeriesForPost($post->ID);
        $brands = $this->defaultLanguageBrands();
        $families = $this->defaultLanguageFamilyTerms(0);
        $series = $this->defaultLanguageSeriesTerms();
        ?>
        <p><label for="wprc-robot-model-brand"><strong><?php echo esc_html__('Marque', 'rc-catalog'); ?></strong></label></p>
        <select id="wprc-robot-model-brand" name="wprc_robot_model_brand" class="widefat">
            <option value=""><?php echo esc_html__('— Sélectionner —', 'rc-catalog'); ?></option>
            <?php foreach ($brands as $brand) : ?>
                <option value="<?php echo esc_attr((string) $brand->term_id); ?>" <?php selected($brandId, $brand->term_id); ?>><?php echo esc_html($brand->name); ?></option>
            <?php endforeach; ?>
        </select>

        <p><label for="wprc-robot-model-family"><strong><?php echo esc_html__('Famille', 'rc-catalog'); ?></strong></label></p>
        <select id="wprc-robot-model-family" name="wprc_robot_model_family" class="widefat">
            <option value=""><?php echo esc_html__('— Sélectionner —', 'rc-catalog'); ?></option>
            <?php foreach ($families as $family) : ?>
                <option value="<?php echo esc_attr((string) $family->term_id); ?>" <?php selected($familyId, $family->term_id); ?>><?php echo esc_html($family->name); ?></option>
            <?php endforeach; ?>
        </select>

        <p><label for="wprc-robot-model-series"><strong><?php echo esc_html__('Série', 'rc-catalog'); ?></strong></label></p>
        <select id="wprc-robot-model-series" name="wprc_robot_model_series" class="widefat">
            <option value=""><?php echo esc_html__('— Aucune série —', 'rc-catalog'); ?></option>
            <?php foreach ($series as $term) : ?>
                <option value="<?php echo esc_attr((string) $term->term_id); ?>" data-parent="<?php echo esc_attr((string) $this->canonicalizer->term((int) $term->parent)); ?>" <?php selected($seriesId, $term->term_id); ?>><?php echo esc_html($term->name); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html__('La marque réutilise product_brand. Famille et série utilisent la taxonomie hiérarchique RC Catalog ; une seule branche peut être affectée au modèle.', 'rc-catalog'); ?></p>
        <?php
    }

    public function renderTechnical(WP_Post $post): void
    {
        $model = $this->models->findByPostId($post->ID);
        $axesByNumber = [];
        if ($model) {
            foreach ($model->axes as $axis) {
                $axesByNumber[$axis->axisNumber] = $axis;
            }
        }
        $axesCount = $model?->axesCount ?? 6;
        ?>
        <p class="description"><strong><?php echo esc_html__('Données communes aux traductions.', 'rc-catalog'); ?></strong> <?php echo esc_html__('Le contenu éditorial du CPT reste traduit avec Polylang ; les caractéristiques ci-dessous appartiennent à l’entité technique unique.', 'rc-catalog'); ?></p>
        <div class="wprc-rm-grid">
            <?php $this->numberField('payload_kg', __('Charge utile', 'rc-catalog'), $model?->payloadKg, 'kg'); ?>
            <?php $this->numberField('reach_mm', __('Portée', 'rc-catalog'), $model?->reachMm, 'mm'); ?>
            <?php $this->numberField('mass_kg', __('Masse', 'rc-catalog'), $model?->massKg, 'kg'); ?>
            <?php $this->numberField('repeatability_mm', __('Répétabilité', 'rc-catalog'), $model?->repeatabilityMm, 'mm'); ?>
            <?php $this->textField('structure', __('Structure', 'rc-catalog'), $model?->structure); ?>
            <p><label><strong><?php echo esc_html__('Nombre d’axes', 'rc-catalog'); ?></strong></label><br><select name="wprc_robot_model_technical[axes_count]" id="wprc-rm-axes-count"><option value=""></option><?php for ($i = 1; $i <= 7; $i++) : ?><option value="<?php echo esc_attr((string) $i); ?>" <?php selected($axesCount, $i); ?>><?php echo esc_html((string) $i); ?></option><?php endfor; ?></select></p>
            <?php $this->textField('ip_base', __('Indice IP base', 'rc-catalog'), $model?->ipBase); ?>
            <?php $this->textField('ip_wrist', __('Indice IP poignet', 'rc-catalog'), $model?->ipWrist); ?>
        </div>

        <h4><?php echo esc_html__('Amplitudes et vélocités', 'rc-catalog'); ?></h4>
        <table class="widefat striped wprc-rm-axis-table">
            <thead><tr><th><?php echo esc_html__('Axe', 'rc-catalog'); ?></th><th><?php echo esc_html__('Mini (°)', 'rc-catalog'); ?></th><th><?php echo esc_html__('Maxi (°)', 'rc-catalog'); ?></th><th><?php echo esc_html__('Vitesse max (°/s)', 'rc-catalog'); ?></th></tr></thead>
            <tbody>
            <?php for ($i = 1; $i <= 7; $i++) : $axis = $axesByNumber[$i] ?? null; ?>
                <tr data-axis-row="<?php echo esc_attr((string) $i); ?>" <?php echo $i > $axesCount ? 'style="display:none"' : ''; ?>>
                    <td><strong>A<?php echo esc_html((string) $i); ?></strong><input type="hidden" name="wprc_robot_model_axes[<?php echo esc_attr((string) $i); ?>][motion_type]" value="rotary"></td>
                    <td><input type="text" inputmode="decimal" name="wprc_robot_model_axes[<?php echo esc_attr((string) $i); ?>][range_min]" value="<?php echo esc_attr($this->formatNumber($axis?->rangeMin)); ?>"></td>
                    <td><input type="text" inputmode="decimal" name="wprc_robot_model_axes[<?php echo esc_attr((string) $i); ?>][range_max]" value="<?php echo esc_attr($this->formatNumber($axis?->rangeMax)); ?>"></td>
                    <td><input type="text" inputmode="decimal" name="wprc_robot_model_axes[<?php echo esc_attr((string) $i); ?>][max_velocity]" value="<?php echo esc_attr($this->formatNumber($axis?->maxVelocity)); ?>"></td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
        <?php
    }

    public function renderCompatibility(WP_Post $post): void
    {
        $model = $this->models->findByPostId($post->ID);
        $modelId = $model?->id ?? 0;
        $brandId = $this->canonicalBrandForPost($post->ID);
        $selectedControllers = $modelId > 0 ? $this->relations->controllerIds($modelId) : [];
        $controllers = $brandId > 0 ? $this->controllers->all($brandId) : [];
        $selectedApplications = $modelId > 0 ? $this->relations->pageIds($modelId, 'application') : [];
        $applicationPages = $this->applicationPagesForEditor($post->ID);
        ?>
        <div class="wprc-rm-two-columns">
            <div>
                <h4><?php echo esc_html__('Contrôleurs compatibles', 'rc-catalog'); ?></h4>
                <div id="wprc-rm-controllers-list" data-selected="<?php echo esc_attr(wp_json_encode($selectedControllers)); ?>">
                    <?php if ($brandId <= 0) : ?><p class="description"><?php echo esc_html__('Sélectionnez d’abord une marque.', 'rc-catalog'); ?></p><?php endif; ?>
                    <?php foreach ($controllers as $controller) : ?>
                        <label class="wprc-rm-check"><input type="checkbox" name="wprc_robot_model_controllers[]" value="<?php echo esc_attr((string) $controller->id); ?>" <?php checked(in_array($controller->id, $selectedControllers, true)); ?>> <strong><?php echo esc_html($controller->name); ?></strong><?php echo $controller->reference ? ' — ' . esc_html($controller->reference) : ''; ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h4><?php echo esc_html__('Applications compatibles', 'rc-catalog'); ?></h4>
                <?php if (CatalogOptions::applicationsParentId() <= 0) : ?>
                    <p class="description"><?php echo esc_html__('Configurez d’abord la page parente dans WooCommerce > Réglages > Catalogue.', 'rc-catalog'); ?></p>
                <?php elseif ($applicationPages === []) : ?>
                    <p class="description"><?php echo esc_html__('Aucune page enfant trouvée.', 'rc-catalog'); ?></p>
                <?php else : ?>
                    <?php foreach ($applicationPages as $page) : $canonical = $this->canonicalizer->post($page->ID); ?>
                        <label class="wprc-rm-check"><input type="checkbox" name="wprc_robot_model_applications[]" value="<?php echo esc_attr((string) $canonical); ?>" <?php checked(in_array($canonical, $selectedApplications, true)); ?>> <?php echo esc_html($page->post_title); ?></label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function renderProducts(WP_Post $post): void
    {
        $model = $this->models->findByPostId($post->ID);
        $modelId = $model?->id ?? 0;
        $groups = [
            'compatible_part' => __('Pièces / consommables compatibles', 'rc-catalog'),
            'component' => __('Composants compatibles', 'rc-catalog'),
            'service' => __('Services associés', 'rc-catalog'),
        ];

        foreach ($groups as $type => $label) {
            $selected = $modelId > 0 ? $this->relations->erpProducts($modelId, $type) : [];
            $this->renderErpProductSearch($type, $label, $selected);
        }

        echo '<p class="description">' . esc_html__('Ces relations pointent vers les produits de l’ERP via RC Core. Aucun produit WooCommerce technique n’est créé. La source ERP et l’identifiant externe sont conservés dans les tables Catalog.', 'rc-catalog') . '</p>';
    }

    public function renderMaintenance(WP_Post $post): void
    {
        $model = $this->models->findByPostId($post->ID);
        $modelId = $model?->id ?? 0;
        $lubrication = $modelId > 0 ? $this->relations->lubrication($modelId) : [];
        $belts = $modelId > 0 ? $this->relations->belts($modelId) : [];
        $balancers = $modelId > 0 ? $this->relations->balancers($modelId) : [];
        ?>
        <h4><?php echo esc_html__('Vidanges / lubrification', 'rc-catalog'); ?></h4>
        <div class="wprc-rm-table-scroll"><table class="widefat striped wprc-rm-repeater" data-repeater="lubrication"><thead><tr>
            <th><?php echo esc_html__('Point', 'rc-catalog'); ?></th><th><?php echo esc_html__('Produit ERP', 'rc-catalog'); ?></th><th><?php echo esc_html__('Qté', 'rc-catalog'); ?></th><th><?php echo esc_html__('Unité', 'rc-catalog'); ?></th><th></th>
        </tr></thead><tbody><?php foreach ($lubrication as $i => $row) { $this->renderLubricationRow((int) $i, $row); } ?></tbody></table></div>
        <p><button type="button" class="button wprc-rm-add-row" data-kind="lubrication"><?php echo esc_html__('Ajouter une ligne', 'rc-catalog'); ?></button></p>

        <h4><?php echo esc_html__('Courroies', 'rc-catalog'); ?></h4>
        <div class="wprc-rm-table-scroll"><table class="widefat striped wprc-rm-repeater" data-repeater="belts"><thead><tr>
            <th><?php echo esc_html__('Section', 'rc-catalog'); ?></th><th><?php echo esc_html__('Axe', 'rc-catalog'); ?></th><th><?php echo esc_html__('Produit ERP', 'rc-catalog'); ?></th><th><?php echo esc_html__('Nominal', 'rc-catalog'); ?></th><th><?php echo esc_html__('Delta', 'rc-catalog'); ?></th><th><?php echo esc_html__('Unité', 'rc-catalog'); ?></th><th></th>
        </tr></thead><tbody><?php foreach ($belts as $i => $row) { $this->renderBeltRow((int) $i, $row); } ?></tbody></table></div>
        <p><button type="button" class="button wprc-rm-add-row" data-kind="belts"><?php echo esc_html__('Ajouter une ligne', 'rc-catalog'); ?></button></p>

        <h4><?php echo esc_html__('Groupes d’équilibrage', 'rc-catalog'); ?></h4>
        <div class="wprc-rm-table-scroll"><table class="widefat striped wprc-rm-repeater" data-repeater="balancers"><thead><tr>
            <th><?php echo esc_html__('Libellé', 'rc-catalog'); ?></th><th><?php echo esc_html__('Produit ERP', 'rc-catalog'); ?></th><th><?php echo esc_html__('P mini', 'rc-catalog'); ?></th><th><?php echo esc_html__('P nominale', 'rc-catalog'); ?></th><th><?php echo esc_html__('Unité', 'rc-catalog'); ?></th><th></th>
        </tr></thead><tbody><?php foreach ($balancers as $i => $row) { $this->renderBalancerRow((int) $i, $row); } ?></tbody></table></div>
        <p><button type="button" class="button wprc-rm-add-row" data-kind="balancers"><?php echo esc_html__('Ajouter une ligne', 'rc-catalog'); ?></button></p>

        <script type="text/html" id="tmpl-wprc-rm-lubrication-row"><?php $this->renderLubricationRow(-1, []); ?></script>
        <script type="text/html" id="tmpl-wprc-rm-belts-row"><?php $this->renderBeltRow(-1, []); ?></script>
        <script type="text/html" id="tmpl-wprc-rm-balancers-row"><?php $this->renderBalancerRow(-1, []); ?></script>
        <?php
    }

    public function save(int $postId, WP_Post $post, bool $update): void
    {
        unset($update);
        if ($post->post_type !== ContentTypes::POST_TYPE || $post->post_status === 'auto-draft' || wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        try {
            $model = $this->models->findByPostId($postId);
            if (!$model) {
                return;
            }

            $brandId = isset($_POST['wprc_robot_model_brand']) ? $this->canonicalizer->term(absint(wp_unslash((string) $_POST['wprc_robot_model_brand']))) : 0;
            $brandId = $this->validCanonicalBrand($brandId) ? $brandId : 0;
            if ($brandId > 0) {
                $brandForLanguage = $this->canonicalizer->translatedTerm($brandId, $this->canonicalizer->postLanguage($postId));
                wp_set_object_terms($postId, [$brandForLanguage ?: $brandId], 'product_brand', false);
            } else {
                wp_set_object_terms($postId, [], 'product_brand', false);
            }

            $familyId = isset($_POST['wprc_robot_model_family']) ? $this->canonicalizer->term(absint(wp_unslash((string) $_POST['wprc_robot_model_family']))) : 0;
            $seriesId = isset($_POST['wprc_robot_model_series']) ? $this->canonicalizer->term(absint(wp_unslash((string) $_POST['wprc_robot_model_series']))) : 0;
            $familyId = $this->validTopLevelFamily($familyId) ? $familyId : 0;
            $seriesId = $this->validSeriesForFamily($seriesId, $familyId) ? $seriesId : 0;
            $classificationId = $seriesId > 0 ? $seriesId : $familyId;
            if ($classificationId > 0) {
                $translatedClassification = $this->canonicalizer->translatedTerm($classificationId, $this->canonicalizer->postLanguage($postId));
                wp_set_object_terms($postId, [$translatedClassification ?: $classificationId], ContentTypes::FAMILY_TAXONOMY, false);
            } else {
                wp_set_object_terms($postId, [], ContentTypes::FAMILY_TAXONOMY, false);
            }

            $technical = isset($_POST['wprc_robot_model_technical']) && is_array($_POST['wprc_robot_model_technical']) ? wp_unslash($_POST['wprc_robot_model_technical']) : [];
            $axesCount = $this->nullableInt($technical['axes_count'] ?? null, 1, 7);
            $this->models->updateTechnicalData($model->id, [
                'payload_kg' => $this->nullableDecimal($technical['payload_kg'] ?? null),
                'reach_mm' => $this->nullableDecimal($technical['reach_mm'] ?? null),
                'mass_kg' => $this->nullableDecimal($technical['mass_kg'] ?? null),
                'repeatability_mm' => $this->nullableDecimal($technical['repeatability_mm'] ?? null),
                'structure' => sanitize_text_field((string) ($technical['structure'] ?? '')) ?: null,
                'axes_count' => $axesCount,
                'ip_base' => sanitize_text_field((string) ($technical['ip_base'] ?? '')) ?: null,
                'ip_wrist' => sanitize_text_field((string) ($technical['ip_wrist'] ?? '')) ?: null,
            ]);

            $axesInput = isset($_POST['wprc_robot_model_axes']) && is_array($_POST['wprc_robot_model_axes']) ? wp_unslash($_POST['wprc_robot_model_axes']) : [];
            $axes = [];
            for ($i = 1; $i <= ($axesCount ?? 0); $i++) {
                $row = isset($axesInput[$i]) && is_array($axesInput[$i]) ? $axesInput[$i] : [];
                $axes[] = new AxisSpecification(
                    $i,
                    in_array(($row['motion_type'] ?? 'rotary'), ['rotary', 'linear'], true) ? (string) $row['motion_type'] : 'rotary',
                    $this->nullableDecimal($row['range_min'] ?? null),
                    $this->nullableDecimal($row['range_max'] ?? null),
                    'deg',
                    $this->nullableDecimal($row['max_velocity'] ?? null),
                    'deg_s'
                );
            }
            $this->models->replaceAxes($model->id, $axes);

            $controllerIds = isset($_POST['wprc_robot_model_controllers']) && is_array($_POST['wprc_robot_model_controllers']) ? array_map('absint', wp_unslash($_POST['wprc_robot_model_controllers'])) : [];
            if ($brandId > 0) {
                $allowed = array_map(static fn ($controller): int => $controller->id, $this->controllers->all($brandId));
                $controllerIds = array_values(array_intersect($controllerIds, $allowed));
            } else {
                $controllerIds = [];
            }
            $this->relations->replaceControllers($model->id, $controllerIds);

            $applications = isset($_POST['wprc_robot_model_applications']) && is_array($_POST['wprc_robot_model_applications']) ? array_map('absint', wp_unslash($_POST['wprc_robot_model_applications'])) : [];
            $allowedApplications = [];
            foreach ($this->applicationPagesForEditor($postId) as $applicationPage) {
                if ($applicationPage instanceof WP_Post) {
                    $canonicalPageId = $this->canonicalizer->post((int) $applicationPage->ID);
                    if ($canonicalPageId > 0) {
                        $allowedApplications[$canonicalPageId] = $canonicalPageId;
                    }
                }
            }
            $applications = array_values(array_intersect(
                array_values(array_unique(array_map(fn (int $pageId): int => $this->canonicalizer->post($pageId), $applications))),
                array_values($allowedApplications)
            ));
            $this->relations->replacePages($model->id, 'application', $applications);

            foreach (['compatible_part', 'component', 'service'] as $relationType) {
                $key = 'wprc_robot_model_erp_products_' . $relationType;
                $tokens = isset($_POST[$key]) && is_array($_POST[$key]) ? array_map('sanitize_text_field', wp_unslash($_POST[$key])) : [];
                $existing = $this->relations->erpProducts($model->id, $relationType);
                $this->relations->replaceErpProducts($model->id, $relationType, $this->hydrateErpSelections($tokens, $existing));
            }

            $maintenance = isset($_POST['wprc_robot_model_maintenance']) && is_array($_POST['wprc_robot_model_maintenance']) ? wp_unslash($_POST['wprc_robot_model_maintenance']) : [];
            $this->relations->replaceLubrication(
                $model->id,
                $this->hydrateMaintenanceRows(
                    is_array($maintenance['lubrication'] ?? null) ? $maintenance['lubrication'] : [],
                    $this->relations->lubrication($model->id)
                )
            );
            $this->relations->replaceBelts(
                $model->id,
                $this->hydrateMaintenanceRows(
                    is_array($maintenance['belts'] ?? null) ? $maintenance['belts'] : [],
                    $this->relations->belts($model->id)
                )
            );
            $this->relations->replaceBalancers(
                $model->id,
                $this->hydrateMaintenanceRows(
                    is_array($maintenance['balancers'] ?? null) ? $maintenance['balancers'] : [],
                    $this->relations->balancers($model->id)
                )
            );

            $this->logger->info('Robot model technical data saved.', 'catalog', ['post_id' => $postId, 'model_id' => $model->id], 'catalog.robot_model.saved');
        } catch (Throwable $exception) {
            $this->logger->error('Robot model save failed.', 'catalog', ['post_id' => $postId, 'error' => $exception->getMessage()], 'catalog.robot_model.save_failed');
        }
    }

    public function enqueueAssets(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== ContentTypes::POST_TYPE) {
            return;
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script('selectWoo');
        wp_enqueue_style('selectWoo');
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_style('woocommerce_admin_styles');

        wp_enqueue_style('wprc-robot-model-admin', WPRC_CATALOG_URL . 'assets/admin/robot-models.css', [], RC_CATALOG_VERSION);
        wp_enqueue_script('wprc-robot-model-admin', WPRC_CATALOG_URL . 'assets/admin/robot-models.js', ['jquery', 'selectWoo', 'wc-enhanced-select'], RC_CATALOG_VERSION, true);
        wp_localize_script('wprc-robot-model-admin', 'wprcRobotModels', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wprc_robot_model_ajax'),
            'erpNonce' => wp_create_nonce('wprc_woo_ajax'),
            'erpSource' => $this->lookup->activeErpSource(),
            'erpProductAction' => 'wprc_search_erp_products',
            'erpProductPlaceholder' => __('Rechercher un produit ERP…', 'rc-catalog'),
        ]);
    }

    public function ajaxControllersByBrand(): void
    {
        check_ajax_referer('wprc_robot_model_ajax', 'nonce');
        if (!Capabilities::canEditRobotModels()) {
            wp_send_json_error(['message' => __('Forbidden', 'rc-catalog')], 403);
        }
        $brandId = isset($_GET['brand_id']) ? $this->canonicalizer->term(absint(wp_unslash((string) $_GET['brand_id']))) : 0;
        $items = [];
        foreach ($this->controllers->all($brandId) as $controller) {
            $items[] = ['id' => $controller->id, 'name' => $controller->name, 'reference' => $controller->reference];
        }
        wp_send_json_success($items);
    }

    /** @param array<int,array<string,mixed>> $selected */
    private function renderErpProductSearch(string $type, string $label, array $selected): void
    {
        echo '<p><label><strong>' . esc_html($label) . '</strong></label><br>';
        echo '<select class="wprc-erp-product-search" multiple="multiple" style="width:100%" name="wprc_robot_model_erp_products_' . esc_attr($type) . '[]" data-placeholder="' . esc_attr__('Rechercher un produit ERP…', 'rc-catalog') . '">';
        foreach ($selected as $row) {
            $token = $this->erpToken(
                (string) ($row['erp_source'] ?? ''),
                (string) ($row['external_product_id'] ?? ''),
                (string) ($row['product_name'] ?? ''),
                (string) ($row['product_reference'] ?? '')
            );
            if ($token === '') {
                continue;
            }
            echo '<option value="' . esc_attr($token) . '" selected>' . esc_html($this->erpRelationLabel($row)) . '</option>';
        }
        echo '</select></p>';
    }

    private function renderErpProductSearchCell(string $name, array $row): void
    {
        $token = $this->erpToken(
                (string) ($row['erp_source'] ?? ''),
                (string) ($row['external_product_id'] ?? ''),
                (string) ($row['product_name'] ?? ''),
                (string) ($row['product_reference'] ?? '')
            );
        echo '<select class="wprc-erp-product-search" style="width:220px" name="' . esc_attr($name) . '" data-placeholder="' . esc_attr__('Produit ERP…', 'rc-catalog') . '" data-allow-clear="true">';
        if ($token !== '') {
            echo '<option value="' . esc_attr($token) . '" selected>' . esc_html($this->erpRelationLabel($row)) . '</option>';
        }
        echo '</select>';
    }

    private function renderLubricationRow(int $index, array $row): void
    {
        $idx = $index >= 0 ? (string) $index : '__INDEX__';
        $targetType = (string) ($row['target_type'] ?? 'axis');
        $axis = isset($row['axis_number']) ? (int) $row['axis_number'] : 1;
        $target = $targetType === 'axis_5_6' ? 'axis_5_6' : 'axis_' . max(1, min(7, $axis));
        echo '<tr><td><select name="wprc_robot_model_maintenance[lubrication][' . esc_attr($idx) . '][target]">';
        for ($i = 1; $i <= 7; $i++) {
            echo '<option value="axis_' . $i . '" ' . selected($target, 'axis_' . $i, false) . '>A' . $i . '</option>';
        }
        echo '<option value="axis_5_6" ' . selected($target, 'axis_5_6', false) . '>A5/A6</option>';
        echo '</select></td><td>';
        $this->renderErpProductSearchCell('wprc_robot_model_maintenance[lubrication][' . $idx . '][product_token]', $row);
        echo '</td><td><input class="small-text" type="text" inputmode="decimal" name="wprc_robot_model_maintenance[lubrication][' . esc_attr($idx) . '][quantity]" value="' . esc_attr((string) ($row['quantity'] ?? '')) . '"></td>';
        echo '<td><select name="wprc_robot_model_maintenance[lubrication][' . esc_attr($idx) . '][quantity_unit]"><option value="l" ' . selected((string) ($row['quantity_unit'] ?? 'l'), 'l', false) . '>L</option><option value="cm3" ' . selected((string) ($row['quantity_unit'] ?? 'l'), 'cm3', false) . '>cm³</option></select></td>';
        echo '<td><button type="button" class="button-link-delete wprc-rm-remove-row">×</button></td></tr>';
    }

    private function renderBeltRow(int $index, array $row): void
    {
        $idx = $index >= 0 ? (string) $index : '__INDEX__';
        $section = in_array((string) ($row['section'] ?? ''), ['wrist', 'motor'], true) ? (string) $row['section'] : 'wrist';
        echo '<tr><td><select name="wprc_robot_model_maintenance[belts][' . esc_attr($idx) . '][section]">';
        echo '<option value="wrist" ' . selected($section, 'wrist', false) . '>' . esc_html__('Poignet', 'rc-catalog') . '</option>';
        echo '<option value="motor" ' . selected($section, 'motor', false) . '>' . esc_html__('Moteur', 'rc-catalog') . '</option>';
        echo '</select></td><td><select name="wprc_robot_model_maintenance[belts][' . esc_attr($idx) . '][axis_number]">';
        for ($i = 1; $i <= 7; $i++) {
            echo '<option value="' . $i . '" ' . selected((int) ($row['axis_number'] ?? 1), $i, false) . '>A' . $i . '</option>';
        }
        echo '</select></td><td>';
        $this->renderErpProductSearchCell('wprc_robot_model_maintenance[belts][' . $idx . '][product_token]', $row);
        echo '</td><td><input class="small-text" type="text" inputmode="decimal" name="wprc_robot_model_maintenance[belts][' . esc_attr($idx) . '][tension_nominal]" value="' . esc_attr((string) ($row['tension_nominal'] ?? '')) . '"></td>';
        echo '<td><input class="small-text" type="text" inputmode="decimal" name="wprc_robot_model_maintenance[belts][' . esc_attr($idx) . '][tension_delta]" value="' . esc_attr((string) ($row['tension_delta'] ?? '')) . '"></td>';
        echo '<td><input class="small-text" type="text" name="wprc_robot_model_maintenance[belts][' . esc_attr($idx) . '][tension_unit]" value="' . esc_attr((string) ($row['tension_unit'] ?? 'Hz')) . '"></td>';
        echo '<td><button type="button" class="button-link-delete wprc-rm-remove-row">×</button></td></tr>';
    }

    private function renderBalancerRow(int $index, array $row): void
    {
        $idx = $index >= 0 ? (string) $index : '__INDEX__';
        echo '<tr><td><input type="text" name="wprc_robot_model_maintenance[balancers][' . esc_attr($idx) . '][label]" value="' . esc_attr((string) ($row['balancer_label'] ?? '')) . '"></td><td>';
        $this->renderErpProductSearchCell('wprc_robot_model_maintenance[balancers][' . $idx . '][product_token]', $row);
        echo '</td>';
        foreach (['pressure_min', 'pressure_nominal'] as $field) {
            echo '<td><input class="small-text" type="text" inputmode="decimal" name="wprc_robot_model_maintenance[balancers][' . esc_attr($idx) . '][' . esc_attr($field) . ']" value="' . esc_attr((string) ($row[$field] ?? '')) . '"></td>';
        }
        echo '<td><input class="small-text" type="text" name="wprc_robot_model_maintenance[balancers][' . esc_attr($idx) . '][pressure_unit]" value="' . esc_attr((string) ($row['pressure_unit'] ?? 'bar')) . '"></td>';
        echo '<td><button type="button" class="button-link-delete wprc-rm-remove-row">×</button></td></tr>';
    }

    /**
     * @param string[] $tokens
     * @param array<int,array<string,mixed>> $existing
     * @return array<int,array<string,mixed>>
     */
    private function hydrateErpSelections(array $tokens, array $existing): array
    {
        $fallback = $this->erpSnapshotMap($existing);
        $items = [];

        foreach (array_filter($tokens) as $token) {
            [$source, $externalId, $name, $reference] = $this->parseErpToken((string) $token);
            if ($source === '' || $externalId === '') {
                continue;
            }

            $key = $this->erpBaseToken($source, $externalId);
            $old = $fallback[$key] ?? [];
            $items[$key] = [
                'erp_source' => $source,
                'external_product_id' => $externalId,
                'product_name' => $name !== '' ? $name : (string) ($old['product_name'] ?? ''),
                'product_reference' => $reference !== '' ? $reference : (string) ($old['product_reference'] ?? ''),
                'quantity' => 1,
            ];
        }

        return array_values($items);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array<int,array<string,mixed>> $existing
     * @return array<int,array<string,mixed>>
     */
    private function hydrateMaintenanceRows(array $rows, array $existing): array
    {
        $fallback = $this->erpSnapshotMap($existing);
        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            [$source, $externalId, $name, $reference] = $this->parseErpToken((string) ($row['product_token'] ?? ''));
            if ($source !== '' && $externalId !== '') {
                $key = $this->erpBaseToken($source, $externalId);
                $old = $fallback[$key] ?? [];
                $row['erp_source'] = $source;
                $row['external_product_id'] = $externalId;
                $row['product_name'] = $name !== '' ? $name : (string) ($old['product_name'] ?? '');
                $row['product_reference'] = $reference !== '' ? $reference : (string) ($old['product_reference'] ?? '');
            }

            unset($row['product_token']);
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Build a request-local fallback map from already persisted snapshots.
     *
     * No ERP lookup is performed while saving a robot model. Labels returned by
     * the authenticated AJAX search are carried by the submitted token instead.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,array<string,mixed>>
     */
    private function erpSnapshotMap(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = $this->erpBaseToken(
                (string) ($row['erp_source'] ?? ''),
                (string) ($row['external_product_id'] ?? '')
            );
            if ($key !== '') {
                $map[$key] = $row;
            }
        }
        return $map;
    }

    /** @return array{0:string,1:string,2:string,3:string} */
    private function parseErpToken(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['', '', '', ''];
        }

        $parts = explode('::', $token, 4);
        if (count($parts) === 1) {
            return [
                sanitize_key($this->lookup->activeErpSource()),
                sanitize_text_field($parts[0]),
                '',
                '',
            ];
        }

        return [
            sanitize_key((string) ($parts[0] ?? '')),
            sanitize_text_field((string) ($parts[1] ?? '')),
            sanitize_text_field(rawurldecode((string) ($parts[2] ?? ''))),
            sanitize_text_field(rawurldecode((string) ($parts[3] ?? ''))),
        ];
    }

    private function erpToken(string $source, string $externalId, string $name = '', string $reference = ''): string
    {
        $base = $this->erpBaseToken($source, $externalId);
        if ($base === '') {
            return '';
        }

        return $base . '::' . rawurlencode(sanitize_text_field($name)) . '::' . rawurlencode(sanitize_text_field($reference));
    }

    private function erpBaseToken(string $source, string $externalId): string
    {
        $source = sanitize_key($source);
        $externalId = sanitize_text_field($externalId);
        return $source !== '' && $externalId !== '' ? $source . '::' . $externalId : '';
    }

    /** @param array<string,mixed> $row */
    private function erpRelationLabel(array $row): string
    {
        $name = trim((string) ($row['product_name'] ?? ''));
        $reference = trim((string) ($row['product_reference'] ?? ''));
        if ($name !== '' && $reference !== '') {
            return $reference . ' — ' . $name;
        }
        if ($name !== '') {
            return $name;
        }
        if ($reference !== '') {
            return $reference;
        }
        return '[' . (string) ($row['erp_source'] ?? 'ERP') . '] ' . (string) ($row['external_product_id'] ?? '');
    }

    private function validCanonicalBrand(int $termId): bool
    {
        if ($termId <= 0) {
            return false;
        }
        $term = get_term($termId, 'product_brand');
        return $term instanceof WP_Term && !is_wp_error($term);
    }

    /** @return WP_Term[] */
    private function defaultLanguageBrands(): array
    {
        $terms = get_terms(['taxonomy' => 'product_brand', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        if (is_wp_error($terms) || !is_array($terms)) {
            return [];
        }
        $default = $this->canonicalizer->defaultLanguage();
        return array_values(array_filter($terms, static function ($term) use ($default): bool {
            if (!$term instanceof WP_Term || !function_exists('pll_get_term_language')) {
                return $term instanceof WP_Term;
            }
            $language = pll_get_term_language($term->term_id, 'slug');
            return !is_string($language) || $language === '' || $language === $default;
        }));
    }

    private function canonicalBrandForPost(int $postId): int
    {
        $terms = get_the_terms($postId, 'product_brand');
        if (!is_array($terms) || !isset($terms[0]) || !$terms[0] instanceof WP_Term) {
            return 0;
        }
        return $this->canonicalizer->term($terms[0]->term_id);
    }

    /** @return array{0:int,1:int} */
    private function canonicalFamilySeriesForPost(int $postId): array
    {
        $terms = get_the_terms($postId, ContentTypes::FAMILY_TAXONOMY);
        if (!is_array($terms) || $terms === []) {
            return [0, 0];
        }

        $deepest = null;
        $deepestDepth = -1;
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }
            $canonicalId = $this->canonicalizer->term((int) $term->term_id);
            $canonical = get_term($canonicalId, ContentTypes::FAMILY_TAXONOMY);
            if (!$canonical instanceof WP_Term) {
                continue;
            }
            $depth = count(get_ancestors($canonical->term_id, ContentTypes::FAMILY_TAXONOMY, 'taxonomy'));
            if ($depth > $deepestDepth) {
                $deepest = $canonical;
                $deepestDepth = $depth;
            }
        }

        if (!$deepest instanceof WP_Term) {
            return [0, 0];
        }
        if ((int) $deepest->parent === 0) {
            return [(int) $deepest->term_id, 0];
        }

        $ancestors = array_reverse(get_ancestors($deepest->term_id, ContentTypes::FAMILY_TAXONOMY, 'taxonomy'));
        $familyId = isset($ancestors[0]) ? (int) $ancestors[0] : (int) $deepest->parent;
        return [$familyId, (int) $deepest->term_id];
    }

    /** @return WP_Term[] */
    private function defaultLanguageFamilyTerms(int $parent): array
    {
        $terms = get_terms([
            'taxonomy' => ContentTypes::FAMILY_TAXONOMY,
            'hide_empty' => false,
            'parent' => $parent,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        if (is_wp_error($terms) || !is_array($terms)) {
            return [];
        }
        $default = $this->canonicalizer->defaultLanguage();
        return array_values(array_filter($terms, static function ($term) use ($default): bool {
            if (!$term instanceof WP_Term || !function_exists('pll_get_term_language')) {
                return $term instanceof WP_Term;
            }
            $language = pll_get_term_language($term->term_id, 'slug');
            return !is_string($language) || $language === '' || $language === $default;
        }));
    }

    /** @return WP_Term[] */
    private function defaultLanguageSeriesTerms(): array
    {
        $terms = get_terms([
            'taxonomy' => ContentTypes::FAMILY_TAXONOMY,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        if (is_wp_error($terms) || !is_array($terms)) {
            return [];
        }
        $default = $this->canonicalizer->defaultLanguage();
        return array_values(array_filter($terms, static function ($term) use ($default): bool {
            if (!$term instanceof WP_Term || (int) $term->parent <= 0) {
                return false;
            }
            if (!function_exists('pll_get_term_language')) {
                return true;
            }
            $language = pll_get_term_language($term->term_id, 'slug');
            return !is_string($language) || $language === '' || $language === $default;
        }));
    }

    private function validTopLevelFamily(int $termId): bool
    {
        $term = $termId > 0 ? get_term($termId, ContentTypes::FAMILY_TAXONOMY) : null;
        return $term instanceof WP_Term && (int) $term->parent === 0;
    }

    private function validSeriesForFamily(int $termId, int $familyId): bool
    {
        if ($termId <= 0 || $familyId <= 0) {
            return false;
        }
        $term = get_term($termId, ContentTypes::FAMILY_TAXONOMY);
        if (!$term instanceof WP_Term) {
            return false;
        }
        $ancestors = get_ancestors($term->term_id, ContentTypes::FAMILY_TAXONOMY, 'taxonomy');
        return in_array($familyId, array_map('intval', $ancestors), true);
    }

    /** @return WP_Post[] */
    private function applicationPagesForEditor(int $postId): array
    {
        $parent = CatalogOptions::applicationsParentId();
        if ($parent <= 0) {
            return [];
        }
        $language = $this->canonicalizer->postLanguage($postId);
        $translatedParent = $this->canonicalizer->translatedPost($parent, $language);
        return get_pages([
            'child_of' => $translatedParent,
            'sort_column' => 'menu_order,post_title',
            'sort_order' => 'ASC',
            'post_status' => ['publish', 'private', 'draft'],
        ]);
    }

    private function numberField(string $key, string $label, ?float $value, string $unit): void
    {
        echo '<p><label><strong>' . esc_html($label) . '</strong></label><br><input type="text" inputmode="decimal" name="wprc_robot_model_technical[' . esc_attr($key) . ']" value="' . esc_attr($this->formatNumber($value)) . '"> <span>' . esc_html($unit) . '</span></p>';
    }

    private function textField(string $key, string $label, ?string $value): void
    {
        echo '<p><label><strong>' . esc_html($label) . '</strong></label><br><input type="text" name="wprc_robot_model_technical[' . esc_attr($key) . ']" value="' . esc_attr((string) $value) . '"></p>';
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = str_replace(',', '.', trim((string) $value));
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInt(mixed $value, int $min, int $max): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (int) $value;
        return $value >= $min && $value <= $max ? $value : null;
    }

    private function formatNumber(?float $value): string
    {
        if ($value === null) {
            return '';
        }
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
