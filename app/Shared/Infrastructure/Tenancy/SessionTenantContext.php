<?php

namespace App\Shared\Infrastructure\Tenancy;

use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Illuminate\Support\Facades\Auth;

final class SessionTenantContext implements TenantContext
{
    public function companyId(): ?int
    {
        $user = Auth::user();

        if ($user === null) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            $companyId = session('active_company_id');

            return $companyId !== null ? (int) $companyId : null;
        }

        return $user->company_id;
    }

    public function hasCompany(): bool
    {
        return $this->companyId() !== null;
    }

    public function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public function setCompanyId(?int $companyId): void
    {
        $user = Auth::user();

        if ($user === null || ! $user->isSuperAdmin()) {
            throw DomainException::because('Solo un superadministrador puede cambiar de empresa.');
        }

        if ($companyId === null) {
            $this->clear();

            return;
        }

        $company = CompanyModel::query()
            ->whereKey($companyId)
            ->where('status', CompanyStatus::Active->value)
            ->first();

        if ($company === null) {
            throw DomainException::because('La empresa seleccionada no existe o está inactiva.');
        }

        session()->put('active_company_id', $company->id);
    }

    public function clear(): void
    {
        session()->forget('active_company_id');
    }
}
