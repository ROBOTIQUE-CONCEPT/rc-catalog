<?php

declare(strict_types=1);

namespace WPRC\Catalog\RobotModel\Domain;

defined('ABSPATH') || exit;

final class Controller
{
    public function __construct(
        public readonly int $id,
        public readonly string $uid,
        public readonly int $brandTermId,
        public readonly string $name,
        public readonly ?string $reference,
        public readonly string $status,
        public readonly int $sortOrder = 0
    ) {
    }
}
