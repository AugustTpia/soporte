<?php

namespace App\Maintenance\Application\DTOs;

final readonly class MaintenanceChecklistItemInput
{
    public function __construct(
        public string $label,
        public bool $isRequired,
    ) {}
}
