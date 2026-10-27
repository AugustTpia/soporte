<?php

namespace App\Departments\Application\UseCases;

use App\Departments\Application\DTOs\DepartmentInput;
use App\Departments\Domain\Entities\Department;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;
use Illuminate\Support\Str;

final readonly class CreateDepartment
{
    public function __construct(
        private DepartmentRepository $departments,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, DepartmentInput $input): Department
    {
        $slug = Str::slug($input->name);

        if ($this->departments->slugExists($companyId, $slug)) {
            throw DomainException::because('Ya existe un departamento con un nombre similar en esta empresa.');
        }

        $department = Department::create(
            companyId: $companyId,
            name: $input->name,
            slug: $slug,
            description: $input->description,
        );

        if (! $input->isActive) {
            $department->deactivate();
        }

        $saved = $this->departments->save($department);

        $this->audit->log(
            event: 'department.created',
            description: "Se creó el departamento {$saved->name}.",
            subjectType: DepartmentModel::class,
            subjectId: $saved->id,
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
