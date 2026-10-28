<?php

namespace App\Reports\Application\DTOs;

use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use DateTimeImmutable;

final readonly class TicketReportRow
{
    /**
     * @param  array<int, string>  $technicians
     */
    public function __construct(
        public int $id,
        public ?string $folio,
        public DateTimeImmutable $openedAt,
        public string $subject,
        public TicketCategory $category,
        public TicketPriority $priority,
        public TicketStatus $status,
        public ?string $locationName,
        public array $technicians,
        public ?DateTimeImmutable $resolvedAt,
        public ?DateTimeImmutable $closedAt,
        public ?float $resolutionHours,
    ) {}
}
