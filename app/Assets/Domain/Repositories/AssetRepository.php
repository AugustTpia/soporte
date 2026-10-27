<?php

namespace App\Assets\Domain\Repositories;

use App\Assets\Domain\Entities\Asset;
use App\Assets\Domain\Entities\AssetAssignment;
use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Shared\Domain\Pagination\Page;

interface AssetRepository
{
    /**
     * @return Page<Asset>
     */
    public function search(
        int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?AssetCategory $category = null,
        ?AssetStatus $status = null,
        ?int $locationId = null,
    ): Page;

    public function find(int $id, int $companyId): ?Asset;

    /**
     * @return array<int, Asset>
     */
    public function forCompany(int $companyId): array;

    public function save(Asset $asset): Asset;

    public function internalCodeExists(int $companyId, string $internalCode, ?int $ignoreId = null): bool;

    public function hostnameExists(int $companyId, string $hostname, ?int $ignoreId = null): bool;

    public function closeCurrentAssignment(int $assetId): void;

    public function recordAssignment(
        int $assetId,
        int $locationId,
        ?int $assignedUserId,
        ?int $createdBy,
        ?string $notes = null,
    ): void;

    /**
     * @return array<int, AssetAssignment>
     */
    public function assignmentHistory(int $assetId): array;
}
