<?php

namespace App\Reports\Application\Contracts;

use App\Reports\Application\DTOs\MaintenanceReportFilters;
use App\Reports\Application\DTOs\MaintenanceReportRow;
use App\Shared\Domain\Pagination\Page;
use Generator;

interface MaintenanceReportQuery
{
    /**
     * @return array{total: int, scheduled: int, in_progress: int, completed: int, cancelled: int, avg_minutes: float|null}
     */
    public function summary(int $companyId, MaintenanceReportFilters $filters): array;

    /**
     * @return Page<MaintenanceReportRow>
     */
    public function paginate(int $companyId, MaintenanceReportFilters $filters, int $page = 1, int $perPage = 20): Page;

    /**
     * @return Generator<int, MaintenanceReportRow>
     */
    public function exportRows(int $companyId, MaintenanceReportFilters $filters): Generator;
}
