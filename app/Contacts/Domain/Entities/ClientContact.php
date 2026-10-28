<?php

namespace App\Contacts\Domain\Entities;

use App\Shared\Domain\Exceptions\DomainException;

final class ClientContact
{
    public function __construct(
        public ?int $id,
        public int $companyId,
        public ?int $locationId,
        public string $name,
        public ?string $position,
        public ?string $email,
        public ?string $phone,
        public ?string $whatsapp,
        public bool $isPrimary,
        public bool $isActive,
        public ?string $notes,
        public ?string $locationName = null,
    ) {
        $this->guard();
    }

    public static function create(
        int $companyId,
        string $name,
        ?int $locationId = null,
        ?string $position = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $whatsapp = null,
        bool $isPrimary = false,
        ?string $notes = null,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            locationId: $locationId,
            name: $name,
            position: $position,
            email: $email,
            phone: $phone,
            whatsapp: $whatsapp,
            isPrimary: $isPrimary,
            isActive: true,
            notes: $notes,
        );
    }

    public function updateDetails(
        string $name,
        ?int $locationId,
        ?string $position,
        ?string $email,
        ?string $phone,
        ?string $whatsapp,
        bool $isPrimary,
        ?string $notes,
        bool $isActive,
    ): void {
        $this->name = $name;
        $this->locationId = $locationId;
        $this->position = $position;
        $this->email = $email;
        $this->phone = $phone;
        $this->whatsapp = $whatsapp;
        $this->isPrimary = $isPrimary;
        $this->notes = $notes;
        $this->isActive = $isActive;

        $this->guard();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->isPrimary = false;
    }

    public function contactValue(): ?string
    {
        return $this->email ?? $this->phone ?? $this->whatsapp;
    }

    private function guard(): void
    {
        $this->name = trim($this->name);
        $this->position = $this->nullable($this->position);
        $this->email = $this->nullable($this->email);
        $this->phone = $this->nullable($this->phone);
        $this->whatsapp = $this->nullable($this->whatsapp);
        $this->notes = $this->nullable($this->notes);

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa del contacto es obligatoria.');
        }

        if ($this->name === '') {
            throw DomainException::because('El nombre del contacto es obligatorio.');
        }

        if ($this->locationId !== null && $this->locationId <= 0) {
            throw DomainException::because('La ubicación seleccionada no es válida.');
        }

        if ($this->email !== null && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            throw DomainException::because('El correo del contacto no es válido.');
        }

        if ($this->isPrimary && ! $this->isActive) {
            throw DomainException::because('Un contacto inactivo no puede ser el contacto principal.');
        }

        $this->ensureMax($this->name, 150, 'El nombre del contacto no puede superar 150 caracteres.');
        $this->ensureMax($this->position, 100, 'El puesto no puede superar 100 caracteres.');
        $this->ensureMax($this->email, 150, 'El correo no puede superar 150 caracteres.');
        $this->ensureMax($this->phone, 30, 'El teléfono no puede superar 30 caracteres.');
        $this->ensureMax($this->whatsapp, 30, 'El WhatsApp no puede superar 30 caracteres.');
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
