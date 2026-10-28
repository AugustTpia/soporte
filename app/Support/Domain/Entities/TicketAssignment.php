<?php

namespace App\Support\Domain\Entities;

use DateTimeImmutable;

final class TicketAssignment
{
    public function __construct(
        public ?int $id,
        public int $ticketId,
        public int $userId,
        public ?string $userName,
        public DateTimeImmutable $assignedAt,
        public ?DateTimeImmutable $unassignedAt = null,
        public ?int $assignedBy = null,
        public ?int $unassignedBy = null,
        public ?string $reason = null,
    ) {}

    public function isActive(): bool
    {
        return $this->unassignedAt === null;
    }
}
