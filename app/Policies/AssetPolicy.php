<?php

namespace App\Policies;

use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;

final class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('assets.view');
    }

    public function view(User $user, AssetModel $asset): bool
    {
        return $user->can('assets.view') && $user->company_id === $asset->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('assets.manage') && $user->company_id !== null;
    }

    public function update(User $user, AssetModel $asset): bool
    {
        return $user->can('assets.manage') && $user->company_id === $asset->company_id;
    }

    public function delete(User $user, AssetModel $asset): bool
    {
        return $user->can('assets.manage') && $user->company_id === $asset->company_id;
    }
}
