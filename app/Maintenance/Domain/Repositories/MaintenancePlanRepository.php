<?php

namespace App\Maintenance\Domain\Repositories;

use App\Maintenance\Application\DTOs\MaintenancePlanFilters;
use App\Maintenance\Domain\Entities\MaintenancePlan;
use App\Shared\Domain\Pagination\Page;
use DateTimeImmutable;

interface MaintenancePlanRepository
{
    /**
     * @return Page<MaintenancePlan>
     */
    public function search(int $companyId, MaintenancePlanFilters $filters, int $page = 1, int $perPage = 15): Page;

    public function find(int $id, int $companyId): ?MaintenancePlan;

    /**
     * @return array<int, MaintenancePlan>
     */
    public function forCompany(int $companyId): array;

    public function save(MaintenancePlan $plan): MaintenancePlan;

    /**
     * @return array{active: int, paused: int, overdue: int, upcoming: int}
     */
    public function counts(int $companyId, ?DateTimeImmutable $reference = null): array;
}
