<?php

namespace App\Reports\Application\UseCases;

use App\Identity\Domain\Repositories\UserRepository;
use App\Reports\Application\Contracts\TicketReportQuery;
use App\Reports\Application\DTOs\ReportDocument;
use App\Reports\Application\DTOs\ReportMetric;
use App\Reports\Application\DTOs\TicketReportFilters;
use App\Reports\Application\DTOs\TicketReportRow;
use App\Reports\Application\Support\ReportValue;
use App\Reports\Domain\Enums\ReportType;
use App\Shared\Domain\Pagination\Page;
use DateTimeImmutable;
use Generator;

final readonly class BuildTicketReport
{
    public function __construct(
        private TicketReportQuery $query,
        private UserRepository $users,
    ) {}

    /**
     * @return array<int, ReportMetric>
     */
    public function metrics(int $companyId, TicketReportFilters $filters): array
    {
        return $this->metricsFromSummary($this->query->summary($companyId, $filters));
    }

    /**
     * @param  array{total: int, unresolved: int, resolved: int, cancelled: int, urgent: int, avg_resolution_hours: float|null}  $summary
     * @return array<int, ReportMetric>
     */
    private function metricsFromSummary(array $summary): array
    {
        return [
            new ReportMetric('Total de tickets', (string) $summary['total'], 'En el periodo seleccionado'),
            new ReportMetric('Sin resolver', (string) $summary['unresolved'], 'Abiertos, asignados, en progreso o en espera', 'amber'),
            new ReportMetric('Resueltos', (string) $summary['resolved'], 'Con fecha de resolución', 'emerald'),
            new ReportMetric('Cancelados', (string) $summary['cancelled'], 'Solicitudes canceladas', 'zinc'),
            new ReportMetric('Urgentes pendientes', (string) $summary['urgent'], 'Prioridad urgente sin resolver', 'rose'),
            new ReportMetric('Tiempo medio de resolución', ReportValue::hours($summary['avg_resolution_hours']), 'Desde apertura hasta resolución', 'royal'),
        ];
    }

    /**
     * @return Page<TicketReportRow>
     */
    public function page(int $companyId, TicketReportFilters $filters, int $page = 1, int $perPage = 20): Page
    {
        return $this->query->paginate($companyId, $filters, $page, $perPage);
    }

    public function document(
        int $companyId,
        TicketReportFilters $filters,
        string $companyName,
        string $generatedBy,
    ): ReportDocument {
        $summary = $this->query->summary($companyId, $filters);

        return new ReportDocument(
            type: ReportType::Tickets,
            title: 'Reporte de tickets',
            companyName: $companyName,
            period: $filters->period,
            filterLabels: $this->filterLabels($companyId, $filters),
            metrics: $this->metricsFromSummary($summary),
            headings: ['Fecha', 'Folio', 'Asunto', 'Categoría', 'Prioridad', 'Estado', 'Ubicación', 'Técnicos', 'Resuelto', 'Horas'],
            rows: $this->cells($companyId, $filters),
            totalRows: $summary['total'],
            generatedBy: $generatedBy,
            generatedAt: new DateTimeImmutable,
        );
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    private function cells(int $companyId, TicketReportFilters $filters): Generator
    {
        foreach ($this->query->exportRows($companyId, $filters) as $row) {
            yield [
                ReportValue::dateTime($row->openedAt),
                ReportValue::text($row->folio),
                $row->subject,
                $row->category->label(),
                $row->priority->label(),
                $row->status->label(),
                ReportValue::text($row->locationName),
                ReportValue::list($row->technicians),
                ReportValue::dateTime($row->resolvedAt),
                ReportValue::hours($row->resolutionHours),
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private function filterLabels(int $companyId, TicketReportFilters $filters): array
    {
        $labels = ['Periodo' => $filters->period->label()];

        if (filled($filters->term)) {
            $labels['Búsqueda'] = (string) $filters->term;
        }

        if ($filters->status !== null) {
            $labels['Estado'] = $filters->status->label();
        }

        if ($filters->priority !== null) {
            $labels['Prioridad'] = $filters->priority->label();
        }

        if ($filters->category !== null) {
            $labels['Categoría'] = $filters->category->label();
        }

        if ($filters->technicianId !== null) {
            $technician = $this->users->find($filters->technicianId);

            if ($technician !== null && $technician->companyId === $companyId) {
                $labels['Técnico'] = $technician->name;
            }
        }

        return $labels;
    }
}
