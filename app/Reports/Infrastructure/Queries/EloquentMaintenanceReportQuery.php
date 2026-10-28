<?php

namespace App\Reports\Infrastructure\Queries;

use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Reports\Application\Contracts\MaintenanceReportQuery;
use App\Reports\Application\DTOs\MaintenanceReportFilters;
use App\Reports\Application\DTOs\MaintenanceReportRow;
use App\Shared\Domain\Pagination\Page;
use Generator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentMaintenanceReportQuery implements MaintenanceReportQuery
{
    /**
     * @return array{total: int, scheduled: int, in_progress: int, completed: int, cancelled: int, avg_minutes: float|null}
     */
    public function summary(int $companyId, MaintenanceReportFilters $filters): array
    {
        $base = $this->baseQuery($companyId, $filters);
        $average = (clone $base)->avg('time_spent_minutes');

        return [
            'total' => (clone $base)->count(),
            'scheduled' => (clone $base)->where('status', MaintenanceExecutionStatus::Scheduled->value)->count(),
            'in_progress' => (clone $base)->where('status', MaintenanceExecutionStatus::InProgress->value)->count(),
            'completed' => (clone $base)->where('status', MaintenanceExecutionStatus::Completed->value)->count(),
            'cancelled' => (clone $base)->where('status', MaintenanceExecutionStatus::Cancelled->value)->count(),
            'avg_minutes' => $average === null ? null : round((float) $average, 1),
        ];
    }

    /**
     * @return Page<MaintenanceReportRow>
     */
    public function paginate(int $companyId, MaintenanceReportFilters $filters, int $page = 1, int $perPage = 20): Page
    {
        $paginator = $this->baseQuery($companyId, $filters)
            ->with(['plan', 'technician', 'supportTicket'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(max(1, $perPage), page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()
                ->map(fn (MaintenanceExecutionModel $execution) => $this->toRow($execution))
                ->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    /**
     * @return Generator<int, MaintenanceReportRow>
     */
    public function exportRows(int $companyId, MaintenanceReportFilters $filters): Generator
    {
        $query = $this->baseQuery($companyId, $filters)
            ->with(['plan', 'technician', 'supportTicket'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id');

        foreach ($query->lazy(500) as $execution) {
            yield $this->toRow($execution);
        }
    }

    /**
     * @return Builder<MaintenanceExecutionModel>
     */
    private function baseQuery(int $companyId, MaintenanceReportFilters $filters): Builder
    {
        return MaintenanceExecutionModel::query()
            ->where('company_id', $companyId)
            ->whereBetween('scheduled_at', [$filters->period->from, $filters->period->to])
            ->when(filled($filters->term), function (Builder $query) use ($filters): void {
                $term = (string) $filters->term;

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('target_label', 'like', "%{$term}%")
                        ->orWhere('findings', 'like', "%{$term}%")
                        ->orWhere('actions', 'like', "%{$term}%")
                        ->orWhere('recommendations', 'like', "%{$term}%")
                        ->orWhereHas('plan', fn (Builder $plan) => $plan->where('title', 'like', "%{$term}%"))
                        ->orWhereHas('supportTicket', fn (Builder $ticket) => $ticket->where('folio', 'like', "%{$term}%"));
                });
            })
            ->when($filters->status !== null, fn (Builder $query) => $query->where('status', $filters->status->value))
            ->when($filters->type !== null, fn (Builder $query) => $query->where('type', $filters->type->value))
            ->when($filters->technicianId !== null, fn (Builder $query) => $query->where('technician_id', $filters->technicianId))
            ->when($filters->planId !== null, fn (Builder $query) => $query->where('maintenance_plan_id', $filters->planId));
    }

    private function toRow(MaintenanceExecutionModel $execution): MaintenanceReportRow
    {
        return new MaintenanceReportRow(
            id: $execution->id,
            targetLabel: $execution->target_label,
            planTitle: $execution->plan?->title,
            type: MaintenanceExecutionType::from($execution->type),
            status: MaintenanceExecutionStatus::from($execution->status),
            scheduledAt: $execution->scheduled_at->toDateTimeImmutable(),
            technicianName: $execution->technician?->name,
            startedAt: $execution->started_at?->toDateTimeImmutable(),
            completedAt: $execution->completed_at?->toDateTimeImmutable(),
            timeSpentMinutes: $execution->time_spent_minutes,
            supportTicketFolio: $execution->supportTicket?->folio,
        );
    }
}
