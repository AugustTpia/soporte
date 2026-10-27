<?php

namespace App\Shared\Application\Contracts;

interface TenantContext
{
    public function companyId(): ?int;

    public function hasCompany(): bool;

    public function isSuperAdmin(): bool;

    public function setCompanyId(?int $companyId): void;

    public function clear(): void;
}
