<?php

namespace App\Accounts\Domain\Entities;

use DateTimeImmutable;

final readonly class AccountAssetLink
{
    public function __construct(
        public int $id,
        public int $serviceAccountId,
        public int $assetId,
        public ?string $assetInternalCode,
        public ?string $assetHostname,
        public DateTimeImmutable $linkedAt,
        public ?DateTimeImmutable $unlinkedAt,
        public ?string $reason,
        public ?string $unlinkReason,
    ) {}

    public function isActive(): bool
    {
        return $this->unlinkedAt === null;
    }
}
