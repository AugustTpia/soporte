<?php

namespace App\Policies;

use App\Identity\Infrastructure\Persistence\Eloquent\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id
            || ($user->can('users.view') && $user->company_id === $model->company_id);
    }

    public function create(User $user): bool
    {
        return $user->can('users.create') && $user->company_id !== null;
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update')
            && $user->id !== $model->id
            && ! $model->isSuperAdmin()
            && ! $model->hasRole('company-admin')
            && $user->company_id === $model->company_id;
    }

    public function toggleStatus(User $user, User $model): bool
    {
        return $user->can('users.toggle')
            && $user->id !== $model->id
            && ! $model->isSuperAdmin()
            && ! $model->hasRole('company-admin')
            && $user->company_id === $model->company_id;
    }
}
