<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Mappers;

use App\Maintenance\Domain\Entities\MaintenancePlan;
use App\Maintenance\Domain\Entities\MaintenancePlanChecklistItem;
use App\Maintenance\Domain\Enums\MaintenanceIntervalUnit;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanChecklistItemModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

final class MaintenancePlanMapper
{
    public function toDomain(MaintenancePlanModel $model): MaintenancePlan
    {
        return new MaintenancePlan(
            id: $model->id,
            companyId: $model->company_id,
            targetType: MaintenanceTargetType::from($model->target_type),
            assetId: $model->asset_id,
            locationId: $model->location_id,
            targetLabel: $model->target_label,
            title: $model->title,
            description: $model->description,
            intervalValue: $model->interval_value,
            intervalUnit: MaintenanceIntervalUnit::from($model->interval_unit),
            nextDueAt: $this->toDate($model->next_due_at) ?? new DateTimeImmutable,
            lastCompletedAt: $this->toDate($model->last_completed_at),
            assignedUserId: (int) $model->assigned_user_id,
            status: MaintenancePlanStatus::from($model->status),
            createdBy: $model->created_by,
            assignedUserName: $model->relationLoaded('assignee') ? $model->assignee?->name : null,
            assetLabel: $model->relationLoaded('asset') ? $this->assetLabel($model) : null,
            locationName: $model->relationLoaded('location') ? $model->location?->name : null,
            checklistItems: $model->relationLoaded('checklistItems')
                ? $model->checklistItems->map(fn (MaintenancePlanChecklistItemModel $item) => $this->itemToDomain($item))->all()
                : [],
            createdAt: $this->toDate($model->created_at),
        );
    }

    public function fillModel(MaintenancePlan $plan, MaintenancePlanModel $model): MaintenancePlanModel
    {
        $model->company_id = $plan->companyId;
        $model->target_type = $plan->targetType->value;
        $model->asset_id = $plan->assetId;
        $model->location_id = $plan->locationId;
        $model->target_label = $plan->targetLabel;
        $model->title = $plan->title;
        $model->description = $plan->description;
        $model->interval_value = $plan->intervalValue;
        $model->interval_unit = $plan->intervalUnit->value;
        $model->next_due_at = $this->toCarbon($plan->nextDueAt);
        $model->last_completed_at = $this->toCarbon($plan->lastCompletedAt);
        $model->assigned_user_id = $plan->assignedUserId;
        $model->status = $plan->status->value;
        $model->created_by = $plan->createdBy;

        return $model;
    }

    public function itemToDomain(MaintenancePlanChecklistItemModel $model): MaintenancePlanChecklistItem
    {
        return new MaintenancePlanChecklistItem(
            id: $model->id,
            label: $model->label,
            isRequired: $model->is_required,
            sortOrder: $model->sort_order,
        );
    }

    private function assetLabel(MaintenancePlanModel $model): ?string
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
