<?php

namespace App\Assets\Application\UseCases;

use App\Assets\Domain\Entities\Asset;
use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchAssets
{
    public function __construct(private AssetRepository $assets) {}

    /**
     * @return Page<Asset>
     */
    public function execute(
        int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?AssetCategory $category = null,
        ?AssetStatus $status = null,
        ?int $locationId = null,
    ): Page {
        return $this->assets->search($companyId, $term, $page, $perPage, $category, $status, $locationId);
    }
}
