<?php

namespace App\Maintenance\Application\DTOs;

use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use DateTimeImmutable;

final readonly class MaintenanceExecutionFilters
{
    public function __construct(
        public ?string $term = null,
        public ?MaintenanceExecutionStatus $status = null,
        public ?MaintenanceExecutionType $type = null,
        public ?int $technicianId = null,
        public ?int $planId = null,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
    ) {}
}
