<?php

namespace App\Reports\Application\Contracts;

use App\Reports\Application\DTOs\TicketReportFilters;
use App\Reports\Application\DTOs\TicketReportRow;
use App\Shared\Domain\Pagination\Page;
use Generator;

interface TicketReportQuery
{
    /**
     * @return array{total: int, unresolved: int, resolved: int, cancelled: int, urgent: int, avg_resolution_hours: float|null}
     */
    public function summary(int $companyId, TicketReportFilters $filters): array;

    /**
     * @return Page<TicketReportRow>
     */
    public function paginate(int $companyId, TicketReportFilters $filters, int $page = 1, int $perPage = 20): Page;

    /**
     * @return Generator<int, TicketReportRow>
     */
    public function exportRows(int $companyId, TicketReportFilters $filters): Generator;
}
