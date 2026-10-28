<?php

namespace App\Maintenance\Application\DTOs;

use App\Maintenance\Domain\Enums\MaintenanceIntervalUnit;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use DateTimeImmutable;

final readonly class MaintenancePlanInput
{
    /**
     * @param  array<int, MaintenanceChecklistItemInput>  $checklistItems
     */
    public function __construct(
        public MaintenanceTargetType $targetType,
        public ?int $assetId,
        public ?int $locationId,
        public string $title,
        public ?string $description,
        public int $intervalValue,
        public MaintenanceIntervalUnit $intervalUnit,
        public DateTimeImmutable $nextDueAt,
        public int $assignedUserId,
        public array $checklistItems,
    ) {}
}
