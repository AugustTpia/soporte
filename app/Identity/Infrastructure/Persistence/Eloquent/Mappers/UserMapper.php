<?php

namespace App\Identity\Infrastructure\Persistence\Eloquent\Mappers;

use App\Identity\Domain\Entities\User as UserEntity;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Infrastructure\Persistence\Eloquent\User as UserModel;
use Carbon\CarbonImmutable;

final class UserMapper
{
    public function toDomain(UserModel $model): UserEntity
    {
        return new UserEntity(
            id: $model->id,
            companyId: $model->company_id,
            name: $model->name,
            email: $model->email,
            jobTitle: $model->job_title,
            phone: $model->phone,
            status: UserStatus::from($model->status),
            roles: $model->getRoleNames()->all(),
            departmentIds: $model->departments->pluck('id')->all(),
            departmentNames: $model->departments->pluck('name')->all(),
            companyName: $model->company?->name,
            createdAt: $model->created_at?->toDateTimeImmutable(),
            lastLoginAt: $model->last_login_at?->toDateTimeImmutable(),
        );
    }

    public function fillModel(UserEntity $user, UserModel $model, ?string $plainPassword = null): UserModel
    {
        $model->company_id = $user->companyId;
        $model->name = $user->name;
        $model->email = $user->email;
        $model->job_title = $user->jobTitle;
        $model->phone = $user->phone;
        $model->status = $user->status->value;
        $model->deactivated_at = $user->isActive() ? null : CarbonImmutable::now();

        if ($plainPassword !== null) {
            $model->password = $plainPassword;
        }

        return $model;
    }
}
