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

final readonly class CreateBusTicketCancellation
{
    public function __construct(
        private BusTicketCancellationRepository $cancellations,
        private CompanyRepository $companies,
        private UserRepository $users,
        private TicketRepository $tickets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, BusTicketCancellationInput $input, int $actorId): BusTicketCancellation
    {
        $this->assertFeature($companyId);
        $this->assertOperator($companyId, $input->operatorId);
        $this->assertSupportTicket($companyId, $input->supportTicketId);

        $cancellation = BusTicketCancellation::create(
            companyId: $companyId,
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
            createdBy: $actorId,
        );

        return $this->transactions->run(function () use ($cancellation): BusTicketCancellation {
            $saved = $this->cancellations->save($cancellation);

            $this->audit->log(
                event: 'bus_ticket_cancellation.created',
                description: "Se registró la cancelación del boleto {$saved->ticketNumber}.",
                subjectType: BusTicketCancellationModel::class,
                subjectId: $saved->id,
                newValues: $saved->auditValues(),
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
