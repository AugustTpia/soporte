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

final readonly class UpdateProjectTask
{
    public function __construct(
        private ProjectRepository $projects,
        private ProjectTaskRepository $tasks,
        private TicketRepository $tickets,
        private UserRepository $users,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $projectId,
        int $taskId,
        ProjectTaskInput $input,
        int $actorId,
        bool $actorCanManageProject,
    ): ProjectTask {
        $canViewAll = $this->canViewAll($actorId);

        $project = $this->projects->findVisible($projectId, $companyId, $actorId, $canViewAll);

        if ($project === null) {
            throw DomainException::because('Proyecto no encontrado.');
        }

        if ($project->status->isTerminal()) {
            throw DomainException::because('No se pueden editar tareas de un proyecto completado o cancelado.');
        }

        $task = $this->tasks->findVisible($taskId, $projectId, $companyId, $actorId, $canViewAll);

        if ($task === null) {
            throw DomainException::because('Tarea no encontrada.');
        }

        if (! $actorCanManageProject && $task->assignedUserId !== $actorId) {
            throw DomainException::because('Solo puedes editar tareas asignadas a ti.');
        }

        if (! $actorCanManageProject) {
            $input = new ProjectTaskInput(
                title: $input->title,
                description: $input->description,
                status: $input->status,
                priority: $input->priority,
                assignedUserId: $task->assignedUserId,
                dueDate: $input->dueDate,
                ticketId: $task->ticketId,
            );
        } elseif ($input->ticketId !== null) {
            $ticket = $this->tickets->find($input->ticketId, $companyId);

            if ($ticket === null) {
                throw DomainException::because('El ticket seleccionado no pertenece a esta empresa.');
            }

            if ($this->tasks->findByTicket($companyId, $input->ticketId, $task->id) !== null) {
                throw DomainException::because('El ticket ya está vinculado a otra tarea.');
            }
        }

        $task->updateDetails(
            title: $input->title,
            description: $input->description,
            status: $input->status,
            priority: $input->priority,
            assignedUserId: $input->assignedUserId,
            dueDate: $input->dueDate,
            ticketId: $input->ticketId,
        );

        return $this->transactions->run(function () use ($task, $companyId): ProjectTask {
            $saved = $this->tasks->save($task);

            $this->audit->log(
                event: 'project_task.updated',
                description: "Se actualizó la tarea {$saved->title}.",
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
}
