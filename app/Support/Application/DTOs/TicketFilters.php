<?php

namespace App\Support\Application\DTOs;

use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use DateTimeImmutable;

final readonly class TicketFilters
{
    public function __construct(
        public ?string $term = null,
        public ?TicketStatus $status = null,
        public ?TicketPriority $priority = null,
        public ?TicketCategory $category = null,
        public ?int $assignedTo = null,
        public bool $unassigned = false,
        public bool $unresolved = false,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
    ) {}
}
