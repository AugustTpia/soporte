<?php

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\UserStatus;
use App\Shared\Domain\Pagination\Page;

interface UserRepository
{
    /**
     * @return Page<User>
     */
    public function search(
        ?int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?UserStatus $status = null,
        ?string $role = null,
        ?int $departmentId = null,
    ): Page;

    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user, ?string $plainPassword = null): User;

    /**
     * @param  array<int, string>  $roles
     */
    public function syncRoles(int $userId, array $roles): void;

    /**
     * @param  array<int, int>  $departmentIds
     */
    public function syncDepartments(int $userId, array $departmentIds): void;

    public function emailExists(string $email, ?int $ignoreId = null): bool;

    public function count(?int $companyId, ?UserStatus $status = null): int;

    public function countSuperAdmins(): int;

    /**
     * @return array<int, User>
     */
    public function recent(?int $companyId, int $limit = 5): array;
}
