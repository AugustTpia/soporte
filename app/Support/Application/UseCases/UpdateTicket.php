<?php

namespace App\Support\Application\UseCases;

use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Application\DTOs\TicketInput;
use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Enums\TicketActivityType;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

final readonly class UpdateTicket
{
    public function __construct(
        private TicketRepository $tickets,
        private LocationRepository $locations,
        private AssetRepository $assets,
        private ServiceAccountRepository $accounts,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $ticketId, TicketInput $input, int $actorId): SupportTicket
    {
        $ticket = $this->tickets->find($ticketId, $companyId)
            ?? throw DomainException::because('El ticket no existe en esta empresa.');

        $this->assertLinks($companyId, $input);

        $before = $ticket->auditValues();

        $ticket->updateDetails(
            category: $input->category,
            subject: $input->subject,
            description: $input->description,
            requesterName: $input->requesterName,
            requesterContact: $input->requesterContact,
            priority: $input->priority,
            locationId: $input->locationId,
            assetId: $input->assetId,
            serviceAccountId: $input->serviceAccountId,
            dueAt: $input->dueAt,
        );

        $after = $ticket->auditValues();
        $changed = array_keys(array_diff_assoc($after, $before));

        if ($changed === []) {
            return $ticket;
        }

        return $this->transactions->run(function () use ($ticket, $before, $after, $changed, $actorId): SupportTicket {
            $saved = $this->tickets->save($ticket);

            $this->tickets->recordActivity(
                ticketId: (int) $saved->id,
                actorId: $actorId,
                type: TicketActivityType::Updated,
                body: 'Se actualizaron los datos del ticket.',
                metadata: ['fields' => $changed],
            );

            $this->audit->log(
                event: 'ticket.updated',
                description: "Se actualizó el ticket {$saved->folio}.",
                subjectType: SupportTicketModel::class,
                subjectId: $saved->id,
                oldValues: $before,
                newValues: $after,
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }

    private function assertLinks(int $companyId, TicketInput $input): void
    {
        if ($input->locationId !== null && $this->locations->find($input->locationId, $companyId) === null) {
            throw DomainException::because('La ubicación seleccionada no pertenece a esta empresa.');
        }

        if ($input->assetId !== null && $this->assets->find($input->assetId, $companyId) === null) {
            throw DomainException::because('El activo seleccionado no pertenece a esta empresa.');
        }

        if ($input->serviceAccountId !== null && $this->accounts->find($input->serviceAccountId, $companyId) === null) {
            throw DomainException::because('La cuenta seleccionada no pertenece a esta empresa.');
        }
    }
}
