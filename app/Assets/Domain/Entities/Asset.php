<?php

namespace App\Assets\Domain\Entities;

use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class Asset
{
    public function __construct(
        public ?int $id,
        public int $companyId,
        public string $internalCode,
        public ?string $hostname,
        public AssetCategory $category,
        public ?string $brand,
        public ?string $model,
        public ?string $serialNumber,
        public int $locationId,
        public ?int $assignedUserId,
        public AssetStatus $status,
        public ?string $operatingSystem,
        public ?string $ipAddress,
        public ?DateTimeImmutable $purchaseDate,
        public ?DateTimeImmutable $warrantyUntil,
        public ?string $notes,
        public ?int $createdBy = null,
        public ?string $locationName = null,
        public ?string $assignedUserName = null,
    ) {
        $this->guard();
    }

    public static function create(
        int $companyId,
        string $internalCode,
        AssetCategory $category,
        int $locationId,
        ?string $hostname = null,
        ?string $brand = null,
        ?string $model = null,
        ?string $serialNumber = null,
        ?int $assignedUserId = null,
        ?string $operatingSystem = null,
        ?string $ipAddress = null,
        ?DateTimeImmutable $purchaseDate = null,
        ?DateTimeImmutable $warrantyUntil = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            internalCode: $internalCode,
            hostname: $hostname,
            category: $category,
            brand: $brand,
            model: $model,
            serialNumber: $serialNumber,
            locationId: $locationId,
            assignedUserId: $assignedUserId,
            status: AssetStatus::Active,
            operatingSystem: $operatingSystem,
            ipAddress: $ipAddress,
            purchaseDate: $purchaseDate,
            warrantyUntil: $warrantyUntil,
            notes: $notes,
            createdBy: $createdBy,
        );
    }

    public function updateDetails(
        string $internalCode,
        ?string $hostname,
        AssetCategory $category,
        ?string $brand,
        ?string $model,
        ?string $serialNumber,
        int $locationId,
        ?int $assignedUserId,
        ?string $operatingSystem,
        ?string $ipAddress,
        ?DateTimeImmutable $purchaseDate,
        ?DateTimeImmutable $warrantyUntil,
        ?string $notes,
    ): void {
        $this->internalCode = $internalCode;
        $this->hostname = $hostname;
        $this->category = $category;
        $this->brand = $brand;
        $this->model = $model;
        $this->serialNumber = $serialNumber;
        $this->locationId = $locationId;
        $this->assignedUserId = $assignedUserId;
        $this->operatingSystem = $operatingSystem;
        $this->ipAddress = $ipAddress;
        $this->purchaseDate = $purchaseDate;
        $this->warrantyUntil = $warrantyUntil;
        $this->notes = $notes;

        $this->guard();
    }

    public function changeStatus(AssetStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw DomainException::because(
                "No se puede cambiar el estado de {$this->internalCode} de {$this->status->label()} a {$status->label()}.",
            );
        }

        $this->status = $status;
    }

    private function guard(): void
    {
        $this->internalCode = trim($this->internalCode);
        $this->hostname = $this->nullable($this->hostname);
        $this->brand = $this->nullable($this->brand);
        $this->model = $this->nullable($this->model);
        $this->serialNumber = $this->nullable($this->serialNumber);
        $this->operatingSystem = $this->nullable($this->operatingSystem);
        $this->ipAddress = $this->nullable($this->ipAddress);
        $this->notes = $this->nullable($this->notes);

        if ($this->hostname !== null) {
            $this->hostname = mb_strtolower($this->hostname);
        }

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa del activo es obligatoria.');
        }

        if ($this->internalCode === '') {
            throw DomainException::because('El código interno del activo es obligatorio.');
        }

        if ($this->locationId <= 0) {
            throw DomainException::because('La ubicación del activo es obligatoria.');
        }

        if ($this->purchaseDate !== null && $this->warrantyUntil !== null && $this->warrantyUntil < $this->purchaseDate) {
            throw DomainException::because('La garantía no puede vencer antes de la fecha de compra.');
        }

        $this->ensureMax($this->internalCode, 40, 'El código interno no puede superar 40 caracteres.');
        $this->ensureMax($this->hostname, 255, 'El hostname no puede superar 255 caracteres.');
        $this->ensureMax($this->brand, 120, 'La marca no puede superar 120 caracteres.');
        $this->ensureMax($this->model, 120, 'El modelo no puede superar 120 caracteres.');
        $this->ensureMax($this->serialNumber, 120, 'El número de serie no puede superar 120 caracteres.');
        $this->ensureMax($this->operatingSystem, 120, 'El sistema operativo no puede superar 120 caracteres.');
        $this->ensureMax($this->ipAddress, 45, 'La dirección IP no puede superar 45 caracteres.');
        $this->ensureMax($this->notes, 2000, 'Las notas no pueden superar 2000 caracteres.');
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
