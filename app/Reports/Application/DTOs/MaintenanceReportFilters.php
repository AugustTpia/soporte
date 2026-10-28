<?php

namespace App\Reports\Application\DTOs;

use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;

final readonly class MaintenanceReportFilters
{
    public function __construct(
        public ReportPeriod $period,
        public ?string $term = null,
        public ?MaintenanceExecutionStatus $status = null,
        public ?MaintenanceExecutionType $type = null,
        public ?int $technicianId = null,
        public ?int $planId = null,
    ) {}
}
