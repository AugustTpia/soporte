<?php

namespace App\Policies;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

final class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view');
    }

    public function view(User $user, SupportTicketModel $ticket): bool
    {
        return $user->can('tickets.view') && $user->company_id === $ticket->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create') && $user->company_id !== null;
    }

    public function update(User $user, SupportTicketModel $ticket): bool
    {
        return $user->can('tickets.manage')
            && $user->company_id === $ticket->company_id
            && $this->canManageTicket($user, $ticket);
    }

    public function assign(User $user, SupportTicketModel $ticket): bool
    {
        return $user->can('tickets.assign') && $user->company_id === $ticket->company_id;
    }

    public function cancel(User $user, SupportTicketModel $ticket): bool
    {
        return $user->can('tickets.cancel') && $user->company_id === $ticket->company_id;
    }

    public function delete(User $user, SupportTicketModel $ticket): bool
    {
        return false;
    }

    private function canManageTicket(User $user, SupportTicketModel $ticket): bool
    {
        if ($user->hasAnyRole([
            RoleName::CompanyAdmin->value,
            RoleName::ServiceManager->value,
        ])) {
            return true;
        }

        if ($ticket->created_by === $user->id) {
            return true;
        }

        return $ticket->activeAssignments()
            ->where('user_id', $user->id)
            ->exists();
    }
}
