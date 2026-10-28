<?php

namespace App\Support\Domain\Entities;

use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use DateTimeImmutable;

final class SupportTicket
{
    /**
     * @param  array<int, TicketAssignment>  $assignments
     * @param  array<int, TicketActivity>  $activities
     */
    public function __construct(
        public ?int $id,
        public int $companyId,
        public ?string $folio,
        public TicketCategory $category,
        public string $subject,
        public string $description,
        public ?string $requesterName,
        public ?string $requesterContact,
        public TicketPriority $priority,
        public TicketStatus $status,
        public ?int $locationId,
        public ?int $assetId,
        public ?int $serviceAccountId,
        public ?int $clientContactId,
        public DateTimeImmutable $openedAt,
        public ?DateTimeImmutable $dueAt = null,
        public ?DateTimeImmutable $resolvedAt = null,
        public ?DateTimeImmutable $closedAt = null,
        public ?DateTimeImmutable $cancelledAt = null,
        public ?string $cancellationReason = null,
        public ?int $createdBy = null,
        public ?string $locationName = null,
        public ?string $assetLabel = null,
        public ?string $accountLabel = null,
        public ?string $clientContactName = null,
        public ?string $creatorName = null,
        public array $assignments = [],
        public array $activities = [],
    ) {
        $this->guard();
    }

    public static function create(
        int $companyId,
        TicketCategory $category,
        string $subject,
        string $description,
        TicketPriority $priority,
        ?string $requesterName = null,
        ?string $requesterContact = null,
        ?int $locationId = null,
        ?int $assetId = null,
        ?int $serviceAccountId = null,
        ?int $clientContactId = null,
        ?DateTimeImmutable $dueAt = null,
        ?DateTimeImmutable $openedAt = null,
        ?int $createdBy = null,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            folio: null,
            category: $category,
            subject: $subject,
            description: $description,
            requesterName: $requesterName,
            requesterContact: $requesterContact,
            priority: $priority,
            status: TicketStatus::Open,
            locationId: $locationId,
            assetId: $assetId,
            serviceAccountId: $serviceAccountId,
            clientContactId: $clientContactId,
            openedAt: $openedAt ?? new DateTimeImmutable,
            dueAt: $dueAt,
            createdBy: $createdBy,
        );
    }

    public function updateDetails(
        TicketCategory $category,
        string $subject,
        string $description,
        ?string $requesterName,
        ?string $requesterContact,
        TicketPriority $priority,
        ?int $locationId,
        ?int $assetId,
        ?int $serviceAccountId,
        ?int $clientContactId = null,
        ?DateTimeImmutable $dueAt = null,
    ): void {
        if ($this->status === TicketStatus::Cancelled) {
            throw DomainException::because('No se puede editar un ticket cancelado.');
        }

        $this->category = $category;
        $this->subject = $subject;
        $this->description = $description;
        $this->requesterName = $requesterName;
        $this->requesterContact = $requesterContact;
        $this->priority = $priority;
        $this->locationId = $locationId;
        $this->assetId = $assetId;
        $this->serviceAccountId = $serviceAccountId;
        $this->clientContactId = $clientContactId;
        $this->dueAt = $dueAt;

        $this->guard();
    }

    public function setFolio(string $folio): void
    {
        $this->folio = $folio;

        $this->guard();
    }

    public function changeStatus(TicketStatus $target, ?string $reason = null): void
    {
        if ($this->status === $target) {
            return;
        }

        if (in_array($target, [TicketStatus::Open, TicketStatus::Assigned], true)) {
            throw DomainException::because('Ese estado se gestiona mediante las asignaciones del ticket.');
        }

        if (! $this->status->canTransitionTo($target)) {
            throw DomainException::because(
                "No se puede cambiar el estado del ticket de {$this->status->label()} a {$target->label()}.",
            );
        }

        if ($target->requiresActiveAssignee() && ! $this->hasActiveAssignments()) {
            throw DomainException::because('El ticket necesita al menos un técnico asignado para ese estado.');
        }

        $reason = $this->nullable($reason);

        if ($target->requiresReason($this->status) && $reason === null) {
            throw DomainException::because('El motivo es obligatorio para cancelar o reabrir un ticket.');
        }

        $now = new DateTimeImmutable;

        if ($target === TicketStatus::InProgress) {
            $this->resolvedAt = null;
            $this->closedAt = null;
        }

        if ($target === TicketStatus::Resolved) {
            $this->resolvedAt = $now;
            $this->closedAt = null;
        }

        if ($target === TicketStatus::Closed) {
            $this->closedAt = $now;
        }

        if ($target === TicketStatus::Cancelled) {
            $this->cancelledAt = $now;
            $this->cancellationReason = $reason;
        }

        $this->status = $target;
    }

    public function markAssigned(): void
    {
        if ($this->status === TicketStatus::Open) {
            $this->status = TicketStatus::Assigned;
        }
    }

    public function markOpen(): void
    {
        if ($this->status === TicketStatus::Assigned) {
            $this->status = TicketStatus::Open;
        }
    }

    public function assertCanChangeAssignments(): void
    {
        if (in_array($this->status, [TicketStatus::Resolved, TicketStatus::Closed, TicketStatus::Cancelled], true)) {
            throw DomainException::because('No se pueden modificar asignaciones de un ticket resuelto, cerrado o cancelado.');
        }
    }

    public function assertCanRemoveAssignee(int $userId): void
    {
        $this->assertCanChangeAssignments();

        if (! $this->isAssignedTo($userId)) {
            throw DomainException::because('El usuario no está asignado actualmente al ticket.');
        }

        if ($this->activeAssignmentCount() === 1
            && in_array($this->status, [TicketStatus::InProgress, TicketStatus::Waiting], true)) {
            throw DomainException::because('No puedes retirar al último técnico de un ticket en progreso o en espera.');
        }
    }

    /**
     * @return array<int, TicketAssignment>
     */
    public function activeAssignments(): array
    {
        return array_values(array_filter(
            $this->assignments,
            fn (TicketAssignment $assignment) => $assignment->isActive(),
        ));
    }

    public function activeAssignmentCount(): int
    {
        return count($this->activeAssignments());
    }

    public function hasActiveAssignments(): bool
    {
        return $this->activeAssignments() !== [];
    }

    public function isAssignedTo(int $userId): bool
    {
        foreach ($this->activeAssignments() as $assignment) {
            if ($assignment->userId === $userId) {
                return true;
            }
        }

        return false;
    }

    public function isCreatedBy(int $userId): bool
    {
        return $this->createdBy === $userId;
    }

    /**
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'folio' => $this->folio,
            'category' => $this->category->value,
            'subject' => $this->subject,
            'description' => $this->description,
            'requester_name' => $this->requesterName,
            'requester_contact' => $this->requesterContact,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'location_id' => $this->locationId,
            'asset_id' => $this->assetId,
            'service_account_id' => $this->serviceAccountId,
            'client_contact_id' => $this->clientContactId,
            'opened_at' => $this->openedAt->format('Y-m-d H:i:s'),
            'due_at' => $this->dueAt?->format('Y-m-d H:i:s'),
            'resolved_at' => $this->resolvedAt?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closedAt?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelledAt?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellationReason,
        ];
    }

    private function guard(): void
    {
        $this->subject = trim($this->subject);
        $this->description = trim($this->description);
        $this->requesterName = $this->nullable($this->requesterName);
        $this->requesterContact = $this->nullable($this->requesterContact);
        $this->cancellationReason = $this->nullable($this->cancellationReason);

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa del ticket es obligatoria.');
        }

        if ($this->subject === '') {
            throw DomainException::because('El asunto del ticket es obligatorio.');
        }

        if ($this->description === '') {
            throw DomainException::because('La descripción del ticket es obligatoria.');
        }

        if ($this->locationId !== null && $this->locationId <= 0) {
            throw DomainException::because('La ubicación seleccionada no es válida.');
        }

        if ($this->assetId !== null && $this->assetId <= 0) {
            throw DomainException::because('El activo seleccionado no es válido.');
        }

        if ($this->serviceAccountId !== null && $this->serviceAccountId <= 0) {
            throw DomainException::because('La cuenta seleccionada no es válida.');
        }

        if ($this->clientContactId !== null && $this->clientContactId <= 0) {
            throw DomainException::because('El contacto seleccionado no es válido.');
        }

        if ($this->dueAt !== null && $this->dueAt < $this->openedAt) {
            throw DomainException::because('La fecha compromiso no puede ser anterior a la apertura del ticket.');
        }

        if ($this->folio !== null && preg_match('/^TCK-\d{6,}$/', $this->folio) !== 1) {
            throw DomainException::because('El folio del ticket no tiene un formato válido.');
        }

        $this->ensureMax($this->subject, 180, 'El asunto no puede superar 180 caracteres.');
        $this->ensureMax($this->description, 10000, 'La descripción no puede superar 10000 caracteres.');
        $this->ensureMax($this->requesterName, 150, 'El nombre del solicitante no puede superar 150 caracteres.');
        $this->ensureMax($this->requesterContact, 150, 'El medio de contacto no puede superar 150 caracteres.');
        $this->ensureMax($this->cancellationReason, 2000, 'El motivo de cancelación no puede superar 2000 caracteres.');
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function ensureMax(?string $value, int $max, string $message): void
    {
        if ($value !== null && mb_strlen($value) > $max) {
            throw DomainException::because($message);
        }
    }
}
