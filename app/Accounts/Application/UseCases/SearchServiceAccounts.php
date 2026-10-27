<?php

namespace App\Accounts\Application\UseCases;

use App\Accounts\Domain\Entities\ServiceAccount;
use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchServiceAccounts
{
    public function __construct(private ServiceAccountRepository $accounts) {}

    /**
     * @return Page<ServiceAccount>
     */
    public function execute(
        int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?ServiceType $service = null,
        ?ServiceAccountStatus $status = null,
        ?int $assetId = null,
    ): Page {
        return $this->accounts->search($companyId, $term, $page, $perPage, $service, $status, $assetId);
    }
}
