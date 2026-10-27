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

final readonly class UpdateUser
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
        int $userId,
        UserInput $input,
    ): User {
        $target = $this->users->find($userId)
            ?? throw DomainException::because('El usuario no existe.');

        $this->guardTarget($target, $actorIsSuperAdmin, $actorCompanyId);

        $companyId = $actorIsSuperAdmin ? $input->companyId : $actorCompanyId;
        $roles = $this->normalizeRoles($input->roles);

        if (! $actorIsSuperAdmin && in_array(true, array_map(fn (RoleName $role) => $role->isPrivileged(), $roles), true)) {
            throw DomainException::because('No tienes permiso para asignar ese rol.');
        }

        if ($companyId !== null) {
            $company = $this->companies->find($companyId)
                ?? throw DomainException::because('La empresa seleccionada no existe.');

            if (! $company->isActive()) {
                throw DomainException::because('No se puede asignar un usuario a una empresa inactiva.');
            }
        }

        if (! in_array(RoleName::SuperAdmin, $roles, true) && $companyId === null) {
            throw DomainException::because('El usuario debe pertenecer a una empresa.');
        }

        if ($target->isSuperAdmin() && ! in_array(RoleName::SuperAdmin, $roles, true) && $this->users->countSuperAdmins() <= 1) {
            throw DomainException::because('No puedes retirar el rol al último superadministrador.');
        }

        if ($this->users->emailExists($input->email, $target->id)) {
            throw DomainException::because('Ya existe un usuario con ese correo electrónico.');
        }

        if ($input->password !== null && $input->password !== '' && mb_strlen($input->password) < 8) {
            throw DomainException::because('La contraseña debe tener al menos 8 caracteres.');
        }

        $departmentIds = $this->validDepartmentIds($input->departmentIds, $companyId);

        $before = $this->safeValues($target);

        $target->updateProfile(
            companyId: $companyId,
            name: $input->name,
            email: $input->email,
            jobTitle: $input->jobTitle,
            phone: $input->phone,
            status: $target->status,
            roles: array_map(fn (RoleName $role) => $role->value, $roles),
            departmentIds: $departmentIds,
        );

        $saved = $this->users->save($target, $input->password ?: null);
        $this->users->syncRoles($saved->id, $target->roles);
        $this->users->syncDepartments($saved->id, $departmentIds);
        $saved = $this->users->find($saved->id);

        $this->audit->log(
            event: 'user.updated',
            description: "Se actualizó el usuario {$saved->name}.",
            subjectType: UserModel::class,
            subjectId: $saved->id,
            oldValues: $before,
            newValues: $this->safeValues($saved),
            companyId: $saved->companyId,
        );

        return $saved;
    }

    private function guardTarget(User $target, bool $actorIsSuperAdmin, ?int $actorCompanyId): void
    {
        if ($actorIsSuperAdmin) {
            return;
        }

        if ($target->companyId !== $actorCompanyId) {
            throw DomainException::because('No puedes administrar usuarios de otra empresa.');
        }

        if ($target->isSuperAdmin() || in_array(RoleName::CompanyAdmin->value, $target->roles, true)) {
            throw DomainException::because('Solo un superadministrador puede modificar a este usuario.');
        }
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
