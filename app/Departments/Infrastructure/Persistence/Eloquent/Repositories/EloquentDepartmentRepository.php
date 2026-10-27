<?php

namespace App\Departments\Infrastructure\Persistence\Eloquent\Repositories;

use App\Departments\Domain\Entities\Department;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Departments\Infrastructure\Persistence\Eloquent\Mappers\DepartmentMapper;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentDepartmentRepository implements DepartmentRepository
{
    public function __construct(private readonly DepartmentMapper $mapper) {}

    /**
     * @return Page<Department>
     */
    public function search(int $companyId, ?string $term, int $page = 1, int $perPage = 15, ?bool $active = null): Page
    {
        $query = DepartmentModel::query()
            ->where('company_id', $companyId)
            ->withCount('users')
            ->when(filled($term), function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($active !== null, fn ($query) => $query->where('is_active', $active))
            ->orderBy('name');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (DepartmentModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?Department
    {
        $model = DepartmentModel::query()
            ->where('company_id', $companyId)
            ->withCount('users')
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(Department $department): Department
    {
        $model = $department->id === null
            ? new DepartmentModel
            : DepartmentModel::query()->findOrFail($department->id);

        $this->mapper->fillModel($department, $model)->save();

        return $this->mapper->toDomain($model->refresh()->loadCount('users'));
    }

    public function slugExists(int $companyId, string $slug, ?int $ignoreId = null): bool
    {
        return DepartmentModel::query()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function allBelongToCompany(array $departmentIds, int $companyId): bool
    {
        $departmentIds = array_values(array_unique(array_map('intval', $departmentIds)));

        if ($departmentIds === []) {
            return true;
        }

        return DepartmentModel::query()
            ->where('company_id', $companyId)
            ->whereKey($departmentIds)
            ->count() === count($departmentIds);
    }

    public function count(int $companyId, ?bool $active = null): int
    {
        return DepartmentModel::query()
            ->where('company_id', $companyId)
            ->when($active !== null, fn ($query) => $query->where('is_active', $active))
            ->count();
    }

    public function activeForCompany(int $companyId): array
    {
        return DepartmentModel::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (DepartmentModel $model) => $this->mapper->toDomain($model))
            ->all();
    }
}
