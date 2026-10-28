<?php

namespace App\Policies;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;

final class MaintenancePlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('maintenance.view');
    }

    public function view(User $user, MaintenancePlanModel $plan): bool
    {
        return $user->can('maintenance.view') && $user->company_id === $plan->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('maintenance.manage') && $user->company_id !== null;
    }

    public function update(User $user, MaintenancePlanModel $plan): bool
    {
        return $user->can('maintenance.manage') && $user->company_id === $plan->company_id;
    }

    public function delete(User $user, MaintenancePlanModel $plan): bool
    {
        return false;
    }
}
