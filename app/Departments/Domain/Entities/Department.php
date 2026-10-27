<?php

namespace App\Departments\Domain\Entities;

use App\Shared\Domain\Exceptions\DomainException;

final class Department
{
    public function __construct(
        public ?int $id,
        public int $companyId,
        public string $name,
        public string $slug,
        public ?string $description,
        public bool $isActive,
        public int $usersCount = 0,
    ) {
        $this->guard();
    }

    public static function create(int $companyId, string $name, string $slug, ?string $description = null): self
    {
        return new self(
            id: null,
            companyId: $companyId,
            name: $name,
            slug: $slug,
            description: $description,
            isActive: true,
        );
    }

    public function updateDetails(string $name, string $slug, ?string $description): void
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;

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

        if ($this->name === '') {
            throw DomainException::because('El nombre del departamento es obligatorio.');
        }

        if (mb_strlen($this->name) > 120) {
            throw DomainException::because('El nombre del departamento no puede superar 120 caracteres.');
        }

        if ($this->slug === '') {
            throw DomainException::because('El identificador del departamento es obligatorio.');
        }
    }
}
