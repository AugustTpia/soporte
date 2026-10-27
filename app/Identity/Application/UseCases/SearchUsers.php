<?php

namespace App\Identity\Application\UseCases;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\UserRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchUsers
{
    public function __construct(private UserRepository $users) {}

    /**
     * @return Page<User>
     */
    public function execute(
        ?int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?UserStatus $status = null,
        ?string $role = null,
        ?int $departmentId = null,
    ): Page {
        return $this->users->search($companyId, $term, $page, $perPage, $status, $role, $departmentId);
    }
}
