<?php

namespace App\BusTicketCancellations\Application\DTOs;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use DateTimeImmutable;

final readonly class BusTicketCancellationInput
{
    public function __construct(
        public ?int $supportTicketId,
        public string $ticketNumber,
        public DateTimeImmutable $requestedAt,
        public ?DateTimeImmutable $executedAt,
        public ?DateTimeImmutable $originalTicketAt,
        public ?int $amountCents,
        public int $operatorId,
        public BusTicketCancellationResult $result,
        public string $reason,
        public ?string $authorizationReference = null,
        public ?string $evidenceNotes = null,
    ) {}
}
