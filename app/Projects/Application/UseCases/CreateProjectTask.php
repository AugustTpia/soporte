<?php

namespace App\Projects\Application\UseCases;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Projects\Application\DTOs\ProjectTaskInput;
use App\Projects\Domain\Entities\ProjectTask;
use App\Projects\Domain\Repositories\ProjectRepository;
use App\Projects\Domain\Repositories\ProjectTaskRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Repositories\TicketRepository;

final readonly class CreateProjectTask
{
    public function __construct(
        private ProjectRepository $projects,
        private ProjectTaskRepository $tasks,
        private TicketRepository $tickets,
        private UserRepository $users,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $projectId, ProjectTaskInput $input, int $actorId): ProjectTask
    {
        $project = $this->projects->findVisible($projectId, $companyId, $actorId, $this->canViewAll($actorId));

        if ($project === null) {
            throw DomainException::because('Proyecto no encontrado.');
        }

        if ($project->status->isTerminal()) {
            throw DomainException::because('No se pueden crear tareas en un proyecto completado o cancelado.');
        }

        if ($input->assignedUserId !== null) {
            $this->requireAssignableMember($projectId, $companyId, $input->assignedUserId);
        }

        if ($input->ticketId !== null) {
            $ticket = $this->tickets->find($input->ticketId, $companyId);

            if ($ticket === null) {
                throw DomainException::because('El ticket seleccionado no pertenece a esta empresa.');
            }

            if ($this->tasks->findByTicket($companyId, $input->ticketId) !== null) {
                throw DomainException::because('El ticket ya está vinculado a otra tarea.');
            }
        }

        $task = ProjectTask::create(
            projectId: $projectId,
            title: $input->title,
            description: $input->description,
            status: $input->status,
            priority: $input->priority,
            assignedUserId: $input->assignedUserId,
            dueDate: $input->dueDate,
            ticketId: $input->ticketId,
            sortOrder: $this->tasks->nextSortOrder($projectId, $input->status),
            createdBy: $actorId,
        );

        return $this->transactions->run(function () use ($task, $companyId): ProjectTask {
            $saved = $this->tasks->save($task);

            $this->audit->log(
                event: 'project_task.created',
                description: "Se creó la tarea {$saved->title}.",
                subjectType: ProjectTask::class,
                subjectId: $saved->id,
                newValues: $saved->auditValues(),
                companyId: $companyId,
            );

            return $saved;
        });
    }

    private function canViewAll(int $userId): bool
    {
        $user = $this->users->find($userId);

        return $user !== null && ($user->isSuperAdmin() || $user->primaryRole()?->value === RoleName::CompanyAdmin->value);
    }

    private function requireAssignableMember(int $projectId, int $companyId, int $userId): void
    {
        $memberIds = array_map(fn (array $member) => (int) $member['id'], $this->projects->members($projectId));

        if (! in_array($userId, $memberIds, true)) {
            throw DomainException::because('El responsable de la tarea no es miembro del proyecto.');
        }

        $user = $this->users->find($userId);

        if ($user === null || $user->companyId !== $companyId || ! $user->isActive()) {
            throw DomainException::because('El responsable de la tarea no pertenece a la empresa activa.');
        }

        $role = $user->primaryRole()?->value;

        if (in_array($role, [RoleName::Auditor->value, RoleName::ReadOnly->value], true)) {
            throw DomainException::because('No se puede asignar una tarea a un rol de solo lectura.');
        }
    }
}
