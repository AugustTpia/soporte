<?php

namespace App\Reports\Application\DTOs;

use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use DateTimeImmutable;

final readonly class MaintenanceReportRow
{
    public function __construct(
        public int $id,
        public string $targetLabel,
        public ?string $planTitle,
        public MaintenanceExecutionType $type,
        public MaintenanceExecutionStatus $status,
        public DateTimeImmutable $scheduledAt,
        public ?string $technicianName,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $completedAt,
        public ?int $timeSpentMinutes,
        public ?string $supportTicketFolio,
    ) {}
}
