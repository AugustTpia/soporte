<?php

namespace App\Reports\Application\Contracts;

use App\Reports\Application\DTOs\CancellationReportFilters;
use App\Reports\Application\DTOs\CancellationReportRow;
use App\Shared\Domain\Pagination\Page;
use Generator;

interface CancellationReportQuery
{
    /**
     * @return array{total: int, cancelled: int, not_cancelled: int, pending: int, amount_cents: int}
     */
    public function summary(int $companyId, CancellationReportFilters $filters): array;

    /**
     * @return Page<CancellationReportRow>
     */
    public function paginate(int $companyId, CancellationReportFilters $filters, int $page = 1, int $perPage = 20): Page;

    /**
     * @return Generator<int, CancellationReportRow>
     */
    public function exportRows(int $companyId, CancellationReportFilters $filters): Generator;
}
