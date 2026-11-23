<?php

namespace App\Projects\Domain\Entities;

use App\Projects\Domain\Enums\ProjectTaskPriority;
use App\Projects\Domain\Enums\ProjectTaskStatus;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class ProjectTask
{
    public function __construct(
        public ?int $id,
        public int $projectId,
        public string $title,
        public ?string $description,
        public ProjectTaskStatus $status,
        public ProjectTaskPriority $priority,
        public ?int $assignedUserId,
        public ?DateTimeImmutable $dueDate,
        public ?int $ticketId,
        public int $sortOrder,
        public ?int $createdBy,
        public ?string $assignedUserName = null,
        public ?string $ticketLabel = null,
        public ?DateTimeImmutable $createdAt = null,
    ) {
        $this->guard();
    }

    public static function create(
        int $projectId,
        string $title,
        ?string $description,
        ProjectTaskStatus $status,
        ProjectTaskPriority $priority,
        ?int $assignedUserId,
        ?DateTimeImmutable $dueDate,
        ?int $ticketId,
        int $sortOrder,
        int $createdBy,
    ): self {
        return new self(
            id: null,
            projectId: $projectId,
            title: $title,
            description: $description,
            status: $status,
            priority: $priority,
            assignedUserId: $assignedUserId,
            dueDate: $dueDate,
            ticketId: $ticketId,
            sortOrder: $sortOrder,
            createdBy: $createdBy,
        );
    }

    public function updateDetails(
        string $title,
        ?string $description,
        ProjectTaskStatus $status,
        ProjectTaskPriority $priority,
        ?int $assignedUserId,
        ?DateTimeImmutable $dueDate,
        ?int $ticketId,
    ): void {
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->priority = $priority;
        $this->assignedUserId = $assignedUserId;
        $this->dueDate = $dueDate;
        $this->ticketId = $ticketId;

        $this->guard();
    }

    public function moveTo(ProjectTaskStatus $status, int $sortOrder): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw DomainException::because("No se puede cambiar la tarea de {$this->status->label()} a {$status->label()}.");
        }

        $this->status = $status;
        $this->sortOrder = max(0, $sortOrder);
    }

    public function assign(?int $userId): void
    {
        $this->assignedUserId = $userId;
        $this->guard();
    }

    /**
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'project_id' => $this->projectId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'assigned_user_id' => $this->assignedUserId,
            'due_date' => $this->dueDate?->format('Y-m-d'),
            'ticket_id' => $this->ticketId,
            'sort_order' => $this->sortOrder,
        ];
    }

    private function guard(): void
    {
        $this->title = trim($this->title);
        $this->description = $this->nullable($this->description);

        if ($this->projectId <= 0) {
            throw DomainException::because('El proyecto de la tarea es obligatorio.');
        }

        if ($this->title === '') {
            throw DomainException::because('El título de la tarea es obligatorio.');
        }

        if (mb_strlen($this->title) > 200) {
            throw DomainException::because('El título de la tarea no puede superar 200 caracteres.');
        }

        if ($this->description !== null && mb_strlen($this->description) > 5000) {
            throw DomainException::because('La descripción de la tarea no puede superar 5000 caracteres.');
        }

        if ($this->assignedUserId !== null && $this->assignedUserId <= 0) {
            throw DomainException::because('El responsable de la tarea debe ser válido.');
        }

        if ($this->ticketId !== null && $this->ticketId <= 0) {
            throw DomainException::because('El ticket vinculado debe ser válido.');
        }

        if ($this->sortOrder < 0) {
            throw DomainException::because('El orden de la tarea no puede ser negativo.');
        }
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
