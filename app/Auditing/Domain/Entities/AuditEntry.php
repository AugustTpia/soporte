<?php

namespace App\Auditing\Domain\Entities;

use DateTimeImmutable;

final readonly class AuditEntry
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $id,
        public ?int $companyId,
        public ?string $companyName,
        public ?int $actorId,
        public ?string $actorName,
        public string $event,
        public string $description,
        public ?string $subjectType,
        public int|string|null $subjectId,
        public ?array $oldValues,
        public ?array $newValues,
        public ?array $metadata,
        public ?string $ipAddress,
        public DateTimeImmutable $createdAt,
    ) {}
}
