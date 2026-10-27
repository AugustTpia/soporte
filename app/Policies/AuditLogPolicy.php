<?php

namespace App\Policies;

use App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Identity\Infrastructure\Persistence\Eloquent\User;

final class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view');
    }

    public function view(User $user, AuditLogModel $auditLog): bool
    {
        return $user->can('audit.view') && $user->company_id === $auditLog->company_id;
    }
}
