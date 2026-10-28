<?php

namespace App\Accounts\Infrastructure\Persistence\Eloquent\Repositories;

use App\Accounts\Domain\Entities\AccountAssetLink;
use App\Accounts\Domain\Entities\ServiceAccount;
use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Accounts\Infrastructure\Persistence\Eloquent\Mappers\AccountAssetLinkMapper;
use App\Accounts\Infrastructure\Persistence\Eloquent\Mappers\ServiceAccountMapper;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountAssetLinkModel;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentServiceAccountRepository implements ServiceAccountRepository
{
    public function __construct(
        private readonly ServiceAccountMapper $mapper,
        private readonly AccountAssetLinkMapper $linkMapper,
    ) {}

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
    ): Page {
        $query = ServiceAccountModel::query()
            ->with(['responsibleUser', 'links.asset'])
            ->where('company_id', $companyId)
            ->when(filled($term), function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('username', 'like', "%{$term}%")
                        ->orWhere('instance', 'like', "%{$term}%")
                        ->orWhere('owner_name', 'like', "%{$term}%")
                        ->orWhereHas('responsibleUser', fn ($query) => $query->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('links.asset', function ($query) use ($term): void {
                            $query->where('internal_code', 'like', "%{$term}%")
                                ->orWhere('hostname', 'like', "%{$term}%");
                        });
                });
            })
            ->when($service !== null, fn ($query) => $query->where('service', $service->value))
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($assetId !== null, function ($query) use ($assetId): void {
                $query->whereHas('links', function ($query) use ($assetId): void {
                    $query->where('asset_id', $assetId)->whereNull('unlinked_at');
                });
            })
            ->orderBy('username');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (ServiceAccountModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?ServiceAccount
    {
        $model = ServiceAccountModel::query()
            ->with(['responsibleUser', 'links.asset'])
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function forCompany(int $companyId): array
    {
        return ServiceAccountModel::query()
            ->with(['responsibleUser', 'links.asset'])
            ->where('company_id', $companyId)
            ->orderBy('username')
            ->get()
            ->map(fn (ServiceAccountModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(ServiceAccount $account): ServiceAccount
    {
        $model = $account->id === null
            ? new ServiceAccountModel
            : ServiceAccountModel::query()->findOrFail($account->id);

        $this->mapper->fillModel($account, $model)->save();

        return $this->mapper->toDomain(
            $model->refresh()->load(['responsibleUser', 'links.asset']),
        );
    }

    public function identityExists(
        int $companyId,
        ServiceType $service,
        string $instance,
        string $username,
        ?int $ignoreId = null,
    ): bool {
        return ServiceAccountModel::query()
            ->withTrashed()
            ->where('company_id', $companyId)
            ->where('service', $service->value)
            ->where('instance', $instance)
            ->where('username', $username)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function hasActiveLink(int $serviceAccountId, int $assetId): bool
    {
        return ServiceAccountAssetLinkModel::query()
            ->where('service_account_id', $serviceAccountId)
            ->where('asset_id', $assetId)
            ->whereNull('unlinked_at')
            ->exists();
    }

    public function findLink(int $linkId, int $serviceAccountId): ?AccountAssetLink
    {
        $model = ServiceAccountAssetLinkModel::query()
            ->with('asset')
            ->where('service_account_id', $serviceAccountId)
            ->find($linkId);

        return $model ? $this->linkMapper->toDomain($model) : null;
    }

    public function findActiveLink(int $serviceAccountId, int $assetId): ?AccountAssetLink
    {
        $model = ServiceAccountAssetLinkModel::query()
            ->with('asset')
            ->where('service_account_id', $serviceAccountId)
            ->where('asset_id', $assetId)
            ->whereNull('unlinked_at')
            ->first();

        return $model ? $this->linkMapper->toDomain($model) : null;
    }

    public function recordLink(
        int $serviceAccountId,
        int $assetId,
        ?int $actorId,
        ?string $reason = null,
    ): void {
        ServiceAccountAssetLinkModel::query()->create([
            'service_account_id' => $serviceAccountId,
            'asset_id' => $assetId,
            'linked_at' => now(),
            'reason' => $reason,
            'linked_by' => $actorId,
        ]);
    }

    public function closeLink(int $linkId, ?int $actorId, ?string $reason = null): void
    {
        ServiceAccountAssetLinkModel::query()
            ->whereKey($linkId)
            ->whereNull('unlinked_at')
            ->update([
                'unlinked_at' => now(),
                'unlinked_by' => $actorId,
                'unlink_reason' => $reason,
                'updated_at' => now(),
            ]);
    }
}
