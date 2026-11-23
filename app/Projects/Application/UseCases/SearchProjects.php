<?php

namespace App\Projects\Application\UseCases;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Projects\Application\DTOs\ProjectFilters;
use App\Projects\Domain\Entities\Project;
use App\Projects\Domain\Repositories\ProjectRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchProjects
{
    public function __construct(
        private ProjectRepository $projects,
        private UserRepository $users,
    ) {}

    /**
     * @return Page<Project>
     */
    public function execute(int $companyId, int $userId, ProjectFilters $filters, int $page = 1, int $perPage = 15): Page
    {
        $user = $this->users->find($userId);
        $canViewAll = $user !== null && ($user->isSuperAdmin() || $user->primaryRole()?->value === RoleName::CompanyAdmin->value);

        return $this->projects->searchVisible(
            companyId: $companyId,
            userId: $userId,
            canViewAll: $canViewAll,
            filters: $filters,
            page: $page,
            perPage: $perPage,
        );
    }
}
