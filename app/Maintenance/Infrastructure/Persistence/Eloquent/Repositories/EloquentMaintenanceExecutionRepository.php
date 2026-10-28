<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Repositories;

use App\Maintenance\Application\DTOs\MaintenanceExecutionFilters;
use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Entities\MaintenanceExecutionChecklistItem;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Mappers\MaintenanceExecutionMapper;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionChecklistItemModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Domain\Exceptions\DomainException;
use App\Shared\Domain\Pagination\Page;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

final class EloquentMaintenanceExecutionRepository implements MaintenanceExecutionRepository
{
    public function __construct(private readonly MaintenanceExecutionMapper $mapper) {}

    /**
     * @return Page<MaintenanceExecution>
     */
    public function search(int $companyId, MaintenanceExecutionFilters $filters, int $page = 1, int $perPage = 15): Page
    {
        $query = MaintenanceExecutionModel::query()
            ->with(['plan', 'asset', 'location', 'supportTicket', 'technician', 'checklistItems'])
            ->where('company_id', $companyId)
            ->when(filled($filters->term), function ($query) use ($filters): void {
                $term = $filters->term;

                $query->where(function ($query) use ($term): void {
                    $query->where('target_label', 'like', "%{$term}%")
                        ->orWhere('findings', 'like', "%{$term}%")
                        ->orWhere('actions', 'like', "%{$term}%")
                        ->orWhereHas('plan', fn ($query) => $query->where('title', 'like', "%{$term}%"))
                        ->orWhereHas('supportTicket', fn ($query) => $query->where('folio', 'like', "%{$term}%"));
                });
            })
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status->value))
            ->when($filters->type !== null, fn ($query) => $query->where('type', $filters->type->value))
            ->when($filters->technicianId !== null, fn ($query) => $query->where('technician_id', $filters->technicianId))
            ->when($filters->planId !== null, fn ($query) => $query->where('maintenance_plan_id', $filters->planId))
            ->when($filters->from !== null, fn ($query) => $query->whereDate('scheduled_at', '>=', $filters->from->format('Y-m-d')))
            ->when($filters->to !== null, fn ($query) => $query->whereDate('scheduled_at', '<=', $filters->to->format('Y-m-d')))
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id');

        $paginator = $query->paginate(max(1, $perPage), page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (MaintenanceExecutionModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?MaintenanceExecution
    {
        $model = MaintenanceExecutionModel::query()
            ->with(['plan', 'asset', 'location', 'supportTicket', 'technician', 'checklistItems'])
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    /**
     * @return array<int, MaintenanceExecution>
     */
    public function forPlan(int $planId, int $companyId): array
    {
        return MaintenanceExecutionModel::query()
            ->with(['plan', 'asset', 'location', 'supportTicket', 'technician', 'checklistItems'])
            ->where('company_id', $companyId)
            ->where('maintenance_plan_id', $planId)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MaintenanceExecutionModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(MaintenanceExecution $execution): MaintenanceExecution
    {
        $model = $execution->id === null
            ? new MaintenanceExecutionModel
            : MaintenanceExecutionModel::query()
                ->where('company_id', $execution->companyId)
                ->findOrFail($execution->id);

        $this->mapper->fillModel($execution, $model)->save();
        $this->syncChecklist($model, $execution->checklistItems);

        return $this->find($model->id, $model->company_id)
            ?? throw DomainException::because('No se pudo recuperar la ejecución de mantenimiento guardada.');
    }

    /**
     * @return array{scheduled: int, in_progress: int, completed_month: int, cancelled_month: int}
     */
    public function counts(int $companyId): array
    {
        $base = MaintenanceExecutionModel::query()->where('company_id', $companyId);
        $startOfMonth = CarbonImmutable::now()->startOfMonth();
        $endOfMonth = CarbonImmutable::now()->endOfMonth();

        return [
            'scheduled' => (clone $base)->where('status', 'scheduled')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'completed_month' => (clone $base)->where('status', 'completed')
                ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])->count(),
            'cancelled_month' => (clone $base)->where('status', 'cancelled')
                ->whereBetween('cancelled_at', [$startOfMonth, $endOfMonth])->count(),
        ];
    }

    /**
     * @param  array<int, MaintenanceExecutionChecklistItem>  $items
     */
    private function syncChecklist(MaintenanceExecutionModel $execution, array $items): void
    {
        $existing = MaintenanceExecutionChecklistItemModel::query()
            ->where('maintenance_execution_id', $execution->id)
            ->get()
            ->keyBy('id');

        $retainedIds = [];

        foreach (array_values($items) as $item) {
            if ($item->id !== null && $existing->has($item->id)) {
                $model = $existing->get($item->id);
                $model->label = $item->label;
                $model->is_required = $item->isRequired;
                $model->sort_order = $item->sortOrder;
                $model->is_completed = $item->isCompleted;
                $model->notes = $item->notes;
                $model->completed_at = $this->toCarbon($item->completedAt);
                $model->completed_by = $item->completedBy;
                $model->save();
                $retainedIds[] = $model->id;

                continue;
            }

            $model = MaintenanceExecutionChecklistItemModel::query()->create([
                'maintenance_execution_id' => $execution->id,
                'maintenance_plan_checklist_item_id' => $item->planChecklistItemId,
                'label' => $item->label,
                'is_required' => $item->isRequired,
                'sort_order' => $item->sortOrder,
                'is_completed' => $item->isCompleted,
                'notes' => $item->notes,
                'completed_at' => $this->toCarbon($item->completedAt),
                'completed_by' => $item->completedBy,
            ]);
            $retainedIds[] = $model->id;
        }

        MaintenanceExecutionChecklistItemModel::query()
            ->where('maintenance_execution_id', $execution->id)
            ->whereNotIn('id', $retainedIds)
            ->delete();
    }

    private function toCarbon(?DateTimeImmutable $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::createFromInterface($value);
    }
}
