<?php

namespace App\Projects\Application\DTOs;

use App\Projects\Domain\Enums\ProjectTaskPriority;
use App\Projects\Domain\Enums\ProjectTaskStatus;
use DateTimeImmutable;

final readonly class ProjectTaskInput
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ProjectTaskStatus $status,
        public ProjectTaskPriority $priority,
        public ?int $assignedUserId,
        public ?DateTimeImmutable $dueDate,
        public ?int $ticketId,
    ) {}
}
