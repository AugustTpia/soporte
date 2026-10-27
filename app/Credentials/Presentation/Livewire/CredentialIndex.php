<?php

namespace App\Credentials\Presentation\Livewire;

use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Credentials\Application\UseCases\SearchCredentials;
use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Credenciales')]
class CredentialIndex extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public string $account = '';

    public string $kind = '';

    public string $status = '';

    public ?int $companyId = null;

    public string $companyName = '';

    /** @var array<int, array{id: int, label: string}> */
    public array $accountOptions = [];

    /** @var array<string, string> */
    public array $kindOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', CredentialModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->accountOptions = ServiceAccountModel::query()
            ->where('company_id', $this->companyId)
            ->orderBy('username')
            ->get(['id', 'username', 'instance'])
            ->map(fn (ServiceAccountModel $account) => [
                'id' => $account->id,
                'label' => $account->username.' · '.$account->instance,
            ])
            ->all();

        $this->kindOptions = collect(CredentialKind::cases())
            ->mapWithKeys(fn (CredentialKind $kind) => [$kind->value => $kind->label()])
            ->all();

        $this->statusOptions = collect(CredentialStatus::cases())
            ->mapWithKeys(fn (CredentialStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAccount(): void
    {
        $this->resetPage();
    }

    public function updatedKind(): void
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
            return view('credentials.livewire.index', [
                'credentials' => new LengthAwarePaginator([], 0, 12),
                'companyName' => '',
                'accountOptions' => $this->accountOptions,
                'kindOptions' => $this->kindOptions,
                'statusOptions' => $this->statusOptions,
            ]);
        }

        $result = app(SearchCredentials::class)->execute(
            companyId: $this->companyId,
            term: $this->search !== '' ? $this->search : null,
            page: $this->getPage(),
            perPage: 12,
            serviceAccountId: $this->account !== '' ? (int) $this->account : null,
            status: $this->status !== '' ? CredentialStatus::tryFrom($this->status) : null,
            kind: $this->kind !== '' ? CredentialKind::tryFrom($this->kind) : null,
        );

        $credentials = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('credentials.index')],
        );

        return view('credentials.livewire.index', [
            'credentials' => $credentials,
            'companyName' => $this->companyName,
            'accountOptions' => $this->accountOptions,
            'kindOptions' => $this->kindOptions,
            'statusOptions' => $this->statusOptions,
        ]);
    }
}
