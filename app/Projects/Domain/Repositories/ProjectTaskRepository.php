<?php

namespace App\Projects\Domain\Repositories;

use App\Projects\Domain\Entities\ProjectTask;
use App\Projects\Domain\Enums\ProjectTaskStatus;

interface ProjectTaskRepository
{
    /**
     * @return array<int, ProjectTask>
     */
    public function boardForProject(int $projectId, int $companyId, int $userId, bool $canViewAll): array;

    public function findVisible(int $id, int $projectId, int $companyId, int $userId, bool $canViewAll): ?ProjectTask;

    public function findByTicket(int $companyId, int $ticketId, ?int $ignoreTaskId = null): ?ProjectTask;

    public function save(ProjectTask $task): ProjectTask;

    public function moveTask(int $taskId, int $projectId, int $companyId, int $userId, bool $canViewAll, ProjectTaskStatus $status, int $sortOrder): ?ProjectTask;

    public function nextSortOrder(int $projectId, ProjectTaskStatus $status): int;
}
