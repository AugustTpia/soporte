<?php

namespace App\Projects\Domain\Repositories;

use App\Projects\Application\DTOs\ProjectFilters;
use App\Projects\Domain\Entities\Project;
use App\Shared\Domain\Pagination\Page;

interface ProjectRepository
{
    /**
     * @return Page<Project>
     */
    public function searchVisible(int $companyId, int $userId, bool $canViewAll, ProjectFilters $filters, int $page = 1, int $perPage = 15): Page;

    public function findForUpdate(int $id, int $companyId): ?Project;

    public function findVisible(int $id, int $companyId, int $userId, bool $canViewAll): ?Project;

    public function save(Project $project): Project;

    /**
     * @param  array<int, int>  $userIds
     */
    public function syncMembers(int $projectId, array $userIds): void;

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function members(int $projectId): array;

    public function hasAnyVisible(int $companyId, int $userId, bool $canViewAll): bool;
}
