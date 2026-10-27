<?php

namespace App\Policies;

use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;

final class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('credentials.view');
    }

    public function view(User $user, CredentialModel $credential): bool
    {
        return $user->can('credentials.view') && $user->company_id === $credential->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('credentials.manage') && $user->company_id !== null;
    }

    public function update(User $user, CredentialModel $credential): bool
    {
        return $user->can('credentials.manage') && $user->company_id === $credential->company_id;
    }

    public function setSecret(User $user, CredentialModel $credential): bool
    {
        return $user->can('credentials.manage') && $user->company_id === $credential->company_id;
    }

    public function reveal(User $user, CredentialModel $credential): bool
    {
        return $user->can('credentials.reveal') && $user->company_id === $credential->company_id;
    }

    public function delete(User $user, CredentialModel $credential): bool
    {
        return false;
    }
}
