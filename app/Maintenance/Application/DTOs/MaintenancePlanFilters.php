<?php

namespace App\Maintenance\Application\DTOs;

use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;

final readonly class MaintenancePlanFilters
{
    public function __construct(
        public ?string $term = null,
        public ?MaintenanceTargetType $targetType = null,
        public ?MaintenancePlanStatus $status = null,
        public ?int $assignedTo = null,
        public bool $overdue = false,
    ) {}
}
