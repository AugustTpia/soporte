<?php

namespace App\Policies;

use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;

final class ServiceAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('accounts.view');
    }

    public function view(User $user, ServiceAccountModel $account): bool
    {
        return $user->can('accounts.view') && $user->company_id === $account->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('accounts.manage') && $user->company_id !== null;
    }

    public function update(User $user, ServiceAccountModel $account): bool
    {
        return $user->can('accounts.manage') && $user->company_id === $account->company_id;
    }

    public function delete(User $user, ServiceAccountModel $account): bool
    {
        return $user->can('accounts.manage') && $user->company_id === $account->company_id;
    }
}
