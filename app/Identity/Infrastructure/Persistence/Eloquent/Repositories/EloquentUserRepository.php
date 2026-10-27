<?php

namespace App\Identity\Infrastructure\Persistence\Eloquent\Repositories;

use App\Identity\Domain\Entities\User as UserEntity;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\UserRepository;
use App\Identity\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;
use App\Identity\Infrastructure\Persistence\Eloquent\User as UserModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentUserRepository implements UserRepository
{
    public function __construct(private readonly UserMapper $mapper) {}

    /**
     * @return Page<UserEntity>
     */
    public function search(
        ?int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?UserStatus $status = null,
        ?string $role = null,
        ?int $departmentId = null,
    ): Page {
        $query = UserModel::query()
            ->with(['company', 'roles', 'departments'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(filled($term), function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('job_title', 'like', "%{$term}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->when($role, fn ($query) => $query->whereHas('roles', fn ($query) => $query->where('name', $role)))
            ->when($departmentId, fn ($query) => $query->whereHas('departments', fn ($query) => $query->whereKey($departmentId)))
            ->orderBy('name');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (UserModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id): ?UserEntity
    {
        $model = UserModel::query()
            ->with(['company', 'roles', 'departments'])
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $model = UserModel::query()
            ->with(['company', 'roles', 'departments'])
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function forCompany(int $companyId): array
    {
        return UserModel::query()
            ->with(['company', 'roles', 'departments'])
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get()
            ->map(fn (UserModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(UserEntity $user, ?string $plainPassword = null): UserEntity
    {
        $isNew = $user->id === null;

        $model = $isNew
            ? new UserModel
            : UserModel::query()->findOrFail($user->id);

        if ($isNew) {
            $model->email_verified_at = now();
        }

        $this->mapper->fillModel($user, $model, $plainPassword)->save();

        return $this->find($model->id);
    }

    public function syncRoles(int $userId, array $roles): void
    {
        UserModel::query()->findOrFail($userId)->syncRoles($roles);
    }

    public function syncDepartments(int $userId, array $departmentIds): void
    {
        UserModel::query()->findOrFail($userId)->departments()->sync($departmentIds);
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        return UserModel::query()
            ->withTrashed()
            ->where('email', mb_strtolower(trim($email)))
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function count(?int $companyId, ?UserStatus $status = null): int
    {
        return UserModel::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->count();
    }

    public function countSuperAdmins(): int
    {
        return UserModel::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
            ->count();
    }

    public function recent(?int $companyId, int $limit = 5): array
    {
        return UserModel::query()
            ->with(['company', 'roles', 'departments'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (UserModel $model) => $this->mapper->toDomain($model))
            ->all();
    }
}
