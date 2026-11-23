<?php

namespace App\Projects\Infrastructure\Persistence\Eloquent\Repositories;

use App\Projects\Application\DTOs\ProjectFilters;
use App\Projects\Domain\Entities\Project;
use App\Projects\Domain\Repositories\ProjectRepository;
use App\Projects\Infrastructure\Persistence\Eloquent\Mappers\ProjectMapper;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectMemberModel;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentProjectRepository implements ProjectRepository
{
    public function __construct(private readonly ProjectMapper $mapper) {}

    /**
     * @return Page<Project>
     */
    public function searchVisible(int $companyId, int $userId, bool $canViewAll, ProjectFilters $filters, int $page = 1, int $perPage = 15): Page
    {
        $query = ProjectModel::query()
            ->with(['manager', 'creator', 'members.user', 'tasks'])
            ->where('company_id', $companyId)
            ->when(filled($filters->term), function ($query) use ($filters): void {
                $term = $filters->term;
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->when($filters->managerId !== null, fn ($query) => $query->where('manager_id', $filters->managerId))
            ->when(! $canViewAll, fn ($query) => $query->whereHas('members', fn ($q) => $q->where('user_id', $userId)))
            ->orderBy('name');

        $paginator = $query->paginate(max(1, $perPage), page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (ProjectModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function findForUpdate(int $id, int $companyId): ?Project
    {
        $model = ProjectModel::query()
            ->with(['manager', 'creator'])
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findVisible(int $id, int $companyId, int $userId, bool $canViewAll): ?Project
    {
        $query = ProjectModel::query()
            ->with(['manager', 'creator', 'members.user'])
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->when(! $canViewAll, fn ($q) => $q->whereHas('members', fn ($q) => $q->where('user_id', $userId)));

        $model = $query->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(Project $project): Project
    {
        $model = $project->id === null
            ? new ProjectModel
            : ProjectModel::query()->where('company_id', $project->companyId)->findOrFail($project->id);

        $this->mapper->fillModel($project, $model)->save();

        return $this->findVisible($model->id, $model->company_id, 0, true) ?? $this->mapper->toDomain($model);
    }

    /**
     * @param  array<int, int>  $userIds
     */
    public function syncMembers(int $projectId, array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter($userIds, fn ($id) => $id > 0)));

        ProjectMemberModel::query()
            ->where('project_id', $projectId)
            ->whereNotIn('user_id', $userIds)
            ->delete();

        $existing = ProjectMemberModel::query()
            ->where('project_id', $projectId)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        foreach ($userIds as $userId) {
            if ($existing->has($userId)) {
                continue;
            }

            ProjectMemberModel::query()->create([
                'project_id' => $projectId,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function members(int $projectId): array
    {
        return ProjectMemberModel::query()
            ->with('user')
            ->where('project_id', $projectId)
            ->get()
            ->map(fn (ProjectMemberModel $member) => [
                'id' => $member->user_id,
                'name' => $member->user?->name,
            ])
            ->all();
    }

    public function hasAnyVisible(int $companyId, int $userId, bool $canViewAll): bool
    {
        $query = ProjectModel::query()->where('company_id', $companyId);

        return $canViewAll
            ? $query->exists()
            : $query->whereHas('members', fn ($q) => $q->where('user_id', $userId))->exists();
    }
}
