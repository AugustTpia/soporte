<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Repositories;

use App\Maintenance\Application\DTOs\MaintenancePlanFilters;
use App\Maintenance\Domain\Entities\MaintenancePlan;
use App\Maintenance\Domain\Entities\MaintenancePlanChecklistItem;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Mappers\MaintenancePlanMapper;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanChecklistItemModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Shared\Domain\Exceptions\DomainException;
use App\Shared\Domain\Pagination\Page;
use DateTimeImmutable;

final class EloquentMaintenancePlanRepository implements MaintenancePlanRepository
{
    public function __construct(private readonly MaintenancePlanMapper $mapper) {}

    /**
     * @return Page<MaintenancePlan>
     */
    public function search(int $companyId, MaintenancePlanFilters $filters, int $page = 1, int $perPage = 15): Page
    {
        $query = MaintenancePlanModel::query()
            ->with(['asset', 'location', 'assignee', 'checklistItems'])
            ->where('company_id', $companyId)
            ->when(filled($filters->term), function ($query) use ($filters): void {
                $term = $filters->term;

                $query->where(function ($query) use ($term): void {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('target_label', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($filters->targetType !== null, fn ($query) => $query->where('target_type', $filters->targetType->value))
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status->value))
            ->when($filters->assignedTo !== null, fn ($query) => $query->where('assigned_user_id', $filters->assignedTo))
            ->when($filters->overdue, function ($query): void {
                $query->where('status', MaintenancePlanStatus::Active->value)
                    ->whereDate('next_due_at', '<', now()->toDateString());
            })
            ->orderBy('next_due_at')
            ->orderBy('id');

        $paginator = $query->paginate(max(1, $perPage), page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (MaintenancePlanModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?MaintenancePlan
    {
        $model = MaintenancePlanModel::query()
            ->with(['asset', 'location', 'assignee', 'checklistItems'])
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    /**
     * @return array<int, MaintenancePlan>
     */
    public function forCompany(int $companyId): array
    {
        return MaintenancePlanModel::query()
            ->with(['asset', 'location', 'assignee', 'checklistItems'])
            ->where('company_id', $companyId)
            ->orderBy('title')
            ->limit(200)
            ->get()
            ->map(fn (MaintenancePlanModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(MaintenancePlan $plan): MaintenancePlan
    {
        $model = $plan->id === null
            ? new MaintenancePlanModel
            : MaintenancePlanModel::query()
                ->where('company_id', $plan->companyId)
                ->findOrFail($plan->id);

        $this->mapper->fillModel($plan, $model)->save();
        $this->syncChecklist($model, $plan->checklistItems);

        return $this->find($model->id, $model->company_id)
            ?? throw DomainException::because('No se pudo recuperar el plan de mantenimiento guardado.');
    }

    /**
     * @return array{active: int, paused: int, overdue: int, upcoming: int}
     */
    public function counts(int $companyId, ?DateTimeImmutable $reference = null): array
    {
        $reference = $reference ?? new DateTimeImmutable;
        $today = $reference->format('Y-m-d');
        $upcomingLimit = $reference->modify('+30 days')->format('Y-m-d');
        $base = MaintenancePlanModel::query()->where('company_id', $companyId);

        return [
            'active' => (clone $base)->where('status', MaintenancePlanStatus::Active->value)->count(),
            'paused' => (clone $base)->where('status', MaintenancePlanStatus::Paused->value)->count(),
            'overdue' => (clone $base)->where('status', MaintenancePlanStatus::Active->value)
                ->whereDate('next_due_at', '<', $today)->count(),
            'upcoming' => (clone $base)->where('status', MaintenancePlanStatus::Active->value)
                ->whereDate('next_due_at', '>=', $today)
                ->whereDate('next_due_at', '<=', $upcomingLimit)->count(),
        ];
    }

    /**
     * @param  array<int, MaintenancePlanChecklistItem>  $items
     */
    private function syncChecklist(MaintenancePlanModel $plan, array $items): void
    {
        $existing = MaintenancePlanChecklistItemModel::query()
            ->where('maintenance_plan_id', $plan->id)
            ->get()
            ->keyBy('id');

        $offset = 60000;

        foreach ($existing->values() as $index => $model) {
            $model->sort_order = $offset + $index;
            $model->save();
        }

        $retainedIds = [];

        foreach (array_values($items) as $index => $item) {
            if ($item->id !== null && $existing->has($item->id)) {
                $model = $existing->get($item->id);
                $model->label = $item->label;
                $model->is_required = $item->isRequired;
                $model->sort_order = $item->sortOrder;
                $model->save();
                $retainedIds[] = $model->id;

                continue;
            }

            $model = MaintenancePlanChecklistItemModel::query()->create([
                'maintenance_plan_id' => $plan->id,
                'label' => $item->label,
                'is_required' => $item->isRequired,
                'sort_order' => $item->sortOrder,
            ]);
            $retainedIds[] = $model->id;
        }

        MaintenancePlanChecklistItemModel::query()
            ->where('maintenance_plan_id', $plan->id)
            ->whereNotIn('id', $retainedIds)
            ->delete();
    }
}
