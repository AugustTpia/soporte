<?php

namespace App\Reports\Application\DTOs;

use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;

final readonly class TicketReportFilters
{
    public function __construct(
        public ReportPeriod $period,
        public ?string $term = null,
        public ?TicketStatus $status = null,
        public ?TicketPriority $priority = null,
        public ?TicketCategory $category = null,
        public ?int $technicianId = null,
    ) {}
}
