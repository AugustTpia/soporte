<?php

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Enums\UserStatus;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class User
{
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, int>  $departmentIds
     * @param  array<int, string>  $departmentNames
     */
    public function __construct(
        public ?int $id,
        public ?int $companyId,
        public string $name,
        public string $email,
        public ?string $jobTitle,
        public ?string $phone,
        public UserStatus $status,
        public array $roles = [],
        public array $departmentIds = [],
        public array $departmentNames = [],
        public ?string $companyName = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $lastLoginAt = null,
    ) {
        $this->guard();
    }

    /**
     * @param  array<int, string>  $roles
     * @param  array<int, int>  $departmentIds
     */
    public static function create(
        ?int $companyId,
        string $name,
        string $email,
        ?string $jobTitle = null,
        ?string $phone = null,
        array $roles = [],
        array $departmentIds = [],
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            name: $name,
            email: $email,
            jobTitle: $jobTitle,
            phone: $phone,
            status: UserStatus::Active,
            roles: $roles,
            departmentIds: $departmentIds,
        );
    }

    /**
     * @param  array<int, string>  $roles
     * @param  array<int, int>  $departmentIds
     */
    public function updateProfile(
        ?int $companyId,
        string $name,
        string $email,
        ?string $jobTitle,
        ?string $phone,
        UserStatus $status,
        array $roles,
        array $departmentIds,
    ): void {
        $this->companyId = $companyId;
        $this->name = $name;
        $this->email = $email;
        $this->jobTitle = $jobTitle;
        $this->phone = $phone;
        $this->status = $status;
        $this->roles = $roles;
        $this->departmentIds = $departmentIds;

        $this->guard();
    }

    public function deactivate(): void
    {
        $this->status = UserStatus::Inactive;
    }

    public function activate(): void
    {
        $this->status = UserStatus::Active;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuperAdmin(): bool
    {
        return in_array(RoleName::SuperAdmin->value, $this->roles, true);
    }

    public function primaryRole(): ?RoleName
    {
        $role = $this->roles[0] ?? null;

        return $role ? RoleName::tryFrom($role) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_substr($part, 0, 1);
        }

        return mb_strtoupper($initials !== '' ? $initials : 'U');
    }

    private function guard(): void
    {
        $this->name = trim($this->name);
        $this->email = mb_strtolower(trim($this->email));

        if ($this->name === '') {
            throw DomainException::because('El nombre del usuario es obligatorio.');
        }

        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw DomainException::because('El correo electrónico no es válido.');
        }

        foreach ($this->roles as $role) {
            if (RoleName::tryFrom($role) === null) {
                throw DomainException::because("El rol {$role} no es válido.");
            }
        }

        if (count($this->roles) !== count(array_unique($this->roles))) {
            throw DomainException::because('No se pueden repetir roles.');
        }
    }
}
