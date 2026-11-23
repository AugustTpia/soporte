<?php

namespace App\Policies;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel;

final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, ProjectModel $project): bool
    {
        if (! $user->can('projects.view')) {
            return false;
        }

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

    public function update(User $user, ProjectModel $project): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole(RoleName::CompanyAdmin->value)) {
            return $user->company_id === $project->company_id;
        }

        return $user->id === $project->manager_id;
    }

    public function delete(User $user, ProjectModel $project): bool
    {
        return false;
    }
}
