<?php

namespace App\Maintenance\Domain\Repositories;

use App\Maintenance\Application\DTOs\MaintenanceExecutionFilters;
use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Shared\Domain\Pagination\Page;

interface MaintenanceExecutionRepository
{
    /**
     * @return Page<MaintenanceExecution>
     */
    public function search(int $companyId, MaintenanceExecutionFilters $filters, int $page = 1, int $perPage = 15): Page;

    public function find(int $id, int $companyId): ?MaintenanceExecution;

    /**
     * @return array<int, MaintenanceExecution>
     */
    public function forPlan(int $planId, int $companyId): array;

    public function save(MaintenanceExecution $execution): MaintenanceExecution;

    /**
     * @return array{scheduled: int, in_progress: int, completed_month: int, cancelled_month: int}
     */
    public function counts(int $companyId): array;
}
