<?php

namespace App\BusTicketCancellations\Application\DTOs;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use DateTimeImmutable;

final readonly class BusTicketCancellationFilters
{
    public function __construct(
        public ?string $term = null,
        public ?BusTicketCancellationResult $result = null,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
        public ?int $supportTicketId = null,
    ) {}

    public function hasFilters(): bool
    {
        return $this->term !== null
            || $this->result !== null
            || $this->from !== null
            || $this->to !== null
            || $this->supportTicketId !== null;
    }
}
