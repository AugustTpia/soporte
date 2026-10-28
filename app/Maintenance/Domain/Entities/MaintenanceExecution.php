<?php

namespace App\Maintenance\Domain\Entities;

use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class MaintenanceExecution
{
    /**
     * @param  array<int, MaintenanceExecutionChecklistItem>  $checklistItems
     */
    public function __construct(
        public ?int $id,
        public int $companyId,
        public ?int $planId,
        public ?int $assetId,
        public ?int $locationId,
        public ?int $supportTicketId,
        public MaintenanceTargetType $targetType,
        public string $targetLabel,
        public MaintenanceExecutionType $type,
        public MaintenanceExecutionStatus $status,
        public DateTimeImmutable $scheduledAt,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $completedAt,
        public ?DateTimeImmutable $cancelledAt,
        public int $technicianId,
        public ?string $findings,
        public ?string $actions,
        public ?string $recommendations,
        public ?string $partsUsed,
        public ?int $timeSpentMinutes,
        public ?string $cancellationReason,
        public ?int $createdBy,
        public ?string $technicianName = null,
        public ?string $planTitle = null,
        public ?string $supportTicketFolio = null,
        public ?string $assetLabel = null,
        public ?string $locationName = null,
        public array $checklistItems = [],
        public ?DateTimeImmutable $createdAt = null,
    ) {
        $this->guard();
    }

    /**
     * @param  array<int, MaintenanceExecutionChecklistItem>  $checklistItems
     */
    public static function schedule(
        int $companyId,
        ?int $planId,
        ?int $assetId,
        ?int $locationId,
        ?int $supportTicketId,
        MaintenanceTargetType $targetType,
        string $targetLabel,
        MaintenanceExecutionType $type,
        DateTimeImmutable $scheduledAt,
        int $technicianId,
        ?int $createdBy,
        array $checklistItems,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            planId: $planId,
            assetId: $assetId,
            locationId: $locationId,
            supportTicketId: $supportTicketId,
            targetType: $targetType,
            targetLabel: $targetLabel,
            type: $type,
            status: MaintenanceExecutionStatus::Scheduled,
            scheduledAt: $scheduledAt,
            startedAt: null,
            completedAt: null,
            cancelledAt: null,
            technicianId: $technicianId,
            findings: null,
            actions: null,
            recommendations: null,
            partsUsed: null,
            timeSpentMinutes: null,
            cancellationReason: null,
            createdBy: $createdBy,
            checklistItems: $checklistItems,
        );
    }

    public function updateDetails(
        DateTimeImmutable $scheduledAt,
        int $technicianId,
        ?string $findings,
        ?string $actions,
        ?string $recommendations,
        ?string $partsUsed,
        ?int $timeSpentMinutes,
    ): void {
        $this->assertEditable();

        $this->scheduledAt = $scheduledAt;
        $this->technicianId = $technicianId;
        $this->findings = $findings;
        $this->actions = $actions;
        $this->recommendations = $recommendations;
        $this->partsUsed = $partsUsed;
        $this->timeSpentMinutes = $timeSpentMinutes;

        $this->guard();
    }

    public function start(DateTimeImmutable $startedAt): void
    {
        if ($this->status !== MaintenanceExecutionStatus::Scheduled) {
            throw DomainException::because('Solo se puede iniciar una ejecución programada.');
        }

        $this->status = MaintenanceExecutionStatus::InProgress;
        $this->startedAt = $startedAt;
    }

    public function updateChecklistItem(
        int $itemId,
        bool $isCompleted,
        ?string $notes,
        int $actorId,
    ): void {
        $this->assertEditable();

        $item = null;

        foreach ($this->checklistItems as $candidate) {
            if ($candidate->id === $itemId) {
                $item = $candidate;
                break;
            }
        }

        if ($item === null) {
            throw DomainException::because('El punto del checklist no pertenece a esta ejecución.');
        }

        $item->notes = $this->nullable($notes);

        if ($item->notes !== null && mb_strlen($item->notes) > 1000) {
            throw DomainException::because('La nota del checklist no puede superar 1000 caracteres.');
        }

        $item->markCompleted($isCompleted, $actorId);
    }

    public function complete(DateTimeImmutable $completedAt): void
    {
        $this->assertEditable();

        if ($completedAt > new DateTimeImmutable) {
            throw DomainException::because('La fecha de cierre no puede estar en el futuro.');
        }

        if ($this->startedAt !== null && $completedAt < $this->startedAt) {
            throw DomainException::because('La fecha de cierre no puede ser anterior al inicio.');
        }

        foreach ($this->checklistItems as $item) {
            if ($item->isRequired && ! $item->isCompleted) {
                throw DomainException::because('Completa todos los puntos obligatorios del checklist antes de cerrar.');
            }
        }

        $this->startedAt ??= $completedAt;
        $this->completedAt = $completedAt;
        $this->status = MaintenanceExecutionStatus::Completed;
    }

    public function cancel(string $reason, DateTimeImmutable $cancelledAt): void
    {
        $this->assertEditable();

        $reason = trim($reason);

        if ($reason === '') {
            throw DomainException::because('El motivo de cancelación es obligatorio.');
        }

        if (mb_strlen($reason) > 2000) {
            throw DomainException::because('El motivo de cancelación no puede superar 2000 caracteres.');
        }

        $this->cancellationReason = $reason;
        $this->cancelledAt = $cancelledAt;
        $this->status = MaintenanceExecutionStatus::Cancelled;
    }

    public function assertEditable(): void
    {
        if (! $this->status->isEditable()) {
            throw DomainException::because('La ejecución ya está cerrada y no se puede modificar.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'plan_id' => $this->planId,
            'target_type' => $this->targetType->value,
            'target_label' => $this->targetLabel,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'scheduled_at' => $this->scheduledAt->format('Y-m-d H:i:s'),
            'started_at' => $this->startedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'technician_id' => $this->technicianId,
            'time_spent_minutes' => $this->timeSpentMinutes,
            'checklist_items' => count($this->checklistItems),
        ];
    }

    private function guard(): void
    {
        $this->targetLabel = trim($this->targetLabel);
        $this->findings = $this->nullable($this->findings);
        $this->actions = $this->nullable($this->actions);
        $this->recommendations = $this->nullable($this->recommendations);
        $this->partsUsed = $this->nullable($this->partsUsed);
        $this->cancellationReason = $this->nullable($this->cancellationReason);

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa de la ejecución es obligatoria.');
        }

        if ($this->planId !== null && $this->planId <= 0) {
            throw DomainException::because('El plan vinculado no es válido.');
        }

        if ($this->supportTicketId !== null && $this->supportTicketId <= 0) {
            throw DomainException::because('El ticket vinculado no es válido.');
        }

        if ($this->targetLabel === '') {
            throw DomainException::because('El objetivo de la ejecución es obligatorio.');
        }

        if (mb_strlen($this->targetLabel) > 200) {
            throw DomainException::because('El objetivo de la ejecución no puede superar 200 caracteres.');
        }

        if ($this->targetType === MaintenanceTargetType::Asset) {
            if ($this->assetId === null || $this->assetId <= 0 || $this->locationId !== null) {
                throw DomainException::because('La ejecución debe apuntar a un activo válido.');
            }
        } elseif ($this->targetType === MaintenanceTargetType::Location) {
            if ($this->locationId === null || $this->locationId <= 0 || $this->assetId !== null) {
                throw DomainException::because('La ejecución debe apuntar a una ubicación válida.');
            }
        } elseif ($this->assetId !== null || $this->locationId !== null) {
            throw DomainException::because('Una ejecución de empresa no debe apuntar a un activo o ubicación.');
        }

        if ($this->technicianId <= 0) {
            throw DomainException::because('El técnico de la ejecución es obligatorio.');
        }

        if ($this->timeSpentMinutes !== null && ($this->timeSpentMinutes < 0 || $this->timeSpentMinutes > 10080)) {
            throw DomainException::because('El tiempo invertido debe estar entre 0 y 10080 minutos.');
        }

        $this->ensureMax($this->findings, 5000, 'Los hallazgos no pueden superar 5000 caracteres.');
        $this->ensureMax($this->actions, 5000, 'Las acciones no pueden superar 5000 caracteres.');
        $this->ensureMax($this->recommendations, 5000, 'Las recomendaciones no pueden superar 5000 caracteres.');
        $this->ensureMax($this->partsUsed, 2000, 'Los repuestos no pueden superar 2000 caracteres.');

        if (count($this->checklistItems) > 100) {
            throw DomainException::because('La ejecución no puede tener más de 100 puntos de checklist.');
        }

        if ($this->status === MaintenanceExecutionStatus::Completed) {
            if ($this->completedAt === null) {
                throw DomainException::because('Una ejecución completada necesita fecha de cierre.');
            }

            foreach ($this->checklistItems as $item) {
                if ($item->isRequired && ! $item->isCompleted) {
                    throw DomainException::because('Completa todos los puntos obligatorios del checklist antes de cerrar.');
                }
            }
        }

        if ($this->status === MaintenanceExecutionStatus::Cancelled && $this->cancellationReason === null) {
            throw DomainException::because('Una ejecución cancelada necesita un motivo.');
        }
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
