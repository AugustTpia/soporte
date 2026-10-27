<?php

namespace App\Accounts\Domain\Entities;

use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Shared\Domain\Exceptions\DomainException;

final class ServiceAccount
{
    /**
     * @param  array<int, AccountAssetLink>  $links
     */
    public function __construct(
        public ?int $id,
        public int $companyId,
        public ServiceType $service,
        public string $username,
        public string $instance,
        public ?string $ownerName,
        public ServiceAccountStatus $status,
        public ?int $responsibleUserId,
        public ?string $notes,
        public ?int $createdBy = null,
        public ?string $responsibleUserName = null,
        public array $links = [],
    ) {
        $this->guard();
    }

    public static function create(
        int $companyId,
        ServiceType $service,
        string $username,
        string $instance,
        ?string $ownerName = null,
        ?int $responsibleUserId = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            service: $service,
            username: $username,
            instance: $instance,
            ownerName: $ownerName,
            status: ServiceAccountStatus::Active,
            responsibleUserId: $responsibleUserId,
            notes: $notes,
            createdBy: $createdBy,
        );
    }

    public function updateDetails(
        ServiceType $service,
        string $username,
        string $instance,
        ?string $ownerName,
        ?int $responsibleUserId,
        ?string $notes,
    ): void {
        $this->service = $service;
        $this->username = $username;
        $this->instance = $instance;
        $this->ownerName = $ownerName;
        $this->responsibleUserId = $responsibleUserId;
        $this->notes = $notes;

        $this->guard();
    }

    public function changeStatus(ServiceAccountStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw DomainException::because(
                "No se puede cambiar el estado de la cuenta {$this->username} de {$this->status->label()} a {$status->label()}.",
            );
        }

        $this->status = $status;
    }

    public function assertCanLinkAssets(): void
    {
        if ($this->status !== ServiceAccountStatus::Active) {
            throw DomainException::because('Solo las cuentas activas pueden vincularse a equipos.');
        }
    }

    /**
     * @return array<int, AccountAssetLink>
     */
    public function activeLinks(): array
    {
        return array_values(array_filter(
            $this->links,
            fn (AccountAssetLink $link) => $link->isActive(),
        ));
    }

    private function guard(): void
    {
        $this->username = trim($this->username);
        $this->instance = mb_strtolower(trim($this->instance));
        $this->ownerName = $this->nullable($this->ownerName);
        $this->notes = $this->nullable($this->notes);

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa de la cuenta es obligatoria.');
        }

        if ($this->username === '') {
            throw DomainException::because('El nombre de usuario de la cuenta es obligatorio.');
        }

        if ($this->instance === '') {
            throw DomainException::because('La instancia o servidor de la cuenta es obligatorio.');
        }

        if ($this->responsibleUserId !== null && $this->responsibleUserId <= 0) {
            throw DomainException::because('El responsable de la cuenta no es válido.');
        }

        $this->ensureMax($this->username, 150, 'El nombre de usuario no puede superar 150 caracteres.');
        $this->ensureMax($this->instance, 255, 'La instancia o servidor no puede superar 255 caracteres.');
        $this->ensureMax($this->ownerName, 150, 'La persona de la cuenta no puede superar 150 caracteres.');
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
