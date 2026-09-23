<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Domain;

defined('ABSPATH') || exit;

final class RobotModel
{
    /** @param AxisSpecification[] $axes */
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
        public readonly string $reference,
        public readonly ?float $payloadKg,
        public readonly ?float $reachMm,
        public readonly ?float $massKg,
        public readonly ?float $repeatabilityMm,
        public readonly ?string $structure,
        public readonly ?int $axesCount,
        public readonly ?string $ipBase,
        public readonly ?string $ipWrist,
        public readonly string $status,
        public readonly ?int $postId,
        public readonly ?string $language,
        public readonly ?string $title,
        public readonly array $axes = []
    ) {
    }
}
