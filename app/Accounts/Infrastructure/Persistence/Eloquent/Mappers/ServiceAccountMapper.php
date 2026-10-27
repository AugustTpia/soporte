<?php

namespace App\Accounts\Infrastructure\Persistence\Eloquent\Mappers;

use App\Accounts\Domain\Entities\ServiceAccount;
use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountAssetLinkModel;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;

final class ServiceAccountMapper
{
    public function __construct(private readonly AccountAssetLinkMapper $links) {}

    public function toDomain(ServiceAccountModel $model): ServiceAccount
    {
        return new ServiceAccount(
            id: $model->id,
            companyId: $model->company_id,
            service: ServiceType::from($model->service),
            username: $model->username,
            instance: $model->instance,
            ownerName: $model->owner_name,
            status: ServiceAccountStatus::from($model->status),
            responsibleUserId: $model->responsible_user_id,
            notes: $model->notes,
            createdBy: $model->created_by,
            responsibleUserName: $model->relationLoaded('responsibleUser') ? $model->responsibleUser?->name : null,
            links: $model->relationLoaded('links')
                ? $model->links->map(fn (ServiceAccountAssetLinkModel $link) => $this->links->toDomain($link))->all()
                : [],
        );
    }

    public function fillModel(ServiceAccount $account, ServiceAccountModel $model): ServiceAccountModel
    {
        $model->company_id = $account->companyId;
        $model->service = $account->service->value;
        $model->username = $account->username;
        $model->instance = $account->instance;
        $model->owner_name = $account->ownerName;
        $model->status = $account->status->value;
        $model->responsible_user_id = $account->responsibleUserId;
        $model->notes = $account->notes;
        $model->created_by = $account->createdBy;

        return $model;
    }
}
