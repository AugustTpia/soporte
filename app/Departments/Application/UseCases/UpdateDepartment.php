<?php

namespace App\Departments\Application\UseCases;

use App\Departments\Application\DTOs\DepartmentInput;
use App\Departments\Domain\Entities\Department;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;
use Illuminate\Support\Str;

final readonly class UpdateDepartment
{
    public function __construct(
        private DepartmentRepository $departments,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, int $departmentId, DepartmentInput $input): Department
    {
        $department = $this->departments->find($departmentId, $companyId)
            ?? throw DomainException::because('El departamento no existe en esta empresa.');

        $slug = Str::slug($input->name);

        if ($this->departments->slugExists($companyId, $slug, $department->id)) {
            throw DomainException::because('Ya existe un departamento con un nombre similar en esta empresa.');
        }

        $before = [
            'name' => $department->name,
            'slug' => $department->slug,
            'description' => $department->description,
            'is_active' => $department->isActive,
        ];

        $department->updateDetails($input->name, $slug, $input->description);

        $input->isActive ? $department->activate() : $department->deactivate();

        $saved = $this->departments->save($department);

        $this->audit->log(
            event: 'department.updated',
            description: "Se actualizó el departamento {$saved->name}.",
            subjectType: DepartmentModel::class,
            subjectId: $saved->id,
            oldValues: $before,
            newValues: [
                'name' => $saved->name,
                'slug' => $saved->slug,
                'description' => $saved->description,
                'is_active' => $saved->isActive,
            ],
            companyId: $companyId,
        );

        return $saved;
    }
}
