<?php

namespace App\Locations\Infrastructure\Persistence\Eloquent\Repositories;

use App\Locations\Domain\Entities\Location;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Locations\Infrastructure\Persistence\Eloquent\Mappers\LocationMapper;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentLocationRepository implements LocationRepository
{
    public function __construct(private readonly LocationMapper $mapper) {}

    /**
     * @return Page<Location>
     */
    public function search(int $companyId, ?string $term, int $page = 1, int $perPage = 15, ?bool $active = null): Page
    {
        $query = LocationModel::query()
            ->where('company_id', $companyId)
            ->when(filled($term), function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('street', 'like', "%{$term}%")
                        ->orWhere('exterior_number', 'like', "%{$term}%")
                        ->orWhere('neighborhood', 'like', "%{$term}%")
                        ->orWhere('city', 'like', "%{$term}%");
                });
            })
            ->when($active !== null, fn ($query) => $query->where('is_active', $active))
            ->orderBy('name');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (LocationModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?Location
    {
        $model = LocationModel::query()
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function forCompany(int $companyId): array
    {
        return LocationModel::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get()
            ->map(fn (LocationModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(Location $location): Location
    {
        $model = $location->id === null
            ? new LocationModel
            : LocationModel::query()->findOrFail($location->id);

        $this->mapper->fillModel($location, $model)->save();

        return $this->mapper->toDomain($model->refresh());
    }

    public function slugExists(int $companyId, string $slug, ?int $ignoreId = null): bool
    {
        return LocationModel::query()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
