<?php

namespace App\Credentials\Domain\Entities;

use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class Credential
{
    public const MAX_SECRET_LENGTH = 4096;

    public function __construct(
        public ?int $id,
        public int $companyId,
        public int $serviceAccountId,
        public string $accountUsername,
        public ?string $accountInstance,
        public string $label,
        public CredentialKind $kind,
        public CredentialStatus $status,
        public bool $hasSecret,
        public ?DateTimeImmutable $lastRotatedAt,
        public ?int $createdBy = null,
    ) {
        $this->guard();
    }

    public static function create(
        int $companyId,
        int $serviceAccountId,
        string $accountUsername,
        ?string $accountInstance,
        string $label,
        CredentialKind $kind,
        ?int $createdBy = null,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            serviceAccountId: $serviceAccountId,
            accountUsername: $accountUsername,
            accountInstance: $accountInstance,
            label: $label,
            kind: $kind,
            status: CredentialStatus::Active,
            hasSecret: false,
            lastRotatedAt: null,
            createdBy: $createdBy,
        );
    }

    public function updateDetails(string $label, CredentialKind $kind): void
    {
        $this->label = $label;
        $this->kind = $kind;

        $this->guard();
    }

    public function changeStatus(CredentialStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw DomainException::because(
                "No se puede cambiar el estado de la credencial de {$this->status->label()} a {$status->label()}.",
            );
        }

        $this->status = $status;
    }

    public function assertCanStoreSecret(): void
    {
        if ($this->status !== CredentialStatus::Active) {
            throw DomainException::because('Solo las credenciales activas pueden almacenar o rotar secretos.');
        }
    }

    public function assertCanReveal(): void
    {
        if ($this->status !== CredentialStatus::Active) {
            throw DomainException::because('No se puede revelar una credencial revocada.');
        }

        if (! $this->hasSecret) {
            throw DomainException::because('Esta credencial todavía no tiene un secreto almacenado.');
        }
    }

    private function guard(): void
    {
        $this->label = trim($this->label);
        $this->accountUsername = trim($this->accountUsername);
        $this->accountInstance = $this->nullable($this->accountInstance);

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa de la credencial es obligatoria.');
        }

        if ($this->serviceAccountId <= 0) {
            throw DomainException::because('La cuenta de servicio de la credencial es obligatoria.');
        }

        if ($this->label === '') {
            throw DomainException::because('La etiqueta de la credencial es obligatoria.');
        }

        if (mb_strlen($this->label) > 120) {
            throw DomainException::because('La etiqueta no puede superar 120 caracteres.');
        }
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
