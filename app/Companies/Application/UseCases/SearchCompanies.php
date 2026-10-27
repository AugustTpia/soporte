<?php

namespace App\Companies\Application\UseCases;

use App\Companies\Domain\Entities\Company;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchCompanies
{
    public function __construct(private CompanyRepository $companies) {}

    /**
     * @return Page<Company>
     */
    public function execute(?string $term, int $page = 1, int $perPage = 15): Page
    {
        return $this->companies->search($term, $page, $perPage);
    }
}
