<?php

namespace App\Policies;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectTaskModel;

final class ProjectTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, ProjectTaskModel $task): bool
    {
        if (! $user->can('projects.view')) {
            return false;
        }

        $project = $task->project;

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole(RoleName::CompanyAdmin->value)) {
            return $user->company_id === $project->company_id;
        }

        return $project->members->contains('user_id', $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('projects.manage') && $user->company_id !== null;
    }

    public function update(User $user, ProjectTaskModel $task): bool
    {
        $project = $task->project;

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole(RoleName::CompanyAdmin->value)) {
            return $user->company_id === $project->company_id;
        }

        if ($user->id === $project->manager_id) {
            return true;
        }

        return $user->can('projects.tasks.update') && $task->assigned_user_id === $user->id;
    }

    public function delete(User $user, ProjectTaskModel $task): bool
    {
        return false;
    }
}
