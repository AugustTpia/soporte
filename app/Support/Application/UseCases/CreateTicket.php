<?php

namespace App\Support\Application\UseCases;

use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Contacts\Domain\Repositories\ClientContactRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Application\DTOs\TicketInput;
use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Enums\TicketActivityType;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

final readonly class CreateTicket
{
    public function __construct(
        private TicketRepository $tickets,
        private LocationRepository $locations,
        private AssetRepository $assets,
        private ServiceAccountRepository $accounts,
        private ClientContactRepository $contacts,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, TicketInput $input, int $actorId): SupportTicket
    {
        $this->assertLinks($companyId, $input);

        $ticket = SupportTicket::create(
            companyId: $companyId,
            category: $input->category,
            subject: $input->subject,
            description: $input->description,
            priority: $input->priority,
            requesterName: $input->requesterName,
            requesterContact: $input->requesterContact,
            locationId: $input->locationId,
            assetId: $input->assetId,
            serviceAccountId: $input->serviceAccountId,
            clientContactId: $input->clientContactId,
            dueAt: $input->dueAt,
            createdBy: $actorId,
        );

        return $this->transactions->run(function () use ($ticket, $actorId): SupportTicket {
            $saved = $this->tickets->save($ticket);

            $this->tickets->recordActivity(
                ticketId: (int) $saved->id,
                actorId: $actorId,
                type: TicketActivityType::Created,
                body: 'Ticket creado.',
            );

            $this->audit->log(
                event: 'ticket.created',
                description: "Se creó el ticket {$saved->folio}.",
                subjectType: SupportTicketModel::class,
                subjectId: $saved->id,
                newValues: $saved->auditValues(),
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

        if ($input->clientContactId !== null && $this->contacts->find($input->clientContactId, $companyId) === null) {
            throw DomainException::because('El contacto seleccionado no pertenece a esta empresa.');
        }
    }
}
