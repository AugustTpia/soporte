<?php

namespace App\Locations\Domain\Entities;

use App\Shared\Domain\Exceptions\DomainException;

final class Location
{
    public function __construct(
        public ?int $id,
        public int $companyId,
        public string $name,
        public string $slug,
        public string $street,
        public string $exteriorNumber,
        public ?string $interiorNumber,
        public ?string $neighborhood,
        public string $city,
        public ?string $state,
        public ?string $postalCode,
        public string $country,
        public ?string $phone,
        public ?string $notes,
        public bool $isActive,
    ) {
        $this->guard();
    }

    public static function create(
        int $companyId,
        string $name,
        string $slug,
        string $street,
        string $exteriorNumber,
        string $city,
        string $country = 'México',
        ?string $interiorNumber = null,
        ?string $neighborhood = null,
        ?string $state = null,
        ?string $postalCode = null,
        ?string $phone = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            name: $name,
            slug: $slug,
            street: $street,
            exteriorNumber: $exteriorNumber,
            interiorNumber: $interiorNumber,
            neighborhood: $neighborhood,
            city: $city,
            state: $state,
            postalCode: $postalCode,
            country: $country,
            phone: $phone,
            notes: $notes,
            isActive: true,
        );
    }

    public function updateDetails(
        string $name,
        string $slug,
        string $street,
        string $exteriorNumber,
        string $city,
        string $country,
        ?string $interiorNumber = null,
        ?string $neighborhood = null,
        ?string $state = null,
        ?string $postalCode = null,
        ?string $phone = null,
        ?string $notes = null,
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->street = $street;
        $this->exteriorNumber = $exteriorNumber;
        $this->interiorNumber = $interiorNumber;
        $this->neighborhood = $neighborhood;
        $this->city = $city;
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->country = $country;
        $this->phone = $phone;
        $this->notes = $notes;

        $this->guard();
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    private function guard(): void
    {
        $this->name = trim($this->name);
        $this->slug = trim($this->slug);
        $this->street = trim($this->street);
        $this->exteriorNumber = trim($this->exteriorNumber);
        $this->interiorNumber = $this->nullable($this->interiorNumber);
        $this->neighborhood = $this->nullable($this->neighborhood);
        $this->city = trim($this->city);
        $this->state = $this->nullable($this->state);
        $this->postalCode = $this->nullable($this->postalCode);
        $this->country = trim($this->country);
        $this->phone = $this->nullable($this->phone);
        $this->notes = $this->nullable($this->notes);

        if ($this->name === '') {
            throw DomainException::because('El nombre de la ubicación es obligatorio.');
        }

        if ($this->slug === '') {
            throw DomainException::because('El identificador de la ubicación es obligatorio.');
        }

        if ($this->street === '') {
            throw DomainException::because('La calle es obligatoria.');
        }

        if ($this->exteriorNumber === '') {
            throw DomainException::because('El número exterior es obligatorio.');
        }

        if ($this->city === '') {
            throw DomainException::because('La ciudad es obligatoria.');
        }

        if ($this->country === '') {
            throw DomainException::because('El país es obligatorio.');
        }

        $this->ensureMax($this->name, 120, 'El nombre de la ubicación no puede superar 120 caracteres.');
        $this->ensureMax($this->slug, 140, 'El identificador de la ubicación no puede superar 140 caracteres.');
        $this->ensureMax($this->street, 180, 'La calle no puede superar 180 caracteres.');
        $this->ensureMax($this->exteriorNumber, 20, 'El número exterior no puede superar 20 caracteres.');
        $this->ensureMax($this->interiorNumber, 20, 'El número interior no puede superar 20 caracteres.');
        $this->ensureMax($this->neighborhood, 120, 'La colonia no puede superar 120 caracteres.');
        $this->ensureMax($this->city, 120, 'La ciudad no puede superar 120 caracteres.');
        $this->ensureMax($this->state, 120, 'El estado no puede superar 120 caracteres.');
        $this->ensureMax($this->postalCode, 12, 'El código postal no puede superar 12 caracteres.');
        $this->ensureMax($this->country, 80, 'El país no puede superar 80 caracteres.');
        $this->ensureMax($this->phone, 30, 'El teléfono no puede superar 30 caracteres.');
        $this->ensureMax($this->notes, 1000, 'Las notas no pueden superar 1000 caracteres.');
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
