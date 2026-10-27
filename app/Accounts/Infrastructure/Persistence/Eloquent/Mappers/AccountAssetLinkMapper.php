<?php

namespace App\Accounts\Infrastructure\Persistence\Eloquent\Mappers;

use App\Accounts\Domain\Entities\AccountAssetLink;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountAssetLinkModel;
use DateTimeImmutable;
use DateTimeInterface;

final class AccountAssetLinkMapper
{
    public function toDomain(ServiceAccountAssetLinkModel $model): AccountAssetLink
    {
        return new AccountAssetLink(
            id: $model->id,
            serviceAccountId: $model->service_account_id,
            assetId: $model->asset_id,
            assetInternalCode: $model->relationLoaded('asset') ? $model->asset?->internal_code : null,
            assetHostname: $model->relationLoaded('asset') ? $model->asset?->hostname : null,
            linkedAt: $this->toDate($model->linked_at) ?? new DateTimeImmutable,
            unlinkedAt: $this->toDate($model->unlinked_at),
            reason: $model->reason,
            unlinkReason: $model->unlink_reason,
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
