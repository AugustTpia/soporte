<?php

namespace App\Companies\Presentation\Livewire\Companies;

use App\Companies\Application\UseCases\ChangeCompanyStatus;
use App\Companies\Application\UseCases\SearchCompanies;
use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Empresas')]
class CompanyIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', CompanyModel::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $companyId): void
    {
        $this->authorize('update', CompanyModel::query()->findOrFail($companyId));

        $company = app(CompanyRepository::class)->find($companyId);

        if ($company === null) {
            abort(404);
        }

        try {
            $newStatus = $company->isActive() ? CompanyStatus::Inactive : CompanyStatus::Active;

            app(ChangeCompanyStatus::class)->execute($companyId, $newStatus);

            if ($newStatus === CompanyStatus::Inactive && app(TenantContext::class)->companyId() === $companyId) {
                app(TenantContext::class)->clear();
            }

            Flux::toast(variant: 'success', text: 'Estado de la empresa actualizado.');
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $result = app(SearchCompanies::class)->execute(
            $this->search !== '' ? $this->search : null,
            $this->getPage(),
            10,
        );

        $companies = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('companies.index')],
        );

        return view('companies.livewire.companies.index', [
            'companies' => $companies,
        ]);
    }
}
