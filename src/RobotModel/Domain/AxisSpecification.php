<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Domain;

use InvalidArgumentException;

defined('ABSPATH') || exit;

final class AxisSpecification
{
    public function __construct(
        public readonly int $axisNumber,
        public readonly string $motionType = 'rotary',
        public readonly ?float $rangeMin = null,
        public readonly ?float $rangeMax = null,
        public readonly string $rangeUnit = 'deg',
        public readonly ?float $maxVelocity = null,
        public readonly string $velocityUnit = 'deg_s'
    ) {
        if ($axisNumber < 1 || $axisNumber > 7) {
            throw new InvalidArgumentException('Axis number must be between 1 and 7.');
        }
        if (!in_array($motionType, ['rotary', 'linear'], true)) {
            throw new InvalidArgumentException('Unsupported axis motion type.');
        }
        if ($rangeMin !== null && $rangeMax !== null && $rangeMin > $rangeMax) {
            throw new InvalidArgumentException('Axis minimum range cannot exceed maximum range.');
        }
        if ($maxVelocity !== null && $maxVelocity < 0) {
            throw new InvalidArgumentException('Axis velocity cannot be negative.');
        }
    }
}
