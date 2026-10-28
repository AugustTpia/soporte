<?php

namespace App\Companies\Application\Contracts;

use App\Companies\Application\DTOs\CompanyOverview;

interface CompanyOverviewQuery
{
    /**
     * @param  array<int, string>  $modules
     */
    public function execute(int $companyId, array $modules): CompanyOverview;
}
