<?php

namespace App\Assets\Domain\Entities;

use DateTimeImmutable;

final readonly class AssetAssignment
{
    public function __construct(
        public int $id,
        public int $assetId,
        public int $locationId,
        public ?int $assignedUserId,
        public ?string $locationName,
        public ?string $assignedUserName,
        public DateTimeImmutable $assignedAt,
        public ?DateTimeImmutable $releasedAt,
        public ?string $notes,
    ) {}

    public function isActive(): bool
    {
        return $this->releasedAt === null;
    }
}
