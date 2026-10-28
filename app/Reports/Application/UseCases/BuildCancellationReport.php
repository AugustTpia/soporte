<?php

namespace App\Reports\Application\UseCases;

use App\Identity\Domain\Repositories\UserRepository;
use App\Reports\Application\Contracts\CancellationReportQuery;
use App\Reports\Application\DTOs\CancellationReportFilters;
use App\Reports\Application\DTOs\CancellationReportRow;
use App\Reports\Application\DTOs\ReportDocument;
use App\Reports\Application\DTOs\ReportMetric;
use App\Reports\Application\Support\ReportValue;
use App\Reports\Domain\Enums\ReportType;
use App\Shared\Domain\Pagination\Page;
use DateTimeImmutable;
use Generator;

final readonly class BuildCancellationReport
{
    public function __construct(
        private CancellationReportQuery $query,
        private UserRepository $users,
    ) {}

    /**
     * @return array<int, ReportMetric>
     */
    public function metrics(int $companyId, CancellationReportFilters $filters): array
    {
        return $this->metricsFromSummary($this->query->summary($companyId, $filters));
    }

    /**
     * @return Page<CancellationReportRow>
     */
    public function page(int $companyId, CancellationReportFilters $filters, int $page = 1, int $perPage = 20): Page
    {
        return $this->query->paginate($companyId, $filters, $page, $perPage);
    }

    public function document(
        int $companyId,
        CancellationReportFilters $filters,
        string $companyName,
        string $generatedBy,
    ): ReportDocument {
        $summary = $this->query->summary($companyId, $filters);

        return new ReportDocument(
            type: ReportType::Cancellations,
            title: 'Reporte de cancelaciones de boletos',
            companyName: $companyName,
            period: $filters->period,
            filterLabels: $this->filterLabels($companyId, $filters),
            metrics: $this->metricsFromSummary($summary),
            headings: ['Solicitado', 'Boleto', 'Ticket', 'Operador', 'Resultado', 'Importe', 'Ejecutado', 'Motivo', 'Autorización'],
            rows: $this->cells($companyId, $filters),
            totalRows: $summary['total'],
            generatedBy: $generatedBy,
            generatedAt: new DateTimeImmutable,
        );
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    private function cells(int $companyId, CancellationReportFilters $filters): Generator
    {
        foreach ($this->query->exportRows($companyId, $filters) as $row) {
            yield [
                ReportValue::dateTime($row->requestedAt),
                $row->ticketNumber,
                ReportValue::text($row->supportTicketFolio),
                $row->operatorName,
                $row->result->label(),
                ReportValue::money($row->amountCents),
                ReportValue::dateTime($row->executedAt),
                $row->reason,
                ReportValue::text($row->authorizationReference),
            ];
        }
    }

    /**
     * @param  array{total: int, cancelled: int, not_cancelled: int, pending: int, amount_cents: int}  $summary
     * @return array<int, ReportMetric>
     */
    private function metricsFromSummary(array $summary): array
    {
        return [
            new ReportMetric('Total de cancelaciones', (string) $summary['total'], 'En el periodo seleccionado'),
            new ReportMetric('Canceladas', (string) $summary['cancelled'], 'Boletos cancelados', 'emerald'),
            new ReportMetric('No canceladas', (string) $summary['not_cancelled'], 'Sin cancelación efectiva', 'rose'),
            new ReportMetric('Pendientes', (string) $summary['pending'], 'En proceso', 'amber'),
            new ReportMetric('Importe registrado', ReportValue::money($summary['amount_cents']), 'Solo cuando el importe fue capturado', 'royal'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function filterLabels(int $companyId, CancellationReportFilters $filters): array
    {
        $labels = ['Periodo' => $filters->period->label()];

        if (filled($filters->term)) {
            $labels['Búsqueda'] = (string) $filters->term;
        }

        if ($filters->result !== null) {
            $labels['Resultado'] = $filters->result->label();
        }

        if ($filters->operatorId !== null) {
            $user = $this->users->find($filters->operatorId);

            if ($user !== null && $user->companyId === $companyId) {
                $labels['Operador'] = $user->name;
            }
        }

        return $labels;
    }
}
