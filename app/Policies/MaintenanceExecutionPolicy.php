<?php

namespace App\Policies;

use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;

final class MaintenanceExecutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('maintenance.view');
    }

    public function view(User $user, MaintenanceExecutionModel $execution): bool
    {
        return $user->can('maintenance.view') && $user->company_id === $execution->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('maintenance.manage') && $user->company_id !== null;
    }

    public function update(User $user, MaintenanceExecutionModel $execution): bool
    {
        return $user->can('maintenance.manage') && $user->company_id === $execution->company_id;
    }

    public function delete(User $user, MaintenanceExecutionModel $execution): bool
    {
        return false;
    }
}
