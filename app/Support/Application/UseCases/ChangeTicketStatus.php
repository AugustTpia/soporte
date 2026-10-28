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

final readonly class ChangeTicketStatus
{
    public function __construct(
        private TicketRepository $tickets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $ticketId,
        TicketStatus $target,
        int $actorId,
        ?string $reason = null,
    ): SupportTicket {
        $ticket = $this->tickets->find($ticketId, $companyId)
            ?? throw DomainException::because('El ticket no existe en esta empresa.');

        $from = $ticket->status;
        $ticket->changeStatus($target, $reason);

        $reason = $this->clean($reason);

        return $this->transactions->run(function () use ($ticket, $from, $target, $actorId, $reason): SupportTicket {
            $this->tickets->save($ticket);

            $body = $reason ?? "Estado cambiado de {$from->label()} a {$target->label()}.";

            $this->tickets->recordActivity(
                ticketId: (int) $ticket->id,
                actorId: $actorId,
                type: TicketActivityType::StatusChanged,
                body: $body,
                metadata: [
                    'from' => $from->value,
                    'to' => $target->value,
                    'reason' => $reason,
                ],
            );

            $event = $this->eventName($from, $target);

            $this->audit->log(
                event: $event,
                description: "El ticket {$ticket->folio} cambió de {$from->label()} a {$target->label()}.",
                subjectType: SupportTicketModel::class,
                subjectId: $ticket->id,
                oldValues: ['status' => $from->value],
                newValues: ['status' => $target->value],
                metadata: ['reason' => $reason],
                companyId: $ticket->companyId,
            );

            return $this->tickets->find($ticket->id, $ticket->companyId)
                ?? throw DomainException::because('No se pudo recuperar el ticket actualizado.');
        });
    }

    private function eventName(TicketStatus $from, TicketStatus $target): string
    {
        if ($target === TicketStatus::Cancelled) {
            return 'ticket.cancelled';
        }

        if ($target === TicketStatus::InProgress
            && in_array($from, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            return 'ticket.reopened';
        }

        return 'ticket.status_changed';
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
