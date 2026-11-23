<?php

namespace App\Projects\Application\UseCases;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Projects\Application\DTOs\ProjectInput;
use App\Projects\Domain\Entities\Project;
use App\Projects\Domain\Repositories\ProjectRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class UpdateProject
{
    public function __construct(
        private ProjectRepository $projects,
        private UserRepository $users,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $projectId, ProjectInput $input, int $actorId): Project
    {
        $project = $this->projects->findForUpdate($projectId, $companyId);

        if ($project === null) {
            throw DomainException::because('Proyecto no encontrado.');
        }

        $manager = $this->requireActiveCompanyUser($companyId, $input->managerId);
        $this->requireOperationalRole($manager);

        $memberIds = array_unique(array_merge([$input->managerId], $input->memberIds));
        foreach ($memberIds as $memberId) {
            $this->requireActiveCompanyUser($companyId, $memberId);
        }

        $project->updateDetails(
            name: $input->name,
            description: $input->description,
            status: $input->status,
            managerId: $input->managerId,
            startDate: $input->startDate,
            dueDate: $input->dueDate,
        );

        return $this->transactions->run(function () use ($project, $memberIds): Project {
            $saved = $this->projects->save($project);
            $this->projects->syncMembers($saved->id, $memberIds);

            $this->audit->log(
                event: 'project.updated',
                description: "Se actualizó el proyecto {$saved->name}.",
                subjectType: Project::class,
                subjectId: $saved->id,
                newValues: $saved->auditValues(),
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }

    private function requireActiveCompanyUser(int $companyId, int $userId): User
    {
        $user = $this->users->find($userId);

        if ($user === null || $user->companyId !== $companyId || ! $user->isActive()) {
            throw DomainException::because('El usuario seleccionado no pertenece a la empresa activa.');
        }

        return $user;
    }

    private function requireOperationalRole(User $user): void
    {
        $role = $user->primaryRole()?->value;

        if (! in_array($role, [RoleName::ServiceManager->value, RoleName::Engineer->value, RoleName::Technician->value, RoleName::CompanyAdmin->value], true)) {
            throw DomainException::because('El responsable del proyecto debe ser un usuario operativo.');
        }
    }
}
