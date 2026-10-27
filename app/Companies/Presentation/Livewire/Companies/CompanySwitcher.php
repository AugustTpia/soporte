<?php

namespace App\Companies\Presentation\Livewire\Companies;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CompanySwitcher extends Component
{
    public string $selectedCompany = '';

    /** @var array<int, array{id: int, name: string}> */
    public array $companies = [];

    public string $activeCompanyName = '';

    public function mount(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            return;
        }

        $tenant = app(TenantContext::class);
        $companyId = $tenant->companyId();

        $this->selectedCompany = $companyId === null ? '' : (string) $companyId;

        $this->companies = collect(app(CompanyRepository::class)->activeCompanies())
            ->map(fn ($company) => ['id' => $company->id, 'name' => $company->name])
            ->all();

        if ($companyId !== null) {
            $company = app(CompanyRepository::class)->find($companyId);
            $this->activeCompanyName = $company !== null ? $company->name : '';
        }
    }

    public function updatedSelectedCompany(): void
    {
        $this->switchCompany();
    }

    public function switchCompany(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        try {
            app(TenantContext::class)->setCompanyId(
                $this->selectedCompany === '' ? null : (int) $this->selectedCompany,
            );

            $this->redirectRoute('dashboard');
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('companies.livewire.company-switcher');
    }
}
