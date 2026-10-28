<?php

namespace App\Support\Application\UseCases;

use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Enums\TicketActivityType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

final readonly class UnassignTicket
{
    public function __construct(
        private TicketRepository $tickets,
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

        $ticket->assertCanRemoveAssignee($userId);

        $assignment = $this->tickets->findActiveAssignment($ticketId, $userId);

        if ($assignment === null) {
            throw DomainException::because('El usuario no está asignado actualmente al ticket.');
        }

        $activeCount = $this->tickets->activeAssignmentCount($ticketId);
        $reason = $this->clean($reason);

        return $this->transactions->run(function () use ($ticket, $userId, $assignment, $activeCount, $actorId, $reason): SupportTicket {
            $statusBefore = $ticket->status;

            $this->tickets->closeAssignment($ticket->id, $userId, $actorId, $reason);

            if ($activeCount === 1 && $ticket->status === TicketStatus::Assigned) {
                $ticket->markOpen();
                $this->tickets->save($ticket);
            }

            $this->tickets->recordActivity(
                ticketId: (int) $ticket->id,
                actorId: $actorId,
                type: TicketActivityType::Unassigned,
                body: 'Se retiró a '.($assignment->userName ?? 'un técnico').' del ticket.',
                metadata: [
                    'user_id' => $userId,
                    'user_name' => $assignment->userName,
                    'status_from' => $statusBefore->value,
                    'status_to' => $ticket->status->value,
                    'reason' => $reason,
                ],
            );

            $this->audit->log(
                event: 'ticket.unassigned',
                description: "Se retiró a {$assignment->userName} del ticket {$ticket->folio}.",
                subjectType: SupportTicketModel::class,
                subjectId: $ticket->id,
                oldValues: ['status' => $statusBefore->value, 'assigned_user_id' => $userId],
                newValues: ['status' => $ticket->status->value],
                metadata: ['reason' => $reason],
                companyId: $ticket->companyId,
            );

            return $this->tickets->find($ticket->id, $ticket->companyId)
                ?? throw DomainException::because('No se pudo recuperar el ticket actualizado.');
        });
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
