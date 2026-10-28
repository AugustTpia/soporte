<?php

namespace App\Reports\Application\UseCases;

use App\Identity\Domain\Repositories\UserRepository;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Reports\Application\Contracts\MaintenanceReportQuery;
use App\Reports\Application\DTOs\MaintenanceReportFilters;
use App\Reports\Application\DTOs\MaintenanceReportRow;
use App\Reports\Application\DTOs\ReportDocument;
use App\Reports\Application\DTOs\ReportMetric;
use App\Reports\Application\Support\ReportValue;
use App\Reports\Domain\Enums\ReportType;
use App\Shared\Domain\Pagination\Page;
use DateTimeImmutable;
use Generator;

final readonly class BuildMaintenanceReport
{
    public function __construct(
        private MaintenanceReportQuery $query,
        private UserRepository $users,
        private MaintenancePlanRepository $plans,
    ) {}

    /**
     * @return array<int, ReportMetric>
     */
    public function metrics(int $companyId, MaintenanceReportFilters $filters): array
    {
        return $this->metricsFromSummary($this->query->summary($companyId, $filters));
    }

    /**
     * @return Page<MaintenanceReportRow>
     */
    public function page(int $companyId, MaintenanceReportFilters $filters, int $page = 1, int $perPage = 20): Page
    {
        return $this->query->paginate($companyId, $filters, $page, $perPage);
    }

    public function document(
        int $companyId,
        MaintenanceReportFilters $filters,
        string $companyName,
        string $generatedBy,
    ): ReportDocument {
        $summary = $this->query->summary($companyId, $filters);

        return new ReportDocument(
            type: ReportType::Maintenance,
            title: 'Reporte de mantenimientos',
            companyName: $companyName,
            period: $filters->period,
            filterLabels: $this->filterLabels($companyId, $filters),
            metrics: $this->metricsFromSummary($summary),
            headings: ['Programado', 'Objetivo', 'Plan', 'Tipo', 'Técnico', 'Estado', 'Inicio', 'Cierre', 'Tiempo', 'Ticket'],
            rows: $this->cells($companyId, $filters),
            totalRows: $summary['total'],
            generatedBy: $generatedBy,
            generatedAt: new DateTimeImmutable,
        );
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    private function cells(int $companyId, MaintenanceReportFilters $filters): Generator
    {
        foreach ($this->query->exportRows($companyId, $filters) as $row) {
            yield [
                ReportValue::dateTime($row->scheduledAt),
                $row->targetLabel,
                ReportValue::text($row->planTitle),
                $row->type->label(),
                ReportValue::text($row->technicianName),
                $row->status->label(),
                ReportValue::dateTime($row->startedAt),
                ReportValue::dateTime($row->completedAt),
                ReportValue::minutes($row->timeSpentMinutes),
                ReportValue::text($row->supportTicketFolio),
            ];
        }
    }

    /**
     * @param  array{total: int, scheduled: int, in_progress: int, completed: int, cancelled: int, avg_minutes: float|null}  $summary
     * @return array<int, ReportMetric>
     */
    private function metricsFromSummary(array $summary): array
    {
        $average = $summary['avg_minutes'] === null ? null : (int) round($summary['avg_minutes']);

        return [
            new ReportMetric('Total de ejecuciones', (string) $summary['total'], 'En el periodo seleccionado'),
            new ReportMetric('Programadas', (string) $summary['scheduled'], 'Pendientes de inicio', 'amber'),
            new ReportMetric('En progreso', (string) $summary['in_progress'], 'Trabajo en curso', 'royal'),
            new ReportMetric('Completadas', (string) $summary['completed'], 'Servicios finalizados', 'emerald'),
            new ReportMetric('Canceladas', (string) $summary['cancelled'], 'Ejecuciones canceladas', 'zinc'),
            new ReportMetric('Tiempo medio invertido', ReportValue::minutes($average), 'Sobre ejecuciones cerradas', 'royal'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function filterLabels(int $companyId, MaintenanceReportFilters $filters): array
    {
        $labels = ['Periodo' => $filters->period->label()];

        if (filled($filters->term)) {
            $labels['Búsqueda'] = (string) $filters->term;
        }

        if ($filters->status !== null) {
            $labels['Estado'] = $filters->status->label();
        }

        if ($filters->type !== null) {
            $labels['Tipo'] = $filters->type->label();
        }

        if ($filters->technicianId !== null) {
            $technician = $this->users->find($filters->technicianId);

            if ($technician !== null && $technician->companyId === $companyId) {
                $labels['Técnico'] = $technician->name;
            }
        }

        if ($filters->planId !== null) {
            $plan = $this->plans->find($filters->planId, $companyId);

            if ($plan !== null) {
                $labels['Plan'] = $plan->title;
            }
        }

        return $labels;
    }
}
