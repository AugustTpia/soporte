<?php

namespace App\Identity\Application\UseCases;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Identity\Application\DTOs\UserInput;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Identity\Infrastructure\Persistence\Eloquent\User as UserModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class CreateUser
{
    public function __construct(
        private UserRepository $users,
        private CompanyRepository $companies,
        private DepartmentRepository $departments,
        private AuditLogger $audit,
    ) {}

    public function execute(
        int $actorId,
        bool $actorIsSuperAdmin,
        ?int $actorCompanyId,
        UserInput $input,
    ): User {
        $companyId = $actorIsSuperAdmin ? $input->companyId : $actorCompanyId;
        $roles = $this->normalizeRoles($input->roles);

        if (! $actorIsSuperAdmin && in_array(true, array_map(fn (RoleName $role) => $role->isPrivileged(), $roles), true)) {
            throw DomainException::because('No tienes permiso para asignar ese rol.');
        }

        if (! $actorIsSuperAdmin && $companyId === null) {
            throw DomainException::because('El usuario debe pertenecer a una empresa.');
        }

        if ($companyId !== null) {
            $company = $this->companies->find($companyId)
                ?? throw DomainException::because('La empresa seleccionada no existe.');

            if (! $company->isActive()) {
                throw DomainException::because('No se pueden crear usuarios en una empresa inactiva.');
            }
        }

        $hasSuperAdmin = in_array(RoleName::SuperAdmin, $roles, true);

        if (! $hasSuperAdmin && $companyId === null) {
            throw DomainException::because('Selecciona la empresa a la que pertenece el usuario.');
        }

        if ($this->users->emailExists($input->email)) {
            throw DomainException::because('Ya existe un usuario con ese correo electrónico.');
        }

        if ($input->password === null || mb_strlen($input->password) < 8) {
            throw DomainException::because('La contraseña debe tener al menos 8 caracteres.');
        }

        $departmentIds = $this->validDepartmentIds($input->departmentIds, $companyId);

        $user = User::create(
            companyId: $companyId,
            name: $input->name,
            email: $input->email,
            jobTitle: $input->jobTitle,
            phone: $input->phone,
            roles: array_map(fn (RoleName $role) => $role->value, $roles),
            departmentIds: $departmentIds,
        );

        $saved = $this->users->save($user, $input->password);
        $this->users->syncRoles($saved->id, $user->roles);
        $this->users->syncDepartments($saved->id, $departmentIds);
        $saved = $this->users->find($saved->id);

        $this->audit->log(
            event: 'user.created',
            description: "Se creó el usuario {$saved->name}.",
            subjectType: UserModel::class,
            subjectId: $saved->id,
            newValues: $this->safeValues($saved),
            companyId: $saved->companyId,
        );

        return $saved;
    }

    /**
     * @param  array<int, string>  $roles
     * @return array<int, RoleName>
     */
    private function normalizeRoles(array $roles): array
    {
        $normalized = [];

        foreach (array_unique($roles) as $role) {
            $roleName = RoleName::tryFrom($role)
                ?? throw DomainException::because("El rol {$role} no es válido.");

            $normalized[] = $roleName;
        }

        if ($normalized === []) {
            throw DomainException::because('Selecciona al menos un rol para el usuario.');
        }

        return $normalized;
    }

    /**
     * @param  array<int, int>  $departmentIds
     * @return array<int, int>
     */
    private function validDepartmentIds(array $departmentIds, ?int $companyId): array
    {
        $departmentIds = array_values(array_unique(array_map('intval', $departmentIds)));

        if ($departmentIds === []) {
            return [];
        }

        if ($companyId === null || ! $this->departments->allBelongToCompany($departmentIds, $companyId)) {
            throw DomainException::because('Uno o más departamentos no pertenecen a la empresa seleccionada.');
        }

        return $departmentIds;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeValues(User $user): array
    {
        return [
            'company_id' => $user->companyId,
            'name' => $user->name,
            'email' => $user->email,
            'job_title' => $user->jobTitle,
            'phone' => $user->phone,
            'status' => $user->status->value,
            'roles' => $user->roles,
            'department_ids' => $user->departmentIds,
        ];
    }
}
