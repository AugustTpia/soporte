<?php

namespace App\Assets\Infrastructure\Persistence\Eloquent\Mappers;

use App\Assets\Domain\Entities\AssetAssignment;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetAssignmentModel;
use DateTimeImmutable;
use DateTimeInterface;

final class AssetAssignmentMapper
{
    public function toDomain(AssetAssignmentModel $model): AssetAssignment
    {
        return new AssetAssignment(
            id: $model->id,
            assetId: $model->asset_id,
            locationId: $model->location_id,
            assignedUserId: $model->assigned_user_id,
            locationName: $model->relationLoaded('location') ? $model->location->name : null,
            assignedUserName: $model->relationLoaded('assignedUser') ? $model->assignedUser?->name : null,
            assignedAt: $this->toDate($model->assigned_at) ?? new DateTimeImmutable,
            releasedAt: $this->toDate($model->released_at),
            notes: $model->notes,
        );
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
