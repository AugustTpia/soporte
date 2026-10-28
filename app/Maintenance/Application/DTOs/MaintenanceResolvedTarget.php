<?php

namespace App\Maintenance\Application\DTOs;

use App\Maintenance\Domain\Enums\MaintenanceTargetType;

final readonly class MaintenanceResolvedTarget
{
    public function __construct(
        public MaintenanceTargetType $type,
        public ?int $assetId,
        public ?int $locationId,
        public string $label,
    ) {}
}
