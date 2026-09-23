<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Placeholder;

use WPRC\Catalog\RobotModel\Application\RobotModels;
use WPRC\Catalog\RobotModel\Domain\RobotModel;
use WPRC\Catalog\RobotModel\WordPress\ContentTypes;
use WPRC\Core\Contracts\PlaceholderProviderInterface;
use WPRC\Core\Data\Content\PlaceholderContext;

\defined('ABSPATH') || exit;

/**
 * Exposes atomic robot-model values to RC Core's generic placeholder engine.
 *
 * Complex collections (axes, applications, software and available products)
 * deliberately remain presentation concerns and are not emitted as HTML here.
 */
final class RobotModelPlaceholderProvider implements PlaceholderProviderInterface
{
    private const PLACEHOLDERS = [
        'robot_model_name',
        'robot_model_reference',
        'robot_model_brand',
        'robot_model_family',
        'robot_model_series',
        'robot_model_payload',
        'robot_model_reach',
        'robot_model_repeatability',
        'robot_model_mass',
        'robot_model_axes_count',
        'robot_model_structure',
        'robot_model_ip_base',
        'robot_model_ip_wrist',
        'robot_model_url',
        'robot_model_image_url',
        'robot_model_available_count',
    ];

    /** @var array<int,RobotModel|null> */
    private array $resolvedModels = [];

    /** @var array<string,mixed> */
    private array $resolvedValues = [];

    public function __construct(private readonly RobotModels $models)
    {
    }

    /** @return list<string> */
    public function placeholders(): array
    {
        return self::PLACEHOLDERS;
    }

    public function resolve(string $placeholder, PlaceholderContext $context): string|int|float|bool|null
    {
        $model = $this->resolveModel($context);
        if (!$model instanceof RobotModel || !$model->postId) {
            return '';
        }

        $cacheKey = $model->id . ':' . $placeholder . ':' . (string) $model->language;
        if (array_key_exists($cacheKey, $this->resolvedValues)) {
            return $this->resolvedValues[$cacheKey];
        }

        $value = match ($placeholder) {
            'robot_model_name' => $model->title ?: $model->reference,
            'robot_model_reference' => $model->reference,
            'robot_model_brand' => ($brand = $this->models->brand($model->id, $model->language)) instanceof \WP_Term ? $brand->name : '',
            'robot_model_family' => (($classification = $this->models->classification($model->id, $model->language))['family'] ?? null) instanceof \WP_Term ? $classification['family']->name : '',
            'robot_model_series' => (($classification = $this->models->classification($model->id, $model->language))['series'] ?? null) instanceof \WP_Term ? $classification['series']->name : '',
            'robot_model_payload' => $model->payloadKg,
            'robot_model_reach' => $model->reachMm,
            'robot_model_repeatability' => $model->repeatabilityMm,
            'robot_model_mass' => $model->massKg,
            'robot_model_axes_count' => $model->axesCount,
            'robot_model_structure' => $model->structure,
            'robot_model_ip_base' => $model->ipBase,
            'robot_model_ip_wrist' => $model->ipWrist,
            'robot_model_url' => (string) get_permalink($model->postId),
            'robot_model_image_url' => (string) (get_the_post_thumbnail_url($model->postId, 'full') ?: ''),
            'robot_model_available_count' => count($this->models->availableProductIds($model->id, $model->language)),
            default => '',
        };

        $this->resolvedValues[$cacheKey] = $value;
        return $value;
    }

    private function resolveModel(PlaceholderContext $context): ?RobotModel
    {
        $postId = $this->modelPostIdFromContext($context);
        if ($postId <= 0) {
            return null;
        }

        if (!array_key_exists($postId, $this->resolvedModels)) {
            $this->resolvedModels[$postId] = $this->models->findByPostId($postId);
        }

        return $this->resolvedModels[$postId];
    }

    private function modelPostIdFromContext(PlaceholderContext $context): int
    {
        // Content Blocks may temporarily replace the global post. The queried
        // object remains the authoritative robot model on single-model pages.
        $queriedId = $context->queriedObjectId ?? (int) get_queried_object_id();
        if ($queriedId > 0 && get_post_type($queriedId) === ContentTypes::POST_TYPE) {
            return $queriedId;
        }

        if ($context->postId !== null && $context->postId > 0 && get_post_type($context->postId) === ContentTypes::POST_TYPE) {
            return $context->postId;
        }

        return 0;
    }
}
