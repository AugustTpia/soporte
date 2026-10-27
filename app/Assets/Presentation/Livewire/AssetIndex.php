<?php

namespace App\Assets\Presentation\Livewire;

use App\Assets\Application\UseCases\SearchAssets;
use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Activos')]
class AssetIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $category = '';

    public string $status = '';

    public string $locationId = '';

    public ?int $companyId = null;

    public string $companyName = '';

    /** @var array<int, array{id: int, name: string, is_active: bool}> */
    public array $locationOptions = [];

    /** @var array<string, string> */
    public array $categoryOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', AssetModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->loadOptions();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedLocationId(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->companyId === null) {
            return view('assets.livewire.index', [
                'assets' => new LengthAwarePaginator([], 0, 10),
                'companyName' => '',
                'locationOptions' => [],
                'categoryOptions' => $this->categoryOptions,
                'statusOptions' => $this->statusOptions,
            ]);
        }

        $result = app(SearchAssets::class)->execute(
            $this->companyId,
            $this->search !== '' ? $this->search : null,
            $this->getPage(),
            10,
            $this->category !== '' ? AssetCategory::tryFrom($this->category) : null,
            $this->status !== '' ? AssetStatus::tryFrom($this->status) : null,
            $this->locationId !== '' ? (int) $this->locationId : null,
        );

        $assets = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('assets.index')],
        );

        return view('assets.livewire.index', [
            'assets' => $assets,
            'companyName' => $this->companyName,
            'locationOptions' => $this->locationOptions,
            'categoryOptions' => $this->categoryOptions,
            'statusOptions' => $this->statusOptions,
        ]);
    }

    private function loadOptions(): void
    {
        if ($this->companyId === null) {
            return;
        }

        $this->locationOptions = collect(app(LocationRepository::class)->forCompany($this->companyId))
            ->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'is_active' => $location->isActive,
            ])
            ->all();

        $this->categoryOptions = collect(AssetCategory::cases())
            ->mapWithKeys(fn (AssetCategory $category) => [$category->value => $category->label()])
            ->all();

        $this->statusOptions = collect(AssetStatus::cases())
            ->mapWithKeys(fn (AssetStatus $status) => [$status->value => $status->label()])
            ->all();
    }
}
