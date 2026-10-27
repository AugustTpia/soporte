<?php

namespace App\Policies;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;

final class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('locations.view');
    }

    public function view(User $user, LocationModel $location): bool
    {
        return $user->can('locations.view') && $user->company_id === $location->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('locations.manage') && $user->company_id !== null;
    }

    public function update(User $user, LocationModel $location): bool
    {
        return $user->can('locations.manage') && $user->company_id === $location->company_id;
    }

    public function delete(User $user, LocationModel $location): bool
    {
        return $user->can('locations.manage') && $user->company_id === $location->company_id;
    }
}
