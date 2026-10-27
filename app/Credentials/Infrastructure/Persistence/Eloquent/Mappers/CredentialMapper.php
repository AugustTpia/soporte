<?php

namespace App\Credentials\Infrastructure\Persistence\Eloquent\Mappers;

use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;

final class CredentialMapper
{
    public function toDomain(CredentialModel $model): Credential
    {
        $account = $model->account;

        return new Credential(
            id: $model->id,
            companyId: $model->company_id,
            serviceAccountId: $model->service_account_id,
            accountUsername: $account->username,
            accountInstance: $account->instance,
            label: $model->label,
            kind: CredentialKind::from($model->kind),
            status: CredentialStatus::from($model->status),
            hasSecret: $model->relationLoaded('secret') && $model->secret !== null,
            lastRotatedAt: $model->last_rotated_at?->toDateTimeImmutable(),
            createdBy: $model->created_by,
        );
    }

    public function fillModel(Credential $credential, CredentialModel $model): CredentialModel
    {
        $model->company_id = $credential->companyId;
        $model->service_account_id = $credential->serviceAccountId;
        $model->label = $credential->label;
        $model->kind = $credential->kind->value;
        $model->status = $credential->status->value;
        $model->created_by = $credential->createdBy;

        return $model;
    }
}
