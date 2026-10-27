<?php

namespace App\Policies;

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;

final class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, CompanyModel $company): bool
    {
        return $user->company_id === $company->id && $user->can('companies.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CompanyModel $company): bool
    {
        return false;
    }

    public function delete(User $user, CompanyModel $company): bool
    {
        return false;
    }
}
