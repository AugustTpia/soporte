<?php

namespace App\Maintenance\Domain\Entities;

use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class MaintenanceExecutionChecklistItem
{
    public function __construct(
        public ?int $id,
        public ?int $planChecklistItemId,
        public string $label,
        public bool $isRequired,
        public int $sortOrder,
        public bool $isCompleted = false,
        public ?string $notes = null,
        public ?DateTimeImmutable $completedAt = null,
        public ?int $completedBy = null,
    ) {
        $this->label = trim($this->label);
        $this->notes = $this->nullable($this->notes);

        if ($this->label === '') {
            throw DomainException::because('Cada punto del checklist necesita una descripción.');
        }

        if (mb_strlen($this->label) > 200) {
            throw DomainException::because('Un punto del checklist no puede superar 200 caracteres.');
        }

        if ($this->sortOrder < 0) {
            throw DomainException::because('El orden del checklist no es válido.');
        }

        if ($this->notes !== null && mb_strlen($this->notes) > 1000) {
            throw DomainException::because('La nota del checklist no puede superar 1000 caracteres.');
        }
    }

    public function markCompleted(bool $completed, ?int $actorId, ?DateTimeImmutable $at = null): void
    {
        $this->isCompleted = $completed;

        if ($completed) {
            $this->completedAt = $at ?? new DateTimeImmutable;
            $this->completedBy = $actorId;

            return;
        }

        $this->completedAt = null;
        $this->completedBy = null;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
