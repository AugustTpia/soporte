<?php

namespace App\Policies;

use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;

final class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('departments.view');
    }

    public function view(User $user, DepartmentModel $department): bool
    {
        return $user->can('departments.view') && $user->company_id === $department->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('departments.manage') && $user->company_id !== null;
    }

    public function update(User $user, DepartmentModel $department): bool
    {
        return $user->can('departments.manage') && $user->company_id === $department->company_id;
    }

    public function delete(User $user, DepartmentModel $department): bool
    {
        return $user->can('departments.manage') && $user->company_id === $department->company_id;
    }
}
