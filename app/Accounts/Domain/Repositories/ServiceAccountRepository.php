<?php

namespace App\Accounts\Domain\Repositories;

use App\Accounts\Domain\Entities\AccountAssetLink;
use App\Accounts\Domain\Entities\ServiceAccount;
use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Shared\Domain\Pagination\Page;

interface ServiceAccountRepository
{
    /**
     * @return Page<ServiceAccount>
     */
    public function search(
        int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?ServiceType $service = null,
        ?ServiceAccountStatus $status = null,
        ?int $assetId = null,
    ): Page;

    public function find(int $id, int $companyId): ?ServiceAccount;

    public function save(ServiceAccount $account): ServiceAccount;

    public function identityExists(
        int $companyId,
        ServiceType $service,
        string $instance,
        string $username,
        ?int $ignoreId = null,
    ): bool;

    public function hasActiveLink(int $serviceAccountId, int $assetId): bool;

    public function findLink(int $linkId, int $serviceAccountId): ?AccountAssetLink;

    public function findActiveLink(int $serviceAccountId, int $assetId): ?AccountAssetLink;

    public function recordLink(
        int $serviceAccountId,
        int $assetId,
        ?int $actorId,
        ?string $reason = null,
    ): void;

    public function closeLink(int $linkId, ?int $actorId, ?string $reason = null): void;
}
