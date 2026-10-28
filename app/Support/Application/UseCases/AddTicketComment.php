<?php

namespace App\Support\Application\UseCases;

use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Enums\TicketActivityType;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

final readonly class AddTicketComment
{
    public function __construct(
        private TicketRepository $tickets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $ticketId, string $body, int $actorId): void
    {
        $ticket = $this->tickets->find($ticketId, $companyId)
            ?? throw DomainException::because('El ticket no existe en esta empresa.');

        $body = trim($body);

        if ($body === '') {
            throw DomainException::because('El comentario no puede estar vacío.');
        }

        if (mb_strlen($body) > 5000) {
            throw DomainException::because('El comentario no puede superar 5000 caracteres.');
        }

        $this->transactions->run(function () use ($ticket, $body, $actorId): void {
            $this->tickets->recordActivity(
                ticketId: (int) $ticket->id,
                actorId: $actorId,
                type: TicketActivityType::Comment,
                body: $body,
            );

            $this->audit->log(
                event: 'ticket.comment_added',
                description: "Se agregó un comentario al ticket {$ticket->folio}.",
                subjectType: SupportTicketModel::class,
                subjectId: $ticket->id,
                metadata: ['type' => TicketActivityType::Comment->value],
                companyId: $ticket->companyId,
            );
        });
    }
}
