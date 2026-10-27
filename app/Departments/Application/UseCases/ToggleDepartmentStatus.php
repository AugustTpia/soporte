<?php

namespace App\Departments\Application\UseCases;

use App\Departments\Domain\Entities\Department;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class ToggleDepartmentStatus
{
    public function __construct(
        private DepartmentRepository $departments,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, int $departmentId): Department
    {
        $department = $this->departments->find($departmentId, $companyId)
            ?? throw DomainException::because('El departamento no existe en esta empresa.');

        $department->isActive ? $department->deactivate() : $department->activate();
        $saved = $this->departments->save($department);

        $this->audit->log(
            event: 'department.status_changed',
            description: "Se cambió el estado del departamento {$saved->name}.",
            subjectType: DepartmentModel::class,
            subjectId: $saved->id,
            oldValues: ['is_active' => ! $saved->isActive],
            newValues: ['is_active' => $saved->isActive],
            companyId: $companyId,
        );

        return $saved;
    }
}
