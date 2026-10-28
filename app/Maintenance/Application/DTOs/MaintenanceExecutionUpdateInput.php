<?php

namespace App\Maintenance\Application\DTOs;

use DateTimeImmutable;

final readonly class MaintenanceExecutionUpdateInput
{
    public function __construct(
        public DateTimeImmutable $scheduledAt,
        public int $technicianId,
        public ?string $findings,
        public ?string $actions,
        public ?string $recommendations,
        public ?string $partsUsed,
        public ?int $timeSpentMinutes,
    ) {}
}
