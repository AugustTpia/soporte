<?php

namespace App\Maintenance\Application\DTOs;

use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use DateTimeImmutable;

final readonly class MaintenanceExecutionInput
{
    public function __construct(
        public ?int $planId,
        public MaintenanceTargetType $targetType,
        public ?int $assetId,
        public ?int $locationId,
        public ?int $supportTicketId,
        public MaintenanceExecutionType $type,
        public DateTimeImmutable $scheduledAt,
        public int $technicianId,
    ) {}
}
