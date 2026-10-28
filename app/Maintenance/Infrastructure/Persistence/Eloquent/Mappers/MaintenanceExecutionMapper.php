<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Mappers;

use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Entities\MaintenanceExecutionChecklistItem;
use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionChecklistItemModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

final class MaintenanceExecutionMapper
{
    public function toDomain(MaintenanceExecutionModel $model): MaintenanceExecution
    {
        return new MaintenanceExecution(
            id: $model->id,
            companyId: $model->company_id,
            planId: $model->maintenance_plan_id,
            assetId: $model->asset_id,
            locationId: $model->location_id,
            supportTicketId: $model->support_ticket_id,
            targetType: MaintenanceTargetType::from($model->target_type),
            targetLabel: $model->target_label,
            type: MaintenanceExecutionType::from($model->type),
            status: MaintenanceExecutionStatus::from($model->status),
            scheduledAt: $this->toDate($model->scheduled_at) ?? new DateTimeImmutable,
            startedAt: $this->toDate($model->started_at),
            completedAt: $this->toDate($model->completed_at),
            cancelledAt: $this->toDate($model->cancelled_at),
            technicianId: (int) $model->technician_id,
            findings: $model->findings,
            actions: $model->actions,
            recommendations: $model->recommendations,
            partsUsed: $model->parts_used,
            timeSpentMinutes: $model->time_spent_minutes,
            cancellationReason: $model->cancellation_reason,
            createdBy: $model->created_by,
            technicianName: $model->relationLoaded('technician') ? $model->technician?->name : null,
            planTitle: $model->relationLoaded('plan') ? $model->plan?->title : null,
            supportTicketFolio: $model->relationLoaded('supportTicket') ? $model->supportTicket?->folio : null,
            assetLabel: $model->relationLoaded('asset') ? $this->assetLabel($model) : null,
            locationName: $model->relationLoaded('location') ? $model->location?->name : null,
            checklistItems: $model->relationLoaded('checklistItems')
                ? $model->checklistItems->map(fn (MaintenanceExecutionChecklistItemModel $item) => $this->itemToDomain($item))->all()
                : [],
            createdAt: $this->toDate($model->created_at),
        );
    }

    public function fillModel(MaintenanceExecution $execution, MaintenanceExecutionModel $model): MaintenanceExecutionModel
    {
        $model->company_id = $execution->companyId;
        $model->maintenance_plan_id = $execution->planId;
        $model->asset_id = $execution->assetId;
        $model->location_id = $execution->locationId;
        $model->support_ticket_id = $execution->supportTicketId;
        $model->target_type = $execution->targetType->value;
        $model->target_label = $execution->targetLabel;
        $model->type = $execution->type->value;
        $model->status = $execution->status->value;
        $model->scheduled_at = $this->toCarbon($execution->scheduledAt);
        $model->started_at = $this->toCarbon($execution->startedAt);
        $model->completed_at = $this->toCarbon($execution->completedAt);
        $model->cancelled_at = $this->toCarbon($execution->cancelledAt);
        $model->technician_id = $execution->technicianId;
        $model->findings = $execution->findings;
        $model->actions = $execution->actions;
        $model->recommendations = $execution->recommendations;
        $model->parts_used = $execution->partsUsed;
        $model->time_spent_minutes = $execution->timeSpentMinutes;
        $model->cancellation_reason = $execution->cancellationReason;
        $model->created_by = $execution->createdBy;

        return $model;
    }

    public function itemToDomain(MaintenanceExecutionChecklistItemModel $model): MaintenanceExecutionChecklistItem
    {
        return new MaintenanceExecutionChecklistItem(
            id: $model->id,
            planChecklistItemId: $model->maintenance_plan_checklist_item_id,
            label: $model->label,
            isRequired: $model->is_required,
            sortOrder: $model->sort_order,
            isCompleted: $model->is_completed,
            notes: $model->notes,
            completedAt: $this->toDate($model->completed_at),
            completedBy: $model->completed_by,
        );
    }

    private function assetLabel(MaintenanceExecutionModel $model): ?string
    {
        if ($model->asset === null) {
            return null;
        }

        return $model->asset->internal_code.($model->asset->hostname !== null ? ' · '.$model->asset->hostname : '');
    }

    private function toCarbon(?DateTimeImmutable $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::createFromInterface($value);
    }

    private function toDate(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
