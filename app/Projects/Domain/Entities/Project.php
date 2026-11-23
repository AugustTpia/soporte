<?php

namespace App\Projects\Domain\Entities;

use App\Projects\Domain\Enums\ProjectStatus;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class Project
{
    public function __construct(
        public ?int $id,
        public int $companyId,
        public string $name,
        public ?string $description,
        public ProjectStatus $status,
        public int $managerId,
        public ?DateTimeImmutable $startDate,
        public ?DateTimeImmutable $dueDate,
        public ?int $createdBy,
        public ?string $managerName = null,
        public ?DateTimeImmutable $createdAt = null,
    ) {
        $this->guard();
    }

    public static function create(
        int $companyId,
        string $name,
        ?string $description,
        ProjectStatus $status,
        int $managerId,
        ?DateTimeImmutable $startDate,
        ?DateTimeImmutable $dueDate,
        int $createdBy,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            name: $name,
            description: $description,
            status: $status,
            managerId: $managerId,
            startDate: $startDate,
            dueDate: $dueDate,
            createdBy: $createdBy,
        );
    }

    public function updateDetails(
        string $name,
        ?string $description,
        ProjectStatus $status,
        int $managerId,
        ?DateTimeImmutable $startDate,
        ?DateTimeImmutable $dueDate,
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->status = $status;
        $this->managerId = $managerId;
        $this->startDate = $startDate;
        $this->dueDate = $dueDate;

        $this->guard();
    }

    public function changeStatus(ProjectStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }

        if (! $this->status->canTransitionTo($status)) {
            throw DomainException::because("No se puede cambiar el proyecto de {$this->status->label()} a {$status->label()}.");
        }

        $this->status = $status;
    }

    /**
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'manager_id' => $this->managerId,
            'start_date' => $this->startDate?->format('Y-m-d'),
            'due_date' => $this->dueDate?->format('Y-m-d'),
        ];
    }

    private function guard(): void
    {
        $this->name = trim($this->name);
        $this->description = $this->nullable($this->description);

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa del proyecto es obligatoria.');
        }

        if ($this->name === '') {
            throw DomainException::because('El nombre del proyecto es obligatorio.');
        }

        if (mb_strlen($this->name) > 160) {
            throw DomainException::because('El nombre del proyecto no puede superar 160 caracteres.');
        }

        if ($this->description !== null && mb_strlen($this->description) > 5000) {
            throw DomainException::because('La descripción del proyecto no puede superar 5000 caracteres.');
        }

        if ($this->managerId <= 0) {
            throw DomainException::because('El responsable del proyecto es obligatorio.');
        }

        if ($this->startDate !== null && $this->dueDate !== null && $this->dueDate < $this->startDate) {
            throw DomainException::because('La fecha de vencimiento no puede ser anterior a la fecha de inicio.');
        }
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
