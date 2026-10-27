<?php

namespace App\Departments\Domain\Repositories;

use App\Departments\Domain\Entities\Department;
use App\Shared\Domain\Pagination\Page;

interface DepartmentRepository
{
    /**
     * @return Page<Department>
     */
    public function search(int $companyId, ?string $term, int $page = 1, int $perPage = 15, ?bool $active = null): Page;

    public function find(int $id, int $companyId): ?Department;

    public function save(Department $department): Department;

    public function slugExists(int $companyId, string $slug, ?int $ignoreId = null): bool;

    /**
     * @param  array<int, int>  $departmentIds
     */
    public function allBelongToCompany(array $departmentIds, int $companyId): bool;

    public function count(int $companyId, ?bool $active = null): int;

    /**
     * @return array<int, Department>
     */
    public function activeForCompany(int $companyId): array;
}
