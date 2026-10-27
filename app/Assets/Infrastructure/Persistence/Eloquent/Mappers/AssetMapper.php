<?php

namespace App\Assets\Infrastructure\Persistence\Eloquent\Mappers;

use App\Assets\Domain\Entities\Asset;
use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

final class AssetMapper
{
    public function toDomain(AssetModel $model): Asset
    {
        return new Asset(
            id: $model->id,
            companyId: $model->company_id,
            internalCode: $model->internal_code,
            hostname: $model->hostname,
            category: AssetCategory::from($model->category),
            brand: $model->brand,
            model: $model->model,
            serialNumber: $model->serial_number,
            locationId: $model->location_id,
            assignedUserId: $model->assigned_user_id,
            status: AssetStatus::from($model->status),
            operatingSystem: $model->operating_system,
            ipAddress: $model->ip_address,
            purchaseDate: $this->toDate($model->purchase_date),
            warrantyUntil: $this->toDate($model->warranty_until),
            notes: $model->notes,
            createdBy: $model->created_by,
            locationName: $model->relationLoaded('location') ? $model->location->name : null,
            assignedUserName: $model->relationLoaded('assignedUser') ? $model->assignedUser?->name : null,
        );
    }

    public function fillModel(Asset $asset, AssetModel $model): AssetModel
    {
        $model->company_id = $asset->companyId;
        $model->internal_code = $asset->internalCode;
        $model->hostname = $asset->hostname;
        $model->category = $asset->category->value;
        $model->brand = $asset->brand;
        $model->model = $asset->model;
        $model->serial_number = $asset->serialNumber;
        $model->location_id = $asset->locationId;
        $model->assigned_user_id = $asset->assignedUserId;
        $model->status = $asset->status->value;
        $model->operating_system = $asset->operatingSystem;
        $model->ip_address = $asset->ipAddress;
        $model->purchase_date = $asset->purchaseDate === null
            ? null
            : CarbonImmutable::createFromInterface($asset->purchaseDate);
        $model->warranty_until = $asset->warrantyUntil === null
            ? null
            : CarbonImmutable::createFromInterface($asset->warrantyUntil);
        $model->notes = $asset->notes;
        $model->created_by = $asset->createdBy;

        return $model;
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
