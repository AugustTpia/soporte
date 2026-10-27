<?php

namespace App\Departments\Infrastructure\Persistence\Eloquent\Mappers;

use App\Departments\Domain\Entities\Department;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;

final class DepartmentMapper
{
    public function toDomain(DepartmentModel $model): Department
    {
        return new Department(
            id: $model->id,
            companyId: $model->company_id,
            name: $model->name,
            slug: $model->slug,
            description: $model->description,
            isActive: (bool) $model->is_active,
            usersCount: (int) ($model->users_count ?? 0),
        );
    }

    public function fillModel(Department $department, DepartmentModel $model): DepartmentModel
    {
        $model->company_id = $department->companyId;
        $model->name = $department->name;
        $model->slug = $department->slug;
        $model->description = $department->description;
        $model->is_active = $department->isActive;

        return $model;
    }
}
