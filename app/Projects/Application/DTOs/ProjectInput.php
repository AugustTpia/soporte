<?php

namespace App\Projects\Application\DTOs;

use App\Projects\Domain\Enums\ProjectStatus;
use DateTimeImmutable;

final readonly class ProjectInput
{
    /**
     * @param  array<int, int>  $memberIds
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ProjectStatus $status,
        public int $managerId,
        public ?DateTimeImmutable $startDate,
        public ?DateTimeImmutable $dueDate,
        public array $memberIds,
    ) {}
}
