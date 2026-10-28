<?php

namespace App\Reports\Application\DTOs;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use DateTimeImmutable;

final readonly class CancellationReportRow
{
    public function __construct(
        public int $id,
        public string $ticketNumber,
        public ?string $supportTicketFolio,
        public DateTimeImmutable $requestedAt,
        public ?DateTimeImmutable $executedAt,
        public ?int $amountCents,
        public string $operatorName,
        public BusTicketCancellationResult $result,
        public string $reason,
        public ?string $authorizationReference,
    ) {}
}
