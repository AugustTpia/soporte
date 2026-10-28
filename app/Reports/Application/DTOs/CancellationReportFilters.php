<?php

namespace App\Reports\Application\DTOs;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;

final readonly class CancellationReportFilters
{
    public function __construct(
        public ReportPeriod $period,
        public ?string $term = null,
        public ?BusTicketCancellationResult $result = null,
        public ?int $operatorId = null,
    ) {}
}
