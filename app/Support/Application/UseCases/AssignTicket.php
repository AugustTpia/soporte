<?php

namespace App\Support\Application\UseCases;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Enums\TicketActivityType;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

final readonly class AssignTicket
{
    public function __construct(
        private TicketRepository $tickets,
        private UserRepository $users,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $ticketId,
        int $userId,
        int $actorId,
        ?string $reason = null,
    ): SupportTicket {
        $ticket = $this->tickets->find($ticketId, $companyId)
            ?? throw DomainException::because('El ticket no existe en esta empresa.');

        $ticket->assertCanChangeAssignments();

        $user = $this->users->find($userId);

        if ($user === null || $user->companyId !== $companyId) {
            throw DomainException::because('El técnico seleccionado no pertenece a esta empresa.');
        }

        if (! $user->isActive()) {
            throw DomainException::because('El técnico seleccionado está inactivo.');
        }

        $assignableRoles = [
            RoleName::CompanyAdmin->value,
            RoleName::ServiceManager->value,
            RoleName::Engineer->value,
            RoleName::Technician->value,
        ];

        if (array_intersect($user->roles, $assignableRoles) === []) {
            throw DomainException::because('El usuario seleccionado no puede ser asignado a tickets.');
        }

        if ($this->tickets->findActiveAssignment($ticketId, $userId) !== null) {
            throw DomainException::because('El técnico ya está asignado a este ticket.');
        }

        $reason = $this->clean($reason);

        return $this->transactions->run(function () use ($ticket, $userId, $user, $actorId, $reason): SupportTicket {
            $statusBefore = $ticket->status;

            $this->tickets->addAssignment($ticket->id, $userId, $actorId, $reason);

            $ticket->markAssigned();

            if ($ticket->status !== $statusBefore) {
                $this->tickets->save($ticket);
            }

            $this->tickets->recordActivity(
                ticketId: (int) $ticket->id,
                actorId: $actorId,
                type: TicketActivityType::Assigned,
                body: "Se asignó el ticket a {$user->name}.",
                metadata: [
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'status_from' => $statusBefore->value,
                    'status_to' => $ticket->status->value,
                    'reason' => $reason,
                ],
            );

            $this->audit->log(
                event: 'ticket.assigned',
                description: "Se asignó el ticket {$ticket->folio} a {$user->name}.",
                subjectType: SupportTicketModel::class,
                subjectId: $ticket->id,
                oldValues: ['status' => $statusBefore->value],
                newValues: ['status' => $ticket->status->value, 'assigned_user_id' => $userId],
                metadata: ['reason' => $reason],
                companyId: $ticket->companyId,
            );

            return $this->tickets->find($ticket->id, $ticket->companyId)
                ?? throw DomainException::because('No se pudo recuperar el ticket asignado.');
        });
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
