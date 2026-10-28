<?php

namespace App\Maintenance\Domain\Entities;

use App\Maintenance\Domain\Enums\MaintenanceIntervalUnit;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class MaintenancePlan
{
    /**
     * @param  array<int, MaintenancePlanChecklistItem>  $checklistItems
     */
    public function __construct(
        public ?int $id,
        public int $companyId,
        public MaintenanceTargetType $targetType,
        public ?int $assetId,
        public ?int $locationId,
        public string $targetLabel,
        public string $title,
        public ?string $description,
        public int $intervalValue,
        public MaintenanceIntervalUnit $intervalUnit,
        public DateTimeImmutable $nextDueAt,
        public ?DateTimeImmutable $lastCompletedAt,
        public int $assignedUserId,
        public MaintenancePlanStatus $status,
        public ?int $createdBy,
        public ?string $assignedUserName = null,
        public ?string $assetLabel = null,
        public ?string $locationName = null,
        public array $checklistItems = [],
        public ?DateTimeImmutable $createdAt = null,
    ) {
        $this->guard();
    }

    /**
     * @param  array<int, MaintenancePlanChecklistItem>  $checklistItems
     */
    public static function create(
        int $companyId,
        MaintenanceTargetType $targetType,
        ?int $assetId,
        ?int $locationId,
        string $targetLabel,
        string $title,
        ?string $description,
        int $intervalValue,
        MaintenanceIntervalUnit $intervalUnit,
        DateTimeImmutable $nextDueAt,
        int $assignedUserId,
        ?int $createdBy,
        array $checklistItems,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            targetType: $targetType,
            assetId: $assetId,
            locationId: $locationId,
            targetLabel: $targetLabel,
            title: $title,
            description: $description,
            intervalValue: $intervalValue,
            intervalUnit: $intervalUnit,
            nextDueAt: $nextDueAt,
            lastCompletedAt: null,
            assignedUserId: $assignedUserId,
            status: MaintenancePlanStatus::Active,
            createdBy: $createdBy,
            checklistItems: $checklistItems,
        );
    }

    /**
     * @param  array<int, MaintenancePlanChecklistItem>  $checklistItems
     */
    public function updateDetails(
        MaintenanceTargetType $targetType,
        ?int $assetId,
        ?int $locationId,
        string $targetLabel,
        string $title,
        ?string $description,
        int $intervalValue,
        MaintenanceIntervalUnit $intervalUnit,
        DateTimeImmutable $nextDueAt,
        int $assignedUserId,
        array $checklistItems,
    ): void {
        $this->targetType = $targetType;
        $this->assetId = $assetId;
        $this->locationId = $locationId;
        $this->targetLabel = $targetLabel;
        $this->title = $title;
        $this->description = $description;
        $this->intervalValue = $intervalValue;
        $this->intervalUnit = $intervalUnit;
        $this->nextDueAt = $nextDueAt;
        $this->assignedUserId = $assignedUserId;
        $this->checklistItems = array_values($checklistItems);

        $this->guard();
    }

    public function changeStatus(MaintenancePlanStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }

        $this->status = $status;
    }

    public function markCompleted(DateTimeImmutable $completedAt): void
    {
        if ($completedAt > new DateTimeImmutable) {
            throw DomainException::because('La fecha de ejecución no puede estar en el futuro.');
        }

        $this->lastCompletedAt = $completedAt;
        $this->nextDueAt = $this->intervalUnit->addTo($completedAt, $this->intervalValue);
    }

    public function isOverdue(?DateTimeImmutable $reference = null): bool
    {
        $reference = ($reference ?? new DateTimeImmutable)->setTime(0, 0);

        return $this->nextDueAt < $reference;
    }

    /**
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'target_type' => $this->targetType->value,
            'asset_id' => $this->assetId,
            'location_id' => $this->locationId,
            'target_label' => $this->targetLabel,
            'title' => $this->title,
            'interval_value' => $this->intervalValue,
            'interval_unit' => $this->intervalUnit->value,
            'next_due_at' => $this->nextDueAt->format('Y-m-d H:i:s'),
            'assigned_user_id' => $this->assignedUserId,
            'status' => $this->status->value,
            'checklist_items' => count($this->checklistItems),
        ];
    }

    private function guard(): void
    {
        $this->targetLabel = trim($this->targetLabel);
        $this->title = trim($this->title);
        $this->description = $this->nullable($this->description);

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa del plan es obligatoria.');
        }

        if ($this->targetLabel === '') {
            throw DomainException::because('El objetivo del plan es obligatorio.');
        }

        if (mb_strlen($this->targetLabel) > 200) {
            throw DomainException::because('El objetivo del plan no puede superar 200 caracteres.');
        }

        if ($this->targetType === MaintenanceTargetType::Asset) {
            if ($this->assetId === null || $this->assetId <= 0 || $this->locationId !== null) {
                throw DomainException::because('El plan debe apuntar a un activo válido.');
            }
        } elseif ($this->targetType === MaintenanceTargetType::Location) {
            if ($this->locationId === null || $this->locationId <= 0 || $this->assetId !== null) {
                throw DomainException::because('El plan debe apuntar a una ubicación válida.');
            }
        } elseif ($this->assetId !== null || $this->locationId !== null) {
            throw DomainException::because('Un plan de empresa no debe apuntar a un activo o ubicación.');
        }

        if ($this->title === '') {
            throw DomainException::because('El nombre del plan es obligatorio.');
        }

        if (mb_strlen($this->title) > 160) {
            throw DomainException::because('El nombre del plan no puede superar 160 caracteres.');
        }

        if ($this->description !== null && mb_strlen($this->description) > 5000) {
            throw DomainException::because('La descripción del plan no puede superar 5000 caracteres.');
        }

        if ($this->intervalValue < 1 || $this->intervalValue > 365) {
            throw DomainException::because('La frecuencia debe estar entre 1 y 365.');
        }

        if ($this->assignedUserId <= 0) {
            throw DomainException::because('El responsable del plan es obligatorio.');
        }

        if ($this->checklistItems === [] || count($this->checklistItems) > 50) {
            throw DomainException::because('El plan debe tener entre 1 y 50 puntos de checklist.');
        }

        $orders = array_map(fn (MaintenancePlanChecklistItem $item) => $item->sortOrder, $this->checklistItems);

        if (count($orders) !== count(array_unique($orders))) {
            throw DomainException::because('El orden del checklist contiene posiciones repetidas.');
        }
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
