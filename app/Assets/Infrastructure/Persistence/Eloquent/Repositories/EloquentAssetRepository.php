<?php

namespace App\Assets\Infrastructure\Persistence\Eloquent\Repositories;

use App\Assets\Domain\Entities\Asset;
use App\Assets\Domain\Entities\AssetAssignment;
use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Assets\Infrastructure\Persistence\Eloquent\Mappers\AssetAssignmentMapper;
use App\Assets\Infrastructure\Persistence\Eloquent\Mappers\AssetMapper;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetAssignmentModel;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentAssetRepository implements AssetRepository
{
    public function __construct(
        private readonly AssetMapper $mapper,
        private readonly AssetAssignmentMapper $assignmentMapper,
    ) {}

    /**
     * @return Page<Asset>
     */
    public function search(
        int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?AssetCategory $category = null,
        ?AssetStatus $status = null,
        ?int $locationId = null,
    ): Page {
        $query = AssetModel::query()
            ->with(['location', 'assignedUser'])
            ->where('company_id', $companyId)
            ->when(filled($term), function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('internal_code', 'like', "%{$term}%")
                        ->orWhere('hostname', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%")
                        ->orWhereHas('location', fn ($query) => $query->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('assignedUser', fn ($query) => $query->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($category !== null, fn ($query) => $query->where('category', $category->value))
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($locationId !== null, fn ($query) => $query->where('location_id', $locationId))
            ->orderBy('internal_code');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (AssetModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?Asset
    {
        $model = AssetModel::query()
            ->with(['location', 'assignedUser'])
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function forCompany(int $companyId): array
    {
        return AssetModel::query()
            ->with(['location', 'assignedUser'])
            ->where('company_id', $companyId)
            ->orderBy('internal_code')
            ->get()
            ->map(fn (AssetModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(Asset $asset): Asset
    {
        $model = $asset->id === null
            ? new AssetModel
            : AssetModel::query()->findOrFail($asset->id);

        $this->mapper->fillModel($asset, $model)->save();

        return $this->mapper->toDomain($model->refresh()->load(['location', 'assignedUser']));
    }

    public function internalCodeExists(int $companyId, string $internalCode, ?int $ignoreId = null): bool
    {
        return AssetModel::query()
            ->withTrashed()
            ->where('company_id', $companyId)
            ->where('internal_code', $internalCode)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function hostnameExists(int $companyId, string $hostname, ?int $ignoreId = null): bool
    {
        return AssetModel::query()
            ->withTrashed()
            ->where('company_id', $companyId)
            ->where('hostname', mb_strtolower($hostname))
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function closeCurrentAssignment(int $assetId): void
    {
        AssetAssignmentModel::query()
            ->where('asset_id', $assetId)
            ->whereNull('released_at')
            ->update([
                'released_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function recordAssignment(
        int $assetId,
        int $locationId,
        ?int $assignedUserId,
        ?int $createdBy,
        ?string $notes = null,
    ): void {
        AssetAssignmentModel::query()->create([
            'asset_id' => $assetId,
            'location_id' => $locationId,
            'assigned_user_id' => $assignedUserId,
            'assigned_at' => now(),
            'released_at' => null,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @return array<int, AssetAssignment>
     */
    public function assignmentHistory(int $assetId): array
    {
        return AssetAssignmentModel::query()
            ->with(['location', 'assignedUser'])
            ->where('asset_id', $assetId)
            ->orderByDesc('assigned_at')
            ->get()
            ->map(fn (AssetAssignmentModel $model) => $this->assignmentMapper->toDomain($model))
            ->all();
    }
}
