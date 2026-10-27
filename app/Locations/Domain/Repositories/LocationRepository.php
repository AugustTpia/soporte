<?php

namespace App\Locations\Domain\Repositories;

use App\Locations\Domain\Entities\Location;
use App\Shared\Domain\Pagination\Page;

interface LocationRepository
{
    /**
     * @return Page<Location>
     */
    public function search(int $companyId, ?string $term, int $page = 1, int $perPage = 15, ?bool $active = null): Page;

    public function find(int $id, int $companyId): ?Location;

    public function save(Location $location): Location;

    public function slugExists(int $companyId, string $slug, ?int $ignoreId = null): bool;
}
