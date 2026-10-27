<?php

namespace App\Assets\Application\DTOs;

use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use DateTimeImmutable;

final readonly class AssetInput
{
    public function __construct(
        public string $internalCode,
        public AssetCategory $category,
        public int $locationId,
        public AssetStatus $status,
        public ?string $hostname = null,
        public ?string $brand = null,
        public ?string $model = null,
        public ?string $serialNumber = null,
        public ?int $assignedUserId = null,
        public ?string $operatingSystem = null,
        public ?string $ipAddress = null,
        public ?DateTimeImmutable $purchaseDate = null,
        public ?DateTimeImmutable $warrantyUntil = null,
        public ?string $notes = null,
    ) {}
}
