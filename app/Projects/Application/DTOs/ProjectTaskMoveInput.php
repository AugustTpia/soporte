<?php

namespace App\Projects\Application\DTOs;

use App\Projects\Domain\Enums\ProjectTaskStatus;

final readonly class ProjectTaskMoveInput
{
    public function __construct(
        public ProjectTaskStatus $status,
        public int $sortOrder,
    ) {}
}
