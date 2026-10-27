<?php

namespace App\Locations\Application\UseCases;

use App\Locations\Domain\Entities\Location;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchLocations
{
    public function __construct(private LocationRepository $locations) {}

    /**
     * @return Page<Location>
     */
    public function execute(int $companyId, ?string $term, int $page = 1, int $perPage = 15, ?bool $active = null): Page
    {
        return $this->locations->search($companyId, $term, $page, $perPage, $active);
    }
}
