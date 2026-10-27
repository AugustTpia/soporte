<?php

namespace App\Companies\Domain\Repositories;

use App\Companies\Domain\Entities\Company;
use App\Companies\Domain\Enums\CompanyStatus;
use App\Shared\Domain\Pagination\Page;

interface CompanyRepository
{
    /**
     * @return Page<Company>
     */
    public function search(?string $term, int $page = 1, int $perPage = 15): Page;

    public function find(int $id): ?Company;

    public function findByPublicId(string $publicId): ?Company;

    public function save(Company $company): Company;

    public function count(?CompanyStatus $status = null): int;

    /**
     * @return array<int, Company>
     */
    public function activeCompanies(): array;

    /**
     * @return array<int, Company>
     */
    public function recent(int $limit = 5): array;
}
