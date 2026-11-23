<?php

namespace App\Projects\Application\DTOs;

final readonly class ProjectFilters
{
    public function __construct(
        public ?string $term = null,
        public ?string $status = null,
        public ?int $managerId = null,
    ) {}
}
