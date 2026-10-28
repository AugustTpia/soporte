<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Application\DTOs\MaintenancePlanFilters;
use App\Maintenance\Domain\Entities\MaintenancePlan;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchMaintenancePlans
{
    public function __construct(private MaintenancePlanRepository $plans) {}

    /**
     * @return Page<MaintenancePlan>
     */
    public function execute(int $companyId, MaintenancePlanFilters $filters, int $page = 1, int $perPage = 15): Page
    {
        return $this->plans->search($companyId, $filters, $page, $perPage);
    }
}
