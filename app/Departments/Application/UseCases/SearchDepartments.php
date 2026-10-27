<?php

namespace App\Departments\Application\UseCases;

use App\Departments\Domain\Entities\Department;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchDepartments
{
    public function __construct(private DepartmentRepository $departments) {}

    /**
     * @return Page<Department>
     */
    public function execute(int $companyId, ?string $term, int $page = 1, int $perPage = 15, ?bool $active = null): Page
    {
        return $this->departments->search($companyId, $term, $page, $perPage, $active);
    }
}
