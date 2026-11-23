<?php

namespace App\Projects\Application\UseCases;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Projects\Domain\Entities\Project;
use App\Projects\Domain\Entities\ProjectTask;
use App\Projects\Domain\Enums\ProjectTaskStatus;
use App\Projects\Domain\Repositories\ProjectRepository;
use App\Projects\Domain\Repositories\ProjectTaskRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class MoveProjectTask
{
    public function __construct(
        private ProjectRepository $projects,
        private ProjectTaskRepository $tasks,
        private UserRepository $users,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $taskId,
        int $projectId,
        int $companyId,
        int $userId,
        ProjectTaskStatus $status,
        int $sortOrder,
    ): ProjectTask {
        $canViewAll = $this->canViewAll($userId);

        $project = $this->projects->findVisible($projectId, $companyId, $userId, $canViewAll);

        if ($project === null) {
            throw DomainException::because('Proyecto no encontrado.');
        }

        if ($project->status->isTerminal()) {
            throw DomainException::because('No se pueden mover tareas de un proyecto completado o cancelado.');
        }

        $task = $this->tasks->findVisible($taskId, $projectId, $companyId, $userId, $canViewAll);

        if ($task === null) {
            throw DomainException::because('Tarea no encontrada.');
        }

        $canManage = $this->canManageProject($userId, $project);

        if (! $canManage && $task->assignedUserId !== $userId) {
            throw DomainException::because('Solo puedes mover tareas asignadas a ti.');
        }

        $task->moveTo($status, $sortOrder);

        return $this->transactions->run(function () use ($task, $companyId): ProjectTask {
            $saved = $this->tasks->save($task);

            $this->audit->log(
                event: 'project_task.moved',
                description: "Se movió la tarea {$saved->title} a {$saved->status->label()}.",
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

    private function canManageProject(int $userId, Project $project): bool
    {
        $user = $this->users->find($userId);

        return $user !== null && (
            $user->isSuperAdmin() ||
            $user->primaryRole()?->value === RoleName::CompanyAdmin->value ||
            $user->id === $project->managerId
        );
    }
}
