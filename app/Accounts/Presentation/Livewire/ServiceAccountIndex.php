<?php

namespace App\Accounts\Presentation\Livewire;

use App\Accounts\Application\UseCases\SearchServiceAccounts;
use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Cuentas')]
class ServiceAccountIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $service = '';

    public string $status = '';

    public ?int $companyId = null;

    public string $companyName = '';

    /** @var array<string, string> */
    public array $serviceOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', ServiceAccountModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->serviceOptions = collect(ServiceType::cases())
            ->mapWithKeys(fn (ServiceType $type) => [$type->value => $type->label()])
            ->all();

        $this->statusOptions = collect(ServiceAccountStatus::cases())
            ->mapWithKeys(fn (ServiceAccountStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedService(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->companyId === null) {
            return view('accounts.livewire.index', [
                'accounts' => new LengthAwarePaginator([], 0, 10),
                'companyName' => '',
                'serviceOptions' => $this->serviceOptions,
                'statusOptions' => $this->statusOptions,
            ]);
        }

        $result = app(SearchServiceAccounts::class)->execute(
            $this->companyId,
            $this->search !== '' ? $this->search : null,
            $this->getPage(),
            10,
            $this->service !== '' ? ServiceType::tryFrom($this->service) : null,
            $this->status !== '' ? ServiceAccountStatus::tryFrom($this->status) : null,
        );

        $accounts = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('accounts.index')],
        );

        return view('accounts.livewire.index', [
            'accounts' => $accounts,
            'companyName' => $this->companyName,
            'serviceOptions' => $this->serviceOptions,
            'statusOptions' => $this->statusOptions,
        ]);
    }
}
