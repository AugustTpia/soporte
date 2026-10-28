<?php

namespace App\Support\Application\DTOs;

use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use DateTimeImmutable;

final readonly class TicketInput
{
    public function __construct(
        public TicketCategory $category,
        public string $subject,
        public string $description,
        public TicketPriority $priority,
        public ?string $requesterName = null,
        public ?string $requesterContact = null,
        public ?int $locationId = null,
        public ?int $assetId = null,
        public ?int $serviceAccountId = null,
        public ?int $clientContactId = null,
        public ?DateTimeImmutable $dueAt = null,
    ) {}
}
