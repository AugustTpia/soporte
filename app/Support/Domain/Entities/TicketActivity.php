<?php

namespace App\Support\Domain\Entities;

use App\Support\Domain\Enums\TicketActivityType;
use DateTimeImmutable;

final class TicketActivity
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?int $id,
        public int $ticketId,
        public ?int $actorId,
        public ?string $actorName,
        public TicketActivityType $type,
        public ?string $body,
        public array $metadata,
        public DateTimeImmutable $createdAt,
    ) {}
}
