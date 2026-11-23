<?php

namespace App\Projects\Infrastructure\Persistence\Eloquent\Repositories;

use App\Projects\Domain\Entities\ProjectTask;
use App\Projects\Domain\Enums\ProjectTaskStatus;
use App\Projects\Domain\Repositories\ProjectTaskRepository;
use App\Projects\Infrastructure\Persistence\Eloquent\Mappers\ProjectTaskMapper;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectTaskModel;

final class EloquentProjectTaskRepository implements ProjectTaskRepository
{
    public function __construct(private readonly ProjectTaskMapper $mapper) {}

    /**
     * @return array<int, ProjectTask>
     */
    public function boardForProject(int $projectId, int $companyId, int $userId, bool $canViewAll): array
    {
        $query = ProjectTaskModel::query()
            ->select('project_tasks.*')
            ->join('projects', 'projects.id', '=', 'project_tasks.project_id')
            ->where('project_tasks.project_id', $projectId)
            ->where('projects.company_id', $companyId)
            ->with(['assignee', 'ticket'])
            ->when(! $canViewAll, fn ($q) => $q->whereHas('project.members', fn ($q) => $q->where('user_id', $userId)))
            ->orderByRaw("CASE project_tasks.status WHEN 'pending' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'blocked' THEN 3 WHEN 'completed' THEN 4 ELSE 5 END")
            ->orderBy('project_tasks.sort_order')
            ->orderBy('project_tasks.id');

        return $query->get()->map(fn (ProjectTaskModel $model) => $this->mapper->toDomain($model))->all();
    }

    public function findVisible(int $id, int $projectId, int $companyId, int $userId, bool $canViewAll): ?ProjectTask
    {
        $query = ProjectTaskModel::query()
            ->select('project_tasks.*')
            ->join('projects', 'projects.id', '=', 'project_tasks.project_id')
            ->where('project_tasks.id', $id)
            ->where('project_tasks.project_id', $projectId)
            ->where('projects.company_id', $companyId)
            ->with(['assignee', 'ticket'])
            ->when(! $canViewAll, fn ($q) => $q->whereHas('project.members', fn ($q) => $q->where('user_id', $userId)));

        $model = $query->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByTicket(int $companyId, int $ticketId, ?int $ignoreTaskId = null): ?ProjectTask
    {
        $query = ProjectTaskModel::query()
            ->select('project_tasks.*')
            ->join('projects', 'projects.id', '=', 'project_tasks.project_id')
            ->where('project_tasks.ticket_id', $ticketId)
            ->where('projects.company_id', $companyId)
            ->when($ignoreTaskId !== null, fn ($q) => $q->where('project_tasks.id', '!=', $ignoreTaskId))
            ->with(['assignee', 'ticket']);

        $model = $query->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(ProjectTask $task): ProjectTask
    {
        if ($task->id === null) {
            $model = new ProjectTaskModel;
        } else {
            $model = ProjectTaskModel::where('id', $task->id)
                ->where('project_id', $task->projectId)
                ->firstOrFail();
        }

        $this->mapper->fillModel($task, $model)->save();

        return $this->findVisible($model->id, $model->project_id, $task->projectId, 0, true) ?? $this->mapper->toDomain($model);
    }

    public function moveTask(int $taskId, int $projectId, int $companyId, int $userId, bool $canViewAll, ProjectTaskStatus $status, int $sortOrder): ?ProjectTask
    {
        $model = ProjectTaskModel::query()
            ->join('projects', 'projects.id', '=', 'project_tasks.project_id')
            ->where('project_tasks.id', $taskId)
            ->where('project_tasks.project_id', $projectId)
            ->where('projects.company_id', $companyId)
            ->when(! $canViewAll, fn ($q) => $q->whereHas('project.members', fn ($q) => $q->where('user_id', $userId)))
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        $model->status = $status->value;
        $model->sort_order = max(0, (int) $sortOrder);
        $model->save();

        return $this->findVisible($model->id, $model->project_id, $companyId, $userId, $canViewAll);
    }

    public function nextSortOrder(int $projectId, ProjectTaskStatus $status): int
    {
        $max = ProjectTaskModel::query()
            ->where('project_id', $projectId)
            ->where('status', $status->value)
            ->max('sort_order');

        return (int) ($max ?? -1) + 1;
    }
}
