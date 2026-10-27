<?php

namespace App\Departments\Application\DTOs;

final readonly class DepartmentInput
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}
}
