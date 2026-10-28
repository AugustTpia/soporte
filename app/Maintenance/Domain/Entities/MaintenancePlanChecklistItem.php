<?php

namespace App\Maintenance\Domain\Entities;

use App\Shared\Domain\Exceptions\DomainException;

final class MaintenancePlanChecklistItem
{
    public function __construct(
        public ?int $id,
        public string $label,
        public bool $isRequired,
        public int $sortOrder,
    ) {
        $this->label = trim($this->label);

        if ($this->label === '') {
            throw DomainException::because('Cada punto del checklist necesita una descripción.');
        }

        if (mb_strlen($this->label) > 200) {
            throw DomainException::because('Un punto del checklist no puede superar 200 caracteres.');
        }

        if ($this->sortOrder < 0) {
            throw DomainException::because('El orden del checklist no es válido.');
        }
    }
}
