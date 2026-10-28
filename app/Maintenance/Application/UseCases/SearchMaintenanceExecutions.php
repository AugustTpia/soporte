<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Application\DTOs\MaintenanceExecutionFilters;
use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchMaintenanceExecutions
{
    public function __construct(private MaintenanceExecutionRepository $executions) {}

    /**
     * @return Page<MaintenanceExecution>
     */
    public function execute(int $companyId, MaintenanceExecutionFilters $filters, int $page = 1, int $perPage = 15): Page
    {
        return $this->executions->search($companyId, $filters, $page, $perPage);
    }
}
