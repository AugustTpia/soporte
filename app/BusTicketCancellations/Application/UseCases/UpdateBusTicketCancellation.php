<?php

namespace App\BusTicketCancellations\Application\UseCases;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationInput;
use App\BusTicketCancellations\Domain\Entities\BusTicketCancellation;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Repositories\UserRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Repositories\TicketRepository;

final readonly class UpdateBusTicketCancellation
{
    public function __construct(
        private BusTicketCancellationRepository $cancellations,
        private CompanyRepository $companies,
        private UserRepository $users,
        private TicketRepository $tickets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $cancellationId,
        BusTicketCancellationInput $input,
        int $actorId,
    ): BusTicketCancellation {
        $this->assertFeature($companyId);
        $this->assertOperator($companyId, $input->operatorId);
        $this->assertSupportTicket($companyId, $input->supportTicketId);

        $cancellation = $this->cancellations->find($cancellationId, $companyId)
            ?? throw DomainException::because('La cancelación no existe en esta empresa.');

        $before = $cancellation->auditValues();

        $cancellation->updateDetails(
            supportTicketId: $input->supportTicketId,
            ticketNumber: $input->ticketNumber,
            requestedAt: $input->requestedAt,
            executedAt: $input->executedAt,
            originalTicketAt: $input->originalTicketAt,
            amountCents: $input->amountCents,
            operatorId: $input->operatorId,
            result: $input->result,
            reason: $input->reason,
            authorizationReference: $input->authorizationReference,
            evidenceNotes: $input->evidenceNotes,
        );

        $after = $cancellation->auditValues();
        $changed = array_keys(array_diff_assoc($after, $before));

        if ($changed === []) {
            return $cancellation;
        }

        return $this->transactions->run(function () use ($cancellation, $before, $after, $actorId): BusTicketCancellation {
            $saved = $this->cancellations->save($cancellation);

            $this->audit->log(
                event: 'bus_ticket_cancellation.updated',
                description: "Se actualizó la cancelación del boleto {$saved->ticketNumber}.",
                subjectType: BusTicketCancellationModel::class,
                subjectId: $saved->id,
                oldValues: $before,
                newValues: $after,
                metadata: ['actor_id' => $actorId],
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }

    private function assertFeature(int $companyId): void
    {
        $company = $this->companies->find($companyId);

        if ($company === null || ! $company->hasFeature(CompanyFeature::BusTicketCancellations)) {
            throw DomainException::because('El módulo de cancelación de boletos no está habilitado para esta empresa.');
        }
    }

    private function assertOperator(int $companyId, int $operatorId): void
    {
        $operator = $this->users->find($operatorId);

        if ($operator === null || $operator->companyId !== $companyId) {
            throw DomainException::because('El operador seleccionado no pertenece a esta empresa.');
        }

        if (! $operator->isActive()) {
            throw DomainException::because('El operador seleccionado está inactivo.');
        }
    }

    private function assertSupportTicket(int $companyId, ?int $supportTicketId): void
    {
        if ($supportTicketId !== null && $this->tickets->find($supportTicketId, $companyId) === null) {
            throw DomainException::because('El ticket de soporte seleccionado no pertenece a esta empresa.');
        }
    }
}
