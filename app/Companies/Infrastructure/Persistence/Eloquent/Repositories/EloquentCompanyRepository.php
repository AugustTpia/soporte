<?php

namespace App\Companies\Infrastructure\Persistence\Eloquent\Repositories;

use App\Companies\Domain\Entities\Company;
use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Mappers\CompanyMapper;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentCompanyRepository implements CompanyRepository
{
    public function __construct(private readonly CompanyMapper $mapper) {}

    /**
     * @return Page<Company>
     */
    public function search(?string $term, int $page = 1, int $perPage = 15): Page
    {
        $query = CompanyModel::query()
            ->withCount(['users', 'departments'])
            ->when(filled($term), function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('legal_name', 'like', "%{$term}%")
                        ->orWhere('tax_id', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (CompanyModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id): ?Company
    {
        $model = CompanyModel::query()->withCount(['users', 'departments'])->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByPublicId(string $publicId): ?Company
    {
        $model = CompanyModel::query()->withCount(['users', 'departments'])->where('public_id', $publicId)->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(Company $company): Company
    {
        $model = $company->id === null
            ? new CompanyModel
            : CompanyModel::query()->findOrFail($company->id);

        $this->mapper->fillModel($company, $model)->save();

        return $this->mapper->toDomain($model->refresh()->loadCount(['users', 'departments']));
    }

    public function count(?CompanyStatus $status = null): int
    {
        return CompanyModel::query()
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->count();
    }

    public function activeCompanies(): array
    {
        return CompanyModel::query()
            ->where('status', CompanyStatus::Active->value)
            ->orderBy('name')
            ->get()
            ->map(fn (CompanyModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function recent(int $limit = 5): array
    {
        return CompanyModel::query()
            ->withCount(['users', 'departments'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (CompanyModel $model) => $this->mapper->toDomain($model))
            ->all();
    }
}
