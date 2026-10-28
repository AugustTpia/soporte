<?php

namespace App\BusTicketCancellations\Application\UseCases;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationFilters;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;
use Generator;

final readonly class ExportBusTicketCancellations
{
    public function __construct(
        private BusTicketCancellationRepository $cancellations,
        private CompanyRepository $companies,
        private AuditLogger $audit,
    ) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Boleto',
            'Ticket de soporte',
            'Fecha de solicitud',
            'Fecha de ejecución',
            'Fecha original del boleto',
            'Importe',
            'Operador',
            'Resultado',
            'Motivo',
            'Referencia de autorización',
            'Notas de evidencia',
        ];
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    public function execute(int $companyId, BusTicketCancellationFilters $filters): Generator
    {
        $company = $this->companies->find($companyId);

        if ($company === null || ! $company->hasFeature(CompanyFeature::BusTicketCancellations)) {
            throw DomainException::because('El módulo de cancelación de boletos no está habilitado para esta empresa.');
        }

        $this->audit->log(
            event: 'bus_ticket_cancellation.exported',
            description: 'Se exportaron las cancelaciones de boletos a CSV.',
            metadata: [
                'term' => $filters->term,
                'result' => $filters->result?->value,
                'from' => $filters->from?->format('Y-m-d'),
                'to' => $filters->to?->format('Y-m-d'),
            ],
            companyId: $companyId,
        );

        foreach ($this->cancellations->export($companyId, $filters) as $cancellation) {
            yield [
                $cancellation->ticketNumber,
                $cancellation->supportTicketFolio ?? '',
                $cancellation->requestedAt->format('Y-m-d H:i'),
                $cancellation->executedAt?->format('Y-m-d H:i') ?? '',
                $cancellation->originalTicketAt?->format('Y-m-d H:i') ?? '',
                $cancellation->amountCents === null
                    ? ''
                    : number_format($cancellation->amountCents / 100, 2, '.', ''),
                $cancellation->operatorName ?? '',
                $cancellation->result->label(),
                $cancellation->reason,
                $cancellation->authorizationReference ?? '',
                $cancellation->evidenceNotes ?? '',
            ];
        }
    }
}
